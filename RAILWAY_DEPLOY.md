# 🚀 ISHUME Chat en Tiempo Real - Railway Deploy

## 📋 Instrucciones de Despliegue en Railway

### **Paso 1: Preparar GitHub**
1. Sube tu proyecto a GitHub
2. Asegúrate de incluir todos los archivos:
   - `socket-server.js`
   - `package.json`
   - `Procfile`
   - `app/Database/mensajeria.sql`

### **Paso 2: Configurar Railway**
1. Ve a [railway.app](https://railway.app)
2. Inicia sesión con GitHub
3. Clic en "New Project"
4. Selecciona "Deploy from GitHub repo"
5. Elige tu repositorio

### **Paso 3: Configurar Base de Datos**
1. En Railway, ve a tu proyecto
2. Clic en "Add Service" → "Database" → "MySQL"
3. Railway creará automáticamente las variables:
   - `MYSQL_HOST`
   - `MYSQL_USER`
   - `MYSQL_PASSWORD`
   - `MYSQL_DATABASE`

### **Paso 4: Configurar Variables de Entorno**
En Railway, ve a "Variables" y agrega:
```
JWT_SECRET=tu_clave_super_secreta_de_produccion
NODE_ENV=production
```

### **Paso 5: Ejecutar SQL**
1. Ve a la pestaña "Data" en Railway
2. Ejecuta el archivo `app/Database/mensajeria.sql`
3. Esto creará todas las tablas necesarias

### **Paso 6: Obtener URL del WebSocket**
1. Railway te dará una URL como: `https://tu-proyecto.railway.app`
2. Anota esta URL

### **Paso 7: Actualizar CodeIgniter**
En `public/assets/js/mensajeria.js`, cambia:
```javascript
const socketUrl = 'https://tu-proyecto.railway.app';
```

### **Paso 8: Configurar CORS**
En `socket-server.js`, actualiza:
```javascript
app.use(cors({
    origin: ["http://localhost", "http://127.0.0.1", "http://tu-dominio.com"],
    credentials: true
}));
```

## 🎯 **URLs Finales:**
- **WebSocket**: `https://tu-proyecto.railway.app`
- **Tu sitio**: `http://tu-dominio.com/mensajeria`

## ✅ **Verificación:**
1. Ve a `https://tu-proyecto.railway.app/api/users-online`
2. Debería devolver: `{"success": true, "users": []}`

## 🔧 **Troubleshooting:**
- **Error de conexión**: Verifica las variables de entorno
- **CORS error**: Actualiza los dominios permitidos
- **DB error**: Verifica que el SQL se ejecutó correctamente

¡Listo! Tu chat en tiempo real estará funcionando en la nube! 🎉
