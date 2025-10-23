#!/bin/bash

# Script de inicio alternativo usando PHP built-in server
# Más simple y confiable para Railway

echo "Starting Railway PHP deployment with built-in server..."

# Crear directorio para logs
mkdir -p /app/writable/logs

# Cambiar al directorio público
cd /app/public

# Iniciar PHP built-in server en primer plano con router
echo "Starting PHP built-in server on port $PORT..."
php -S 0.0.0.0:$PORT router.php
