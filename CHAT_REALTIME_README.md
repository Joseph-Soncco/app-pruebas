# 🚀 Sistema de Mensajería en Tiempo Real - ISHUME

Sistema de mensajería en tiempo real implementado con **Socket.IO** y **MySQL** para la aplicación ISHUME.

## 📋 Características

- ✅ **Chat en tiempo real** con WebSockets
- ✅ **Indicadores de estado** (online/offline/escribiendo)
- ✅ **Notificaciones** en tiempo real
- ✅ **Interfaz moderna** y responsive
- ✅ **Integración** con sistema de mensajería existente
- ✅ **Gestión de conversaciones** y usuarios
- ✅ **Configuración personalizable**

## 🛠️ Instalación

### 1. Instalar dependencias de Node.js

```bash
# Instalar dependencias
npm install

# O usar yarn
yarn install
```

### 2. Configurar base de datos

Ejecutar los siguientes archivos SQL en tu base de datos MySQL:

```sql
-- Estructura para mensajería en tiempo real
SOURCE app/Database/mensajeria.sql;
```

### 3. Configurar servidor WebSocket

Editar el archivo `socket-server.js` y ajustar la configuración de MySQL:

```javascript
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: 'tu_contraseña_mysql', // ← Cambiar aquí
    database: 'appishume',
    charset: 'utf8mb4'
};
```

### 4. Iniciar servidor WebSocket

```bash
# Modo desarrollo (con auto-reload)
npm run dev

# Modo producción
npm start
```

El servidor WebSocket estará disponible en: `http://localhost:3000`

### 5. Configurar CodeIgniter

Asegúrate de que las rutas estén configuradas en `app/Config/Routes.php`:

```php
// Las rutas de mensajería ya están agregadas automáticamente
$routes->get('mensajeria', 'ChatController::index');
// ... más rutas
```

## 🚀 Uso

### Acceder al chat

1. Inicia sesión en tu aplicación ISHUME
2. Navega a: `http://tu-dominio/mensajeria`
3. ¡Comienza a chatear!

### Funcionalidades disponibles

- **Nueva conversación**: Clic en el botón "+" para crear una nueva conversación
- **Enviar mensajes**: Escribe en el campo de texto y presiona Enter
- **Indicadores**: Ve cuando otros usuarios están escribiendo
- **Estado online**: Ve qué usuarios están conectados
- **Notificaciones**: Recibe notificaciones de nuevos mensajes
- **Configuración**: Personaliza sonidos y notificaciones

## 📁 Estructura de archivos

```
appishume/
├── socket-server.js              # Servidor WebSocket principal
├── package.json                  # Dependencias Node.js
├── app/
│   ├── Controllers/
│   │   └── ChatController.php   # Controlador de mensajería
│   ├── Models/
│   │   ├── MensajeChatModel.php
│   │   ├── ConversacionChatModel.php
│   │   └── UsuarioConectadoModel.php
│   ├── Views/
│   │   └── mensajeria/
│   │       └── mensajeria.php
│   └── Database/
│       └── mensajeria.sql
└── public/
    ├── assets/
    │   ├── css/
    │   │   └── mensajeria.css
    │   └── js/
    │       └── mensajeria.js
```

## 🔧 Configuración avanzada

### Variables de entorno

Crear archivo `.env` en la raíz del proyecto:

```env
# Puerto del servidor WebSocket
PORT=3000

# Clave secreta para JWT
JWT_SECRET=tu_clave_secreta_muy_segura

# Configuración de MySQL
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=tu_contraseña
DB_NAME=appishume
```

### Personalización

#### Cambiar puerto del servidor WebSocket

```javascript
// En socket-server.js
const PORT = process.env.PORT || 3000; // Cambiar 3000 por el puerto deseado
```

#### Modificar URL del cliente

```javascript
// En public/assets/js/mensajeria.js
const socketUrl = 'http://localhost:3000'; // Cambiar por tu dominio
```

## 🐛 Solución de problemas

### Error de conexión a MySQL

```
❌ Error conectando a MySQL: Access denied
```

**Solución**: Verificar credenciales en `socket-server.js`

### Error de CORS

```
❌ CORS error: Access to fetch at 'http://localhost:3000' from origin 'http://localhost' has been blocked
```

**Solución**: Agregar tu dominio a la configuración CORS en `socket-server.js`:

```javascript
app.use(cors({
    origin: ["http://localhost", "http://127.0.0.1", "http://tu-dominio.com"],
    credentials: true
}));
```

### El chat no carga

**Verificar**:
1. ✅ Servidor WebSocket está ejecutándose
2. ✅ Base de datos está configurada correctamente
3. ✅ Rutas están configuradas en CodeIgniter
4. ✅ Usuario está autenticado

### Mensajes no se envían

**Verificar**:
1. ✅ Conexión WebSocket establecida
2. ✅ Token de autenticación válido
3. ✅ Usuario tiene permisos para la conversación

## 📊 Monitoreo

### Ver usuarios conectados

```bash
# API endpoint
curl http://localhost:3000/api/users-online
```

### Ver mensajes de una conversación

```bash
# API endpoint
curl http://localhost:3000/api/conversation/1/messages
```

## 🔒 Seguridad

- ✅ **Autenticación JWT** para WebSockets
- ✅ **Validación de permisos** en conversaciones
- ✅ **Sanitización** de mensajes
- ✅ **Rate limiting** (implementar según necesidades)
- ✅ **HTTPS** recomendado en producción

## 🚀 Despliegue en producción

### 1. Usar PM2 para gestión de procesos

```bash
# Instalar PM2
npm install -g pm2

# Iniciar servidor con PM2
pm2 start socket-server.js --name "chat-server"

# Configurar auto-start
pm2 startup
pm2 save
```

### 2. Configurar proxy reverso (Nginx)

```nginx
# En tu configuración de Nginx
location /socket.io/ {
    proxy_pass http://localhost:3000;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

### 3. Variables de entorno en producción

```env
NODE_ENV=production
PORT=3000
JWT_SECRET=clave_super_secreta_de_produccion
DB_HOST=localhost
DB_USER=usuario_mysql
DB_PASSWORD=contraseña_segura
DB_NAME=appishume_prod
```

## 📈 Próximas mejoras

- [ ] **Archivos adjuntos** en mensajes
- [ ] **Emojis** y reacciones
- [ ] **Chat grupal** con múltiples usuarios
- [ ] **Historial de mensajes** con paginación
- [ ] **Búsqueda** en mensajes
- [ ] **Mensajes editados** y eliminados
- [ ] **Notificaciones push** del navegador
- [ ] **Temas** personalizables

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

**¡Disfruta chateando en tiempo real! 🎉**
