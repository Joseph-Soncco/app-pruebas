// Configuración de mensajería - Versión estática
const MENSAJERIA_CONFIG = {
    baseUrl: window.location.origin + '/',
    endpoints: {
        conversaciones: window.location.origin + '/mensajeria/getConversaciones',
        usuarios: window.location.origin + '/mensajeria/getUsuarios',
        enviarMensaje: window.location.origin + '/mensajeria/enviarMensaje',
        marcarLeido: window.location.origin + '/mensajeria/marcarLeido'
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
    const container = $('#lista-conversaciones');
    container.empty();
    
    if (conversaciones.length === 0) {
        container.html('<div class="text-center text-muted p-4">No hay conversaciones</div>');
        return;
    }
    
    conversaciones.forEach(function(conversacion) {
        const item = $(`
            <div class="conversacion-item p-3 border-bottom" data-id="${conversacion.id}" style="cursor: pointer;">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-3 bg-primary">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${conversacion.nombre || 'Usuario'}</div>
                        <div class="text-muted small">${conversacion.ultimo_mensaje || 'Sin mensajes'}</div>
                    </div>
                    <div class="text-muted small">
                        ${conversacion.tiempo || ''}
                    </div>
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
    const container = $('#usuarios-online');
    container.empty();
    
    if (usuarios.length === 0) {
        container.html('<div class="text-center text-muted small">No hay usuarios online</div>');
        return;
    }
    
    usuarios.forEach(function(usuario) {
        const item = $(`
            <div class="usuario-item p-2 border-bottom" data-id="${usuario.id}" style="cursor: pointer;">
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-2 bg-success position-relative">
                        <i class="fas fa-user text-white"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle" style="width: 8px; height: 8px;"></span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small">${usuario.nombre || 'Usuario'}</div>
                        <div class="text-muted" style="font-size: 0.7em;">${usuario.cargo || ''}</div>
                    </div>
                </div>
            </div>
        `);
        
        item.click(function() {
            iniciarConversacion(usuario.id);
        });
        
        container.append(item);
    });
    
    // Actualizar contador
    $('#contador-online').text(usuarios.length);
}

/**
 * Seleccionar conversación
 */
function seleccionarConversacion(conversacionId) {
    conversacionActual = conversacionId;
    
    // Actualizar UI
    $('.conversacion-item').removeClass('bg-light');
    $(`.conversacion-item[data-id="${conversacionId}"]`).addClass('bg-light');
    
    // Mostrar header del chat
    $('#header-chat').show();
    $('#area-escritura').show();
    
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
    const container = $('#area-mensajes');
    container.empty();
    
    mensajes.forEach(function(mensaje) {
        const mensajeHtml = $(`
            <div class="mensaje mb-3 ${mensaje.es_propio ? 'text-end' : 'text-start'}">
                <div class="d-inline-block p-3 rounded ${mensaje.es_propio ? 'bg-primary text-white' : 'bg-light'}">
                    <div class="mensaje-contenido">${mensaje.contenido}</div>
                    <div class="mensaje-tiempo small ${mensaje.es_propio ? 'text-white-50' : 'text-muted'}">${mensaje.tiempo}</div>
                </div>
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
    const input = $('#mensaje-texto');
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
    // Por ahora usar polling como fallback
    console.log('WebSocket no disponible, usando polling');
    setInterval(function() {
        cargarConversaciones();
        cargarUsuariosOnline();
    }, 5000);
}

/**
 * Mostrar error
 */
function mostrarError(mensaje) {
    console.error(mensaje);
    // Aquí podrías mostrar una notificación al usuario
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje
        });
    }
}

// Event listeners
$(document).ready(function() {
    // Enviar mensaje con Enter
    $('#mensaje-texto').keypress(function(e) {
        if (e.which === 13) {
            enviarMensaje();
        }
    });
    
    // Botón enviar
    $('#btn-enviar').click(function() {
        enviarMensaje();
    });
    
    // Botón nuevo mensaje
    $('#btn-nuevo-mensaje, #btn-nuevo-mensaje-main').click(function() {
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
    // Cargar usuarios disponibles
    $.get(MENSAJERIA_CONFIG.endpoints.usuarios)
        .done(function(response) {
            if (response.success) {
                const usuarios = response.data || [];
                const container = $('#usuarios-disponibles');
                container.empty();
                
                usuarios.forEach(function(usuario) {
                    const item = $(`
                        <div class="usuario-item p-3 border-bottom" data-id="${usuario.id}" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3 bg-primary">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">${usuario.nombre || 'Usuario'}</div>
                                    <div class="text-muted small">${usuario.cargo || ''}</div>
                                </div>
                            </div>
                        </div>
                    `);
                    
                    item.click(function() {
                        iniciarConversacion(usuario.id);
                        $('#modalNuevoMensaje').modal('hide');
                    });
                    
                    container.append(item);
                });
                
                $('#modalNuevoMensaje').modal('show');
            }
        })
        .fail(function() {
            mostrarError('Error al cargar usuarios');
        });
}
