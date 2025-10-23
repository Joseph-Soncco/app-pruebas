<?php
// Router with error handling
try {
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

    // Handle diagnostic endpoint
    if ($uri === '/diagnostic.php' || $uri === '/diagnostic') {
        require_once __DIR__ . '/diagnostic.php';
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
    
} catch (Exception $e) {
    // Log the error
    error_log("Router error: " . $e->getMessage());
    
    // Return a simple error page
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "Internal Server Error\n";
    echo "Error: " . $e->getMessage() . "\n";
    return true;
}
