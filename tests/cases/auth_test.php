<?php
/**
 * Pruebas de la autenticación básica de la interfaz web.
 */

test('verifyBasicAuth acepta las credenciales correctas', function () {
    $hash = password_hash('secreta', PASSWORD_BCRYPT);
    $path = ssen_temp_file("ssen:$hash\n", '.htpasswd');
    try {
        assertTrue(verifyBasicAuth('ssen', 'secreta', $path));
        assertFalse(verifyBasicAuth('ssen', 'incorrecta', $path));
        assertFalse(verifyBasicAuth('otro', 'secreta', $path));
    } finally {
        @unlink($path);
    }
});

test('verifyBasicAuth rechaza archivos ausentes o entradas inválidas', function () {
    assertFalse(verifyBasicAuth('ssen', 'secreta', sys_get_temp_dir() . '/ssen_no_existe_' . uniqid()));

    $path = ssen_temp_file("linea-sin-separador\n# comentario\n\n", '.htpasswd');
    try {
        assertFalse(verifyBasicAuth('ssen', 'secreta', $path));
    } finally {
        @unlink($path);
    }
});

test('verifyBasicAuth rechaza credenciales vacías', function () {
    $hash = password_hash('secreta', PASSWORD_BCRYPT);
    $path = ssen_temp_file("ssen:$hash\n", '.htpasswd');
    try {
        assertFalse(verifyBasicAuth('', '', $path));
        assertFalse(verifyBasicAuth(null, null, $path));
    } finally {
        @unlink($path);
    }
});
