<?php
/**
 * Pruebas de integración HTTP con servidor local.
 *
 * Requieren la variable de entorno SSEN_TEST_BASE_URL apuntando a un servidor
 * con tests/fixtures/www como raíz. Defina SSEN_TEST_BASE_URL para ejecutarla.
 */

test('isUrlGone distingue una página ausente de una viva', function () {
    $base = getenv('SSEN_TEST_BASE_URL');
    if ($base === false || $base === '') {
        skip('SSEN_TEST_BASE_URL no está definido');
    }

    assertTrue(isUrlGone(rtrim($base, '/') . '/gone.php'), 'la página ausente debe devolver 404');
    assertFalse(isUrlGone(rtrim($base, '/') . '/alive.php'), 'la página viva no debe considerarse ausente');
});
