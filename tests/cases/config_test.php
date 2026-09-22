<?php
/**
 * Pruebas de la configuración central.
 */

test('ROOT_DIR apunta a la raíz del proyecto', function () {
    assertSame(realpath(SSEN_TEST_ROOT), realpath(ROOT_DIR));
});

test('las rutas derivadas cuelgan de los directorios correctos', function () {
    assertSame(realpath(ROOT_DIR . '/core_index'), realpath(CORE_DIR));
    assertSame(realpath(CORE_DIR . '/config'), realpath(CONFIG_DIR));
    assertSame(realpath(CORE_DIR . '/data'), realpath(DATA_DIR));
    assertSame(ROOT_DIR . '/sitemap.xml', SITEMAP_PATH);
    assertSame(PRIVATE_DIR . '/logs/indexing_api_sitemap.log', LOG_PATH);
});

test('validateConfiguration devuelve errores y advertencias', function () {
    $validation = validateConfiguration();
    assertTrue(isset($validation['valid']) && is_bool($validation['valid']));
    assertTrue(is_array($validation['errors']));
    assertTrue(is_array($validation['warnings']));
});

test('validateConfiguration acepta la configuración con la clave presente', function () {
    if (!file_exists(INDEXNOW_KEY_FILE)) {
        skip('no hay archivo de clave en este entorno');
    }
    $validation = validateConfiguration();
    assertSame([], $validation['errors'], 'no se esperaban errores de configuración');
});

test('la lista blanca de frecuencias coincide con el protocolo', function () {
    assertSame(['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'], getAllowedChangeFrequencies());
});
