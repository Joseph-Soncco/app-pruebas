-- Script SQL simple para crear las tablas de mensajería
-- Ejecutar directamente en MySQL o phpMyAdmin

-- Tabla de conversaciones
CREATE TABLE IF NOT EXISTS `conversaciones` (
  `id` varchar(50) NOT NULL,
  `usuario1_id` int(11) NOT NULL,
  `usuario2_id` int(11) NOT NULL,
  `ultimo_mensaje_id` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_ultimo_mensaje` datetime DEFAULT NULL,
  `mensajes_no_leidos_usuario1` int(11) DEFAULT 0,
  `mensajes_no_leidos_usuario2` int(11) DEFAULT 0,
  `estado` enum('activa','archivada','eliminada') DEFAULT 'activa',
  PRIMARY KEY (`id`),
  KEY `idx_usuario1` (`usuario1_id`),
  KEY `idx_usuario2` (`usuario2_id`),
  KEY `idx_fecha_ultimo` (`fecha_ultimo_mensaje`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de mensajes de chat
CREATE TABLE IF NOT EXISTS `mensajes_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversacion_id` varchar(50) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` enum('texto','imagen','archivo','audio','video') DEFAULT 'texto',
  `archivo_nombre` varchar(255) DEFAULT NULL,
  `archivo_ruta` varchar(500) DEFAULT NULL,
  `archivo_tamaño` int(11) DEFAULT NULL,
  `mensaje_referencia_id` int(11) DEFAULT NULL,
  `editado` tinyint(1) DEFAULT 0,
  `fecha_edicion` datetime DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT 0,
  `fecha_eliminacion` datetime DEFAULT NULL,
  `fecha_envio` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversacion` (`conversacion_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha_envio` (`fecha_envio`),
  KEY `idx_eliminado` (`eliminado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de mensajes leídos
CREATE TABLE IF NOT EXISTS `mensajes_leidos_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensaje_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_lectura` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mensaje_usuario` (`mensaje_id`,`usuario_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha_lectura` (`fecha_lectura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios conectados
CREATE TABLE IF NOT EXISTS `usuarios_conectados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `socket_id` varchar(100) NOT NULL,
  `estado` enum('online','away','busy','offline') DEFAULT 'online',
  `dispositivo` varchar(50) DEFAULT 'web',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ultima_conexion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_conexion` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_usuario_socket` (`usuario_id`,`socket_id`),
  KEY `idx_socket_id` (`socket_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_ultima_conexion` (`ultima_conexion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios escribiendo
CREATE TABLE IF NOT EXISTS `usuarios_escribiendo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversacion_id` varchar(50) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha_ultima_actividad` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversacion_usuario` (`conversacion_id`,`usuario_id`),
  KEY `idx_conversacion` (`conversacion_id`),
  KEY `idx_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones
CREATE TABLE IF NOT EXISTS `notificaciones_chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('mensaje','conversacion_nueva','usuario_online') DEFAULT 'mensaje',
  `titulo` varchar(255) NOT NULL,
  `mensaje` text NOT NULL,
  `conversacion_id` varchar(50) DEFAULT NULL,
  `mensaje_id` int(11) DEFAULT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_lectura` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_leida` (`leida`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de prueba (solo si no existen)
INSERT IGNORE INTO `conversaciones` (`id`, `usuario1_id`, `usuario2_id`, `fecha_creacion`) VALUES
('1_2_demo', 1, 2, NOW()),
('1_3_demo', 1, 3, NOW()),
('2_3_demo', 2, 3, NOW());

INSERT IGNORE INTO `mensajes_chat` (`conversacion_id`, `usuario_id`, `mensaje`, `fecha_envio`) VALUES
('1_2_demo', 2, 'Hola, ¿cómo estás?', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
('1_2_demo', 1, '¡Hola! Todo bien, gracias por preguntar', DATE_SUB(NOW(), INTERVAL 25 MINUTE)),
('1_2_demo', 2, 'Perfecto, ¿tienes tiempo para revisar el proyecto?', DATE_SUB(NOW(), INTERVAL 20 MINUTE)),
('1_3_demo', 3, 'Buenos días, ¿cómo va el trabajo?', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('1_3_demo', 1, 'Todo excelente, gracias por preguntar', DATE_SUB(NOW(), INTERVAL 55 MINUTE));

-- Actualizar conversaciones con último mensaje
UPDATE `conversaciones` SET 
  `ultimo_mensaje_id` = (SELECT id FROM `mensajes_chat` WHERE `conversacion_id` = '1_2_demo' ORDER BY `fecha_envio` DESC LIMIT 1),
  `fecha_ultimo_mensaje` = (SELECT `fecha_envio` FROM `mensajes_chat` WHERE `conversacion_id` = '1_2_demo' ORDER BY `fecha_envio` DESC LIMIT 1)
WHERE `id` = '1_2_demo';

UPDATE `conversaciones` SET 
  `ultimo_mensaje_id` = (SELECT id FROM `mensajes_chat` WHERE `conversacion_id` = '1_3_demo' ORDER BY `fecha_envio` DESC LIMIT 1),
  `fecha_ultimo_mensaje` = (SELECT `fecha_envio` FROM `mensajes_chat` WHERE `conversacion_id` = '1_3_demo' ORDER BY `fecha_envio` DESC LIMIT 1)
WHERE `id` = '1_3_demo';

-- Crear índices adicionales para optimización
CREATE INDEX IF NOT EXISTS `idx_conversaciones_usuarios` ON `conversaciones` (`usuario1_id`, `usuario2_id`);
CREATE INDEX IF NOT EXISTS `idx_mensajes_conversacion_fecha` ON `mensajes_chat` (`conversacion_id`, `fecha_envio`);
CREATE INDEX IF NOT EXISTS `idx_usuarios_conectados_estado` ON `usuarios_conectados` (`estado`, `ultima_conexion`);
