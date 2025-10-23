<?php
/**
 * Router for PHP built-in server
 * This file handles routing for CodeIgniter 4 when using PHP built-in server
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the URI is a file that exists, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything through index.php
require_once __DIR__ . '/index.php';
