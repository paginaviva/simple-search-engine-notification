<?php
/**
 * Sitemap Form URL Module - Interfaz web de entrada (JSON/CSV)
 *
 * @package SSEN
 * @subpackage F2C - Orquestadores
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Punto de entrada web. La autenticación básica se aplica en la aplicación
 * (core_index/auth_guard.php) y debe accederse siempre por HTTPS.
 */

// Cargar configuración y módulos
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/urls_loader.php';
require_once __DIR__ . '/sitemap_generator.php';
require_once __DIR__ . '/auth_guard.php';

/**
 * Escapa texto para HTML.
 *
 * @param mixed $value Valor a escapar
 * @return string Texto seguro
 */
function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Comprueba el límite de frecuencia de la interfaz web.
 *
 * @return array ['allowed' => bool, 'retry_after' => int]
 */
function checkWebRateLimit()
{
    $limit = defined('WEB_RATE_LIMIT_SECONDS') ? (int)WEB_RATE_LIMIT_SECONDS : 0;
    if ($limit <= 0) {
        return ['allowed' => true, 'retry_after' => 0];
    }

    if (!is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0755, true);
    }

    $statePath = LOG_DIR . '/.web_last_run';
    if (file_exists($statePath)) {
        $last = (int)@file_get_contents($statePath);
        $elapsed = time() - $last;
        if ($last > 0 && $elapsed >= 0 && $elapsed < $limit) {
            return ['allowed' => false, 'retry_after' => $limit - $elapsed];
        }
    }

    @file_put_contents($statePath, (string)time(), LOCK_EX);
    return ['allowed' => true, 'retry_after' => 0];
}

/**
 * Devuelve el estilo común de las páginas de la interfaz.
 *
 * @return string Bloque CSS
 */
function renderPageStyle()
{
    return '<style>'
        . 'body{font-family:system-ui,Arial,sans-serif;margin:2rem;background:#f6f7f9;color:#1f2933}'
        . '.card{max-width:760px;margin:0 auto;background:#fff;border-radius:8px;padding:1.5rem 2rem;box-shadow:0 1px 4px rgba(0,0,0,.08)}'
        . 'h1{font-size:1.3rem;margin-top:0}.success{border-left:6px solid #2e7d32}.warning{border-left:6px solid #f9a825}.error{border-left:6px solid #c62828}'
        . 'ul{padding-left:1.2rem}code{background:#eef1f4;padding:.1rem .3rem;border-radius:3px}a{color:#1565c0}'
        . '</style>';
}

/**
 * Renderiza una página sencilla de mensaje.
 *
 * @param string $title Título
 * @param string $message Mensaje
 * @param string $state Estado visual: success, warning o error
 * @return string HTML completo
 */
function renderMessagePage($title, $message, $state = 'error')
{
    return "<!DOCTYPE html>\n<html lang=\"es\">\n<head>\n"
        . "    <meta charset=\"UTF-8\">\n"
        . "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
        . '    <title>' . h($title) . "</title>\n"
        . renderPageStyle()
        . "</head>\n<body>\n"
        . '    <div class="card ' . h($state) . "\">\n"
        . '        <h1>' . h($title) . "</h1>\n"
        . '        <p>' . h($message) . "</p>\n"
        . "    </div>\n</body>\n</html>\n";
}

/**
 * Genera la página HTML de resultado con estadísticas.
 *
 * @param array $result Resultado de generateSitemap()
 * @return string HTML completo
 */
function renderResultPage(array $result)
{
    if (!$result['success']) {
        $state = 'error';
        $title = 'No se pudo generar el sitemap';
        $subtitle = 'El detalle del error está en el registro del servidor: core_index/logs/indexing_api_sitemap.log';
    } else {
        $notificationProblem = false;
        if (isset($result['indexnow_result']['success']) && $result['indexnow_result']['success'] === false) {
            $notificationProblem = true;
        }
        if (isset($result['google_result']['authenticated']) && $result['google_result']['authenticated'] === true
            && isset($result['google_result']['error_count']) && $result['google_result']['error_count'] > 0) {
            $notificationProblem = true;
        }
        $state = $notificationProblem ? 'warning' : 'success';
        $title = $notificationProblem ? 'Sitemap generado con avisos en las notificaciones' : 'Sitemap generado y notificado';
        $subtitle = 'Fecha: ' . date('Y-m-d H:i:s');
    }

    $html = "<!DOCTYPE html>\n<html lang=\"es\">\n<head>\n"
        . "    <meta charset=\"UTF-8\">\n"
        . "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n"
        . "    <title>Resultado - Generación de sitemap</title>\n"
        . renderPageStyle()
        . "</head>\n<body>\n"
        . '    <div class="card ' . h($state) . "\">\n"
        . '        <h1>' . h($title) . "</h1>\n"
        . '        <p>' . h($subtitle) . "</p>\n";

    if (!$result['success']) {
        $html .= "    </div>\n</body>\n</html>\n";
        return $html;
    }

    // Resumen de URLs
    $html .= "        <h2>Resumen</h2>\n        <ul>\n"
        . '            <li>Total de URLs en el sitemap: ' . (int)$result['total_urls'] . "</li>\n"
        . '            <li>Nuevas: ' . count($result['changed_urls']['new']) . "</li>\n"
        . '            <li>Actualizadas: ' . count($result['changed_urls']['updated']) . "</li>\n"
        . '            <li>Eliminadas: ' . count($result['changed_urls']['deleted']) . "</li>\n"
        . "        </ul>\n";

    if (isset($result['stats']['invalid_entries']) && (int)$result['stats']['invalid_entries'] > 0) {
        $html .= '        <p>Aviso: se omitieron ' . (int)$result['stats']['invalid_entries'] . " entradas no válidas.</p>\n";
    }
    if (!empty($result['stats']['limit_reached'])) {
        $html .= "        <p>Aviso: se alcanzó el límite de entradas por sitemap; parte de las URLs no se incluyó.</p>\n";
    }

    // Resultado de IndexNow
    if (!empty($result['indexnow_result'])) {
        $indexnow = $result['indexnow_result'];
        $html .= '        <h2>IndexNow</h2><p>Estado: ' . h($indexnow['message']) . "</p>\n";
        $html .= '        <p>URLs enviadas: ' . (int)$indexnow['urls_count'] . "</p>\n";
        if (!empty($indexnow['http_code'])) {
            $html .= '        <p>Código HTTP: ' . h($indexnow['http_code']) . "</p>\n";
        }
    }

    // Resultado de Google
    if (!empty($result['google_result'])) {
        $google = $result['google_result'];
        $html .= '        <h2>Google Indexing API</h2><p>Estado: ' . h($google['message']) . "</p>\n";
        if (!empty($google['authenticated'])) {
            $html .= '        <p>Notificaciones correctas: ' . (int)$google['success_count'] . "</p>\n";
            $html .= '        <p>Notificaciones con error: ' . (int)$google['error_count'] . "</p>\n";
        }
        if (!empty($google['skipped_deletions'])) {
            $html .= '        <p>Eliminaciones omitidas (la URL no devuelve 404 ni 410): ' . count($google['skipped_deletions']) . "</p>\n";
        }
    }

    // Enlace al sitemap
    $html .= '        <p><a href="' . h(rtrim(BASE_URL, '/') . '/sitemap.xml') . '" target="_blank" rel="noopener">Ver el sitemap generado</a></p>' . "\n"
        . "    </div>\n</body>\n</html>\n";

    return $html;
}

// ==================== FLUJO PRINCIPAL ====================

// Solo ejecutar si se accede directamente (no si se incluye desde otro archivo)
if (basename($_SERVER['PHP_SELF']) == 'sitemap_form_url.php') {

    // 0. Autenticación básica a nivel de aplicación (Apache, LiteSpeed y nginx)
    requireWebAuthentication();

    // 1. Origen solicitado (lista blanca)
    $source = isset($_GET['source']) ? strtolower(trim((string)$_GET['source'])) : 'json';
    if (!in_array($source, ['json', 'csv'], true)) {
        http_response_code(400);
        echo renderMessagePage('Solicitud no válida', 'El parámetro source solo admite los valores json o csv.');
        exit;
    }

    // 2. Validación de configuración (detalle al registro; mensaje genérico)
    $validation = validateConfiguration();
    if (!$validation['valid']) {
        foreach ($validation['errors'] as $error) {
            logSafely('Configuración inválida: ' . $error);
        }
        http_response_code(500);
        echo renderMessagePage('Error de configuración', 'La configuración del sistema no es válida. El detalle está en el registro del servidor.');
        exit;
    }
    foreach ($validation['warnings'] as $warning) {
        logSafely('Aviso de configuración: ' . $warning);
    }

    // 3. Límite de frecuencia
    $rate = checkWebRateLimit();
    if (!$rate['allowed']) {
        http_response_code(429);
        echo renderMessagePage('Demasiadas solicitudes', 'Espere ' . (int)$rate['retry_after'] . ' segundos antes de volver a ejecutar.');
        exit;
    }

    // 4. Bloqueo de ejecución (evita solapamientos con cron u otra visita)
    $lock = acquireGenerationLock();
    if ($lock === false) {
        http_response_code(409);
        echo renderMessagePage('Ejecución en curso', 'Ya hay una generación de sitemap en marcha. Inténtelo de nuevo en unos segundos.');
        exit;
    }

    try {
        // 5. Cargar URLs desde JSON o CSV
        $urlsResult = ($source === 'json') ? loadUrlsFromJSON(URLS_JSON_PATH) : loadUrlsFromCSV(URLS_CSV_PATH);
        if (!$urlsResult['success']) {
            logSafely('Error al cargar las URLs: ' . $urlsResult['error']);
            http_response_code(500);
            echo renderMessagePage('Error al cargar las URLs', 'No se pudieron cargar las URLs de entrada. El detalle está en el registro del servidor.');
            exit;
        }
        foreach ($urlsResult['warnings'] as $warning) {
            logSafely('Aviso de entrada: ' . $warning);
        }

        // 6. Generar el sitemap y notificar
        $generatorResult = generateSitemap($urlsResult['urls']);
        if (!$generatorResult['success']) {
            logSafely('Error de generación: ' . $generatorResult['error']);
        }

        // 7. Renderizar el resultado
        echo renderResultPage($generatorResult);
    } finally {
        releaseGenerationLock($lock);
    }
}
