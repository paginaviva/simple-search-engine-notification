<?php
/**
 * Auth Guard Module - Autenticación básica a nivel de aplicación
 *
 * @package SSEN
 * @subpackage F2A - Soporte Básico
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Se aplica en la propia aplicación para funcionar igual en Apache, LiteSpeed
 * y nginx (donde .htaccess se ignora). El archivo de usuarios es el mismo
 * formato que genera `htpasswd -B` (bcrypt) y vive fuera del documento raíz.
 */

require_once __DIR__ . '/config/config.php';

/**
 * Verifica unas credenciales contra un archivo de usuarios.
 *
 * @param string|null $user Usuario
 * @param string|null $password Contraseña
 * @param string $usersFile Ruta del archivo de usuarios (formato usuario:hash)
 * @return bool True si las credenciales son válidas
 */
function verifyBasicAuth($user, $password, $usersFile)
{
    if ($user === null || $user === '' || $password === null || $password === '') {
        return false;
    }

    if (!is_file($usersFile) || !is_readable($usersFile)) {
        return false;
    }

    $lines = file($usersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return false;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $parts = explode(':', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        if (hash_equals($parts[0], (string)$user)) {
            // Solo se admiten hashes compatibles con password_verify (bcrypt)
            return password_verify((string)$password, trim($parts[1]));
        }
    }

    return false;
}

/**
 * Exige autenticación básica antes de continuar. No hace nada en la interfaz
 * de línea de comandos ni si WEB_AUTH_ENABLED es falso.
 *
 * @return void
 */
function requireWebAuthentication()
{
    if (!defined('WEB_AUTH_ENABLED') || WEB_AUTH_ENABLED !== true) {
        return;
    }

    if (PHP_SAPI === 'cli') {
        return;
    }

    $usersFile = defined('AUTH_USERS_FILE')
        ? AUTH_USERS_FILE
        : dirname(ROOT_DIR) . '/ssen-private/.htpasswd';
    $realm = defined('WEB_AUTH_REALM') ? WEB_AUTH_REALM : 'SSEN';

    // Sin archivo de usuarios la interfaz web no puede autenticar: se bloquea
    if (!is_file($usersFile)) {
        if (function_exists('logSafely')) {
            logSafely('Autenticación web habilitada sin archivo de usuarios: ' . $usersFile);
        }
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Error de configuración de la autenticación.';
        exit;
    }

    $user = isset($_SERVER['PHP_AUTH_USER']) ? (string)$_SERVER['PHP_AUTH_USER'] : null;
    $password = isset($_SERVER['PHP_AUTH_PW']) ? (string)$_SERVER['PHP_AUTH_PW'] : null;

    if (!verifyBasicAuth($user, $password, $usersFile)) {
        if (function_exists('logSafely')) {
            $origin = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'desconocida';
            logSafely('Intento de acceso no autorizado a la interfaz web desde ' . $origin);
        }
        http_response_code(401);
        header('WWW-Authenticate: Basic realm="' . str_replace('"', '', (string)$realm) . '"');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Autenticación requerida.';
        exit;
    }
}
