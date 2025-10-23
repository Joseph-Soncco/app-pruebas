<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle healthcheck endpoint directly
if ($uri === '/health.php') {
    require_once __DIR__ . '/health.php';
    return true;
}

// If the URI is a file that exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything through index.php
require_once __DIR__ . '/index.php';
