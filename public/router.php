<?php
// Simple router that handles requests properly
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle healthcheck endpoint directly
if ($uri === '/health.php' || $uri === '/health') {
    require_once __DIR__ . '/health.php';
    return true;
}

// Handle test endpoint
if ($uri === '/test.php' || $uri === '/test') {
    require_once __DIR__ . '/test.php';
    return true;
}

// Handle root path
if ($uri === '/') {
    require_once __DIR__ . '/index.php';
    return true;
}

// If the URI is a file that exists, serve it directly
if (file_exists(__DIR__ . $uri)) {
    return false;
}

// For any other request, try to serve through index.php
require_once __DIR__ . '/index.php';
