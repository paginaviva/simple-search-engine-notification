<?php
/**
 * IndexNow Authentication Module - Gestión de credenciales IndexNow
 *
 * @package SSEN
 * @subpackage F2A - Soporte Básico
 * @version 1.1.0
 * @date 2026-09-21
 */

/**
 * Obtiene las credenciales de IndexNow.
 *
 * Fuentes únicas: INDEXNOW_KEY_FILE (clave), INDEXNOW_HOST (host) y BASE_URL
 * (ubicación pública del archivo de clave).
 *
 * @param string|null $configPath Ruta al archivo de clave (null = usar INDEXNOW_KEY_FILE)
 * @return array Credenciales con claves: key, keyLocation, host
 * @throws RuntimeException Si el archivo no existe, está vacío o la clave no es válida
 */
function getIndexNowCredentials($configPath = null)
{
    // 1. Determinar la ruta del archivo de clave
    if ($configPath === null) {
        $configPath = defined('INDEXNOW_KEY_FILE')
            ? INDEXNOW_KEY_FILE
            : __DIR__ . '/config/indexnow_key.txt';
    }

    // 2. Verificar existencia y lectura
    if (!file_exists($configPath)) {
        throw new RuntimeException("No existe el archivo de clave de IndexNow: $configPath");
    }

    $content = @file_get_contents($configPath);
    if ($content === false || trim($content) === '') {
        throw new RuntimeException("El archivo de clave de IndexNow está vacío o no se puede leer: $configPath");
    }

    // 3. Validar formato según el protocolo
    $key = trim($content);
    if (!validateIndexNowKey($key)) {
        throw new RuntimeException('La clave de IndexNow no cumple el formato del protocolo (8 a 128 caracteres alfanuméricos o guion)');
    }

    // 4. Construir la ubicación pública del archivo de clave
    if (!defined('BASE_URL') || trim((string)BASE_URL) === '') {
        throw new RuntimeException('BASE_URL no está definida en la configuración');
    }
    $baseUrl = rtrim((string)BASE_URL, '/');
    $keyLocation = $baseUrl . '/' . $key . '.txt';

    // 5. Determinar el host (sin protocolo)
    if (defined('INDEXNOW_HOST') && trim((string)INDEXNOW_HOST) !== '') {
        $host = trim((string)INDEXNOW_HOST);
    } else {
        $host = (string)parse_url($baseUrl, PHP_URL_HOST);
    }
    if ($host === '') {
        throw new RuntimeException('No se pudo determinar el host de IndexNow (revise INDEXNOW_HOST y BASE_URL)');
    }

    return [
        'key' => $key,
        'keyLocation' => $keyLocation,
        'host' => $host
    ];
}

/**
 * Valida el formato de la clave según el protocolo de IndexNow.
 *
 * El protocolo admite entre 8 y 128 caracteres alfanuméricos y guion.
 *
 * @param string $key Clave a validar
 * @return bool True si el formato es válido
 */
function validateIndexNowKey($key)
{
    return is_string($key) && preg_match('/^[A-Za-z0-9-]{8,128}$/', $key) === 1;
}
