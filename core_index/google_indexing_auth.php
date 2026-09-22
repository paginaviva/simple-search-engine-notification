<?php
/**
 * Google Indexing API Authentication Module
 *
 * Gestiona la autenticación OAuth 2.0 con una cuenta de servicio:
 * genera un JWT y lo intercambia por un token de acceso.
 *
 * @package SSEN
 * @subpackage F2B - Clientes API
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Requiere extensiones PHP: openssl, curl, json
 */

// Cargar módulo de registro
require_once __DIR__ . '/logger.php';

/**
 * Obtiene un token OAuth 2.0 para Google Indexing API.
 *
 * @param string $credentialsPath Ruta al archivo credentials.json
 * @return array ['success' => bool, 'token' => string|null, 'error' => string|null, 'expires_in' => int|null]
 */
function getGoogleIndexingToken($credentialsPath)
{
    // 1. Validar la existencia del archivo de credenciales
    if (!file_exists($credentialsPath)) {
        $error = "No se encontró el archivo de cuenta de servicio en: $credentialsPath";
        error_log("Google Indexing Auth Error: $error");
        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    // 2. Interpretar el JSON y validar los campos obligatorios
    $serviceAccountJson = @file_get_contents($credentialsPath);
    $serviceAccount = json_decode((string)$serviceAccountJson, true);

    if (!$serviceAccount) {
        $error = 'No se pudo interpretar el JSON de la cuenta de servicio';
        error_log("Google Indexing Auth Error: $error");
        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    $requiredFields = ['client_email', 'private_key', 'token_uri'];
    foreach ($requiredFields as $field) {
        if (!isset($serviceAccount[$field])) {
            $error = "Falta el campo obligatorio '$field' en el JSON de la cuenta de servicio";
            error_log("Google Indexing Auth Error: $error");
            return [
                'success' => false,
                'token' => null,
                'error' => $error,
                'expires_in' => null
            ];
        }
    }

    // 3. Construir el JWT (cabecera + reclamaciones + firma)
    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];

    $now = time();
    $claimSet = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud' => $serviceAccount['token_uri'],
        'iat' => $now,
        'exp' => $now + 3600
    ];

    $base64UrlHeader = base64UrlEncode(json_encode($header));
    $base64UrlClaimSet = base64UrlEncode(json_encode($claimSet));
    $signatureInput = $base64UrlHeader . '.' . $base64UrlClaimSet;

    // 4. Firmar con la clave privada
    $privateKey = $serviceAccount['private_key'];
    $signature = '';

    $key = openssl_pkey_get_private($privateKey);
    if (!$key) {
        $error = 'No se pudo cargar la clave privada';
        error_log("Google Indexing Auth Error: $error");
        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    // La clave se libera automáticamente desde PHP 8.0 (openssl_free_key está obsoleto)
    $success = openssl_sign($signatureInput, $signature, $key, OPENSSL_ALGO_SHA256);

    if (!$success) {
        $error = 'No se pudo firmar el JWT';
        error_log("Google Indexing Auth Error: $error");
        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    $base64UrlSignature = base64UrlEncode($signature);
    $jwt = $signatureInput . '.' . $base64UrlSignature;

    // 5. Intercambiar el JWT por un token de acceso
    $tokenUrl = $serviceAccount['token_uri'];
    $postData = [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ];
    $timeout = defined('GOOGLE_API_TIMEOUT') ? (int)GOOGLE_API_TIMEOUT : 10;

    $ch = curl_init($tokenUrl);
    if ($ch === false) {
        $error = 'No se pudo inicializar curl para el intercambio de token';
        error_log("Google Indexing Auth Error: $error");
        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $error = "Error de curl durante el intercambio de token: $curlError";
        error_log("Google Indexing Auth Error: $error");
        logAuthSafe(null, $error);

        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    // 6. Validar la respuesta
    if ($httpCode !== 200) {
        $error = "El intercambio de token falló con el código HTTP $httpCode";
        error_log("Google Indexing Auth Error: $error");
        logAuthSafe($httpCode, $error);

        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    $tokenData = json_decode($response, true);
    if (!$tokenData || !isset($tokenData['access_token'])) {
        $error = 'Respuesta de token inválida (falta access_token)';
        error_log("Google Indexing Auth Error: $error");
        return [
            'success' => false,
            'token' => null,
            'error' => $error,
            'expires_in' => null
        ];
    }

    logAuthSafe(200, 'Token obtenido correctamente');

    return [
        'success' => true,
        'token' => $tokenData['access_token'],
        'error' => null,
        'expires_in' => isset($tokenData['expires_in']) ? $tokenData['expires_in'] : 3600
    ];
}

/**
 * Registra una acción de autenticación sin interrumpir la ejecución.
 *
 * @param int|null $httpCode Código HTTP
 * @param string $message Mensaje
 * @return void
 */
function logAuthSafe($httpCode, $message)
{
    if (!defined('LOG_PATH') || !function_exists('logIndexingAction')) {
        return;
    }
    try {
        logIndexingAction(LOG_PATH, 'AUTH', 'AUTH', 'GOOGLE_INDEXING', $httpCode, $message);
    } catch (Throwable $e) {
        error_log('SSEN: fallo al registrar una autenticación: ' . $e->getMessage());
    }
}

/**
 * Codificación Base64 segura para URL (sin relleno).
 *
 * @param string $data Datos a codificar
 * @return string Datos codificados
 */
function base64UrlEncode($data)
{
    $base64 = base64_encode($data);
    $base64Url = strtr($base64, '+/', '-_');
    return rtrim($base64Url, '=');
}
