<?php
/**
 * Logger Module - Sistema centralizado de registro para acciones de indexación
 *
 * @package SSEN
 * @subpackage F2A - Soporte Básico
 * @version 1.1.0
 * @date 2026-09-21
 */

/**
 * Registra una acción de indexación en el archivo de registro.
 *
 * Formato:
 * [YYYY-MM-DD HH:MM:SS] Operation: TYPE | Service: SERVICE | URL: url | HTTP: code | Status: STATUS | Message: msg
 *
 * Respeta LOG_ENABLED y LOG_MAX_SIZE, escribe con bloqueo y sanea los campos.
 *
 * @param string $logFile Ruta absoluta del archivo de registro
 * @param string $url URL notificada (o muestra)
 * @param string $operationType 'NEW', 'UPDATED', 'DELETED', 'AUTH' o 'INTERNAL'
 * @param string $notificationType 'INDEXNOW', 'GOOGLE_INDEXING' o 'INTERNAL'
 * @param int|null $httpCode Código HTTP de respuesta (null si error de conexión)
 * @param string $message Mensaje descriptivo
 * @return bool True si se escribió correctamente
 * @throws RuntimeException Si no se puede escribir en el archivo de registro
 */
function logIndexingAction($logFile, $url, $operationType, $notificationType, $httpCode, $message)
{
    // 1. Respetar la desactivación del registro
    if (defined('LOG_ENABLED') && LOG_ENABLED === false) {
        return true;
    }

    // 2. Determinar el estado según el código HTTP
    if ($httpCode === null) {
        $status = 'ERROR';
        $httpCode = 'N/A';
    } elseif ($httpCode == 200 || $httpCode == 202) {
        $status = 'SUCCESS';
    } else {
        $status = 'FAILED';
    }

    // 3. Sanear todos los campos dinámicos (sin saltos de línea ni tabuladores)
    $sanitize = static function ($value) {
        return trim(preg_replace('/[\r\n\t]+/', ' ', (string)$value));
    };

    $line = sprintf(
        "[%s] Operation: %s | Service: %s | URL: %s | HTTP: %s | Status: %s | Message: %s\n",
        date('Y-m-d H:i:s'),
        $sanitize($operationType),
        $sanitize($notificationType),
        $sanitize($url),
        $sanitize($httpCode),
        $status,
        $sanitize($message)
    );

    // 4. Crear el directorio de registro si no existe
    $logDir = dirname($logFile);
    if (!is_dir($logDir) && !@mkdir($logDir, 0755, true) && !is_dir($logDir)) {
        throw new RuntimeException("No se pudo crear el directorio de registros: $logDir");
    }

    // 5. Rotar el archivo si supera el tamaño máximo configurado
    if (defined('LOG_MAX_SIZE') && (int)LOG_MAX_SIZE > 0 && file_exists($logFile)) {
        clearstatcache(true, $logFile);
        $size = @filesize($logFile);
        if ($size !== false && $size >= (int)LOG_MAX_SIZE) {
            @rename($logFile, $logFile . '.1');
        }
    }

    // 6. Escribir con bloqueo
    $result = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    if ($result === false) {
        throw new RuntimeException("No se pudo escribir en el archivo de registro: $logFile");
    }

    return true;
}
