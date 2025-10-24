<?php
// Router robusto para Railway con manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurar timeout y memoria
set_time_limit(30);
ini_set('memory_limit', '128M');

// Función para manejar errores
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("Error: $errstr in $errfile on line $errline");
    http_response_code(500);
    echo "Error interno del servidor";
    exit;
}

set_error_handler('handleError');

// Función para manejar excepciones
function handleException($exception) {
    error_log("Exception: " . $exception->getMessage());
    http_response_code(500);
    echo "Error interno del servidor";
    exit;
}

set_exception_handler('handleException');

// Obtener la URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Headers de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Manejar endpoints específicos
if ($uri === '/health.php' || $uri === '/health') {
    header('Content-Type: text/plain');
    header('Cache-Control: no-cache');
    http_response_code(200);
    echo "OK\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'PHP') . "\n";
    return true;
}

if ($uri === '/test.php' || $uri === '/test') {
    header('Content-Type: text/plain');
    http_response_code(200);
    echo "Test endpoint is working!\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
    return true;
}

if ($uri === '/diagnostic.php' || $uri === '/diagnostic') {
    header('Content-Type: text/plain');
    http_response_code(200);
    echo "=== Railway PHP Diagnostic ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server') . "\n";
    echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
    echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
    echo "Current Working Directory: " . getcwd() . "\n";
    echo "\n=== Environment Variables ===\n";
    foreach ($_ENV as $key => $value) {
        echo "$key: $value\n";
    }
    return true;
}

// Si es un archivo estático que existe, servirlo directamente
if ($uri !== '/' && file_exists(__DIR__ . $uri) && is_file(__DIR__ . $uri)) {
    return false; // Dejar que el servidor PHP lo sirva
}

// Manejar rutas específicas de la aplicación
if (strpos($uri, '/mensajeria') === 0 || 
    strpos($uri, '/auth') === 0 || 
    strpos($uri, '/welcome') === 0 ||
    strpos($uri, '/dashboard') === 0 ||
    $uri === '/') {
    
    // Para rutas de la aplicación, usar CodeIgniter
    try {
        // Verificar que index.php existe
        if (!file_exists(__DIR__ . '/index.php')) {
            throw new Exception('index.php not found');
        }
        
        // Incluir CodeIgniter
        require_once __DIR__ . '/index.php';
        return true;
        
    } catch (Exception $e) {
        error_log("Router error: " . $e->getMessage());
        http_response_code(500);
        echo "Error interno del servidor";
        return true;
    }
}

// Para cualquier otra ruta, mostrar error 404
http_response_code(404);
echo "Página no encontrada";
return true;
?>