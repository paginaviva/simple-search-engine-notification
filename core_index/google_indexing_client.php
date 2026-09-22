<?php
/**
 * Google Indexing API Client Module - Cliente HTTP para Google Indexing API
 *
 * @package SSEN
 * @subpackage F2B - Clientes API
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Requiere extensiones PHP: curl, json
 *
 * Aviso: la API de indexación de Google solo admite páginas con datos
 * estructurados JobPosting o BroadcastEvent. URL_DELETED requiere además que
 * la URL devuelva 404 o 410 (o incluya noindex) antes de solicitar la baja.
 */

// Cargar módulos de dependencia
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/google_indexing_auth.php';

/**
 * Notifica una URL a Google Indexing API.
 *
 * @param string $url URL a notificar
 * @param string $type Tipo de operación: 'URL_UPDATED' o 'URL_DELETED'
 * @param string $accessToken Token OAuth 2.0 obtenido con getGoogleIndexingToken()
 * @return array Resultado ['success', 'http_code', 'message', 'response']
 */
function submitToGoogleIndexingAPI($url, $type, $accessToken)
{
    // 1. Validar parámetros
    if (empty($url)) {
        return [
            'success' => false,
            'http_code' => null,
            'message' => 'URL no puede estar vacía',
            'response' => null
        ];
    }

    if (!in_array($type, ['URL_UPDATED', 'URL_DELETED'], true)) {
        return [
            'success' => false,
            'http_code' => null,
            'message' => 'Tipo inválido. Debe ser URL_UPDATED o URL_DELETED',
            'response' => null
        ];
    }

    if (empty($accessToken)) {
        return [
            'success' => false,
            'http_code' => null,
            'message' => 'Access token no puede estar vacío',
            'response' => null
        ];
    }

    // 2. Construir la petición
    $endpoint = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    $timeout = defined('GOOGLE_API_TIMEOUT') ? (int)GOOGLE_API_TIMEOUT : 10;

    $payload = [
        'url' => $url,
        'type' => $type
    ];
    $jsonPayload = json_encode($payload);

    $ch = curl_init($endpoint);
    if ($ch === false) {
        return [
            'success' => false,
            'http_code' => null,
            'message' => 'No se pudo inicializar curl',
            'response' => null
        ];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'Content-Length: ' . strlen($jsonPayload)
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 3. Errores de conexión
    if ($curlError) {
        $error = 'Error de conexión: ' . $curlError;
        logGoogleSafe($url, $type, null, $error);

        return [
            'success' => false,
            'http_code' => null,
            'message' => $error,
            'response' => null
        ];
    }

    // 4. Interpretar la respuesta
    $success = ($httpCode == 200);
    $responseData = json_decode($response, true);
    $message = $success ? 'Notificación aceptada' : 'Error en la notificación';

    if (!$success && isset($responseData['error']['message'])) {
        $message = $responseData['error']['message'];
    } elseif (!$success && isset($responseData['error']['status'])) {
        $message = $responseData['error']['status'];
    }

    logGoogleSafe($url, $type, $httpCode, $message);

    return [
        'success' => $success,
        'http_code' => $httpCode,
        'message' => $message,
        'response' => $responseData
    ];
}

/**
 * Comprueba si una URL devuelve 404 o 410.
 *
 * Requisito documentado para solicitar URL_DELETED: la URL debe devolver
 * 404 o 410 (o incluir noindex) antes de pedir la baja.
 *
 * @param string $url URL a comprobar
 * @param int|null $timeout Tiempo máximo en segundos (null = GOOGLE_API_TIMEOUT)
 * @return bool True si la URL devuelve 404 o 410
 */
function isUrlGone($url, $timeout = null)
{
    if (empty($url)) {
        return false;
    }

    $timeout = $timeout !== null ? (int)$timeout : (defined('GOOGLE_API_TIMEOUT') ? (int)GOOGLE_API_TIMEOUT : 10);

    // Primero HEAD; si el servidor no lo admite, se reintenta con GET
    foreach ([true, false] as $headOnly) {
        $ch = curl_init($url);
        if ($ch === false) {
            continue;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, $headOnly);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (in_array($code, [404, 410], true)) {
            return true;
        }

        // Respuesta concluyente con HEAD: la URL sigue viva
        if ($headOnly && $code !== 0 && $code !== 405 && $code !== 501) {
            return false;
        }
    }

    return false;
}

/**
 * Notifica múltiples URLs a Google Indexing API (procesamiento por lotes).
 *
 * @param array $items Elementos ['url', 'type'] o cadenas de URL (tipo URL_UPDATED)
 * @param string $accessToken Token OAuth 2.0
 * @param int|null $delayMs Espera entre peticiones en milisegundos (null = GOOGLE_REQUEST_DELAY_MS)
 * @return array Resultado ['success_count', 'error_count', 'details']
 */
function submitBatchToGoogleIndexingAPI(array $items, $accessToken, $delayMs = null)
{
    $delay = $delayMs !== null ? (int)$delayMs : (defined('GOOGLE_REQUEST_DELAY_MS') ? (int)GOOGLE_REQUEST_DELAY_MS : 100);

    $results = [
        'success_count' => 0,
        'error_count' => 0,
        'details' => []
    ];

    foreach ($items as $item) {
        if (is_string($item)) {
            $url = $item;
            $type = 'URL_UPDATED';
        } else {
            $url = isset($item['url']) ? (string)$item['url'] : '';
            $type = isset($item['type']) ? (string)$item['type'] : 'URL_UPDATED';
        }

        $result = submitToGoogleIndexingAPI($url, $type, $accessToken);

        if ($result['success']) {
            $results['success_count']++;
        } else {
            $results['error_count']++;
        }

        $results['details'][] = [
            'url' => $url,
            'type' => $type,
            'success' => $result['success'],
            'http_code' => $result['http_code'],
            'message' => $result['message']
        ];

        if ($delay > 0) {
            usleep($delay * 1000);
        }
    }

    return $results;
}

/**
 * Registra una acción de Google sin interrumpir la ejecución.
 *
 * @param string $url URL notificada
 * @param string $type Tipo de operación
 * @param int|null $httpCode Código HTTP
 * @param string $message Mensaje
 * @return void
 */
function logGoogleSafe($url, $type, $httpCode, $message)
{
    if (!defined('LOG_PATH') || !function_exists('logIndexingAction')) {
        return;
    }
    try {
        logIndexingAction(LOG_PATH, $url, $type === 'URL_DELETED' ? 'DELETED' : 'UPDATED', 'GOOGLE_INDEXING', $httpCode, $message);
    } catch (Throwable $e) {
        error_log('SSEN: fallo al registrar una acción de Google: ' . $e->getMessage());
    }
}
