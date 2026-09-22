<?php
/**
 * Arranque del arnés de pruebas de SSEN.
 *
 * Carga la configuración y los módulos del núcleo.
 * Ejecución: php tests/run.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('SSEN_TEST_ROOT', dirname(__DIR__));

require_once SSEN_TEST_ROOT . '/core_index/config/config.php';
require_once SSEN_TEST_ROOT . '/core_index/auth_guard.php';
require_once SSEN_TEST_ROOT . '/core_index/logger.php';
require_once SSEN_TEST_ROOT . '/core_index/sitemap_diff.php';
require_once SSEN_TEST_ROOT . '/core_index/urls_loader.php';
require_once SSEN_TEST_ROOT . '/core_index/indexnow_auth.php';
require_once SSEN_TEST_ROOT . '/core_index/sitemap_generator.php';
