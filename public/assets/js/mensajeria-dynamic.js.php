<?php
// Generar JavaScript dinámico para mensajería
header('Content-Type: application/javascript');

// Verificar que CodeIgniter esté disponible
if (!function_exists('base_url')) {
    // Si no está disponible, definir una función básica
    function base_url($path = '') {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = $protocol . '://' . $host . '/';
        return $base . ltrim($path, '/');
    }
}

// Obtener la URL base
$baseUrl = base_url();
?>

// Configuración de mensajería
const MENSAJERIA_CONFIG = {
    baseUrl: '<?= $baseUrl ?>',
    endpoints: {
        conversaciones: '<?= $baseUrl ?>mensajeria/getConversaciones',
        usuarios: '<?= $baseUrl ?>mensajeria/getUsuarios',
        enviarMensaje: '<?= $baseUrl ?>mensajeria/enviarMensaje',
        marcarLeido: '<?= $baseUrl ?>mensajeria/marcarLeido'
    }
};

// Variables globales
let conversaciones = [];
let usuariosOnline = [];
let conversacionActual = null;
let socket = null;

/**
 * Inicializar mensajería
 */
function inicializarMensajeria() {
    console.log('Inicializando mensajería...');
    
    try {
        // Cargar datos iniciales
        Promise.all([
            cargarConversaciones(),
            cargarUsuariosOnline()
        ]);
        
        // Inicializar WebSocket para tiempo real
        inicializarWebSocket();
        
    } catch (error) {
        console.error('Error inicializando mensajería:', error);
    }
}

/**
 * Cargar conversaciones
 */
function cargarConversaciones() {
    return $.get(MENSAJERIA_CONFIG.endpoints.conversaciones)
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
 * Cargar usuarios online
 */
function cargarUsuariosOnline() {
    return $.get(MENSAJERIA_CONFIG.endpoints.usuarios)
        .done(function(response) {
            if (response.success) {
                usuariosOnline = response.data || [];
                mostrarUsuariosOnline(usuariosOnline);
            } else {
                mostrarError('Error al cargar usuarios: ' + response.message);
            }
        })
        .fail(function() {
            mostrarError('Error de conexión al cargar usuarios');
        });
}

/**
 * Mostrar conversaciones
 */
function mostrarConversaciones(conversaciones) {
    const container = $('#conversaciones-container');
    container.empty();
    
    if (conversaciones.length === 0) {
        container.html('<div class="text-center text-muted">No hay conversaciones</div>');
        return;
    }
    
    conversaciones.forEach(function(conversacion) {
        const item = $(`
            <div class="conversacion-item" data-id="${conversacion.id}">
                <div class="conversacion-avatar">
                    <img src="${MENSAJERIA_CONFIG.baseUrl}assets/img/profile.jpg" alt="Avatar">
                </div>
                <div class="conversacion-info">
                    <div class="conversacion-nombre">${conversacion.nombre}</div>
                    <div class="conversacion-ultimo">${conversacion.ultimo_mensaje || 'Sin mensajes'}</div>
                </div>
                <div class="conversacion-tiempo">
                    ${conversacion.tiempo || ''}
                </div>
            </div>
        `);
        
        item.click(function() {
            seleccionarConversacion(conversacion.id);
        });
        
        container.append(item);
    });
}

/**
 * Mostrar usuarios online
 */
function mostrarUsuariosOnline(usuarios) {
    const container = $('#usuarios-online-container');
    container.empty();
    
    if (usuarios.length === 0) {
        container.html('<div class="text-center text-muted">No hay usuarios online</div>');
        return;
    }
    
    usuarios.forEach(function(usuario) {
        const item = $(`
            <div class="usuario-item" data-id="${usuario.id}">
                <div class="usuario-avatar">
                    <img src="${MENSAJERIA_CONFIG.baseUrl}assets/img/profile.jpg" alt="Avatar">
                    <span class="status-online"></span>
                </div>
                <div class="usuario-info">
                    <div class="usuario-nombre">${usuario.nombre}</div>
                    <div class="usuario-cargo">${usuario.cargo || ''}</div>
                </div>
            </div>
        `);
        
        item.click(function() {
            iniciarConversacion(usuario.id);
        });
        
        container.append(item);
    });
}

/**
 * Seleccionar conversación
 */
function seleccionarConversacion(conversacionId) {
    conversacionActual = conversacionId;
    
    // Actualizar UI
    $('.conversacion-item').removeClass('active');
    $(`.conversacion-item[data-id="${conversacionId}"]`).addClass('active');
    
    // Cargar mensajes de la conversación
    cargarMensajes(conversacionId);
}

/**
 * Iniciar nueva conversación
 */
function iniciarConversacion(usuarioId) {
    // Crear nueva conversación
    $.post(MENSAJERIA_CONFIG.endpoints.conversaciones, {
        usuario_id: usuarioId
    })
    .done(function(response) {
        if (response.success) {
            conversacionActual = response.data.id;
            cargarMensajes(conversacionActual);
        }
    })
    .fail(function() {
        mostrarError('Error al crear conversación');
    });
}

/**
 * Cargar mensajes de una conversación
 */
function cargarMensajes(conversacionId) {
    $.get(`${MENSAJERIA_CONFIG.endpoints.conversaciones}/${conversacionId}/mensajes`)
        .done(function(response) {
            if (response.success) {
                mostrarMensajes(response.data);
            }
        })
        .fail(function() {
            mostrarError('Error al cargar mensajes');
        });
}

/**
 * Mostrar mensajes
 */
function mostrarMensajes(mensajes) {
    const container = $('#mensajes-container');
    container.empty();
    
    mensajes.forEach(function(mensaje) {
        const mensajeHtml = $(`
            <div class="mensaje ${mensaje.es_propio ? 'mensaje-propio' : 'mensaje-otro'}">
                <div class="mensaje-contenido">${mensaje.contenido}</div>
                <div class="mensaje-tiempo">${mensaje.tiempo}</div>
            </div>
        `);
        
        container.append(mensajeHtml);
    });
    
    // Scroll al final
    container.scrollTop(container[0].scrollHeight);
}

/**
 * Enviar mensaje
 */
function enviarMensaje() {
    const input = $('#mensaje-input');
    const contenido = input.val().trim();
    
    if (!contenido || !conversacionActual) {
        return;
    }
    
    $.post(MENSAJERIA_CONFIG.endpoints.enviarMensaje, {
        conversacion_id: conversacionActual,
        contenido: contenido
    })
    .done(function(response) {
        if (response.success) {
            input.val('');
            // El mensaje se agregará via WebSocket
        }
    })
    .fail(function() {
        mostrarError('Error al enviar mensaje');
    });
}

/**
 * Inicializar WebSocket para tiempo real
 */
function inicializarWebSocket() {
    // Obtener URL del WebSocket desde el servidor
    $.get(`${MENSAJERIA_CONFIG.baseUrl}mensajeria/getWebSocketUrl`)
        .done(function(response) {
            if (response.success) {
                const wsUrl = response.data.url;
                socket = new WebSocket(wsUrl);
                
                socket.onopen = function() {
                    console.log('WebSocket conectado');
                };
                
                socket.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    manejarMensajeWebSocket(data);
                };
                
                socket.onclose = function() {
                    console.log('WebSocket desconectado');
                    // Reconectar después de 3 segundos
                    setTimeout(inicializarWebSocket, 3000);
                };
                
                socket.onerror = function(error) {
                    console.error('Error WebSocket:', error);
                };
            }
        })
        .fail(function() {
            console.log('WebSocket no disponible, usando polling');
            // Usar polling como fallback
            setInterval(function() {
                cargarConversaciones();
                cargarUsuariosOnline();
            }, 5000);
        });
}

/**
 * Manejar mensajes del WebSocket
 */
function manejarMensajeWebSocket(data) {
    switch (data.tipo) {
        case 'nuevo_mensaje':
            if (data.conversacion_id === conversacionActual) {
                mostrarMensajes([data.mensaje]);
            }
            break;
        case 'usuario_online':
            actualizarUsuarioOnline(data.usuario);
            break;
        case 'usuario_offline':
            actualizarUsuarioOffline(data.usuario_id);
            break;
    }
}

/**
 * Actualizar usuario online
 */
function actualizarUsuarioOnline(usuario) {
    const container = $('#usuarios-online-container');
    const existing = container.find(`[data-id="${usuario.id}"]`);
    
    if (existing.length === 0) {
        const item = $(`
            <div class="usuario-item" data-id="${usuario.id}">
                <div class="usuario-avatar">
                    <img src="${MENSAJERIA_CONFIG.baseUrl}assets/img/profile.jpg" alt="Avatar">
                    <span class="status-online"></span>
                </div>
                <div class="usuario-info">
                    <div class="usuario-nombre">${usuario.nombre}</div>
                    <div class="usuario-cargo">${usuario.cargo || ''}</div>
                </div>
            </div>
        `);
        
        item.click(function() {
            iniciarConversacion(usuario.id);
        });
        
        container.append(item);
    }
}

/**
 * Actualizar usuario offline
 */
function actualizarUsuarioOffline(usuarioId) {
    $(`#usuarios-online-container [data-id="${usuarioId}"]`).remove();
}

/**
 * Mostrar error
 */
function mostrarError(mensaje) {
    console.error(mensaje);
    // Aquí podrías mostrar una notificación al usuario
}

// Event listeners
$(document).ready(function() {
    // Enviar mensaje con Enter
    $('#mensaje-input').keypress(function(e) {
        if (e.which === 13) {
            enviarMensaje();
        }
    });
    
    // Botón enviar
    $('#btn-enviar-mensaje').click(function() {
        enviarMensaje();
    });
    
    // Botón nuevo mensaje
    $('#btn-nuevo-mensaje').click(function() {
        // Mostrar modal para seleccionar usuario
        mostrarModalUsuarios();
    });
    
    // Inicializar mensajería
    inicializarMensajeria();
});

/**
 * Mostrar modal de usuarios
 */
function mostrarModalUsuarios() {
    // Implementar modal para seleccionar usuario
    console.log('Mostrar modal de usuarios');
}
