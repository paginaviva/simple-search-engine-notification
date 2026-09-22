<?php
/**
 * Pruebas del generador de sitemap.
 *
 * Estas pruebas no escriben el sitemap real ni notifican a ninguna API:
 * solo ejercitan la construcción del XML y la validación de entrada.
 */

test('buildSitemapXML escapa los cuatro campos', function () {
    if (!class_exists('DOMDocument')) {
        skip('la extensión dom no está disponible');
    }

    $xml = buildSitemapXML([[
        'url' => 'https://ejemplo.test/a?x=1&y=2',
        'lastmod' => '2026-01-10',
        'changefreq' => 'weekly',
        'priority' => 0.8
    ]]);

    assertContains('&amp;', $xml, 'el ampersand debe ir escapado');
    assertContains('<priority>0.8</priority>', $xml);

    $document = new DOMDocument();
    assertTrue($document->loadXML($xml), 'el XML debe ser interpretable');
    $loc = $document->getElementsByTagName('loc')->item(0)->textContent;
    assertSame('https://ejemplo.test/a?x=1&y=2', $loc, 'el valor debe conservarse al interpretar');
});

test('el XML generado valida contra el esquema oficial', function () {
    if (!class_exists('DOMDocument')) {
        skip('la extensión dom no está disponible');
    }

    $xml = buildSitemapXML([[
        'url' => 'https://ejemplo.test/a',
        'lastmod' => '2026-01-10',
        'changefreq' => 'daily',
        'priority' => 1.0
    ]]);

    $document = new DOMDocument();
    assertTrue($document->loadXML($xml), 'el XML debe ser interpretable');
    $valid = @$document->schemaValidate(__DIR__ . '/../fixtures/sitemap.xsd');
    assertTrue($valid, 'el sitemap debe validar contra sitemap.xsd');
});

test('generateSitemap rechaza entradas sin dirección válida', function () {
    $result = generateSitemap([['loc' => 'no-es-url']]);
    assertFalse($result['success']);
});

test('generateSitemap rechaza un array vacío', function () {
    $result = generateSitemap([]);
    assertFalse($result['success']);
});

test('sitemapGenerationError devuelve la estructura esperada', function () {
    $result = sitemapGenerationError('fallo de prueba');
    assertFalse($result['success']);
    assertSame('fallo de prueba', $result['error']);
    assertTrue(isset($result['changed_urls']['all']));
});
