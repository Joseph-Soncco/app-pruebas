#!/bin/bash
# Script de inicio robusto para Railway

echo "=== Iniciando aplicación PHP en Railway ==="
echo "Timestamp: $(date)"
echo "PHP Version: $(php -v | head -1)"
echo "Working Directory: $(pwd)"
echo "Port: $PORT"

# Verificar que el directorio public existe
if [ ! -d "public" ]; then
    echo "ERROR: Directorio public no encontrado"
    exit 1
fi

# Verificar que router.php existe
if [ ! -f "public/router.php" ]; then
    echo "ERROR: router.php no encontrado"
    exit 1
fi

# Verificar que index.php existe
if [ ! -f "public/index.php" ]; then
    echo "ERROR: index.php no encontrado"
    exit 1
fi

# Configurar variables de entorno
export PHP_CLI_SERVER_WORKERS=1
export PHP_CLI_SERVER_HOST=0.0.0.0
export PHP_CLI_SERVER_PORT=$PORT

echo "=== Configuración ==="
echo "Host: $PHP_CLI_SERVER_HOST"
echo "Port: $PHP_CLI_SERVER_PORT"
echo "Workers: $PHP_CLI_SERVER_WORKERS"

# Cambiar al directorio public
cd public

echo "=== Iniciando servidor PHP ==="
echo "Comando: php -S $PHP_CLI_SERVER_HOST:$PHP_CLI_SERVER_PORT router.php"

# Iniciar el servidor PHP con configuración robusta
exec php -S $PHP_CLI_SERVER_HOST:$PHP_CLI_SERVER_PORT \
    -t . \
    -c /app/php-railway.ini \
    router.php
