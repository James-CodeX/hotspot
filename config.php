<?php

if (!class_exists('Db')) {
    $dbLoader = __DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR . 'Db.php';
    if (file_exists($dbLoader)) {
        require_once $dbLoader;
    }
}

if (class_exists('Db')) {
    Db::loadDotEnv(__DIR__ . DIRECTORY_SEPARATOR . '.env');
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";

// Check if HTTP_HOST is set, otherwise use a default value or SERVER_NAME
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$defaultAppUrl = $protocol . $host . $baseDir;
$configuredAppUrl = class_exists('Db') ? Db::env('APP_URL', $defaultAppUrl) : $defaultAppUrl;
define('APP_URL', rtrim($configuredAppUrl, '/'));


$_app_stage = 'Live'; # Do not change this

$dbConfig = class_exists('Db')
    ? Db::buildConfigFromEnvironment('DB', [
        'host' => 'localhost',
        'port' => '',
        'user' => 'root',
        'pass' => '',
        'name' => 'phpnuxbill',
        'charset' => 'utf8mb4',
        'ssl_mode' => 'DISABLED',
        'ssl_ca' => '',
    ])
    : [
        'host' => 'localhost',
        'port' => '',
        'user' => 'root',
        'pass' => '',
        'name' => 'phpnuxbill',
        'charset' => 'utf8mb4',
        'ssl_mode' => 'DISABLED',
        'ssl_ca' => '',
        'ssl_verify_server_cert' => null,
    ];

$db_host = $dbConfig['host']; # Database Host
$db_port = $dbConfig['port']; # Database Port
$db_user = $dbConfig['user']; # Database Username
$db_pass = $dbConfig['pass']; # Database Password
$db_name = $dbConfig['name']; # Database Name
$db_charset = $dbConfig['charset'];
$db_ssl_mode = $dbConfig['ssl_mode'];
$db_ssl_ca = $dbConfig['ssl_ca'];
$db_ssl_verify_server_cert = $dbConfig['ssl_verify_server_cert'];
$db_password = $db_pass;

$radiusConfig = class_exists('Db') ? Db::buildConfigFromEnvironment('RADIUS', $dbConfig) : $dbConfig;
$radius_host = $radiusConfig['host'];
$radius_port = $radiusConfig['port'];
$radius_user = $radiusConfig['user'];
$radius_pass = $radiusConfig['pass'];
$radius_name = $radiusConfig['name'];
$radius_charset = $radiusConfig['charset'];
$radius_ssl_mode = $radiusConfig['ssl_mode'];
$radius_ssl_ca = $radiusConfig['ssl_ca'];
$radius_ssl_verify_server_cert = $radiusConfig['ssl_verify_server_cert'];
$radius_password = $radius_pass;




//error reporting
if ($_app_stage != 'Live') {
    error_reporting(E_ERROR);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ERROR);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
