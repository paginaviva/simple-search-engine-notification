<?php
/**
 * Herramienta: genera y publica la clave de IndexNow.
 *
 * Uso: php core_index/tools/generate_indexnow_key.php [--force]
 *
 * Escribe la clave en core_index/config/indexnow_key.txt y el archivo de
 * verificación {clave}.txt en la raíz del proyecto (documento raíz del sitio).
 * Ambos deben desplegarse en producción; ninguno se versiona en el repositorio.
 */

require_once __DIR__ . '/../config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Esta herramienta solo se ejecuta por línea de comandos.\n");
}

$options = getopt('', ['force']);
if ($options === false) {
    $options = [];
}
$force = isset($options['force']);

$keyPath = INDEXNOW_KEY_FILE;

// 1. Si ya existe una clave válida y no se fuerza la rotación, no se toca
if (file_exists($keyPath) && !$force) {
    $existing = trim((string)@file_get_contents($keyPath));
    if (preg_match('/^[A-Za-z0-9-]{8,128}$/', $existing)) {
        fwrite(STDOUT, "Ya existe una clave válida. Use --force para rotarla.\n");
        fwrite(STDOUT, 'Archivo de verificación esperado: ' . ROOT_DIR . '/' . $existing . ".txt\n");
        exit(0);
    }
}

// 2. Generar la clave (32 caracteres hexadecimales)
try {
    $key = bin2hex(random_bytes(16));
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo generar la clave: ' . $e->getMessage() . "\n");
    exit(1);
}

// 3. Escribir la clave
$configDir = dirname($keyPath);
if (!is_dir($configDir) && !@mkdir($configDir, 0755, true) && !is_dir($configDir)) {
    fwrite(STDERR, 'No se pudo crear el directorio: ' . $configDir . "\n");
    exit(1);
}
if (@file_put_contents($keyPath, $key . "\n", LOCK_EX) === false) {
    fwrite(STDERR, 'No se pudo escribir la clave en: ' . $keyPath . "\n");
    exit(1);
}

// 4. Escribir el archivo de verificación en la raíz del sitio
$verificationPath = ROOT_DIR . '/' . $key . '.txt';
if (@file_put_contents($verificationPath, $key . "\n", LOCK_EX) === false) {
    fwrite(STDERR, 'No se pudo escribir el archivo de verificación en: ' . $verificationPath . "\n");
    exit(1);
}

// 5. Retirar archivos de verificación anteriores (nombre y contenido iguales a la clave)
foreach (glob(ROOT_DIR . '/*.txt') ?: [] as $candidate) {
    $name = basename($candidate);
    if ($name === $key . '.txt') {
        continue;
    }
    $base = substr($name, 0, -4);
    if (!preg_match('/^[A-Za-z0-9-]{8,128}$/', $base)) {
        continue;
    }
    $content = trim((string)@file_get_contents($candidate));
    if ($content === $base) {
        @unlink($candidate);
        fwrite(STDOUT, "Eliminado archivo de verificación anterior: $name\n");
    }
}

// 6. Resumen
fwrite(STDOUT, "Clave de IndexNow generada correctamente.\n");
fwrite(STDOUT, 'Clave: ' . $key . "\n");
fwrite(STDOUT, 'Archivo de clave: ' . $keyPath . "\n");
fwrite(STDOUT, 'Archivo de verificación: ' . $verificationPath . "\n");
fwrite(STDOUT, "Pasos en producción:\n");
fwrite(STDOUT, "  1) Suba ambos archivos a las mismas rutas del servidor.\n");
fwrite(STDOUT, "  2) Compruebe que https://SU-DOMINIO/" . $key . ".txt devuelve la clave.\n");
fwrite(STDOUT, "  3) Ajuste INDEXNOW_HOST y BASE_URL en core_index/config/config.php.\n");

exit(0);
