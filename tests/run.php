<?php
/**
 * Arnés de pruebas nativo de SSEN (sin dependencias externas).
 *
 * Uso: php tests/run.php
 * Código de salida: 0 si todo pasa, 1 si hay fallos.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("El arnés de pruebas solo se ejecuta por línea de comandos.\n");
}

require_once __DIR__ . '/bootstrap.php';

final class SsenSkip extends Exception
{
}

$GLOBALS['ssen_results'] = ['passed' => 0, 'failed' => 0, 'skipped' => 0, 'failures' => []];

/**
 * Ejecuta una prueba y registra el resultado.
 *
 * @param string $name Nombre de la prueba
 * @param callable $fn Cuerpo de la prueba
 * @return void
 */
function test($name, callable $fn)
{
    try {
        $fn();
        $GLOBALS['ssen_results']['passed']++;
        fwrite(STDOUT, "OK   $name\n");
    } catch (SsenSkip $e) {
        $GLOBALS['ssen_results']['skipped']++;
        fwrite(STDOUT, "OMIT $name: {$e->getMessage()}\n");
    } catch (Throwable $e) {
        $GLOBALS['ssen_results']['failed']++;
        $GLOBALS['ssen_results']['failures'][] = "$name: {$e->getMessage()}";
        fwrite(STDERR, "FALLO $name: {$e->getMessage()}\n");
    }
}

/**
 * Omite la prueba actual con un motivo.
 *
 * @param string $reason Motivo
 * @return void
 * @throws SsenSkip Siempre
 */
function skip($reason)
{
    throw new SsenSkip($reason);
}

function assertTrue($condition, $message = 'Se esperaba verdadero')
{
    if ($condition !== true) {
        throw new RuntimeException($message);
    }
}

function assertFalse($condition, $message = 'Se esperaba falso')
{
    if ($condition !== false) {
        throw new RuntimeException($message);
    }
}

function assertSame($expected, $actual, $message = '')
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message !== '' ? $message . ': ' : '')
            . 'se esperaba ' . var_export($expected, true)
            . ' y se obtuvo ' . var_export($actual, true));
    }
}

function assertContains($needle, $haystack, $message = '')
{
    if (is_string($haystack)) {
        if (strpos($haystack, $needle) === false) {
            throw new RuntimeException(($message !== '' ? $message . ': ' : '') . "no se encontró «$needle»");
        }
        return;
    }

    if (!in_array($needle, $haystack, true)) {
        throw new RuntimeException(($message !== '' ? $message . ': ' : '') . 'no se encontró el valor esperado');
    }
}

function assertThrows(callable $fn, $class, $message = '')
{
    try {
        $fn();
    } catch (Throwable $e) {
        if ($e instanceof $class) {
            return;
        }
        throw new RuntimeException(($message !== '' ? $message . ': ' : '')
            . 'se lanzó ' . get_class($e) . ' en lugar de ' . $class);
    }

    throw new RuntimeException(($message !== '' ? $message . ': ' : '') . "no se lanzó $class");
}

/**
 * Crea un archivo temporal con el contenido indicado.
 *
 * @param string $content Contenido
 * @param string $suffix Sufijo del archivo
 * @return string Ruta del archivo
 */
function ssen_temp_file($content, $suffix = '.tmp')
{
    $path = tempnam(sys_get_temp_dir(), 'ssen_') . $suffix;
    file_put_contents($path, $content);
    return $path;
}

// Ejecutar los casos de prueba
foreach (glob(__DIR__ . '/cases/*_test.php') ?: [] as $caseFile) {
    fwrite(STDOUT, "\n== " . basename($caseFile) . " ==\n");
    require $caseFile;
}

// Resumen final
$results = $GLOBALS['ssen_results'];
fwrite(STDOUT, sprintf(
    "\nResultado: %d correctas, %d fallidas, %d omitidas\n",
    $results['passed'],
    $results['failed'],
    $results['skipped']
));

if ($results['failed'] > 0) {
    fwrite(STDERR, "Fallos:\n");
    foreach ($results['failures'] as $failure) {
        fwrite(STDERR, " - $failure\n");
    }
    exit(1);
}

exit(0);
