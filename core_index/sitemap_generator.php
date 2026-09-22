<?php
/**
 * Sitemap Generator Module - Generador modular de sitemap.xml
 *
 * @package SSEN
 * @subpackage F2C - Orquestadores
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Orquesta el flujo completo: generar sitemap, detectar cambios y notificar APIs.
 * Uso por línea de comandos: php core_index/sitemap_generator.php [--source=json|csv]
 */

// Cargar configuración
require_once __DIR__ . '/config/config.php';

// Cargar módulos F2A
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/sitemap_diff.php';
require_once __DIR__ . '/indexnow_auth.php';

// Cargar módulos F2B
require_once __DIR__ . '/google_indexing_auth.php';
require_once __DIR__ . '/indexnow_client.php';
require_once __DIR__ . '/google_indexing_client.php';

/**
 * Genera sitemap.xml desde un array de URLs y coordina las notificaciones.
 *
 * @param array $urlsData Array de URLs con metadatos (loc, lastmod, changefreq, priority, notify_*)
 * @return array Resultado de la operación
 */
function generateSitemap(array $urlsData)
{
    // 1. Validar entrada
    if (empty($urlsData)) {
        return sitemapGenerationError('Array de URLs vacío');
    }

    // 2. Normalizar y validar cada entrada
    $processedUrls = [];
    $invalidEntries = 0;
    $today = date('Y-m-d');
    $allowedFrequencies = getAllowedChangeFrequencies();

    foreach ($urlsData as $urlData) {
        if (!is_array($urlData)) {
            $invalidEntries++;
            continue;
        }

        $loc = isset($urlData['loc']) ? trim((string)$urlData['loc']) : '';
        if ($loc === '' || filter_var($loc, FILTER_VALIDATE_URL) === false || !preg_match('#^https?://#i', $loc)) {
            $invalidEntries++;
            continue;
        }

        $lastmod = $today;
        if (isset($urlData['lastmod']) && preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:\d{2})?)?$/', trim((string)$urlData['lastmod']))) {
            $lastmod = trim((string)$urlData['lastmod']);
        }

        $changefreq = CHANGEFREQ_DEFAULT;
        if (isset($urlData['changefreq']) && in_array(strtolower(trim((string)$urlData['changefreq'])), $allowedFrequencies, true)) {
            $changefreq = strtolower(trim((string)$urlData['changefreq']));
        }

        $priority = (float)PRIORITY_DEFAULT;
        if (isset($urlData['priority']) && is_numeric($urlData['priority'])) {
            $candidate = (float)$urlData['priority'];
            if ($candidate >= 0.0 && $candidate <= 1.0) {
                $priority = $candidate;
            }
        }

        $processedUrls[] = [
            'url' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'notify_indexnow' => !isset($urlData['notify_indexnow'])
                || (is_scalar($urlData['notify_indexnow']) && filter_var($urlData['notify_indexnow'], FILTER_VALIDATE_BOOLEAN)),
            'notify_google_indexing' => !isset($urlData['notify_google_indexing'])
                || (is_scalar($urlData['notify_google_indexing']) && filter_var($urlData['notify_google_indexing'], FILTER_VALIDATE_BOOLEAN))
        ];
    }

    if (empty($processedUrls)) {
        return sitemapGenerationError('No hay URLs válidas después de la validación', [
            'stats' => ['invalid_entries' => $invalidEntries]
        ]);
    }

    // 3. Aplicar el límite de entradas por sitemap
    $totalRequested = count($processedUrls);
    $limitReached = false;
    if ($totalRequested > MAX_URLS_PER_SITEMAP) {
        $processedUrls = array_slice($processedUrls, 0, MAX_URLS_PER_SITEMAP);
        $limitReached = true;
        logSafely(sprintf(
            'Se descartaron %d URLs por superar MAX_URLS_PER_SITEMAP (%d)',
            $totalRequested - MAX_URLS_PER_SITEMAP,
            MAX_URLS_PER_SITEMAP
        ));
    }

    // 4. Ordenar por prioridad descendente y, después, por fecha descendente
    usort($processedUrls, static function (array $a, array $b) {
        if ($a['priority'] !== $b['priority']) {
            return $a['priority'] > $b['priority'] ? -1 : 1;
        }
        if ($a['lastmod'] === $b['lastmod']) {
            return 0;
        }
        return $a['lastmod'] > $b['lastmod'] ? -1 : 1;
    });

    // 5. Construir el XML
    $sitemapXML = buildSitemapXML($processedUrls);

    // 6. Estado del sitemap anterior
    $previous = readSitemap(SITEMAP_PATH);
    $diffReliable = in_array($previous['status'], ['ok', 'missing'], true);
    if (!$diffReliable) {
        logSafely('No se pudo interpretar el sitemap anterior (' . $previous['status'] . '); se omiten las notificaciones para evitar renotificaciones masivas: ' . (string)$previous['error']);
    }

    // 7. Copia de seguridad del sitemap anterior
    if ($previous['status'] === 'ok' && !@copy(SITEMAP_PATH, SITEMAP_OLD_PATH)) {
        logSafely('No se pudo crear la copia de seguridad: ' . SITEMAP_OLD_PATH);
    }

    // 8. Escribir el nuevo sitemap
    if (file_put_contents(SITEMAP_PATH, $sitemapXML, LOCK_EX) === false) {
        return sitemapGenerationError('No se pudo escribir el sitemap en: ' . SITEMAP_PATH);
    }

    // 9. Clasificar los cambios
    $newUrlsMap = [];
    foreach ($processedUrls as $item) {
        $newUrlsMap[$item['url']] = $item['lastmod'];
    }
    $changes = classifySitemapChanges($previous['urls'], $newUrlsMap);

    // 10. Procesar notificaciones
    if ($diffReliable) {
        $notificationResults = processNotifications($changes, $processedUrls);
    } else {
        $notificationResults = [
            'indexnow' => [
                'success' => false,
                'http_code' => null,
                'message' => 'Notificaciones omitidas: el sitemap anterior no se pudo interpretar',
                'urls_count' => 0,
                'urls_list' => []
            ],
            'google' => [
                'authenticated' => false,
                'success_count' => 0,
                'error_count' => 0,
                'message' => 'Notificaciones omitidas: el sitemap anterior no se pudo interpretar',
                'skipped_deletions' => [],
                'details' => []
            ]
        ];
    }

    // 11. Estadísticas y resultado
    $stats = [
        'urls_added' => $processedUrls,
        'total_urls' => count($processedUrls),
        'invalid_entries' => $invalidEntries,
        'limit_reached' => $limitReached,
        'new_count' => count($changes['new']),
        'updated_count' => count($changes['updated']),
        'deleted_count' => count($changes['deleted']),
        'generation_date' => date('Y-m-d H:i:s')
    ];

    return [
        'success' => true,
        'sitemap_path' => SITEMAP_PATH,
        'total_urls' => count($processedUrls),
        'changed_urls' => [
            'new' => $changes['new'],
            'updated' => $changes['updated'],
            'deleted' => $changes['deleted'],
            'all' => $changes['all']
        ],
        'indexnow_result' => $notificationResults['indexnow'],
        'google_result' => $notificationResults['google'],
        'stats' => $stats
    ];
}

/**
 * Construye el XML del sitemap escapando los cuatro campos.
 *
 * @param array $urlsData URLs procesadas
 * @return string XML completo
 */
function buildSitemapXML(array $urlsData)
{
    $escape = static function ($value) {
        return htmlspecialchars((string)$value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };

    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach ($urlsData as $item) {
        $xml .= "  <url>\n";
        $xml .= '    <loc>' . $escape($item['url']) . "</loc>\n";
        $xml .= '    <lastmod>' . $escape($item['lastmod']) . "</lastmod>\n";
        $xml .= '    <changefreq>' . $escape($item['changefreq']) . "</changefreq>\n";
        $xml .= '    <priority>' . number_format((float)$item['priority'], 1, '.', '') . "</priority>\n";
        $xml .= "  </url>\n";
    }

    $xml .= "</urlset>\n";

    return $xml;
}

/**
 * Procesa las notificaciones a IndexNow y a Google Indexing API.
 *
 * IndexNow recibe nuevas, actualizadas y eliminadas (el protocolo no distingue
 * tipos). Google recibe URL_UPDATED para nuevas y actualizadas, y URL_DELETED
 * solo para eliminadas que devuelven 404 o 410, conforme a su documentación.
 *
 * @param array $changes Clasificación de cambios
 * @param array $urlsData URLs procesadas (para consultar los indicadores de notificación)
 * @return array ['indexnow' => array, 'google' => array]
 */
function processNotifications(array $changes, array $urlsData)
{
    $results = ['indexnow' => null, 'google' => null];

    $byUrl = [];
    foreach ($urlsData as $item) {
        $byUrl[$item['url']] = $item;
    }

    // ---- IndexNow ----
    $indexnowUrls = [];
    foreach ($changes['all'] as $url) {
        $notify = isset($byUrl[$url]) ? (bool)$byUrl[$url]['notify_indexnow'] : true;
        if ($notify) {
            $indexnowUrls[] = $url;
        }
    }

    if (!empty($indexnowUrls)) {
        try {
            $results['indexnow'] = submitToIndexNow($indexnowUrls, getIndexNowCredentials());
        } catch (Throwable $e) {
            $results['indexnow'] = [
                'success' => false,
                'http_code' => null,
                'message' => 'Error: ' . $e->getMessage(),
                'urls_count' => count($indexnowUrls),
                'urls_list' => []
            ];
        }
    } else {
        $results['indexnow'] = [
            'success' => true,
            'http_code' => null,
            'message' => 'No hay URLs para notificar a IndexNow',
            'urls_count' => 0,
            'urls_list' => []
        ];
    }

    // ---- Google Indexing API ----
    $googleItems = [];
    foreach (['new', 'updated'] as $group) {
        foreach ($changes[$group] as $url) {
            if (!isset($byUrl[$url]) || $byUrl[$url]['notify_google_indexing']) {
                $googleItems[] = ['url' => $url, 'type' => 'URL_UPDATED'];
            }
        }
    }

    $skippedDeletions = [];
    foreach ($changes['deleted'] as $url) {
        if (isUrlGone($url)) {
            $googleItems[] = ['url' => $url, 'type' => 'URL_DELETED'];
        } else {
            $skippedDeletions[] = $url;
        }
    }
    if (!empty($skippedDeletions)) {
        logSafely(sprintf(
            'Se omiten %d eliminaciones en Google: las URLs no devuelven 404 ni 410',
            count($skippedDeletions)
        ));
    }

    if (empty($googleItems)) {
        return [
            'indexnow' => $results['indexnow'],
            'google' => [
                'authenticated' => false,
                'success_count' => 0,
                'error_count' => 0,
                'message' => 'No hay URLs para notificar a Google',
                'skipped_deletions' => $skippedDeletions,
                'details' => []
            ]
        ];
    }

    if (!file_exists(GOOGLE_CREDENTIALS_PATH)) {
        return [
            'indexnow' => $results['indexnow'],
            'google' => [
                'authenticated' => false,
                'success_count' => 0,
                'error_count' => 0,
                'message' => 'Google deshabilitado: no hay credenciales en ' . GOOGLE_CREDENTIALS_PATH,
                'skipped_deletions' => $skippedDeletions,
                'details' => []
            ]
        ];
    }

    try {
        $quota = reserveGoogleQuota(count($googleItems));
        if ($quota['allowed'] < count($googleItems)) {
            logSafely(sprintf(
                'Cuota diaria de Google: se envían %d de %d notificaciones',
                $quota['allowed'],
                count($googleItems)
            ));
            $googleItems = array_slice($googleItems, 0, $quota['allowed']);
        }

        if (empty($googleItems)) {
            return [
                'indexnow' => $results['indexnow'],
                'google' => [
                    'authenticated' => false,
                    'success_count' => 0,
                    'error_count' => 0,
                    'message' => 'Cuota diaria de Google agotada; no se envía ninguna notificación',
                    'skipped_deletions' => $skippedDeletions,
                    'details' => []
                ]
            ];
        }

        $tokenResult = getGoogleIndexingToken(GOOGLE_CREDENTIALS_PATH);
        if (!$tokenResult['success']) {
            return [
                'indexnow' => $results['indexnow'],
                'google' => [
                    'authenticated' => false,
                    'success_count' => 0,
                    'error_count' => 0,
                    'message' => 'Error de autenticación: ' . $tokenResult['error'],
                    'skipped_deletions' => $skippedDeletions,
                    'details' => []
                ]
            ];
        }

        $googleResult = submitBatchToGoogleIndexingAPI($googleItems, $tokenResult['token'], GOOGLE_REQUEST_DELAY_MS);

        return [
            'indexnow' => $results['indexnow'],
            'google' => [
                'authenticated' => true,
                'success_count' => $googleResult['success_count'],
                'error_count' => $googleResult['error_count'],
                'message' => 'Notificación a Google completada',
                'skipped_deletions' => $skippedDeletions,
                'details' => $googleResult['details']
            ]
        ];
    } catch (Throwable $e) {
        return [
            'indexnow' => $results['indexnow'],
            'google' => [
                'authenticated' => false,
                'success_count' => 0,
                'error_count' => 0,
                'message' => 'Error inesperado al notificar a Google: ' . $e->getMessage(),
                'skipped_deletions' => $skippedDeletions,
                'details' => []
            ]
        ];
    }
}

/**
 * Reserva cupo de la cuota diaria de Google y actualiza su estado.
 *
 * @param int $requested Notificaciones solicitadas
 * @return array ['allowed' => int, 'remaining' => int]
 */
function reserveGoogleQuota($requested)
{
    $state = ['date' => date('Y-m-d'), 'count' => 0];

    if (file_exists(GOOGLE_QUOTA_STATE_PATH)) {
        $raw = @file_get_contents(GOOGLE_QUOTA_STATE_PATH);
        $decoded = $raw !== false ? json_decode($raw, true) : null;
        if (is_array($decoded) && isset($decoded['date'], $decoded['count']) && $decoded['date'] === $state['date']) {
            $state['count'] = max(0, (int)$decoded['count']);
        }
    }

    $remaining = max(0, (int)GOOGLE_DAILY_QUOTA - $state['count']);
    $allowed = min((int)$requested, $remaining);
    $state['count'] += $allowed;

    if (@file_put_contents(GOOGLE_QUOTA_STATE_PATH, json_encode($state), LOCK_EX) === false) {
        logSafely('No se pudo guardar el estado de la cuota de Google; la cuota diaria no quedará registrada');
    }

    return ['allowed' => $allowed, 'remaining' => $remaining - $allowed];
}

/**
 * Construye una respuesta de error uniforme del generador.
 *
 * @param string $message Mensaje de error
 * @param array $extra Campos adicionales que sobrescriben el resultado base
 * @return array
 */
function sitemapGenerationError($message, array $extra = [])
{
    return array_merge([
        'success' => false,
        'error' => $message,
        'sitemap_path' => null,
        'total_urls' => 0,
        'changed_urls' => ['new' => [], 'updated' => [], 'deleted' => [], 'all' => []],
        'indexnow_result' => null,
        'google_result' => null,
        'stats' => []
    ], $extra);
}

/**
 * Registra un mensaje interno sin interrumpir la ejecución.
 *
 * @param string $message Mensaje
 * @param string $url Referencia
 * @param string $operation Tipo de operación
 * @param int|null $httpCode Código HTTP
 * @return void
 */
function logSafely($message, $url = 'GENERATOR', $operation = 'INTERNAL', $httpCode = null)
{
    if (!defined('LOG_PATH') || !function_exists('logIndexingAction')) {
        return;
    }
    try {
        logIndexingAction(LOG_PATH, $url, $operation, 'INTERNAL', $httpCode, $message);
    } catch (Throwable $e) {
        error_log('SSEN: fallo al registrar: ' . $e->getMessage());
    }
}

/**
 * Intenta adquirir el bloqueo de generación (evita solapamientos).
 *
 * @return resource|false Manejador del bloqueo o false si no se pudo adquirir
 */
function acquireGenerationLock()
{
    $lockPath = LOG_DIR . '/.generate.lock';

    if (!is_dir(LOG_DIR) && !@mkdir(LOG_DIR, 0755, true) && !is_dir(LOG_DIR)) {
        return false;
    }

    $handle = @fopen($lockPath, 'c');
    if ($handle === false) {
        return false;
    }
    if (!flock($handle, LOCK_EX | LOCK_NB)) {
        fclose($handle);
        return false;
    }

    return $handle;
}

/**
 * Libera el bloqueo de generación.
 *
 * @param resource|false $handle Manejador devuelto por acquireGenerationLock()
 * @return void
 */
function releaseGenerationLock($handle)
{
    if (is_resource($handle)) {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

// ==================== INTERFAZ DE LÍNEA DE COMANDOS ====================

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === realpath(__FILE__)) {
    exit(runSitemapGeneratorCli($argv));
}

/**
 * Ejecuta el generador desde la línea de comandos.
 *
 * Opciones: --source=json|csv (por defecto json), --help
 * Códigos de salida: 0 correcto, 1 error de ejecución, 2 error de configuración, 3 ejecución en curso
 *
 * @param array $argv Argumentos de la línea de comandos
 * @return int Código de salida
 */
function runSitemapGeneratorCli(array $argv)
{
    $options = getopt('', ['source:', 'help']);
    if ($options === false) {
        $options = [];
    }

    if (isset($options['help'])) {
        fwrite(STDOUT, "Uso: php core_index/sitemap_generator.php [--source=json|csv]\n");
        return 0;
    }

    $lock = acquireGenerationLock();
    if ($lock === false) {
        fwrite(STDERR, "Ya hay una ejecución en curso o no se pudo crear el bloqueo; se cancela esta.\n");
        return 3;
    }

    try {
        // 1. Validar configuración
        $validation = validateConfiguration();
        if (!$validation['valid']) {
            foreach ($validation['errors'] as $error) {
                fwrite(STDERR, 'ERROR: ' . $error . "\n");
            }
            return 2;
        }
        foreach ($validation['warnings'] as $warning) {
            fwrite(STDERR, 'AVISO: ' . $warning . "\n");
        }

        // 2. Determinar el origen de datos
        $source = isset($options['source']) ? strtolower((string)$options['source']) : 'json';
        if (!in_array($source, ['json', 'csv'], true)) {
            fwrite(STDERR, "Origen no admitido: $source (use json o csv)\n");
            return 2;
        }

        // 3. Cargar URLs
        require_once __DIR__ . '/urls_loader.php';
        $loadResult = ($source === 'json') ? loadUrlsFromJSON(URLS_JSON_PATH) : loadUrlsFromCSV(URLS_CSV_PATH);
        if (!$loadResult['success']) {
            fwrite(STDERR, 'ERROR: ' . $loadResult['error'] . "\n");
            return 1;
        }
        foreach ($loadResult['warnings'] as $warning) {
            fwrite(STDERR, 'AVISO: ' . $warning . "\n");
        }

        // 4. Generar y notificar
        $generation = generateSitemap($loadResult['urls']);
        if (!$generation['success']) {
            fwrite(STDERR, 'ERROR: ' . $generation['error'] . "\n");
            return 1;
        }

        // 5. Resumen
        fwrite(STDOUT, sprintf(
            "Sitemap generado: %s\nURLs: %d | Nuevas: %d | Actualizadas: %d | Eliminadas: %d\nIndexNow: %s\nGoogle: %s\n",
            $generation['sitemap_path'],
            $generation['total_urls'],
            count($generation['changed_urls']['new']),
            count($generation['changed_urls']['updated']),
            count($generation['changed_urls']['deleted']),
            isset($generation['indexnow_result']['message']) ? $generation['indexnow_result']['message'] : 'sin datos',
            isset($generation['google_result']['message']) ? $generation['google_result']['message'] : 'sin datos'
        ));

        return 0;
    } finally {
        releaseGenerationLock($lock);
    }
}
