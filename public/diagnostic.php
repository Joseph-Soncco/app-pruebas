<?php
// Diagnostic endpoint
header('Content-Type: text/plain');
echo "=== Railway PHP Diagnostic ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Path: " . (__FILE__) . "\n";
echo "Working Directory: " . getcwd() . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";

echo "\n=== Environment Variables ===\n";
echo "CI_ENVIRONMENT: " . ($_ENV['CI_ENVIRONMENT'] ?? 'Not set') . "\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'Not set') . "\n";
echo "PORT: " . ($_ENV['PORT'] ?? 'Not set') . "\n";

echo "\n=== File System ===\n";
echo "Public directory exists: " . (is_dir(__DIR__) ? 'Yes' : 'No') . "\n";
echo "Index.php exists: " . (file_exists(__DIR__ . '/index.php') ? 'Yes' : 'No') . "\n";
echo "App directory exists: " . (is_dir(__DIR__ . '/../app') ? 'Yes' : 'No') . "\n";

echo "\n=== CodeIgniter Check ===\n";
if (file_exists(__DIR__ . '/../app/Config/App.php')) {
    echo "CodeIgniter config exists: Yes\n";
} else {
    echo "CodeIgniter config exists: No\n";
}
