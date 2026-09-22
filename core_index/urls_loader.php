<?php
/**
 * URLs Loader Module - Carga y validación de entradas JSON y CSV
 *
 * @package SSEN
 * @subpackage F2A - Soporte Básico
 * @version 1.1.0
 * @date 2026-09-21
 *
 * Contrato JSON: {"urls": [{"loc": "https://...", ...}]}
 * Contrato CSV: cabecera con la columna loc (obligatoria); columnas admitidas:
 * loc, lastmod, changefreq, priority, notify_indexnow, notify_google_indexing
 */

require_once __DIR__ . '/config/config.php';

/**
 * Añade un aviso a la lista, limitando el total para no generar informes enormes.
 *
 * @param array $warnings Lista de avisos (por referencia)
 * @param string $message Mensaje
 * @return void
 */
function addLoaderWarning(array &$warnings, $message)
{
    if (count($warnings) < 50) {
        $warnings[] = $message;
    } elseif (count($warnings) === 50) {
        $warnings[] = 'Hay más avisos omitidos en este informe';
    }
}

/**
 * Valida y normaliza una entrada de URL.
 *
 * @param array $urlData Entrada original
 * @param int|string $reference Índice o línea (para los mensajes)
 * @param array $warnings Lista de avisos (por referencia)
 * @return array|null Entrada normalizada o null si no es válida
 */
function normalizeUrlEntry(array $urlData, $reference, array &$warnings)
{
    $loc = isset($urlData['loc']) ? trim((string)$urlData['loc']) : '';
    if ($loc === '') {
        addLoaderWarning($warnings, "Entrada $reference sin 'loc'; se omite");
        return null;
    }

    if (filter_var($loc, FILTER_VALIDATE_URL) === false || !preg_match('#^https?://#i', $loc)) {
        addLoaderWarning($warnings, "Entrada $reference con 'loc' no válida; se omite: $loc");
        return null;
    }

    $entry = ['loc' => $loc];

    // lastmod: fecha ISO (YYYY-MM-DD) o fecha y hora ISO 8601
    if (isset($urlData['lastmod']) && trim((string)$urlData['lastmod']) !== '') {
        $lastmod = trim((string)$urlData['lastmod']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}([T ]\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:\d{2})?)?$/', $lastmod)) {
            $entry['lastmod'] = $lastmod;
        } else {
            addLoaderWarning($warnings, "Entrada $reference con 'lastmod' no válido; se usará la fecha actual: $lastmod");
        }
    }

    // changefreq: lista blanca del protocolo
    if (isset($urlData['changefreq']) && trim((string)$urlData['changefreq']) !== '') {
        $changefreq = strtolower(trim((string)$urlData['changefreq']));
        if (in_array($changefreq, getAllowedChangeFrequencies(), true)) {
            $entry['changefreq'] = $changefreq;
        } else {
            addLoaderWarning($warnings, "Entrada $reference con 'changefreq' no admitido; se omitirá ese valor: $changefreq");
        }
    }

    // priority: número entre 0 y 1
    if (isset($urlData['priority']) && trim((string)$urlData['priority']) !== '') {
        if (is_numeric($urlData['priority'])) {
            $priority = (float)$urlData['priority'];
            if ($priority >= 0.0 && $priority <= 1.0) {
                $entry['priority'] = $priority;
            } else {
                addLoaderWarning($warnings, "Entrada $reference con 'priority' fuera del rango 0 a 1; se omitirá ese valor");
            }
        } else {
            addLoaderWarning($warnings, "Entrada $reference con 'priority' no numérico; se omitirá ese valor");
        }
    }

    // Indicadores de notificación
    foreach (['notify_indexnow', 'notify_google_indexing'] as $flag) {
        if (isset($urlData[$flag])) {
            if (is_bool($urlData[$flag])) {
                $entry[$flag] = $urlData[$flag];
            } else {
                $entry[$flag] = in_array(strtolower(trim((string)$urlData[$flag])), ['1', 'true'], true);
            }
        }
    }

    return $entry;
}

/**
 * Carga URLs desde un archivo JSON.
 *
 * @param string $jsonPath Ruta al archivo JSON
 * @return array ['success', 'urls', 'count', 'error', 'warnings']
 */
function loadUrlsFromJSON($jsonPath)
{
    $warnings = [];

    if (!file_exists($jsonPath)) {
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => "Archivo JSON no encontrado: $jsonPath", 'warnings' => $warnings];
    }

    $jsonContent = @file_get_contents($jsonPath);
    if ($jsonContent === false) {
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => 'No se pudo leer el archivo JSON', 'warnings' => $warnings];
    }

    $data = json_decode($jsonContent, true);
    if ($data === null) {
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => 'JSON inválido: ' . json_last_error_msg(), 'warnings' => $warnings];
    }

    if (!isset($data['urls']) || !is_array($data['urls'])) {
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => "Campo 'urls' no encontrado o no es un array", 'warnings' => $warnings];
    }

    $urls = [];
    foreach ($data['urls'] as $index => $urlData) {
        if (!is_array($urlData)) {
            addLoaderWarning($warnings, 'Entrada ' . ($index + 1) . ' no es un objeto; se omite');
            continue;
        }
        $entry = normalizeUrlEntry($urlData, $index + 1, $warnings);
        if ($entry !== null) {
            $urls[] = $entry;
        }
    }

    return ['success' => true, 'urls' => $urls, 'count' => count($urls), 'error' => null, 'warnings' => $warnings];
}

/**
 * Carga URLs desde un archivo CSV.
 *
 * @param string $csvPath Ruta al archivo CSV
 * @return array ['success', 'urls', 'count', 'error', 'warnings']
 */
function loadUrlsFromCSV($csvPath)
{
    $warnings = [];

    if (!file_exists($csvPath)) {
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => "Archivo CSV no encontrado: $csvPath", 'warnings' => $warnings];
    }

    $file = @fopen($csvPath, 'r');
    if ($file === false) {
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => 'No se pudo abrir el archivo CSV', 'warnings' => $warnings];
    }

    // Cabecera: quitar BOM, normalizar a minúsculas y validar
    $header = fgetcsv($file);
    if ($header === false || empty($header)) {
        fclose($file);
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => 'CSV sin cabecera o vacío', 'warnings' => $warnings];
    }

    $header = array_map(static function ($column) {
        $column = (string)$column;
        if (strncmp($column, "\xEF\xBB\xBF", 3) === 0) {
            $column = substr($column, 3);
        }
        return strtolower(trim($column));
    }, $header);

    if (!in_array('loc', $header, true)) {
        fclose($file);
        return ['success' => false, 'urls' => [], 'count' => 0, 'error' => "Campo 'loc' obligatorio no encontrado en la cabecera del CSV", 'warnings' => $warnings];
    }

    $allowedColumns = ['loc', 'lastmod', 'changefreq', 'priority', 'notify_indexnow', 'notify_google_indexing'];
    $unknownColumns = array_diff($header, $allowedColumns);
    if (!empty($unknownColumns)) {
        addLoaderWarning($warnings, 'Columnas no reconocidas (se ignoran): ' . implode(', ', $unknownColumns));
    }

    $urls = [];
    $lineNumber = 1;
    while (($row = fgetcsv($file)) !== false) {
        $lineNumber++;

        if ($row === [null] || $row === false) {
            continue;
        }
        if (count($row) !== count($header)) {
            addLoaderWarning($warnings, "Línea $lineNumber con un número de columnas distinto al de la cabecera; se omite");
            continue;
        }

        $urlData = @array_combine($header, $row);
        if ($urlData === false) {
            addLoaderWarning($warnings, "Línea $lineNumber no procesable; se omite");
            continue;
        }

        $entry = normalizeUrlEntry($urlData, $lineNumber, $warnings);
        if ($entry !== null) {
            $urls[] = $entry;
        }
    }

    fclose($file);

    return ['success' => true, 'urls' => $urls, 'count' => count($urls), 'error' => null, 'warnings' => $warnings];
}
