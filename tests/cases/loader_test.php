<?php
/**
 * Pruebas del cargador de entradas JSON y CSV.
 */

test('loadUrlsFromJSON valida y normaliza entradas', function () {
    $json = json_encode(['urls' => [
        ['loc' => 'https://ejemplo.test/a', 'lastmod' => '2026-01-10', 'changefreq' => 'weekly', 'priority' => 0.8, 'notify_indexnow' => true],
        ['loc' => 'no-es-url'],
        ['sin' => 'loc'],
        ['loc' => 'https://ejemplo.test/b', 'changefreq' => 'cada-rato', 'priority' => 7, 'lastmod' => 'ayer']
    ]]);

    $path = ssen_temp_file($json, '.json');
    try {
        $result = loadUrlsFromJSON($path);
        assertTrue($result['success']);
        assertSame(2, $result['count']);
        assertSame(0.8, $result['urls'][0]['priority']);
        assertSame('weekly', $result['urls'][0]['changefreq']);
        assertTrue(count($result['warnings']) >= 4, 'se esperaban avisos por las entradas no válidas');
    } finally {
        @unlink($path);
    }
});

test('loadUrlsFromJSON exige el campo urls', function () {
    $path = ssen_temp_file('{"otra": []}', '.json');
    try {
        $result = loadUrlsFromJSON($path);
        assertFalse($result['success']);
    } finally {
        @unlink($path);
    }
});

test('loadUrlsFromCSV acepta la cabecera con loc y avisa de columnas desconocidas', function () {
    $csv = "\xEF\xBB\xBFloc,lastmod,prioridad\nhttps://ejemplo.test/a,2026-01-10,0.5\n";
    $path = ssen_temp_file($csv, '.csv');
    try {
        $result = loadUrlsFromCSV($path);
        assertTrue($result['success']);
        assertSame(1, $result['count']);
        assertTrue(count($result['warnings']) >= 1, 'se esperaba un aviso por columna desconocida');
    } finally {
        @unlink($path);
    }
});

test('loadUrlsFromCSV rechaza archivos sin columna loc', function () {
    $path = ssen_temp_file("url,lastmod\nhttps://ejemplo.test/a,2026-01-10\n", '.csv');
    try {
        $result = loadUrlsFromCSV($path);
        assertFalse($result['success']);
    } finally {
        @unlink($path);
    }
});

test('normalizeUrlEntry omite direcciones que no son http o https', function () {
    $warnings = [];
    $entry = normalizeUrlEntry(['loc' => 'ftp://ejemplo.test/a'], 1, $warnings);
    assertSame(null, $entry);
    assertSame(1, count($warnings));
});
