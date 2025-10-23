/**
 * MENSAJERÍA EN TIEMPO REAL - JAVASCRIPT
 * Sistema de chat con WebSockets usando Socket.IO
 */

let conversaciones = [];
let conversacionActual = null;
let timeoutBusqueda = null;
let socket = null;
let currentUserId = null;
let onlineUsers = [];

// Configuración del WebSocket
const socketUrl = window.location.protocol === 'https:' 
    ? 'https://tu-proyecto.railway.app'  // ← Cambiar por tu URL de Railway
    : 'http://localhost:3000';

$(document).ready(function() {
    // Obtener ID del usuario actual
    currentUserId = $('meta[name="user-id"]').attr('content') || '1';
    
    // Inicializar chat
    initChat();
    
    // Configurar eventos
    setupEventListeners();
    
    // Cargar datos iniciales
    loadInitialData();
});

    /**
     * Inicializar el chat
     */
async function initChat() {
    try {
            // Conectar a WebSocket
        await connectSocket();
            
        // Cargar conversaciones
        await cargarConversaciones();
            
        // Cargar usuarios online
        await cargarUsuariosOnline();
            
            console.log('✅ Chat inicializado correctamente');
        } catch (error) {
            console.error('❌ Error inicializando chat:', error);
        showError('Error de conexión al inicializar el chat');
        }
    }

    /**
 * Conectar a WebSocket
 */
async function connectSocket() {
    return new Promise((resolve, reject) => {
        try {
            // Importar Socket.IO dinámicamente
            const script = document.createElement('script');
            script.src = 'https://cdn.socket.io/4.7.2/socket.io.min.js';
            script.onload = () => {
                socket = io(socketUrl, {
            transports: ['websocket', 'polling']
        });

                socket.on('connect', () => {
                    console.log('✅ Conectado a WebSocket');
                    updateConnectionStatus('Conectado', 'success');
                    resolve();
                });
                
                socket.on('disconnect', () => {
                    console.log('❌ Desconectado de WebSocket');
                    updateConnectionStatus('Desconectado', 'error');
                });
                
                socket.on('error', (error) => {
                    console.error('❌ Error en WebSocket:', error);
                    reject(error);
                });
                
                // Eventos de mensajes
                socket.on('mensaje_recibido', (data) => {
                    handleMensajeRecibido(data);
                });
                
                socket.on('usuario_escribiendo', (data) => {
                    handleUsuarioEscribiendo(data);
                });
                
                socket.on('usuario_dejo_escribir', (data) => {
                    handleUsuarioDejoEscribir(data);
                });
                
                socket.on('usuarios_online', (data) => {
                    updateUsuariosOnline(data);
                });
            };
            script.onerror = () => reject(new Error('Error cargando Socket.IO'));
            document.head.appendChild(script);
        } catch (error) {
            reject(error);
        }
        });
    }

    /**
 * Configurar event listeners
 */
function setupEventListeners() {
    // Búsqueda de conversaciones
    $('#buscar-conversaciones').on('input', function() {
        const termino = $(this).val().toLowerCase();
        filtrarConversaciones(termino);
    });
    
    // Envío de mensajes
    $('#form-enviar-mensaje').on('submit', function(e) {
        e.preventDefault();
        enviarMensaje();
    });
    
    // Enter para enviar mensaje
    $('#mensaje-texto').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
            enviarMensaje();
        }
    });
    
    // Indicador de escritura
    $('#mensaje-texto').on('input', function() {
        if (conversacionActual) {
            socket.emit('usuario_escribiendo', {
                conversacionId: conversacionActual,
                usuarioId: currentUserId
            });
        }
    });
    
    // Toggle sidebar en móviles
    $('#btn-toggle-sidebar').on('click', function() {
        $('.col-md-4').toggleClass('show');
        $('#mobile-overlay').toggleClass('show');
    });
    
    // Cerrar sidebar al hacer clic en overlay
    $('#mobile-overlay').on('click', function() {
        $('.col-md-4').removeClass('show');
        $('#mobile-overlay').removeClass('show');
    });
    
    // Botones de acción
    $('#btn-nuevo-mensaje, #btn-nuevo-mensaje-main').on('click', function(e) {
        e.preventDefault();
        abrirModalNuevoMensaje();
    });
    
    // Búsqueda en modal
    $('#buscar-usuarios').on('input', function() {
        const termino = $(this).val().toLowerCase();
        filtrarUsuariosDisponibles(termino);
    });
    
    // Selección de usuario en modal
    $(document).on('click', '.usuario-disponible-item', function() {
        const usuarioId = $(this).data('usuario-id');
        const usuarioNombre = $(this).find('.usuario-disponible-nombre').text();
        crearNuevaConversacion(usuarioId, usuarioNombre);
    });
    }

    /**
     * Cargar datos iniciales
     */
async function loadInitialData() {
    try {
        await Promise.all([
            cargarConversaciones(),
            cargarUsuariosOnline()
        ]);
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
        }
    }

    /**
     * Cargar conversaciones
     */
function cargarConversaciones() {
    return $.get('<?= base_url('mensajeria/getConversaciones') ?>')
        .done(function(response) {
            if (response.success) {
                conversaciones = response.data || [];
                mostrarConversaciones(conversaciones);
            } else {
                mostrarError('Error al cargar conversaciones: ' + response.message);
            }
        })
        .fail(function() {
            mostrarError('Error de conexión al cargar conversaciones');
        });
}

/**
 * Mostrar conversaciones
 */
function mostrarConversaciones(conversaciones) {
    const container = $('#lista-conversaciones');
    
    if (conversaciones.length === 0) {
        container.html(`
                <div class="text-center p-4">
                <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                <h5 class="text-muted mb-3">No tienes conversaciones</h5>
                <p class="text-muted mb-4">Comienza una nueva conversación</p>
                <a href="#" class="btn btn-primary" id="btn-nuevo-mensaje-empty">
                    <i class="fas fa-plus me-2"></i>Nuevo Mensaje
                </a>
                </div>
        `);
            return;
        }

    let html = '';
    conversaciones.forEach(function(conv) {
        const ultimoMensaje = conv.ultimo_mensaje || 'Sin mensajes';
        const horaUltimoMensaje = conv.fecha_ultimo_mensaje ? 
            new Date(conv.fecha_ultimo_mensaje).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'}) : '';
        
        // Truncar mensaje largo
        const mensajeTruncado = ultimoMensaje.length > 50 ? 
            ultimoMensaje.substring(0, 50) + '...' : ultimoMensaje;
        
        html += `
            <div class="conversacion-item" data-conversacion-id="${conv.usuario_id}" onclick="abrirConversacion(${conv.usuario_id})">
                <div class="d-flex align-items-center">
                    <div class="conversacion-avatar me-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1 fw-bold">${conv.nombre_completo}</h6>
                            <small class="text-muted">${horaUltimoMensaje}</small>
                        </div>
                        <p class="mb-0 text-muted small">${mensajeTruncado}</p>
                </div>
                    ${conv.mensajes_no_leidos > 0 ? `<span class="badge bg-danger">${conv.mensajes_no_leidos}</span>` : ''}
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

/**
 * Filtrar conversaciones
 */
function filtrarConversaciones(termino) {
    clearTimeout(timeoutBusqueda);
    timeoutBusqueda = setTimeout(() => {
        const conversacionesFiltradas = conversaciones.filter(conv => 
            conv.nombre_completo.toLowerCase().includes(termino)
        );
        mostrarConversaciones(conversacionesFiltradas);
    }, 300);
}

/**
 * Abrir conversación
 */
function abrirConversacion(usuarioId) {
    conversacionActual = usuarioId;
    
            // Actualizar UI
    $('.conversacion-item').removeClass('active');
    $(`.conversacion-item[data-conversacion-id="${usuarioId}"]`).addClass('active');
    
    // Ocultar sidebar en móviles
    $('.col-md-4').removeClass('show');
    $('#mobile-overlay').removeClass('show');

            // Mostrar área de chat
    $('#header-chat').show();
    $('#area-escritura').show();
    $('#destinatario-id').val(usuarioId);
    
    // Cargar mensajes de la conversación
    cargarMensajesConversacion(usuarioId);

            // Actualizar header
    const conversacion = conversaciones.find(c => c.usuario_id == usuarioId);
    if (conversacion) {
        $('#nombre-destinatario').text(conversacion.nombre_completo);
        $('#estado-destinatario').html('<i class="fas fa-circle text-success" style="font-size: 8px;"></i> En línea');
        }
    }

    /**
 * Cargar mensajes de conversación
 */
function cargarMensajesConversacion(usuarioId) {
    $('#area-mensajes').html(`
        <div class="text-center p-3">
            <i class="fas fa-spinner fa-spin text-primary"></i>
            <span class="ms-2 text-muted">Cargando mensajes...</span>
        </div>
    `);
    
    $.get(`<?= base_url('mensajeria/getMensajes') ?>/${usuarioId}`)
        .done(function(response) {
            if (response.success) {
                mostrarMensajes(response.data);
            } else {
                mostrarError('Error al cargar mensajes: ' + response.message);
            }
        })
        .fail(function() {
            mostrarError('Error de conexión al cargar mensajes');
        });
}

/**
 * Mostrar mensajes
 */
function mostrarMensajes(mensajes) {
    const container = $('#area-mensajes');
    const usuarioActual = currentUserId;
    
    if (mensajes.length === 0) {
        container.html(`
            <div class="text-center p-5">
                <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted mb-3">No hay mensajes</h5>
                <p class="text-muted">Envía el primer mensaje para comenzar</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    let fechaAnterior = '';
    
    mensajes.forEach(function(mensaje) {
        const fechaMensaje = new Date(mensaje.fecha_envio).toLocaleDateString('es-ES');
        const horaMensaje = new Date(mensaje.fecha_envio).toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'});
        
        // Mostrar fecha si es diferente a la anterior
        if (fechaMensaje !== fechaAnterior) {
            html += `<div class="fecha-separador"><span>${fechaMensaje}</span></div>`;
            fechaAnterior = fechaMensaje;
        }
        
        const esPropio = mensaje.remitente_id == usuarioActual;
        const claseBubble = esPropio ? 'mensaje-propio' : 'mensaje-recibido';
        
        html += `
            <div class="d-flex ${esPropio ? 'justify-content-end' : 'justify-content-start'} mb-2">
                <div class="mensaje-bubble ${claseBubble}">
                    <div>${mensaje.contenido}</div>
                    <div class="mensaje-hora">${horaMensaje}</div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
    
    // Scroll al final
    container.scrollTop(container[0].scrollHeight);
    }

    /**
     * Enviar mensaje
     */
function enviarMensaje() {
    const texto = $('#mensaje-texto').val().trim();
    const destinatarioId = $('#destinatario-id').val();
    
    if (!texto || !destinatarioId) {
            return;
        }

    const $btnEnviar = $('#btn-enviar');
    const iconoOriginal = $btnEnviar.html();
    
    $btnEnviar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    // Obtener el token CSRF
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    
    $.ajax({
        url: '<?= base_url('mensajeria/enviarMensaje') ?>',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            destinatario_id: destinatarioId,
            contenido: texto,
            tipo: 'normal'
        },
        success: function(response) {
            if (response.success) {
                $('#mensaje-texto').val('');
                // Recargar mensajes
                cargarMensajesConversacion(destinatarioId);
                // Actualizar lista de conversaciones
                cargarConversaciones();
                
                // Enviar por WebSocket
                if (socket) {
                    socket.emit('mensaje_enviado', {
                        conversacionId: destinatarioId,
                        mensaje: texto,
                        usuarioId: currentUserId
                    });
                }
            } else {
                mostrarError(response.message || 'Error al enviar mensaje');
            }
        },
        error: function() {
            mostrarError('Error de conexión');
        },
        complete: function() {
            $btnEnviar.prop('disabled', false).html(iconoOriginal);
        }
    });
}

/**
 * Cargar usuarios online
 */
function cargarUsuariosOnline() {
    return $.get('<?= base_url('mensajeria/getUsuariosOnline') ?>')
        .done(function(response) {
            if (response.success) {
                updateUsuariosOnline(response.usuarios || []);
            }
        })
        .fail(function() {
            console.error('Error cargando usuarios online');
        });
}

/**
 * Actualizar usuarios online
 */
function updateUsuariosOnline(usuarios) {
    const container = $('#usuarios-online');
    const contador = $('#contador-online');
    
    // Actualizar variable global
    onlineUsers = usuarios;
    
    contador.text(usuarios.length);
    
    if (usuarios.length === 0) {
        container.html('<div class="text-center text-muted"><small>No hay usuarios online</small></div>');
        return;
    }
    
    let html = '';
    usuarios.forEach(function(usuario) {
        html += `
            <div class="usuario-online-item">
                <div class="usuario-online-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="usuario-online-nombre">${usuario.nombre}</div>
                <div class="usuario-online-status"></div>
            </div>
        `;
    });
    
    container.html(html);
}

/**
 * Manejar mensaje recibido
 */
function handleMensajeRecibido(data) {
    if (data.conversacionId == conversacionActual) {
        // Recargar mensajes si es la conversación actual
        cargarMensajesConversacion(conversacionActual);
    }
    
    // Actualizar lista de conversaciones
    cargarConversaciones();
    
    // Mostrar notificación
    if (data.conversacionId != conversacionActual) {
        showNotification('Nuevo mensaje', data.mensaje);
        }
    }

    /**
 * Manejar usuario escribiendo
 */
function handleUsuarioEscribiendo(data) {
    if (data.conversacionId == conversacionActual) {
        // TODO: Mostrar indicador de escritura
        console.log('Usuario escribiendo:', data.usuarioId);
        }
    }

    /**
 * Manejar usuario dejó de escribir
 */
function handleUsuarioDejoEscribir(data) {
    if (data.conversacionId == conversacionActual) {
        // TODO: Ocultar indicador de escritura
        console.log('Usuario dejó de escribir:', data.usuarioId);
    }
}

/**
 * Actualizar estado de conexión
 */
function updateConnectionStatus(message, type) {
    // TODO: Implementar indicador de estado
    console.log(`Estado: ${message} (${type})`);
}

/**
 * Mostrar error
 */
function mostrarError(mensaje) {
    Swal.fire({
        title: 'Error',
        text: mensaje,
        icon: 'error',
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc3545'
    });
}

/**
 * Mostrar información
 */
function showInfo(mensaje) {
    Swal.fire({
        title: 'Información',
        text: mensaje,
        icon: 'info',
        confirmButtonText: 'OK'
        });
    }

    /**
 * Mostrar notificación
 */
function showNotification(titulo, mensaje) {
    // Usar SweetAlert2 para notificaciones
    Swal.fire({
        title: titulo,
        text: mensaje,
        icon: 'info',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}

/**
 * Abrir modal de nuevo mensaje
 */
function abrirModalNuevoMensaje() {
    $('#modalNuevoMensaje').modal('show');
    cargarUsuariosDisponibles();
}

/**
 * Cargar usuarios disponibles
 */
function cargarUsuariosDisponibles() {
    const container = $('#usuarios-disponibles');
    
    container.html(`
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted mt-2">Cargando usuarios...</p>
        </div>
    `);
    
    $.get('<?= base_url('usuarios/getUsuariosActivos') ?>')
        .done(function(response) {
            if (response.success) {
                mostrarUsuariosDisponibles(response.data);
            } else {
                container.html(`
                    <div class="text-center p-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <p class="text-muted">Error al cargar usuarios</p>
                    </div>
                `);
            }
        })
        .fail(function() {
            container.html(`
                <div class="text-center p-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                    <p class="text-muted">Error de conexión</p>
                </div>
            `);
        });
}

/**
 * Mostrar usuarios disponibles
 */
function mostrarUsuariosDisponibles(usuarios) {
    const container = $('#usuarios-disponibles');
    
    if (usuarios.length === 0) {
        container.html(`
            <div class="text-center p-4">
                <i class="fas fa-users fa-2x text-muted mb-3"></i>
                <p class="text-muted">No hay usuarios disponibles</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    usuarios.forEach(function(usuario) {
        // Verificar si el usuario está online
        const estaOnline = onlineUsers.some(u => u.id === usuario.idusuario);
        const estadoClase = estaOnline ? 'online' : 'offline';
        const estadoTexto = estaOnline ? 'En línea' : 'Desconectado';
        const ultimaConexion = estaOnline ? 'Ahora' : 'Hace 2 horas'; // TODO: Implementar lógica real
        
        html += `
            <div class="usuario-disponible-item" data-usuario-id="${usuario.idusuario}">
                <div class="usuario-disponible-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="usuario-disponible-info">
                    <div class="usuario-disponible-nombre">${usuario.nombres}</div>
                    <div class="usuario-disponible-email">${usuario.email}</div>
                    <div class="usuario-disponible-estado">
                        <div class="estado-indicator ${estadoClase}"></div>
                        <span class="estado-${estadoClase}">${estadoTexto}</span>
                        <span class="usuario-disponible-ultima-conexion">• ${ultimaConexion}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

/**
 * Filtrar usuarios disponibles
 */
function filtrarUsuariosDisponibles(termino) {
    $('.usuario-disponible-item').each(function() {
        const nombre = $(this).find('.usuario-disponible-nombre').text().toLowerCase();
        const email = $(this).find('.usuario-disponible-email').text().toLowerCase();
        
        if (nombre.includes(termino) || email.includes(termino)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

/**
 * Crear nueva conversación
 */
function crearNuevaConversacion(usuarioId, usuarioNombre) {
    // Cerrar modal
    $('#modalNuevoMensaje').modal('hide');
    
    // Crear conversación en el backend
    $.post('<?= base_url('mensajeria/crearConversacion') ?>', {
        usuario_id: usuarioId
    })
    .done(function(response) {
        if (response.success) {
            // Abrir la nueva conversación
            abrirConversacion(usuarioId);
            
            // Mostrar mensaje de éxito
            showNotification('Nueva conversación', `Conversación iniciada con ${usuarioNombre}`);
        } else {
            mostrarError('Error al crear conversación: ' + response.message);
        }
    })
    .fail(function() {
        mostrarError('Error de conexión al crear conversación');
    });
}

// Auto-refresh cada 30 segundos
setInterval(function() {
    if (conversacionActual) {
        cargarMensajesConversacion(conversacionActual);
    }
    cargarConversaciones();
    cargarUsuariosOnline();
}, 30000);