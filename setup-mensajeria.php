<?php
/**
 * Script para crear las tablas de mensajería en la base de datos
 * Ejecutar desde la línea de comandos: php setup-mensajeria.php
 */

require_once 'vendor/autoload.php';

use CodeIgniter\Config\Services;

// Configurar CodeIgniter
$app = \Config\Services::codeigniter();
$app->initialize();

try {
    $db = \Config\Database::connect();
    
    echo "🚀 Configurando sistema de mensajería...\n\n";
    
    // Leer el archivo SQL
    $sqlFile = APPPATH . 'Database/mensajeria_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en consultas individuales
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        if (empty($query) || strpos($query, '--') === 0) {
            continue;
        }
        
        try {
            $db->query($query);
            $successCount++;
            
            // Mostrar progreso
            if (strpos($query, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`(\w+)`/', $query, $matches);
                $tableName = $matches[1] ?? 'tabla';
                echo "✅ Tabla '$tableName' creada correctamente\n";
            } elseif (strpos($query, 'INSERT') !== false) {
                echo "✅ Datos de prueba insertados\n";
            } elseif (strpos($query, 'UPDATE') !== false) {
                echo "✅ Conversaciones actualizadas\n";
            } elseif (strpos($query, 'CREATE INDEX') !== false) {
                echo "✅ Índices creados\n";
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Error ejecutando consulta: " . $e->getMessage() . "\n";
            echo "   Consulta: " . substr($query, 0, 100) . "...\n";
        }
    }
    
    echo "\n📊 Resumen:\n";
    echo "   ✅ Consultas exitosas: $successCount\n";
    echo "   ❌ Consultas con error: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 ¡Sistema de mensajería configurado correctamente!\n";
        echo "\n📋 Próximos pasos:\n";
        echo "   1. Iniciar el servidor WebSocket: node socket-server.js\n";
        echo "   2. Acceder a la mensajería: /mensajeria\n";
        echo "   3. Probar el chat en tiempo real\n";
    } else {
        echo "\n⚠️  Se encontraron errores. Revisa los mensajes anteriores.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}
