<?php
/**
 * Script simple para crear las tablas de mensajería
 * Ejecutar: php setup-mensajeria-simple.php
 */

// Configuración de base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ishume';

try {
    // Conectar a MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🚀 Configurando sistema de mensajería...\n\n";
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/app/Database/mensajeria_tables.sql';
    
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
            $pdo->exec($query);
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
        echo "   1. Instalar dependencias: npm install express socket.io mysql2 jsonwebtoken cors\n";
        echo "   2. Iniciar el servidor WebSocket: node socket-server.js\n";
        echo "   3. Acceder a la mensajería: /mensajeria\n";
        echo "   4. Probar el chat en tiempo real\n";
    } else {
        echo "\n⚠️  Se encontraron errores. Revisa los mensajes anteriores.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
    echo "\n💡 Verifica que:\n";
    echo "   - MySQL esté ejecutándose\n";
    echo "   - Las credenciales de base de datos sean correctas\n";
    echo "   - La base de datos '$database' exista\n";
    exit(1);
}
