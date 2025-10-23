<?php
/**
 * Healthcheck endpoint for Railway
 * This endpoint is specifically designed for Railway health checks
 */

// Allow requests from Railway healthcheck hostname
$allowed_hostnames = ['healthcheck.railway.app', 'localhost', '127.0.0.1'];
$request_hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

// Check if request is from allowed hostname
if (!in_array($request_hostname, $allowed_hostnames) && !str_contains($request_hostname, 'railway.app')) {
    // For development, allow any hostname
    if ($_SERVER['CI_ENVIRONMENT'] !== 'production') {
        // Allow in development
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Hostname not allowed']);
        exit;
    }
}

// Simple health check - just return 200 OK
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'service' => 'CodeIgniter 4 App',
    'hostname' => $request_hostname
]);
