<?php
// Script para configurar la base de datos en Railway
// Este script se ejecuta una vez para crear las tablas

// Cargar variables de entorno
$dotenv = parse_ini_file('env.railway', true);
if (!$dotenv) {
    // Si no hay env.railway, usar variables del sistema
    $dotenv = $_ENV;
}

// Configuración de base de datos
$host = $dotenv['DB_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost';
$user = $dotenv['DB_USER'] ?? $_ENV['DB_USER'] ?? 'root';
$password = $dotenv['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? '';
$database = $dotenv['DB_NAME'] ?? $_ENV['DB_NAME'] ?? 'ishume';
$port = $dotenv['DB_PORT'] ?? $_ENV['DB_PORT'] ?? 3306;

echo "=== Configurando base de datos ===\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Database: $database\n";
echo "Port: $port\n\n";

try {
    // Conectar a MySQL
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado a MySQL exitosamente\n";
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
    echo "✅ Base de datos '$database' creada/verificada\n";
    
    // Usar la base de datos
    $pdo->exec("USE `$database`");
    
    // Leer y ejecutar database.sql
    $sqlFile = __DIR__ . '/app/Database/database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        // Dividir por punto y coma y ejecutar cada statement
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignorar errores de tablas que ya existen
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️  Error en statement: " . substr($statement, 0, 50) . "...\n";
                        echo "   Error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✅ Tablas principales creadas desde database.sql\n";
    }
    
    // Leer y ejecutar inventario.sql
    $inventarioFile = __DIR__ . '/app/Database/inventario.sql';
    if (file_exists($inventarioFile)) {
        $sql = file_get_contents($inventarioFile);
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(USE)/i', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️  Error en statement: " . substr($statement, 0, 50) . "...\n";
                        echo "   Error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✅ Tablas de inventario creadas desde inventario.sql\n";
    }
    
    // Leer y ejecutar mensajeria.sql
    $mensajeriaFile = __DIR__ . '/app/Database/mensajeria.sql';
    if (file_exists($mensajeriaFile)) {
        $sql = file_get_contents($mensajeriaFile);
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(USE)/i', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️  Error en statement: " . substr($statement, 0, 50) . "...\n";
                        echo "   Error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        echo "✅ Tablas de mensajería creadas desde mensajeria.sql\n";
    }
    
    echo "\n🎉 ¡Base de datos configurada exitosamente!\n";
    echo "Tu aplicación ahora puede conectarse a la base de datos.\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    exit(1);
}
?>
