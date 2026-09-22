<?php
/**
 * Sitemap Diff Module - Comparación de sitemaps y detección de cambios
 *
 * @package SSEN
 * @subpackage F2A - Soporte Básico
 * @version 1.1.0
 * @date 2026-09-21
 */

/**
 * Lee un sitemap y devuelve su estado y sus URLs.
 *
 * Distingue entre archivo ausente, ilegible, XML corrupto y lectura correcta,
 * para que el orquestador pueda decidir si es seguro calcular diferencias.
 *
 * @param string $sitemapPath Ruta absoluta al sitemap
 * @return array ['status' => 'ok'|'missing'|'unreadable'|'corrupt', 'urls' => array, 'error' => string|null]
 * @throws InvalidArgumentException Si la ruta está vacía
 */
function readSitemap($sitemapPath)
{
    if (empty($sitemapPath)) {
        throw new InvalidArgumentException('sitemapPath no puede estar vacío');
    }

    // 1. Archivo ausente: primer uso, sin error
    if (!file_exists($sitemapPath)) {
        return ['status' => 'missing', 'urls' => [], 'error' => null];
    }

    // 2. Archivo no legible
    if (!is_readable($sitemapPath)) {
        return [
            'status' => 'unreadable',
            'urls' => [],
            'error' => 'El archivo no es legible: ' . $sitemapPath
        ];
    }

    // 3. Intentar interpretar el XML capturando los errores de libxml
    $previousUseErrors = libxml_use_internal_errors(true);
    $xml = simplexml_load_file($sitemapPath);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($previousUseErrors);

    if ($xml === false) {
        $detail = '';
        if (!empty($errors) && isset($errors[0]->message)) {
            $detail = trim($errors[0]->message);
        }
        return [
            'status' => 'corrupt',
            'urls' => [],
            'error' => $detail !== '' ? $detail : 'XML no interpretable'
        ];
    }

    // 4. Extraer el mapa [url => lastmod]
    $urls = [];
    foreach ($xml->url as $urlNode) {
        $loc = (string)$urlNode->loc;
        if ($loc === '') {
            continue;
        }
        $lastmod = isset($urlNode->lastmod) ? (string)$urlNode->lastmod : '';
        $urls[$loc] = $lastmod;
    }

    return ['status' => 'ok', 'urls' => $urls, 'error' => null];
}

/**
 * Compatibilidad: devuelve solo el mapa de URLs del sitemap.
 *
 * @param string $sitemapPath Ruta absoluta al sitemap
 * @return array Mapa [url => lastmod] (vacío si no existe o no se puede interpretar)
 */
function parseSitemap($sitemapPath)
{
    $result = readSitemap($sitemapPath);
    return $result['urls'];
}

/**
 * Clasifica los cambios entre dos sitemaps.
 *
 * Criterios: nueva (solo en el nuevo), actualizada (lastmod distinto) y
 * eliminada (solo en el antiguo).
 *
 * @param array $oldUrls Mapa antiguo [url => lastmod]
 * @param array $newUrls Mapa nuevo [url => lastmod]
 * @return array ['new' => string[], 'updated' => string[], 'deleted' => string[], 'all' => string[]]
 */
function classifySitemapChanges(array $oldUrls, array $newUrls)
{
    $new = [];
    $updated = [];
    $deleted = [];

    foreach ($newUrls as $url => $lastmod) {
        if (!array_key_exists($url, $oldUrls)) {
            $new[] = $url;
        } elseif ($oldUrls[$url] !== $lastmod) {
            $updated[] = $url;
        }
    }

    foreach ($oldUrls as $url => $lastmod) {
        if (!array_key_exists($url, $newUrls)) {
            $deleted[] = $url;
        }
    }

    return [
        'new' => $new,
        'updated' => $updated,
        'deleted' => $deleted,
        'all' => array_values(array_unique(array_merge($new, $updated, $deleted)))
    ];
}

/**
 * Compatibilidad: devuelve la lista plana de URLs cambiadas.
 *
 * @param array $oldUrls Mapa antiguo [url => lastmod]
 * @param array $newUrls Mapa nuevo [url => lastmod]
 * @return string[] URLs nuevas, actualizadas o eliminadas
 */
function detectChangedUrls($oldUrls, $newUrls)
{
    $changes = classifySitemapChanges($oldUrls, $newUrls);
    return $changes['all'];
}
