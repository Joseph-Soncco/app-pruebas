-- =============================================
-- EXTENSIÓN PARA CHAT EN TIEMPO REAL - ISHUME
-- =============================================

-- Tabla para conversaciones (para agrupar mensajes)
CREATE TABLE IF NOT EXISTS conversaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario1_id INT NOT NULL,
    usuario2_id INT NOT NULL,
    ultimo_mensaje_id INT NULL,
    fecha_ultimo_mensaje DATETIME NULL,
    mensajes_no_leidos_usuario1 INT DEFAULT 0,
    mensajes_no_leidos_usuario2 INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario1 (usuario1_id),
    INDEX idx_usuario2 (usuario2_id),
    INDEX idx_ultimo_mensaje (fecha_ultimo_mensaje),
    FOREIGN KEY (usuario1_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    FOREIGN KEY (usuario2_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_conversation (usuario1_id, usuario2_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para usuarios conectados (estado online/offline)
CREATE TABLE IF NOT EXISTS usuarios_conectados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    socket_id VARCHAR(255) NOT NULL,
    ultima_conexion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('online', 'away', 'busy', 'offline') DEFAULT 'online',
    dispositivo VARCHAR(100) NULL, -- 'web', 'mobile', 'desktop'
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_socket (socket_id),
    INDEX idx_estado (estado),
    INDEX idx_ultima_conexion (ultima_conexion),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_socket (socket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para mensajes de chat en tiempo real
CREATE TABLE IF NOT EXISTS mensajes_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('texto', 'imagen', 'archivo', 'emoji', 'sistema') DEFAULT 'texto',
    archivo_nombre VARCHAR(255) NULL,
    archivo_ruta VARCHAR(500) NULL,
    archivo_tamaño INT NULL,
    mensaje_referencia_id INT NULL, -- Para respuestas a mensajes
    editado BOOLEAN DEFAULT FALSE,
    fecha_edicion DATETIME NULL,
    eliminado BOOLEAN DEFAULT FALSE,
    fecha_eliminacion DATETIME NULL,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversacion (conversacion_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha_envio (fecha_envio),
    INDEX idx_tipo (tipo),
    INDEX idx_eliminado (eliminado),
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    FOREIGN KEY (mensaje_referencia_id) REFERENCES mensajes_chat(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para indicadores de "escribiendo"
CREATE TABLE IF NOT EXISTS usuarios_escribiendo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_inicio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actividad DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_conversacion (conversacion_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha_actividad (fecha_ultima_actividad),
    FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_conversacion_usuario (conversacion_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para mensajes leídos en tiempo real
CREATE TABLE IF NOT EXISTS mensajes_leidos_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mensaje_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_lectura DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mensaje (mensaje_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha_lectura (fecha_lectura),
    FOREIGN KEY (mensaje_id) REFERENCES mensajes_chat(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_mensaje_usuario (mensaje_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para configuraciones de chat
CREATE TABLE IF NOT EXISTS configuracion_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    notificaciones_sonido BOOLEAN DEFAULT TRUE,
    notificaciones_push BOOLEAN DEFAULT TRUE,
    mostrar_estado_online BOOLEAN DEFAULT TRUE,
    mostrar_ultima_conexion BOOLEAN DEFAULT TRUE,
    tema_chat ENUM('claro', 'oscuro', 'auto') DEFAULT 'auto',
    tamano_fuente ENUM('pequeno', 'normal', 'grande') DEFAULT 'normal',
    auto_scroll BOOLEAN DEFAULT TRUE,
    enter_para_enviar BOOLEAN DEFAULT TRUE,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario (usuario_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para salas de chat grupal (opcional)
CREATE TABLE IF NOT EXISTS salas_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    creador_id INT NOT NULL,
    tipo ENUM('publico', 'privado', 'temporal') DEFAULT 'privado',
    codigo_invitacion VARCHAR(50) NULL,
    max_participantes INT DEFAULT 50,
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_creador (creador_id),
    INDEX idx_tipo (tipo),
    INDEX idx_codigo (codigo_invitacion),
    INDEX idx_activa (activa),
    FOREIGN KEY (creador_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para participantes de salas de chat
CREATE TABLE IF NOT EXISTS participantes_sala (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sala_id INT NOT NULL,
    usuario_id INT NOT NULL,
    rol ENUM('admin', 'moderador', 'miembro') DEFAULT 'miembro',
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_sala (sala_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo),
    FOREIGN KEY (sala_id) REFERENCES salas_chat(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_sala_usuario (sala_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto para usuarios existentes
INSERT IGNORE INTO configuracion_chat (usuario_id)
SELECT idusuario FROM usuarios WHERE estado = 1;

-- Triggers para el chat en tiempo real
DELIMITER //

-- Trigger para actualizar estado de conexión
-- Trigger para actualizar estado de conexión (comentado porque ultima_conexion no existe en usuarios)
-- CREATE TRIGGER tr_usuario_conectado 
-- AFTER INSERT ON usuarios_conectados
-- FOR EACH ROW
-- BEGIN
--     -- Actualizar última conexión en tabla de usuarios si existe
--     UPDATE usuarios 
--     SET ultima_conexion = NOW() 
--     WHERE idusuario = NEW.usuario_id;
-- END//

-- Trigger para limpiar usuarios desconectados (ejecutar periódicamente)
CREATE TRIGGER tr_usuario_desconectado 
AFTER DELETE ON usuarios_conectados
FOR EACH ROW
BEGIN
    -- Limpiar indicadores de "escribiendo" del usuario desconectado
    DELETE FROM usuarios_escribiendo WHERE usuario_id = OLD.usuario_id;
END//

-- Trigger para crear notificación de mensaje de chat
CREATE TRIGGER tr_mensaje_chat_enviado 
AFTER INSERT ON mensajes_chat
FOR EACH ROW
BEGIN
    DECLARE destinatario_id INT;
    
    -- Obtener el destinatario de la conversación
    SELECT CASE 
        WHEN usuario1_id = NEW.usuario_id THEN usuario2_id 
        ELSE usuario1_id 
    END INTO destinatario_id
    FROM conversaciones 
    WHERE id = NEW.conversacion_id;
    
    -- Crear notificación solo si el destinatario no está conectado
    IF NOT EXISTS (
        SELECT 1 FROM usuarios_conectados 
        WHERE usuario_id = destinatario_id AND estado = 'online'
    ) THEN
        INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, datos_extra)
        VALUES (
            destinatario_id, 
            'mensaje', 
            '💬 Nuevo Mensaje de Chat',
            CONCAT('De: ', (SELECT CONCAT(nombres, ' ', apellidos) FROM personas p JOIN usuarios u ON p.idpersona = u.idpersona WHERE u.idusuario = NEW.usuario_id), ' - ', LEFT(NEW.mensaje, 50)),
            JSON_OBJECT('mensaje_chat_id', NEW.id, 'conversacion_id', NEW.conversacion_id, 'remitente_id', NEW.usuario_id)
        );
    END IF;
    
    -- Actualizar conversación
    UPDATE conversaciones 
    SET ultimo_mensaje_id = NEW.id, 
        fecha_ultimo_mensaje = NEW.fecha_envio,
        mensajes_no_leidos_usuario2 = CASE 
            WHEN usuario1_id = NEW.usuario_id THEN mensajes_no_leidos_usuario2 + 1
            ELSE mensajes_no_leidos_usuario1 + 1
        END
    WHERE id = NEW.conversacion_id;
END//

DELIMITER ;

-- Agregar clave foránea para ultimo_mensaje_id después de crear mensajes_chat
ALTER TABLE conversaciones 
ADD CONSTRAINT fk_conversaciones_ultimo_mensaje 
FOREIGN KEY (ultimo_mensaje_id) REFERENCES mensajes_chat(id) ON DELETE SET NULL;

-- Vista para mensajes de chat con información completa
CREATE VIEW v_mensajes_chat_completos AS
SELECT 
    mc.*,
    CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
    u.nombreusuario as usuario_usuario,
    u.email as usuario_email,
    c.usuario1_id,
    c.usuario2_id,
    CASE 
        WHEN c.usuario1_id = mc.usuario_id THEN c.usuario2_id 
        ELSE c.usuario1_id 
    END as destinatario_id
FROM mensajes_chat mc
JOIN usuarios u ON mc.usuario_id = u.idusuario
JOIN personas p ON u.idpersona = p.idpersona
JOIN conversaciones c ON mc.conversacion_id = c.id;

-- Vista para usuarios online
CREATE VIEW v_usuarios_online AS
SELECT 
    uc.*,
    CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
    u.nombreusuario as usuario_usuario,
    u.email as usuario_email
FROM usuarios_conectados uc
JOIN usuarios u ON uc.usuario_id = u.idusuario
JOIN personas p ON u.idpersona = p.idpersona
WHERE uc.estado = 'online';

-- Procedimiento para limpiar conexiones inactivas
DELIMITER //
CREATE PROCEDURE sp_limpiar_conexiones_inactivas()
BEGIN
    -- Eliminar conexiones inactivas por más de 5 minutos
    DELETE FROM usuarios_conectados 
    WHERE ultima_conexion < DATE_SUB(NOW(), INTERVAL 5 MINUTE);
    
    -- Limpiar indicadores de "escribiendo" inactivos
    DELETE FROM usuarios_escribiendo 
    WHERE fecha_ultima_actividad < DATE_SUB(NOW(), INTERVAL 30 SECOND);
END//
DELIMITER ;

-- Evento para ejecutar limpieza automática cada minuto
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS ev_limpiar_conexiones_inactivas
ON SCHEDULE EVERY 1 MINUTE
DO
  CALL sp_limpiar_conexiones_inactivas();
