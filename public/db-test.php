<?php
// Script de diagnóstico para verificar conexión a base de datos
header('Content-Type: text/plain');

echo "=== DIAGNÓSTICO DE BASE DE DATOS ===\n\n";

// Mostrar variables de entorno
echo "Variables de entorno:\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NO CONFIGURADA') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NO CONFIGURADA') . "\n";
echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? 'CONFIGURADA' : 'NO CONFIGURADA') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NO CONFIGURADA') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NO CONFIGURADA') . "\n\n";

// Intentar conexión
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $user = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    $database = $_ENV['DB_NAME'] ?? 'ishume';
    $port = $_ENV['DB_PORT'] ?? 3306;
    
    echo "Intentando conectar a:\n";
    echo "Host: $host\n";
    echo "Puerto: $port\n";
    echo "Usuario: $user\n";
    echo "Base de datos: $database\n\n";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ CONEXIÓN EXITOSA!\n\n";
    
    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tablas encontradas (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
}
