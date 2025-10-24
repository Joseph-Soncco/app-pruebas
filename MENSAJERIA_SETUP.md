# 🚀 Sistema de Mensajería en Tiempo Real - Guía de Instalación

## 📋 Requisitos Previos

- PHP 7.4+ con CodeIgniter 4
- Node.js 14+ con npm
- MySQL 5.7+ o MariaDB 10.3+
- Servidor web (Apache/Nginx)

## 🔧 Instalación Paso a Paso

### 1. Configurar Base de Datos

```bash
# Ejecutar el script de configuración de la base de datos
php setup-mensajeria.php
```

Este script creará todas las tablas necesarias:
- `conversaciones` - Almacena las conversaciones entre usuarios
- `mensajes_chat` - Almacena todos los mensajes
- `mensajes_leidos_chat` - Control de mensajes leídos
- `usuarios_conectados` - Estado de conexión de usuarios
- `usuarios_escribiendo` - Indicadores de "escribiendo"
- `notificaciones_chat` - Sistema de notificaciones

### 2. Instalar Dependencias Node.js

```bash
# Instalar dependencias del servidor WebSocket
npm install express socket.io mysql2 jsonwebtoken cors
```

### 3. Iniciar Servidor WebSocket

```bash
# Iniciar el servidor WebSocket en segundo plano
node socket-server.js &

# O usar PM2 para producción
npm install -g pm2
pm2 start socket-server.js --name "mensajeria-ws"
```

### 4. Configurar Variables de Entorno

Crear archivo `.env` con:

```env
# Base de datos
MYSQL_HOST=localhost
MYSQL_USER=root
MYSQL_PASSWORD=tu_password
MYSQL_DATABASE=ishume

# WebSocket
PORT=3000
JWT_SECRET=tu_clave_secreta_aqui

# Railway (si usas Railway)
RAILWAY_PUBLIC_DOMAIN=tu-dominio.up.railway.app
```

## 🎯 Funcionalidades Implementadas

### ✅ Mensajería en Tiempo Real
- **WebSockets** para comunicación instantánea
- **Socket.IO** para compatibilidad cross-browser
- **Reconexión automática** en caso de pérdida de conexión

### ✅ Historial de Conversaciones
- **Persistencia** de mensajes en base de datos
- **Carga bajo demanda** de mensajes antiguos
- **Búsqueda** en conversaciones y mensajes

### ✅ Estados de Usuario
- **Indicadores online/offline** en tiempo real
- **Contador** de usuarios conectados
- **Estado "escribiendo"** (preparado para implementar)

### ✅ Notificaciones
- **Notificaciones push** para mensajes nuevos
- **Sistema de alertas** visuales
- **Marcado de mensajes** como leídos

### ✅ Interfaz Moderna
- **Diseño responsive** tipo WhatsApp
- **Burbujas de mensaje** diferenciadas por usuario
- **Timestamps** formateados inteligentemente
- **Indicadores de conexión** en tiempo real

## 🔧 Configuración Avanzada

### Para Producción (Railway)

1. **Configurar variables de entorno en Railway:**
   ```env
   MYSQL_HOST=tu-host-mysql
   MYSQL_USER=tu-usuario
   MYSQL_PASSWORD=tu-password
   MYSQL_DATABASE=tu-base-datos
   PORT=3000
   ```

2. **Actualizar URLs en el código:**
   - El sistema detecta automáticamente el dominio de Railway
   - Las URLs de WebSocket se generan dinámicamente

### Para Desarrollo Local

1. **Usar XAMPP/WAMP:**
   ```bash
   # Iniciar Apache y MySQL
   # Ejecutar setup-mensajeria.php
   # Iniciar WebSocket: node socket-server.js
   ```

2. **Acceder a la aplicación:**
   ```
   http://localhost/app/mensajeria
   ```

## 🧪 Pruebas

### 1. Probar Conexión WebSocket
```javascript
// En la consola del navegador
console.log('Estado conexión:', mensajeria.isConnected);
console.log('Usuarios online:', mensajeria.usuariosOnline.size);
```

### 2. Probar Envío de Mensajes
- Abrir dos pestañas del navegador
- Enviar mensajes desde una pestaña
- Verificar que aparecen en tiempo real en la otra

### 3. Probar Estados de Usuario
- Conectar/desconectar usuarios
- Verificar indicadores de estado
- Probar notificaciones

## 🐛 Solución de Problemas

### Error: "WebSocket connection failed"
- Verificar que el servidor WebSocket esté ejecutándose
- Comprobar que el puerto 3000 esté disponible
- Revisar configuración de CORS

### Error: "Database connection failed"
- Verificar credenciales de MySQL
- Comprobar que las tablas se crearon correctamente
- Ejecutar `php setup-mensajeria.php` nuevamente

### Los mensajes no se muestran
- Verificar que el JavaScript se carga correctamente
- Comprobar la consola del navegador por errores
- Verificar que las rutas de API funcionan

## 📱 Características Adicionales

### Implementadas
- ✅ Chat en tiempo real
- ✅ Historial persistente
- ✅ Estados de usuario
- ✅ Notificaciones
- ✅ Interfaz responsive

### Preparadas para Implementar
- 🔄 Indicadores "escribiendo"
- 📎 Envío de archivos
- 🖼️ Envío de imágenes
- 🔊 Mensajes de voz
- 🔍 Búsqueda avanzada
- 📱 Notificaciones push del navegador

## 🚀 Despliegue en Producción

### Railway
```bash
# Subir código a Railway
# Configurar variables de entorno
# Railway iniciará automáticamente ambos servidores
```

### VPS/Servidor Propio
```bash
# Usar PM2 para gestión de procesos
pm2 start socket-server.js --name "mensajeria-ws"
pm2 startup
pm2 save
```

## 📞 Soporte

Si encuentras problemas:
1. Revisar logs del servidor WebSocket
2. Verificar consola del navegador
3. Comprobar configuración de base de datos
4. Ejecutar `php setup-mensajeria.php` para verificar tablas

---

**¡El sistema está listo para usar! 🎉**

Accede a `/mensajeria` en tu aplicación para comenzar a chatear en tiempo real.
