# 🚀 Sistema de Mensajería en Tiempo Real - Guía de Instalación

## 📋 Requisitos Previos

- PHP 7.4+ con CodeIgniter 4
- Node.js 14+ con npm
- MySQL 5.7+ o MariaDB 10.3+
- Servidor web (Apache/Nginx)
- **Usuario autenticado en el sistema**

## 🔧 Instalación Paso a Paso

### 1. Configurar Base de Datos

**Opción A: Usando phpMyAdmin o cliente MySQL**
```sql
-- Ejecutar el contenido del archivo mensajeria_tables.sql
-- Copiar y pegar todo el contenido en phpMyAdmin
```

**Opción B: Usando línea de comandos**
```bash
# Conectar a MySQL
mysql -u root -p

# Seleccionar la base de datos
USE ishume;

# Ejecutar el script
source mensajeria_tables.sql;
```

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

## 🔐 Autenticación y Seguridad

### ✅ Sistema de Autenticación Integrado
- **Requiere login previo** - Solo usuarios autenticados pueden acceder
- **Sesiones PHP** - Usa el sistema de sesiones existente
- **Filtros de seguridad** - Todas las rutas protegidas con `AuthFilter`
- **Datos reales de usuario** - Obtiene información de la base de datos

### ✅ Verificaciones de Seguridad
- Verificación de sesión activa en cada request
- Validación de permisos de usuario
- Redirección automática al login si no está autenticado
- Tokens de autenticación para WebSocket

## 🎯 Funcionalidades Implementadas

### ✅ Mensajería en Tiempo Real
- **WebSockets** para comunicación instantánea
- **Socket.IO** para compatibilidad cross-browser
- **Reconexión automática** en caso de pérdida de conexión
- **Autenticación integrada** con el sistema existente

### ✅ Historial de Conversaciones
- **Persistencia** de mensajes en base de datos
- **Carga bajo demanda** de mensajes antiguos
- **Búsqueda** en conversaciones y mensajes
- **Conversaciones por usuario** - Solo ve sus propias conversaciones

### ✅ Estados de Usuario
- **Indicadores online/offline** en tiempo real
- **Contador** de usuarios conectados
- **Estado "escribiendo"** (preparado para implementar)
- **Datos reales de usuarios** del sistema

### ✅ Notificaciones
- **Notificaciones push** para mensajes nuevos
- **Sistema de alertas** visuales
- **Marcado de mensajes** como leídos
- **Notificaciones por usuario** - Solo para el destinatario

### ✅ Interfaz Moderna
- **Diseño responsive** tipo WhatsApp
- **Burbujas de mensaje** diferenciadas por usuario
- **Timestamps** formateados inteligentemente
- **Indicadores de conexión** en tiempo real
- **Datos del usuario** mostrados correctamente

## 🚀 Cómo Usar el Sistema

### 1. Acceso a la Mensajería
```
1. Iniciar sesión en el sistema (/login)
2. Acceder a /mensajeria
3. El sistema verificará automáticamente la autenticación
```

### 2. Funcionalidades Disponibles
- **Ver usuarios del sistema** - Lista de todos los usuarios registrados
- **Iniciar conversaciones** - Click en "Nuevo Mensaje"
- **Enviar mensajes** - Escribir y presionar Enter
- **Ver historial** - Las conversaciones se mantienen persistentes
- **Indicadores de estado** - Ver quién está online/offline

### 3. Flujo de Trabajo
```
Usuario A inicia sesión → Ve lista de usuarios → Selecciona Usuario B
→ Se crea conversación → Envían mensajes → Mensajes se guardan en BD
→ Usuario B ve notificación → Puede responder → Conversación continúa
```

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

2. **El sistema detecta automáticamente:**
   - Dominio de Railway
   - URLs de WebSocket
   - Configuración de CORS

### Para Desarrollo Local

1. **Usar XAMPP/WAMP:**
   ```bash
   # Iniciar Apache y MySQL
   # Ejecutar mensajeria_tables.sql en phpMyAdmin
   # Iniciar WebSocket: node socket-server.js
   ```

2. **Acceder a la aplicación:**
   ```
   http://localhost/app/login (primero login)
   http://localhost/app/mensajeria (después de login)
   ```

## 🧪 Pruebas

### 1. Probar Autenticación
```bash
# Sin login - debe redirigir
curl http://localhost/app/mensajeria
# Respuesta: Redirect to /login

# Con login - debe funcionar
# 1. Login en el navegador
# 2. Acceder a /mensajeria
```

### 2. Probar Conexión WebSocket
```javascript
// En la consola del navegador (después de login)
console.log('Estado conexión:', mensajeria.isConnected);
console.log('Usuario actual:', mensajeria.usuarioActual);
console.log('Usuarios online:', mensajeria.usuariosOnline.size);
```

### 3. Probar Envío de Mensajes
- Abrir dos pestañas del navegador con diferentes usuarios
- Enviar mensajes desde una pestaña
- Verificar que aparecen en tiempo real en la otra

## 🐛 Solución de Problemas

### Error: "Debes iniciar sesión para acceder"
- **Causa:** Usuario no autenticado
- **Solución:** Hacer login primero en `/login`

### Error: "WebSocket connection failed"
- **Causa:** Servidor WebSocket no ejecutándose
- **Solución:** 
  ```bash
  node socket-server.js
  # Verificar que el puerto 3000 esté disponible
  ```

### Error: "Database connection failed"
- **Causa:** MySQL no ejecutándose o credenciales incorrectas
- **Solución:** 
  - Verificar que MySQL esté ejecutándose
  - Comprobar credenciales en `.env`
  - Ejecutar `mensajeria_tables.sql`

### Los mensajes no se muestran
- **Causa:** JavaScript no se carga o errores en consola
- **Solución:**
  - Verificar consola del navegador por errores
  - Comprobar que las rutas de API funcionan
  - Verificar que el usuario esté autenticado

## 📱 Características Adicionales

### Implementadas
- ✅ Chat en tiempo real con usuarios autenticados
- ✅ Historial persistente por usuario
- ✅ Estados de usuario en tiempo real
- ✅ Notificaciones por usuario
- ✅ Interfaz responsive
- ✅ Seguridad integrada

### Preparadas para Implementar
- 🔄 Indicadores "escribiendo"
- 📎 Envío de archivos
- 🖼️ Envío de imágenes
- 🔊 Mensajes de voz
- 🔍 Búsqueda avanzada
- 📱 Notificaciones push del navegador
- 👥 Grupos de chat

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
1. Verificar que el usuario esté autenticado
2. Revisar logs del servidor WebSocket
3. Verificar consola del navegador
4. Comprobar configuración de base de datos
5. Ejecutar `mensajeria_tables.sql` para verificar tablas

---

**¡El sistema está listo para usar con usuarios autenticados! 🎉**

**Flujo de uso:**
1. Login en `/login`
2. Acceder a `/mensajeria`
3. Seleccionar usuario para chatear
4. ¡Disfrutar del chat en tiempo real!
