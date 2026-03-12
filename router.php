<?php
/**
 * PHP built-in server router for local development.
 * Usage: php -S localhost:8080 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve real static files directly (css, js, images, fonts, etc.)
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Serve the install wizard directly — it has its own standalone scripts
// that must NOT go through the main bootstrap (which requires a live DB).
if (preg_match('#^/install(/.*)?$#', $uri, $m)) {
    $installFile = __DIR__ . '/install' . ($m[1] ?? '/index.php');
    if (is_dir($installFile)) {
        $installFile .= '/index.php';
    }
    if (file_exists($installFile)) {
        chdir(__DIR__ . '/install');
        require $installFile;
        return;
    }
}

// Strip the base path so index.php gets a clean SCRIPT_NAME
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . '/index.php';
