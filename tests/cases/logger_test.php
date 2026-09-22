<?php
/**
 * Pruebas del registro centralizado.
 */

test('logIndexingAction escribe una única línea saneada', function () {
    $path = ssen_temp_file('', '.log');
    try {
        logIndexingAction($path, "https://ejemplo.test/a\nsegunda línea", 'UPDATED', 'INDEXNOW', 200, "mensaje\r\ncon salto");
        $content = file_get_contents($path);

        assertSame(1, substr_count($content, "\n"), 'debe haber una sola línea');
        assertContains('SUCCESS', $content);
        assertContains('https://ejemplo.test/a segunda línea', $content);
        assertContains('mensaje con salto', $content);
    } finally {
        @unlink($path);
    }
});

test('logIndexingAction marca los errores de conexión', function () {
    $path = ssen_temp_file('', '.log');
    try {
        logIndexingAction($path, 'https://ejemplo.test/a', 'UPDATED', 'INDEXNOW', null, 'sin conexión');
        $content = file_get_contents($path);
        assertContains('Status: ERROR', $content);
        assertContains('HTTP: N/A', $content);
    } finally {
        @unlink($path);
    }
});
