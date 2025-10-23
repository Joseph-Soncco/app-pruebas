<?php
// Robust healthcheck endpoint for Railway
header('Content-Type: text/plain');
header('Cache-Control: no-cache');
http_response_code(200);
echo "OK\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'PHP') . "\n";