<?php
/**
 * Configuración Centralizada del Sistema SSEN
 *
 * @package SSEN
 * @subpackage F2C - Orquestadores y Configuración
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Fuente única de verdad de rutas, credenciales y límites.
 * Plantilla: ajuste BASE_URL e INDEXNOW_HOST antes de desplegar.
 */

// ==================== CONFIGURACIÓN DE DOMINIO ====================

// Dominio base del sitio (SIN barra final)
define('BASE_URL', 'https://tu-dominio.com');

// ==================== DIRECTORIOS DEL SISTEMA ====================

// Raíz del proyecto (directorio padre de core_index; suele ser el documento raíz del sitio)
define('ROOT_DIR', dirname(__DIR__, 2));
define('CORE_DIR', ROOT_DIR . '/core_index');
define('CONFIG_DIR', CORE_DIR . '/config');
define('DATA_DIR', CORE_DIR . '/data');
define('TOOLS_DIR', CORE_DIR . '/tools');

// Directorio privado FUERA del documento raíz (registros, credenciales y autenticación).
// En alojamientos con open_basedir (por ejemplo Hestia), use una ruta permitida,
// como dirname(ROOT_DIR) . '/private'.
define('PRIVATE_DIR', dirname(ROOT_DIR) . '/ssen-private');
define('LOG_DIR', PRIVATE_DIR . '/logs');

// ==================== CONFIGURACIÓN DE ARCHIVOS ====================

// Sitemap (en la raíz del sitio)
define('SITEMAP_PATH', ROOT_DIR . '/sitemap.xml');
define('SITEMAP_OLD_PATH', ROOT_DIR . '/sitemap.old.xml');

// Registro de actividad
define('LOG_PATH', LOG_DIR . '/indexing_api_sitemap.log');

// Entrada de datos
define('URLS_JSON_PATH', DATA_DIR . '/urls.json');
define('URLS_CSV_PATH', DATA_DIR . '/urls.csv');

// ==================== CONFIGURACIÓN INDEXNOW ====================

define('INDEXNOW_HOST', 'tu-dominio.com');
define('INDEXNOW_KEY_FILE', CONFIG_DIR . '/indexnow_key.txt');
define('INDEXNOW_TIMEOUT', 30);
define('INDEXNOW_MAX_URLS_PER_REQUEST', 10000);

// ==================== CONFIGURACIÓN GOOGLE INDEXING API ====================
//
// Aviso: la API de indexación de Google solo admite páginas con datos
// estructurados JobPosting o BroadcastEvent. Para el resto de URLs la
// notificación no está garantizada y el uso indebido puede revocar el acceso.
// Si no hay credenciales válidas, la notificación a Google se omite
// (advertencia, nunca error).

define('GOOGLE_CREDENTIALS_PATH', PRIVATE_DIR . '/credentials.json');
define('GOOGLE_API_TIMEOUT', 10);
define('GOOGLE_DAILY_QUOTA', 200);
define('GOOGLE_REQUEST_DELAY_MS', 100);
define('GOOGLE_QUOTA_STATE_PATH', LOG_DIR . '/google_quota.json');

// ==================== CONFIGURACIÓN DE SITEMAP ====================

define('PRIORITY_INDEX', '1.0');
define('PRIORITY_DEFAULT', '0.8');
define('CHANGEFREQ_INDEX', 'daily');
define('CHANGEFREQ_DEFAULT', 'weekly');
define('MAX_URLS_PER_SITEMAP', 50000);

// Lista blanca de frecuencias admitidas por el protocolo de sitemaps
define('CHANGEFREQ_ALLOWED', 'always,hourly,daily,weekly,monthly,yearly,never');

// ==================== CONFIGURACIÓN DE LOGGING ====================

define('LOG_ENABLED', true);
define('LOG_MAX_SIZE', 10485760); // 10 MB

// ==================== CONFIGURACIÓN DE LA INTERFAZ WEB ====================

// Segundos mínimos entre ejecuciones desde la interfaz web (0 = sin límite)
define('WEB_RATE_LIMIT_SECONDS', 60);

// Autenticación básica a nivel de aplicación (funciona en Apache, LiteSpeed y nginx)
define('WEB_AUTH_ENABLED', true);
define('WEB_AUTH_REALM', 'SSEN - Area restringida');
define('AUTH_USERS_FILE', PRIVATE_DIR . '/.htpasswd');

// ==================== VALIDACIÓN DE CONFIGURACIÓN ====================

/**
 * Valida la configuración y devuelve errores (bloqueantes) y advertencias (no bloqueantes).
 *
 * @return array ['valid' => bool, 'errors' => string[], 'warnings' => string[]]
 */
function validateConfiguration()
{
    $errors = [];
    $warnings = [];

    // Directorios obligatorios
    $requiredDirs = [
        'ROOT_DIR' => ROOT_DIR,
        'CORE_DIR' => CORE_DIR,
        'CONFIG_DIR' => CONFIG_DIR,
        'DATA_DIR' => DATA_DIR
    ];
    foreach ($requiredDirs as $name => $path) {
        if (!is_dir($path)) {
            $errors[] = "$name no existe: $path";
        }
    }

    // Escritura del sitemap
    if (!is_writable(ROOT_DIR)) {
        $warnings[] = 'ROOT_DIR no es escribible; no se podrá generar sitemap.xml: ' . ROOT_DIR;
    }

    // Registro
    if (!is_dir(LOG_DIR)) {
        $warnings[] = 'LOG_DIR no existe todavía (se creará al registrar): ' . LOG_DIR;
    } elseif (!is_writable(LOG_DIR)) {
        $warnings[] = 'LOG_DIR no es escribible: ' . LOG_DIR;
    }

    // BASE_URL
    if (!filter_var(BASE_URL, FILTER_VALIDATE_URL)) {
        $errors[] = 'BASE_URL no es una URL válida: ' . BASE_URL;
    } elseif (substr(BASE_URL, -1) === '/') {
        $errors[] = 'BASE_URL no debe terminar en barra: ' . BASE_URL;
    }

    // INDEXNOW_HOST
    if (INDEXNOW_HOST === '' || INDEXNOW_HOST === 'tu-dominio.com') {
        $warnings[] = 'INDEXNOW_HOST no está configurado (sigue siendo el valor de plantilla)';
    }
    if (strpos(INDEXNOW_HOST, 'http://') === 0 || strpos(INDEXNOW_HOST, 'https://') === 0) {
        $errors[] = 'INDEXNOW_HOST no debe incluir protocolo: ' . INDEXNOW_HOST;
    }

    // Clave IndexNow
    if (!file_exists(INDEXNOW_KEY_FILE)) {
        $errors[] = 'No existe el archivo de clave IndexNow: ' . INDEXNOW_KEY_FILE
            . ' (genérelo con: php core_index/tools/generate_indexnow_key.php)';
    } else {
        $key = trim((string)@file_get_contents(INDEXNOW_KEY_FILE));
        if (!preg_match('/^[A-Za-z0-9-]{8,128}$/', $key)) {
            $errors[] = 'La clave IndexNow no cumple el formato del protocolo (8 a 128 caracteres alfanuméricos o guion)';
        }
    }

    // Credenciales de Google (opcionales)
    if (!file_exists(GOOGLE_CREDENTIALS_PATH)) {
        $warnings[] = 'Sin credenciales de Google; la notificación a Google Indexing API quedará deshabilitada: ' . GOOGLE_CREDENTIALS_PATH;
    }

    // Directorio privado y autenticación web
    if (!is_dir(PRIVATE_DIR)) {
        $warnings[] = 'PRIVATE_DIR no existe todavía (se creará al registrar o guardar credenciales): ' . PRIVATE_DIR;
    }
    if (defined('WEB_AUTH_ENABLED') && WEB_AUTH_ENABLED === true) {
        if (!file_exists(AUTH_USERS_FILE)) {
            $warnings[] = 'No existe el archivo de usuarios de la autenticación web: ' . AUTH_USERS_FILE
                . ' (genérelo con: htpasswd -B -c ' . AUTH_USERS_FILE . ' operador)';
        } else {
            $authPath = realpath(AUTH_USERS_FILE);
            $rootPath = realpath(ROOT_DIR);
            if ($authPath !== false && $rootPath !== false && strpos($authPath, $rootPath) === 0) {
                $warnings[] = 'AUTH_USERS_FILE está dentro del documento raíz; se recomienda moverlo fuera: ' . AUTH_USERS_FILE;
            }
        }
    }

    // Límites
    if (MAX_URLS_PER_SITEMAP < 1 || MAX_URLS_PER_SITEMAP > 50000) {
        $errors[] = 'MAX_URLS_PER_SITEMAP debe estar entre 1 y 50000 (límite del protocolo de sitemaps)';
    }
    if (GOOGLE_DAILY_QUOTA < 1) {
        $errors[] = 'GOOGLE_DAILY_QUOTA debe ser mayor que cero';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Devuelve la lista blanca de frecuencias de cambio admitidas.
 *
 * @return string[]
 */
function getAllowedChangeFrequencies()
{
    return explode(',', CHANGEFREQ_ALLOWED);
}
