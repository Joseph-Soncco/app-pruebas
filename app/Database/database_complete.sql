CREATE DATABASE ishume;
USE ishume;

-- =============================================
-- TABLAS PRINCIPALES DEL SISTEMA
-- =============================================

CREATE TABLE cargos (
    idcargo INT AUTO_INCREMENT PRIMARY KEY,
    cargo VARCHAR(100) NOT NULL
);

CREATE TABLE categorias (
    idcategoria INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(100) NOT NULL
);

CREATE TABLE personas (
    idpersona       INT AUTO_INCREMENT PRIMARY KEY,
    apellidos       VARCHAR(100) NOT NULL,
    nombres         VARCHAR(100) NOT NULL,
    tipodoc         ENUM ('DNI', 'Carne de Extranjería', 'Pasaporte') DEFAULT 'DNI' NOT NULL,
    numerodoc       VARCHAR(12) NOT NULL UNIQUE,
    telprincipal    CHAR(9) NOT NULL,
    telalternativo  CHAR(9) NULL,
    direccion       VARCHAR(150) NOT NULL,
    referencia      VARCHAR(150) NULL
);

CREATE TABLE empresas (
    idempresa       INT AUTO_INCREMENT PRIMARY KEY,
    ruc             CHAR(11) NOT NULL,
    razonsocial     VARCHAR(150) NOT NULL,
    direccion       VARCHAR(150) NOT NULL,
    telefono        CHAR(9) NOT NULL
);

CREATE TABLE clientes (
    idcliente INT AUTO_INCREMENT PRIMARY KEY,
    idpersona INT,
    idempresa INT,
    CONSTRAINT fk_cliente_persona FOREIGN KEY (idpersona) REFERENCES personas(idpersona),
    CONSTRAINT fk_cliente_empresa FOREIGN KEY (idempresa) REFERENCES empresas(idempresa)
);

CREATE TABLE condiciones (
    idcondicion INT AUTO_INCREMENT PRIMARY KEY,
    condicion VARCHAR(100) NOT NULL
);

CREATE TABLE tipocontrato (
    idtipocontrato INT AUTO_INCREMENT PRIMARY KEY,
    tipocontrato VARCHAR(100) NOT NULL,
    vigenciadias INT
);

CREATE TABLE tipoeventos (
    idtipoevento INT AUTO_INCREMENT PRIMARY KEY,
    evento VARCHAR(100) NOT NULL
);

CREATE TABLE tipospago (
    idtipopago INT AUTO_INCREMENT PRIMARY KEY,
    tipopago VARCHAR(100) NOT NULL
);

CREATE TABLE usuarios (
    idusuario INT AUTO_INCREMENT PRIMARY KEY,
    idpersona INT,
    idcargo INT,
    nombreusuario VARCHAR(50) UNIQUE NOT NULL,
    claveacceso VARCHAR(255) NOT NULL,
    estado TINYINT DEFAULT 1,
    tipo_usuario ENUM('admin', 'trabajador') DEFAULT 'trabajador',
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    CONSTRAINT fk_usuario_persona FOREIGN KEY (idpersona) REFERENCES personas(idpersona),
    CONSTRAINT fk_usuario_cargo FOREIGN KEY (idcargo) REFERENCES cargos(idcargo)
);

CREATE TABLE cotizaciones (
    idcotizacion INT AUTO_INCREMENT PRIMARY KEY,
    idcliente INT,
    idtipocontrato INT,
    idusuariocrea INT,
    fechacotizacion DATE,
    fechaevento DATE,
    idtipoevento INT,
    CONSTRAINT fk_cotizacion_cliente FOREIGN KEY (idcliente) REFERENCES clientes(idcliente),
    CONSTRAINT fk_cotizacion_tipocontrato FOREIGN KEY (idtipocontrato) REFERENCES tipocontrato(idtipocontrato),
    CONSTRAINT fk_cotizacion_usuario FOREIGN KEY (idusuariocrea) REFERENCES usuarios(idusuario),
    CONSTRAINT fk_cotizacion_evento FOREIGN KEY (idtipoevento) REFERENCES tipoeventos(idtipoevento)
);

CREATE TABLE contratos (
    idcontrato INT AUTO_INCREMENT PRIMARY KEY,
    idcotizacion INT,
    idcliente INT,
    autorizapublicacion TINYINT DEFAULT 0,
    CONSTRAINT fk_contrato_cotizacion FOREIGN KEY (idcotizacion) REFERENCES cotizaciones(idcotizacion),
    CONSTRAINT fk_contrato_cliente FOREIGN KEY (idcliente) REFERENCES clientes(idcliente)
);

CREATE TABLE controlpagos (
    idpagos INT AUTO_INCREMENT PRIMARY KEY,
    idcontrato INT,
    saldo DECIMAL(10,2),
    amortizacion DECIMAL(10,2),
    deuda DECIMAL(10,2),
    idtipopago INT,
    numtransaccion VARCHAR(50),
    fechahora DATETIME,
    idusuario INT,
    comprobante VARCHAR(255) NULL,
    CONSTRAINT fk_pago_contrato FOREIGN KEY (idcontrato) REFERENCES contratos(idcontrato),
    CONSTRAINT fk_pago_tipopago FOREIGN KEY (idtipopago) REFERENCES tipospago(idtipopago),
    CONSTRAINT fk_pago_usuario FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);

CREATE TABLE listacondiciones (
    idlista INT AUTO_INCREMENT PRIMARY KEY,
    idcondicion INT,
    idtipocontrato INT,
    CONSTRAINT fk_listacondicion_condicion FOREIGN KEY (idcondicion) REFERENCES condiciones(idcondicion),
    CONSTRAINT fk_listacondicion_tipocontrato FOREIGN KEY (idtipocontrato) REFERENCES tipocontrato(idtipocontrato)
);

CREATE TABLE servicios (
    idservicio INT AUTO_INCREMENT PRIMARY KEY,
    servicio VARCHAR(100) NOT NULL,
    descripcion VARCHAR(200),
    precioregular DECIMAL(10,2),
    idcategoria INT,
    CONSTRAINT fk_servicio_categoria FOREIGN KEY (idcategoria) REFERENCES categorias(idcategoria)
);

CREATE TABLE servicioscontratados (
    idserviciocontratado INT AUTO_INCREMENT PRIMARY KEY,
    idcotizacion INT,
    idservicio INT,
    cantidad INT,
    precio DECIMAL(10,2),
    fechahoraservicio DATETIME,
    direccion VARCHAR(150),
    CONSTRAINT fk_servcontratado_cotizacion FOREIGN KEY (idcotizacion) REFERENCES cotizaciones(idcotizacion),
    CONSTRAINT fk_servcontratado_servicio FOREIGN KEY (idservicio) REFERENCES servicios(idservicio)
);

CREATE TABLE entregables (
    identregable INT AUTO_INCREMENT PRIMARY KEY,
    idserviciocontratado INT,
    idpersona INT,
    fechahoraentrega DATETIME,
    fecha_real_entrega DATETIME NULL,
    observaciones VARCHAR(200),
    estado ENUM('pendiente', 'completada') DEFAULT 'pendiente',
    comprobante_entrega VARCHAR(255) NULL,
    CONSTRAINT fk_entregable_servicio FOREIGN KEY (idserviciocontratado) REFERENCES servicioscontratados(idserviciocontratado),
    CONSTRAINT fk_entregable_persona FOREIGN KEY (idpersona) REFERENCES personas(idpersona)
);

CREATE TABLE equipos (
    idequipo INT AUTO_INCREMENT PRIMARY KEY,
    idserviciocontratado INT,
    idusuario INT,
    descripcion VARCHAR(200),
    estadoservicio ENUM('Pendiente','En Proceso','Completado','Programado') DEFAULT 'Pendiente',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipo_servicio FOREIGN KEY (idserviciocontratado) REFERENCES servicioscontratados(idserviciocontratado),
    CONSTRAINT fk_equipo_usuario FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);

-- =============================================
-- TABLAS DE INVENTARIO
-- =============================================

CREATE TABLE cateEquipo (
    idCateEquipo INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nomCate VARCHAR(70) NOT NULL UNIQUE,
    descripcion TEXT NULL
) ENGINE=INNODB;

CREATE TABLE marcaEquipo (
    idMarca INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nomMarca VARCHAR(70) NOT NULL UNIQUE
) ENGINE=INNODB;

CREATE TABLE equipo_inventario (
    idEquipo INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idCateEquipo INT UNSIGNED NOT NULL,
    idMarca INT UNSIGNED NOT NULL,
    modelo VARCHAR(70) NOT NULL,
    descripcion VARCHAR(255) NULL,
    caracteristica TEXT NULL,
    sku VARCHAR(50) UNIQUE NULL,
    numSerie VARCHAR(100) UNIQUE NULL,
    cantDisponible INT UNSIGNED NOT NULL DEFAULT 1,
    estado ENUM('Nuevo','EnUso','EnMantenimiento','Dañado','Otro') NOT NULL DEFAULT 'Nuevo',
    fechaCompra DATE NULL,
    fechaUso DATE NULL,
    imgEquipo VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipo_inventario_categoria FOREIGN KEY (idCateEquipo) REFERENCES cateEquipo(idCateEquipo) ON DELETE RESTRICT,
    CONSTRAINT fk_equipo_inventario_marca FOREIGN KEY (idMarca) REFERENCES marcaEquipo(idMarca) ON DELETE RESTRICT
) ENGINE=INNODB;

CREATE TABLE movimientoEquipo (
    idMovimiento INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idEquipo INT UNSIGNED NOT NULL,
    tipoMovimiento ENUM('Entrada','Salida','Mantenimiento','Baja') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    fechaMovimiento DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observacion TEXT NULL,
    CONSTRAINT fk_movimiento_equipo FOREIGN KEY (idEquipo) REFERENCES equipo_inventario(idEquipo) ON DELETE CASCADE
) ENGINE=INNODB;

CREATE TABLE ubicacion (
    idUbicacion INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombreUbicacion VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL
) ENGINE=INNODB;

CREATE TABLE equipoUbicacion (
    idEquipoUbicacion INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idEquipo INT UNSIGNED NOT NULL,
    idUbicacion INT UNSIGNED NOT NULL,
    fechaAsignacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipoUbicacion_equipo FOREIGN KEY (idEquipo) REFERENCES equipo_inventario(idEquipo) ON DELETE CASCADE,
    CONSTRAINT fk_equipoUbicacion_ubicacion FOREIGN KEY (idUbicacion) REFERENCES ubicacion(idUbicacion) ON DELETE CASCADE
) ENGINE=INNODB;

-- =============================================
-- TABLAS DE MENSAJERÍA
-- =============================================

CREATE TABLE conversaciones (
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

CREATE TABLE usuarios_conectados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    socket_id VARCHAR(255) NOT NULL,
    ultima_conexion DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('online', 'away', 'busy', 'offline') DEFAULT 'online',
    dispositivo VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_socket (socket_id),
    INDEX idx_estado (estado),
    INDEX idx_ultima_conexion (ultima_conexion),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(idusuario) ON DELETE CASCADE,
    UNIQUE KEY unique_socket (socket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensajes_chat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensaje TEXT NOT NULL,
    tipo ENUM('texto', 'imagen', 'archivo', 'emoji', 'sistema') DEFAULT 'texto',
    archivo_nombre VARCHAR(255) NULL,
    archivo_ruta VARCHAR(500) NULL,
    archivo_tamaño INT NULL,
    mensaje_referencia_id INT NULL,
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

CREATE TABLE usuarios_escribiendo (
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

CREATE TABLE mensajes_leidos_chat (
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

CREATE TABLE configuracion_chat (
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

CREATE TABLE salas_chat (
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

CREATE TABLE participantes_sala (
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

-- =============================================
-- DATOS DE PRUEBA
-- =============================================

-- DATOS BÁSICOS (Catálogos)
INSERT INTO cargos (cargo) VALUES 
('Gerente de Proyectos'),
('Coordinador de Eventos'),
('Técnico en Audio'),
('Fotógrafo'),
('Operador de Equipos');

INSERT INTO categorias (categoria) VALUES 
('Audio y Sonido'),
('Fotografía y Video'),
('Iluminación'),
('Decoración'),
('Catering');

INSERT INTO condiciones (condicion) VALUES 
('Pago 50% adelanto'),
('Entrega de equipos 2 horas antes'),
('Cliente proporciona energía eléctrica'),
('Acceso vehicular requerido'),
('Cancelación con 48h anticipación');

INSERT INTO tipocontrato (tipocontrato, vigenciadias) VALUES 
('Evento Único', 1),
('Paquete Mensual', 30),
('Contrato Anual', 365),
('Servicio Corporativo', 90);

INSERT INTO tipoeventos (evento) VALUES 
('Boda'),
('Quinceañero'),
('Evento Corporativo'),
('Conferencia'),
('Concierto');

INSERT INTO tipospago (tipopago) VALUES 
('Efectivo'),
('Transferencia Bancaria'),
('Tarjeta de Crédito'),
('Cheque'),
('Yape/Plin');

-- PERSONAS Y EMPRESAS
INSERT INTO personas (apellidos, nombres, tipodoc, numerodoc, telprincipal, telalternativo, direccion, referencia) VALUES 
('García López', 'Carlos Eduardo', 'DNI', '12345678', '987654321', '945123456', 'Av. Los Álamos 123, San Isidro', 'Cerca al parque central'),
('Rodríguez Silva', 'María Carmen', 'DNI', '87654321', '976543210', NULL, 'Jr. Las Flores 456, Miraflores', 'Frente a la iglesia San Antonio'),
('Mendoza Torres', 'José Antonio', 'DNI', '11223344', '965432109', '912345678', 'Calle Los Pinos 789, Surco', 'A 2 cuadras del mercado central'),
('Fernández Ruiz', 'Ana Lucía', 'DNI', '55667788', '954321098', NULL, 'Av. Industrial 321, Ate', 'Edificio azul, tercer piso'),
('Vásquez Castro', 'Luis Miguel', 'DNI', '99887766', '943210987', '987123456', 'Urbanización El Sol 654, La Molina', 'Casa esquina con jardín'),
('Morales Díaz', 'Patricia Isabel', 'DNI', '44556677', '932109876', NULL, 'Calle Real 987, Pueblo Libre', 'Portón verde, casa colonial'),
('Jiménez Vargas', 'Ricardo Andrés', 'DNI', '33445566', '921098765', '956789012', 'Jr. Comercio 147, Breña', 'Al costado del Banco de Crédito'),
('Smith Johnson', 'Robert William', 'Pasaporte', 'AB1234567', '998877665', NULL, 'Calle Extranjeros 555, San Borja', 'Condominio Las Torres, Dpto 301'),
('González Pérez', 'Carmen Rosa', 'DNI', '77889900', '987123789', '945678123', 'Av. Primavera 888, Surco', 'Cerca al centro comercial');

INSERT INTO empresas (ruc, razonsocial, direccion, telefono) VALUES 
('20123456789', 'Eventos Premium SAC', 'Av. Empresarial 1001, San Isidro', '014567890'),
('20987654321', 'Corporativo Los Andes EIRL', 'Jr. Negocios 202, Miraflores', '014445556'),
('20111222333', 'Celebraciones Especiales SRL', 'Calle Eventos 303, Surco', '013334445'),
('20555666777', 'Hoteles & Convenciones SA', 'Av. Javier Prado 2500, San Borja', '012223334');

-- CLIENTES
INSERT INTO clientes (idpersona, idempresa) VALUES 
(1, NULL),  -- Carlos García (persona)
(2, NULL),  -- María Rodríguez (persona)
(NULL, 1),  -- Eventos Premium SAC
(3, NULL),  -- José Mendoza (persona)
(NULL, 2),  -- Corporativo Los Andes EIRL
(4, NULL),  -- Ana Fernández (persona)
(NULL, 3),  -- Celebraciones Especiales
(8, NULL);  -- Robert Smith (extranjero)

-- USUARIOS
INSERT INTO usuarios (idpersona, idcargo, nombreusuario, claveacceso, estado) VALUES 
(5, 1, 'lvasquez', '1Vasque3', 1),
(6, 2, 'pmorales', 'pM0rales', 1),
(7, 3, 'rjimenez', '4J1menez', 1),
(9, 4, 'cgonzalez', '3Gon3ale3z', 1);

INSERT INTO usuarios (idpersona, idcargo, nombreusuario, claveacceso, tipo_usuario, email, password_hash, estado) VALUES 
(1, 1, 'admin', 'admin123', 'admin', 'admin@ishume.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- SERVICIOS
INSERT INTO servicios (servicio, descripcion, precioregular, idcategoria) VALUES 
('Sonido para Bodas', 'Equipo completo de sonido para ceremonias y recepciones', 800.00, 1),
('Fotografía de Eventos', 'Cobertura fotográfica completa del evento', 1200.00, 2),
('Iluminación LED', 'Sistema de iluminación decorativa con luces LED', 600.00, 3),
('Video en Vivo', 'Transmisión en vivo del evento', 1500.00, 2),
('DJ Profesional', 'Servicio de DJ con música y animación', 400.00, 1),
('Catering Premium', 'Servicio de alimentación para eventos', 25.00, 5),
('Decoración Floral', 'Arreglos florales y decoración temática', 350.00, 4);

-- INVENTARIO - CATEGORÍAS Y MARCAS
INSERT INTO cateEquipo (nomCate, descripcion) VALUES
('Cámaras', 'Cámaras fotográficas y de video'),
('Lentes', 'Lentes intercambiables'),
('Iluminación', 'Luces y accesorios de iluminación'),
('Audio', 'Micrófonos y accesorios'),
('Estabilización y Soporte', 'Gimbals, trípodes y soportes'),
('Almacenamiento', 'Memorias y dispositivos de almacenamiento'),
('Computadoras y Edición', 'PCs y estaciones de edición'),
('Producción y Oficina', 'Equipos para producción y oficina'),
('Escenografía y Fondos', 'Fondos y soportes para escenografía');

INSERT INTO marcaEquipo (nomMarca) VALUES
('Sony'),
('Sigma'),
('DJI'),
('Zhiyun'),
('Epson'),
('Genérico');

-- EQUIPOS DE INVENTARIO
INSERT INTO equipo_inventario (idCateEquipo, idMarca, modelo, cantDisponible, estado) VALUES
(1, 1, 'Sony A6400 con lente kit', 2, 'EnUso'),
(1, 1, 'Sony A6700 con lente kit', 1, 'EnUso'),
(1, 1, 'Sony FX30 con lente kit', 1, 'EnUso'),
(1, 3, 'Dron DJI Mini 4 Pro', 2, 'EnUso'),
(2, 2, 'Sigma 18–50 mm', 1, 'EnUso'),
(2, 2, 'Sigma 24–70 mm', 1, 'EnUso'),
(2, 1, 'Sony 18–135 mm kit', 1, 'EnUso'),
(2, 1, 'Sony 30 mm', 1, 'EnUso'),
(3, 6, 'Luz LED', 4, 'EnUso'),
(3, 6, 'Softbox', 1, 'EnUso'),
(3, 6, 'Difusor', 1, 'EnUso'),
(3, 6, 'Flash V1', 1, 'EnUso'),
(3, 6, 'X2T – Transmisor de flash', 1, 'EnUso'),
(4, 6, 'Micrófono (modelo no especificado)', 1, 'EnUso'),
(5, 6, 'Gimbal Mini Crane 3', 1, 'EnUso'),
(5, 4, 'Zhiyun Weebill S', 1, 'EnUso'),
(5, 4, 'Zhiyun Weebill 3S', 1, 'EnUso'),
(5, 6, 'Trípode estándar', 2, 'EnUso'),
(5, 6, 'Trípode 2.10 m regulable', 5, 'EnUso'),
(6, 6, 'Memoria SD 128 GB', 6, 'EnUso'),
(6, 6, 'Memoria SD 32 GB', 2, 'EnUso'),
(7, 6, 'PC Ryzen 7 con tarjeta gráfica', 4, 'EnUso'),
(8, 5, 'Impresora Epson L8180', 1, 'EnUso'),
(8, 6, 'Guillotina A3', 1, 'EnUso'),
(8, 6, 'Enmicadora A3', 1, 'EnUso'),
(9, 6, 'Porta fondo de papel', 1, 'EnUso'),
(9, 6, 'Porta fondo de tela', 1, 'EnUso');

-- CONFIGURACIÓN DE CHAT PARA USUARIOS EXISTENTES
INSERT IGNORE INTO configuracion_chat (usuario_id)
SELECT idusuario FROM usuarios WHERE estado = 1;
