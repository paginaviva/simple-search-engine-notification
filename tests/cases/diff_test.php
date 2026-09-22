<?php
/**
 * Pruebas del módulo de diferencias de sitemap.
 */

test('readSitemap informa de archivo ausente', function () {
    $result = readSitemap(sys_get_temp_dir() . '/ssen_no_existe_' . uniqid() . '.xml');
    assertSame('missing', $result['status']);
    assertSame([], $result['urls']);
});

test('readSitemap detecta XML corrupto', function () {
    $path = ssen_temp_file('<urlset><url><loc>sin cerrar', '.xml');
    try {
        $result = readSitemap($path);
        assertSame('corrupt', $result['status']);
    } finally {
        @unlink($path);
    }
});

test('readSitemap lee un sitemap correcto', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        . '<url><loc>https://ejemplo.test/a</loc><lastmod>2026-01-10</lastmod></url>'
        . '<url><loc>https://ejemplo.test/b</loc></url>'
        . '</urlset>';

    $path = ssen_temp_file($xml, '.xml');
    try {
        $result = readSitemap($path);
        assertSame('ok', $result['status']);
        assertSame('2026-01-10', $result['urls']['https://ejemplo.test/a']);
        assertSame('', $result['urls']['https://ejemplo.test/b']);
    } finally {
        @unlink($path);
    }
});

test('classifySitemapChanges clasifica nuevas, actualizadas y eliminadas', function () {
    $old = [
        'https://ejemplo.test/a' => '2026-01-01',
        'https://ejemplo.test/b' => '2026-01-02',
        'https://ejemplo.test/c' => '2026-01-03'
    ];
    $new = [
        'https://ejemplo.test/a' => '2026-01-01',
        'https://ejemplo.test/b' => '2026-01-09',
        'https://ejemplo.test/d' => '2026-01-10'
    ];

    $changes = classifySitemapChanges($old, $new);
    assertSame(['https://ejemplo.test/d'], $changes['new']);
    assertSame(['https://ejemplo.test/b'], $changes['updated']);
    assertSame(['https://ejemplo.test/c'], $changes['deleted']);
    assertSame(3, count($changes['all']));
});

test('detectChangedUrls mantiene la compatibilidad', function () {
    $old = ['https://ejemplo.test/a' => '2026-01-01'];
    $new = ['https://ejemplo.test/a' => '2026-01-01', 'https://ejemplo.test/b' => '2026-01-02'];
    assertSame(['https://ejemplo.test/b'], detectChangedUrls($old, $new));
});
