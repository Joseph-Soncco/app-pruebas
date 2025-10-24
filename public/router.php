<?php
// Router simplificado para Railway - Dejar que CodeIgniter maneje todo
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Solo manejar endpoints específicos del servidor
if ($uri === '/health.php' || $uri === '/health') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    http_response_code(200);
    echo "OK\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    return true;
}

if ($uri === '/test.php' || $uri === '/test') {
    header('Content-Type: text/plain');
    http_response_code(200);
    echo "Test endpoint is working!\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    return true;
}

if ($uri === '/diagnostic.php' || $uri === '/diagnostic') {
    header('Content-Type: text/plain');
    http_response_code(200);
    echo "=== Railway PHP Diagnostic ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
    echo "Current Working Directory: " . getcwd() . "\n";
    return true;
}

// Si es un archivo estático que existe, servirlo directamente
if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    return false; // Dejar que el servidor PHP lo sirva
}

// Para TODAS las demás rutas, usar CodeIgniter
try {
    // Verificar que index.php existe
    if (!file_exists(__DIR__ . '/index.php')) {
        throw new Exception('index.php not found');
    }
    
    // Incluir CodeIgniter - que maneje todas las rutas
    require_once __DIR__ . '/index.php';
    return true;
    
} catch (Exception $e) {
    error_log("Router error: " . $e->getMessage());
    http_response_code(500);
    echo "Error interno del servidor: " . $e->getMessage();
    return true;
}
?>