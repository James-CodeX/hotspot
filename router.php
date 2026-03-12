<?php
/**
 * PHP built-in server router for local development.
 * Usage: php -S localhost:8080 router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);

// Serve real static files explicitly (return false is unreliable with spaces in path)
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mime = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'eot'  => 'application/vnd.ms-fontobject',
        'json' => 'application/json',
        'html' => 'text/html',
    ][$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($filePath);
    return;
}

// Serve the install wizard directly — must NOT go through the main bootstrap
// (which requires a live DB connection).
if (preg_match('#^/install(/.*)?$#', $uri, $m)) {
    // No trailing slash → redirect so relative CSS/JS/image paths resolve correctly
    if (!isset($m[1]) || $m[1] === '') {
        header('Location: /install/');
        exit;
    }
    $installFile = __DIR__ . DIRECTORY_SEPARATOR . 'install' . str_replace('/', DIRECTORY_SEPARATOR, $m[1]);
    if (is_dir($installFile)) {
        $installFile .= DIRECTORY_SEPARATOR . 'index.php';
    }
    if (file_exists($installFile)) {
        chdir(__DIR__ . DIRECTORY_SEPARATOR . 'install');
        require $installFile;
        return;
    }
}

// Strip the base path so index.php gets a clean SCRIPT_NAME
$_SERVER['SCRIPT_NAME'] = '/index.php';

require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';
