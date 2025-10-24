<?php
// Script para verificar configuración de CodeIgniter
header('Content-Type: text/plain');

echo "=== CONFIGURACIÓN DE CODEIGNITER ===\n\n";

// Cargar CodeIgniter
require_once __DIR__ . '/../vendor/autoload.php';

try {
    // Crear instancia de la aplicación
    $app = \CodeIgniter\Config\Services::codeigniter();
    
    echo "✅ CodeIgniter cargado correctamente\n\n";
    
    // Verificar configuración de base de datos
    $dbConfig = new \Config\Database();
    
    echo "Configuración de base de datos:\n";
    echo "Host: " . $dbConfig->default['hostname'] . "\n";
    echo "Usuario: " . $dbConfig->default['username'] . "\n";
    echo "Base de datos: " . $dbConfig->default['database'] . "\n";
    echo "Puerto: " . $dbConfig->default['port'] . "\n";
    echo "Driver: " . $dbConfig->default['DBDriver'] . "\n\n";
    
    // Intentar conexión usando CodeIgniter
    $db = \Config\Database::connect();
    echo "✅ Conexión exitosa usando CodeIgniter\n\n";
    
    // Verificar tablas
    $tables = $db->listTables();
    echo "Tablas encontradas (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
