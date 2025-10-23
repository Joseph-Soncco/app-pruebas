#!/bin/bash

# Script de inicio para Railway
# Reemplaza el puerto en nginx.conf con la variable PORT de Railway

echo "Starting Railway PHP deployment..."

# Crear configuración de nginx con el puerto correcto
echo "Configuring Nginx for port $PORT"
sed "s/listen 8080/listen $PORT/g" /app/nginx.conf > /tmp/nginx.conf

# Crear directorio para PHP-FPM
mkdir -p /run/php-fpm

# Iniciar PHP-FPM en segundo plano
echo "Starting PHP-FPM..."
php-fpm -D -c /app/php-fpm.conf

# Esperar un momento para que PHP-FPM se inicie
sleep 2

# Iniciar Nginx en primer plano (daemon off)
echo "Starting Nginx on port $PORT..."
nginx -c /tmp/nginx.conf -g 'daemon off;'
