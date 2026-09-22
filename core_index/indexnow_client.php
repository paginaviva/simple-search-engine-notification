<?php
/**
 * IndexNow Client Module - Cliente HTTP para IndexNow API
 *
 * @package SSEN
 * @subpackage F2B - Clientes API
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Requiere extensiones PHP: curl, json
 */

// Cargar módulos de dependencia
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/indexnow_auth.php';

/**
 * Envía URLs a IndexNow API, troceando en lotes si es necesario.
 *
 * El protocolo admite hasta 10.000 URLs por solicitud; si se supera ese
 * número, se envían varias solicitudes y se agrega el resultado.
 *
 * @param array $urls URLs a notificar
 * @param array $config Credenciales ['host', 'key', 'keyLocation']
 * @return array Resultado ['success', 'http_code', 'message', 'urls_count', 'urls_list']
 */
function submitToIndexNow(array $urls, array $config)
{
    $urls = array_values($urls);

    // 1. Sin URLs no hay nada que hacer
    if (empty($urls)) {
        return [
            'success' => true,
            'http_code' => null,
            'message' => 'No hay URLs para enviar a IndexNow',
            'urls_count' => 0,
            'urls_list' => []
        ];
    }

    // 2. Validar configuración
    $validation = validateIndexNowConfig($config);
    if (!$validation['valid']) {
        return [
            'success' => false,
            'http_code' => null,
            'message' => 'Error de configuración: ' . $validation['error'],
            'urls_count' => count($urls),
            'urls_list' => []
        ];
    }

    // 3. Trocear en lotes según el límite del protocolo
    $maxPerRequest = defined('INDEXNOW_MAX_URLS_PER_REQUEST') ? (int)INDEXNOW_MAX_URLS_PER_REQUEST : 10000;
    $chunks = array_chunk($urls, $maxPerRequest);

    if (count($chunks) === 1) {
        return submitIndexNowBatch($chunks[0], $config);
    }

    $successCount = 0;
    $errorCount = 0;
    $batches = [];
    foreach ($chunks as $index => $chunk) {
        $batchResult = submitIndexNowBatch($chunk, $config);
        if ($batchResult['success']) {
            $successCount++;
        } else {
            $errorCount++;
        }
        $batches[] = [
            'batch' => $index + 1,
            'success' => $batchResult['success'],
            'http_code' => $batchResult['http_code'],
            'message' => $batchResult['message'],
            'urls_count' => $batchResult['urls_count']
        ];
    }

    return [
        'success' => $errorCount === 0,
        'http_code' => null,
        'message' => sprintf('Se enviaron %d lotes a IndexNow (%d correctos, %d con error)', count($chunks), $successCount, $errorCount),
        'urls_count' => count($urls),
        'urls_list' => $urls,
        'batches' => $batches
    ];
}

/**
 * Envía un único lote de URLs (máximo 10.000).
 *
 * @param array $urls URLs del lote
 * @param array $config Credenciales ['host', 'key', 'keyLocation']
 * @return array Resultado de la operación
 */
function submitIndexNowBatch(array $urls, array $config)
{
    $endpoint = 'https://api.indexnow.org/indexnow';
    $timeout = defined('INDEXNOW_TIMEOUT') ? (int)INDEXNOW_TIMEOUT : 30;

    $payload = [
        'host' => $config['host'],
        'key' => $config['key'],
        'keyLocation' => $config['keyLocation'],
        'urlList' => array_values($urls)
    ];

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

    // Enviar petición HTTP con curl
    $ch = curl_init($endpoint);
    if ($ch === false) {
        return [
            'success' => false,
            'http_code' => null,
            'message' => 'No se pudo inicializar curl',
            'urls_count' => count($urls),
            'urls_list' => $urls
        ];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Content-Length: ' . strlen($jsonPayload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Manejo de errores de conexión
    if ($curlError) {
        $error = 'Error de conexión: ' . $curlError;
        logIndexNowSafe(implode(', ', array_slice($urls, 0, 3)), null, $error);

        return [
            'success' => false,
            'http_code' => null,
            'message' => $error,
            'urls_count' => count($urls),
            'urls_list' => $urls
        ];
    }

    // Interpretar códigos HTTP del protocolo
    $messages = [
        200 => 'URLs enviadas correctamente a IndexNow',
        202 => 'URLs aceptadas; IndexNow validará la clave de forma asíncrona',
        400 => 'Error: formato inválido en la solicitud',
        403 => 'Error: clave no válida o no encontrada',
        422 => 'Error: las URLs no pertenecen al host o la clave no coincide',
        429 => 'Aviso: demasiadas solicitudes (límite de frecuencia excedido)'
    ];

    $message = isset($messages[$httpCode]) ? $messages[$httpCode] : "Código HTTP desconocido: $httpCode";
    $success = ($httpCode == 200 || $httpCode == 202);

    $sampleUrls = implode(', ', array_slice($urls, 0, 3)) . (count($urls) > 3 ? '...' : '');
    logIndexNowSafe($sampleUrls, $httpCode, $message);

    return [
        'success' => $success,
        'http_code' => $httpCode,
        'message' => $message,
        'urls_count' => count($urls),
        'urls_list' => $urls
    ];
}

/**
 * Registra una acción de IndexNow sin interrumpir la ejecución.
 *
 * @param string $url Muestra de URLs
 * @param int|null $httpCode Código HTTP
 * @param string $message Mensaje
 * @return void
 */
function logIndexNowSafe($url, $httpCode, $message)
{
    if (!defined('LOG_PATH') || !function_exists('logIndexingAction')) {
        return;
    }
    try {
        logIndexingAction(LOG_PATH, $url, 'UPDATED', 'INDEXNOW', $httpCode, $message);
    } catch (Throwable $e) {
        error_log('SSEN: fallo al registrar una acción de IndexNow: ' . $e->getMessage());
    }
}

/**
 * Valida la configuración de IndexNow.
 *
 * @param array $config Configuración a validar
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validateIndexNowConfig(array $config)
{
    // Campos obligatorios
    $requiredFields = ['host', 'key', 'keyLocation'];
    foreach ($requiredFields as $field) {
        if (!isset($config[$field]) || trim((string)$config[$field]) === '') {
            return [
                'valid' => false,
                'error' => "Campo obligatorio '$field' no está presente o está vacío"
            ];
        }
    }

    // Formato de la clave
    if (!validateIndexNowKey($config['key'])) {
        return [
            'valid' => false,
            'error' => 'Formato de clave inválido (8 a 128 caracteres alfanuméricos o guion)'
        ];
    }

    // keyLocation debe ser una URL HTTPS
    if (!filter_var($config['keyLocation'], FILTER_VALIDATE_URL) ||
        strpos($config['keyLocation'], 'https://') !== 0) {
        return [
            'valid' => false,
            'error' => 'keyLocation debe ser una URL HTTPS válida'
        ];
    }

    // host sin protocolo
    if (strpos($config['host'], 'http://') === 0 || strpos($config['host'], 'https://') === 0) {
        return [
            'valid' => false,
            'error' => 'host no debe incluir protocolo (http:// o https://)'
        ];
    }

    return [
        'valid' => true,
        'error' => null
    ];
}
