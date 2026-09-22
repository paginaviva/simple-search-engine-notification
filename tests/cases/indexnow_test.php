<?php
/**
 * Pruebas de la autenticación de IndexNow.
 */

test('validateIndexNowKey admite el formato del protocolo', function () {
    assertTrue(validateIndexNowKey('abcdef0123456789abcdef0123456789'));
    assertTrue(validateIndexNowKey('clave-con-guion'));
    assertFalse(validateIndexNowKey('corta'));
    assertFalse(validateIndexNowKey(str_repeat('a', 129)));
    assertFalse(validateIndexNowKey('clave con espacios'));
});

test('getIndexNowCredentials lee la clave y deriva la ubicación pública', function () {
    $key = 'abcdef0123456789abcdef0123456789';
    $path = ssen_temp_file($key . "\n", '.txt');
    try {
        $credentials = getIndexNowCredentials($path);
        assertSame($key, $credentials['key']);
        assertContains('/' . $key . '.txt', $credentials['keyLocation']);
        assertSame(INDEXNOW_HOST, $credentials['host']);
    } finally {
        @unlink($path);
    }
});

test('getIndexNowCredentials rechaza claves con formato inválido', function () {
    $path = ssen_temp_file('clave inválida', '.txt');
    try {
        assertThrows(function () use ($path) {
            getIndexNowCredentials($path);
        }, RuntimeException::class);
    } finally {
        @unlink($path);
    }
});

test('getIndexNowCredentials rechaza archivos vacíos', function () {
    $path = ssen_temp_file('', '.txt');
    try {
        assertThrows(function () use ($path) {
            getIndexNowCredentials($path);
        }, RuntimeException::class);
    } finally {
        @unlink($path);
    }
});
