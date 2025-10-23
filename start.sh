#!/bin/bash

# Script de inicio para Railway
# Reemplaza el puerto en nginx.conf con la variable PORT de Railway

# Crear configuración de nginx con el puerto correcto
sed "s/listen 8080/listen $PORT/g" /app/nginx.conf > /tmp/nginx.conf

# Crear directorio para PHP-FPM
mkdir -p /run/php-fpm

# Iniciar PHP-FPM
php-fpm -D -c /app/php-fpm.conf

# Iniciar Nginx con la configuración corregida
nginx -c /tmp/nginx.conf
