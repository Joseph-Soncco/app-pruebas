# Railway PHP Deployment Guide

## Configuración para Railway

Este proyecto está configurado para desplegarse en Railway como una aplicación PHP con CodeIgniter 4.

### Archivos de configuración:
- `nixpacks.toml` - Configuración de construcción para Railway
- `railway.json` - Configuración de despliegue
- `.railwayignore` - Archivos a excluir del despliegue

### Variables de entorno requeridas en Railway:

```
CI_ENVIRONMENT=production
DB_HOST=${{MYSQL_HOST}}
DB_USER=${{MYSQL_USER}}
DB_PASSWORD=${{MYSQL_PASSWORD}}
DB_NAME=${{MYSQL_DATABASE}}
DB_PORT=3306
JWT_SECRET=tu_clave_secreta_muy_larga_y_segura
```

### Comandos de construcción:
1. Instala PHP 8.2 y curl
2. Descarga e instala Composer
3. Ejecuta `composer install --no-dev --optimize-autoloader`
4. Inicia la aplicación con `php spark serve`

### Notas:
- La aplicación usa el puerto dinámico de Railway ($PORT)
- Se excluyen archivos Node.js para evitar conflictos
- La base de datos debe ser MySQL de Railway
