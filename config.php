<?php

// Load .env file for local dev (PHP built-in server / no system env vars set)
if (getenv('DB_HOST') === false && file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        putenv(trim($k) . '=' . trim($v));
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ||
             (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
define('APP_URL', $protocol . $host . $baseDir);

$_app_stage = 'Live'; # Do not change this

// Database connection — env vars take precedence (Docker / cloud deployments)
$db_host    = getenv('DB_HOST') ?: 'localhost';
$db_port    = getenv('DB_PORT') ?: '';       // e.g. 26019 for Aiven, blank = default 3306
$db_user    = getenv('DB_USER') ?: 'root';
$db_pass    = getenv('DB_PASS') ?: '';
$db_name    = getenv('DB_NAME') ?: 'phpnuxbill';

// SSL/TLS — required for cloud databases (Aiven, PlanetScale, etc.)
$db_ssl     = filter_var(getenv('DB_SSL') ?: false, FILTER_VALIDATE_BOOLEAN);  // true = require SSL
$db_ssl_ca  = getenv('DB_SSL_CA') ?: '';     // path to CA cert for full verification (optional)

error_reporting(E_ERROR);
if ($_app_stage !== 'Live') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}
