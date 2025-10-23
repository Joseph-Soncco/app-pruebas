<?php
// Simple test endpoint
header('Content-Type: text/plain');
echo "Server is working!\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
echo "Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";
