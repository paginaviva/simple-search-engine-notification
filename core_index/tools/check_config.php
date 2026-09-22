<?php
/**
 * Herramienta: comprueba la configuración y el entorno de SSEN.
 *
 * Uso: php core_index/tools/check_config.php
 *
 * Códigos de salida: 0 correcto, 2 errores de configuración o de extensiones.
 */

require_once __DIR__ . '/../config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Esta herramienta solo se ejecuta por línea de comandos.\n");
}

$exitCode = 0;

// 1. Validación de la configuración
$validation = validateConfiguration();
foreach ($validation['errors'] as $error) {
    fwrite(STDERR, 'ERROR: ' . $error . "\n");
    $exitCode = 2;
}
foreach ($validation['warnings'] as $warning) {
    fwrite(STDOUT, 'AVISO: ' . $warning . "\n");
}

// 2. Extensiones requeridas
foreach (['curl', 'json', 'openssl', 'simplexml'] as $extension) {
    if (extension_loaded($extension)) {
        fwrite(STDOUT, "Extensión presente: $extension\n");
    } else {
        fwrite(STDERR, "ERROR: falta la extensión $extension\n");
        $exitCode = 2;
    }
}

// 3. Entorno y rutas
fwrite(STDOUT, sprintf("PHP %s | SAPI %s\n", PHP_VERSION, PHP_SAPI));

$paths = [
    'ROOT_DIR' => ROOT_DIR,
    'CORE_DIR' => CORE_DIR,
    'CONFIG_DIR' => CONFIG_DIR,
    'DATA_DIR' => DATA_DIR,
    'LOG_DIR' => LOG_DIR,
    'SITEMAP_PATH' => SITEMAP_PATH,
    'URLS_JSON_PATH' => URLS_JSON_PATH,
    'URLS_CSV_PATH' => URLS_CSV_PATH,
    'INDEXNOW_KEY_FILE' => INDEXNOW_KEY_FILE,
    'GOOGLE_CREDENTIALS_PATH' => GOOGLE_CREDENTIALS_PATH
];
foreach ($paths as $name => $path) {
    fwrite(STDOUT, sprintf("%s: %s%s\n", $name, $path, file_exists($path) ? '' : ' (no existe)'));
}

// 4. Resultado
if ($exitCode === 0) {
    fwrite(STDOUT, "Configuración válida.\n");
} else {
    fwrite(STDERR, "Se encontraron errores de configuración.\n");
}

exit($exitCode);
