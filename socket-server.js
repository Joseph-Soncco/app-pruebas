const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const mysql = require('mysql2/promise');
const jwt = require('jsonwebtoken');
const cors = require('cors');

// Configuración
const PORT = process.env.PORT || 3000;
const JWT_SECRET = process.env.JWT_SECRET || 'tu_clave_secreta_aqui';

// Configuración de base de datos MySQL
const dbConfig = {
    host: process.env.MYSQL_HOST || 'localhost',
    user: process.env.MYSQL_USER || 'root',
    password: process.env.MYSQL_PASSWORD || '',
    database: process.env.MYSQL_DATABASE || 'ishume',
    charset: 'utf8mb4'
};

// Crear aplicación Express
const app = express();
const server = http.createServer(app);

// Configurar CORS
app.use(cors({
    origin: [
        "http://localhost", 
        "http://127.0.0.1", 
        "http://appishume.test",
        process.env.RAILWAY_PUBLIC_DOMAIN ? `https://${process.env.RAILWAY_PUBLIC_DOMAIN}` : "*"
    ],
    credentials: true
}));

// Configurar Socket.IO
const io = socketIo(server, {
    cors: {
        origin: [
            "http://localhost", 
            "http://127.0.0.1", 
            "http://appishume.test",
            process.env.RAILWAY_PUBLIC_DOMAIN ? `https://${process.env.RAILWAY_PUBLIC_DOMAIN}` : "*"
        ],
        methods: ["GET", "POST"],
        credentials: true
    }
});

// Pool de conexiones MySQL
let dbPool;

// Inicializar conexión a base de datos
async function initDatabase() {
    try {
        dbPool = mysql.createPool(dbConfig);
        console.log('✅ Conectado a MySQL');
        
        // Probar conexión
        const connection = await dbPool.getConnection();
        await connection.ping();
        connection.release();
        
    } catch (error) {
        console.error('❌ Error conectando a MySQL:', error);
        process.exit(1);
    }
}

// Middleware para autenticación
async function authenticateSocket(socket, next) {
    try {
        const token = socket.handshake.auth.token || socket.handshake.headers.authorization;
        
        if (!token) {
            return next(new Error('Token de autenticación requerido'));
        }
        
        // Decodificar JWT (simplificado - en producción usar JWT real)
        const decoded = jwt.verify(token.replace('Bearer ', ''), JWT_SECRET);
        
        // Verificar usuario en base de datos
        const [users] = await dbPool.execute(
            'SELECT u.idusuario, u.nombreusuario, p.nombres, p.apellidos FROM usuarios u JOIN personas p ON u.idpersona = p.idpersona WHERE u.idusuario = ? AND u.estado = 1',
            [decoded.userId]
        );
        
        if (users.length === 0) {
            return next(new Error('Usuario no válido'));
        }
        
        socket.userId = decoded.userId;
        socket.userData = users[0];
        
        next();
    } catch (error) {
        console.error('Error de autenticación:', error);
        next(new Error('Token inválido'));
    }
}

// Aplicar middleware de autenticación
io.use(authenticateSocket);

// Almacenar usuarios conectados
const connectedUsers = new Map();

// Eventos de Socket.IO
io.on('connection', async (socket) => {
    console.log(`🔗 Usuario conectado: ${socket.userData.nombres} ${socket.userData.apellidos} (ID: ${socket.userId})`);
    
    // Registrar usuario como conectado
    connectedUsers.set(socket.id, {
        userId: socket.userId,
        userData: socket.userData,
        socketId: socket.id,
        connectedAt: new Date()
    });
    
    try {
        // Guardar conexión en base de datos
        await dbPool.execute(
            'INSERT INTO usuarios_conectados (usuario_id, socket_id, estado, dispositivo, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE socket_id = VALUES(socket_id), ultima_conexion = NOW(), estado = VALUES(estado)',
            [
                socket.userId,
                socket.id,
                'online',
                'web',
                socket.handshake.address,
                socket.handshake.headers['user-agent']
            ]
        );
        
        // Notificar a otros usuarios que este usuario está online
        socket.broadcast.emit('user-online', {
            userId: socket.userId,
            userData: socket.userData
        });
        
        // Enviar lista de usuarios online al usuario conectado
        const onlineUsers = Array.from(connectedUsers.values()).map(user => ({
            userId: user.userId,
            userData: user.userData
        }));
        socket.emit('users-online', onlineUsers);
        
    } catch (error) {
        console.error('Error guardando conexión:', error);
    }
    
    // Unirse a conversación
    socket.on('join-conversation', async (conversationId) => {
        try {
            // Verificar que el usuario tiene acceso a esta conversación
            const [conversations] = await dbPool.execute(
                'SELECT * FROM conversaciones WHERE id = ? AND (usuario1_id = ? OR usuario2_id = ?)',
                [conversationId, socket.userId, socket.userId]
            );
            
            if (conversations.length === 0) {
                socket.emit('error', { message: 'No tienes acceso a esta conversación' });
                return;
            }
            
            socket.join(`conversation-${conversationId}`);
            console.log(`👥 Usuario ${socket.userId} se unió a conversación ${conversationId}`);
            
            // Marcar mensajes como leídos
            await dbPool.execute(
                'INSERT INTO mensajes_leidos_chat (mensaje_id, usuario_id) ' +
                'SELECT id, ? FROM mensajes_chat WHERE conversacion_id = ? AND usuario_id != ? ' +
                'ON DUPLICATE KEY UPDATE fecha_lectura = NOW()',
                [socket.userId, conversationId, socket.userId]
            );
            
            // Actualizar contador de mensajes no leídos
            await dbPool.execute(
                'UPDATE conversaciones SET mensajes_no_leidos_usuario1 = 0 WHERE id = ? AND usuario1_id = ?',
                [conversationId, socket.userId]
            );
            await dbPool.execute(
                'UPDATE conversaciones SET mensajes_no_leidos_usuario2 = 0 WHERE id = ? AND usuario2_id = ?',
                [conversationId, socket.userId]
            );
            
        } catch (error) {
            console.error('Error uniéndose a conversación:', error);
            socket.emit('error', { message: 'Error al unirse a la conversación' });
        }
    });
    
    // Salir de conversación
    socket.on('leave-conversation', (conversationId) => {
        socket.leave(`conversation-${conversationId}`);
        console.log(`👋 Usuario ${socket.userId} salió de conversación ${conversationId}`);
    });
    
    // Enviar mensaje
    socket.on('send-message', async (data) => {
        try {
            const { conversationId, message, tipo = 'texto' } = data;
            
            // Verificar acceso a la conversación
            const [conversations] = await dbPool.execute(
                'SELECT * FROM conversaciones WHERE id = ? AND (usuario1_id = ? OR usuario2_id = ?)',
                [conversationId, socket.userId, socket.userId]
            );
            
            if (conversations.length === 0) {
                socket.emit('error', { message: 'No tienes acceso a esta conversación' });
                return;
            }
            
            // Guardar mensaje en base de datos
            const [result] = await dbPool.execute(
                'INSERT INTO mensajes_chat (conversacion_id, usuario_id, mensaje, tipo) VALUES (?, ?, ?, ?)',
                [conversationId, socket.userId, message, tipo]
            );
            
            const messageId = result.insertId;
            
            // Obtener mensaje completo con información del usuario
            const [messages] = await dbPool.execute(
                'SELECT mc.*, CONCAT(p.nombres, " ", p.apellidos) as usuario_nombre, u.nombreusuario FROM mensajes_chat mc JOIN usuarios u ON mc.usuario_id = u.idusuario JOIN personas p ON u.idpersona = p.idpersona WHERE mc.id = ?',
                [messageId]
            );
            
            const messageData = {
                ...messages[0],
                timestamp: new Date().toISOString()
            };
            
            // Enviar mensaje a todos los usuarios en la conversación
            io.to(`conversation-${conversationId}`).emit('new-message', messageData);
            
            console.log(`💬 Mensaje enviado en conversación ${conversationId} por usuario ${socket.userId}`);
            
        } catch (error) {
            console.error('Error enviando mensaje:', error);
            socket.emit('error', { message: 'Error al enviar mensaje' });
        }
    });
    
    // Usuario escribiendo
    socket.on('typing-start', async (data) => {
        try {
            const { conversationId } = data;
            
            // Guardar estado de "escribiendo"
            await dbPool.execute(
                'INSERT INTO usuarios_escribiendo (conversacion_id, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE fecha_ultima_actividad = NOW()',
                [conversationId, socket.userId]
            );
            
            // Notificar a otros usuarios en la conversación
            socket.to(`conversation-${conversationId}`).emit('user-typing', {
                userId: socket.userId,
                userData: socket.userData,
                conversationId: conversationId
            });
            
        } catch (error) {
            console.error('Error en typing-start:', error);
        }
    });
    
    // Usuario dejó de escribir
    socket.on('typing-stop', async (data) => {
        try {
            const { conversationId } = data;
            
            // Remover estado de "escribiendo"
            await dbPool.execute(
                'DELETE FROM usuarios_escribiendo WHERE conversacion_id = ? AND usuario_id = ?',
                [conversationId, socket.userId]
            );
            
            // Notificar a otros usuarios
            socket.to(`conversation-${conversationId}`).emit('user-stopped-typing', {
                userId: socket.userId,
                conversationId: conversationId
            });
            
        } catch (error) {
            console.error('Error en typing-stop:', error);
        }
    });
    
    // Marcar mensaje como leído
    socket.on('mark-message-read', async (data) => {
        try {
            const { messageId } = data;
            
            await dbPool.execute(
                'INSERT INTO mensajes_leidos_chat (mensaje_id, usuario_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE fecha_lectura = NOW()',
                [messageId, socket.userId]
            );
            
        } catch (error) {
            console.error('Error marcando mensaje como leído:', error);
        }
    });
    
    // Desconexión
    socket.on('disconnect', async () => {
        console.log(`🔌 Usuario desconectado: ${socket.userData.nombres} ${socket.userData.apellidos} (ID: ${socket.userId})`);
        
        // Remover de usuarios conectados
        connectedUsers.delete(socket.id);
        
        try {
            // Actualizar estado en base de datos
            await dbPool.execute(
                'DELETE FROM usuarios_conectados WHERE socket_id = ?',
                [socket.id]
            );
            
            // Limpiar indicadores de "escribiendo"
            await dbPool.execute(
                'DELETE FROM usuarios_escribiendo WHERE usuario_id = ?',
                [socket.userId]
            );
            
            // Notificar a otros usuarios que este usuario está offline
            socket.broadcast.emit('user-offline', {
                userId: socket.userId,
                userData: socket.userData
            });
            
        } catch (error) {
            console.error('Error en desconexión:', error);
        }
    });
});

// Endpoint para obtener usuarios online (para API REST)
app.get('/api/users-online', async (req, res) => {
    try {
        const [users] = await dbPool.execute(
            'SELECT uc.*, CONCAT(p.nombres, " ", p.apellidos) as usuario_nombre, u.nombreusuario FROM usuarios_conectados uc JOIN usuarios u ON uc.usuario_id = u.idusuario JOIN personas p ON u.idpersona = p.idpersona WHERE uc.estado = "online"'
        );
        
        res.json({ success: true, users });
    } catch (error) {
        console.error('Error obteniendo usuarios online:', error);
        res.status(500).json({ success: false, error: 'Error interno del servidor' });
    }
});

// Endpoint para obtener mensajes de una conversación
app.get('/api/conversation/:id/messages', async (req, res) => {
    try {
        const conversationId = req.params.id;
        const limit = req.query.limit || 50;
        const offset = req.query.offset || 0;
        
        const [messages] = await dbPool.execute(
            'SELECT mc.*, CONCAT(p.nombres, " ", p.apellidos) as usuario_nombre, u.nombreusuario FROM mensajes_chat mc JOIN usuarios u ON mc.usuario_id = u.idusuario JOIN personas p ON u.idpersona = p.idpersona WHERE mc.conversacion_id = ? AND mc.eliminado = FALSE ORDER BY mc.fecha_envio DESC LIMIT ? OFFSET ?',
            [conversationId, parseInt(limit), parseInt(offset)]
        );
        
        res.json({ success: true, messages: messages.reverse() });
    } catch (error) {
        console.error('Error obteniendo mensajes:', error);
        res.status(500).json({ success: false, error: 'Error interno del servidor' });
    }
});

// Iniciar servidor
async function startServer() {
    await initDatabase();
    
    server.listen(PORT, () => {
        console.log(`🚀 Servidor WebSocket iniciado en puerto ${PORT}`);
        console.log(`📡 Socket.IO disponible en http://localhost:${PORT}`);
    });
}

// Manejo de errores no capturados
process.on('uncaughtException', (error) => {
    console.error('❌ Error no capturado:', error);
    process.exit(1);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Promesa rechazada no manejada:', reason);
    process.exit(1);
});

// Iniciar servidor
startServer();
