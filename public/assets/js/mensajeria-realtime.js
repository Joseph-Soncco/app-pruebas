/**
 * Sistema de Mensajería en Tiempo Real
 * Integración completa con WebSockets para chat como WhatsApp
 */

class MensajeriaRealtime {
    constructor() {
        this.socket = null;
        this.conversacionActual = null;
        this.usuarioActual = null;
        this.conversaciones = new Map();
        this.mensajes = new Map();
        this.usuariosOnline = new Map();
        this.isConnected = false;
        
        this.init();
    }
    
    async init() {
        console.log('🚀 Inicializando sistema de mensajería...');
        
        // Obtener datos del usuario actual
        await this.obtenerUsuarioActual();
        
        // Conectar WebSocket
        await this.conectarWebSocket();
        
        // Cargar datos iniciales
        await this.cargarDatosIniciales();
        
        // Configurar event listeners
        this.configurarEventListeners();
        
        console.log('✅ Sistema de mensajería inicializado correctamente');
    }
    
    async obtenerUsuarioActual() {
        try {
            // Obtener datos del usuario desde la sesión PHP
            const response = await fetch('/mensajeria/test');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error obteniendo datos del usuario');
            }
            
            this.usuarioActual = {
                id: data.usuario_actual.id,
                nombre: data.usuario_actual.nombre,
                email: data.usuario_actual.email,
                rol: data.usuario_actual.rol
            };
            
            console.log('👤 Usuario actual:', this.usuarioActual);
            
            // Actualizar la interfaz con los datos del usuario
            this.actualizarInterfazUsuario();
            
        } catch (error) {
            console.error('Error obteniendo usuario actual:', error);
            this.mostrarNotificacion('Error de autenticación. Redirigiendo al login...', 'error');
            
            // Redirigir al login después de 2 segundos
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        }
    }
    
    actualizarInterfazUsuario() {
        // Actualizar header del usuario
        const nombreUsuario = document.querySelector('.flex-grow-1 h6');
        const emailUsuario = document.querySelector('.flex-grow-1 small');
        
        if (nombreUsuario) {
            nombreUsuario.textContent = this.usuarioActual.nombre;
        }
        if (emailUsuario) {
            emailUsuario.textContent = this.usuarioActual.email;
        }
    }
    
    async conectarWebSocket() {
        try {
            // Obtener URL del WebSocket
            const response = await fetch('/mensajeria/getWebSocketUrl');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error('No se pudo obtener la URL del WebSocket');
            }
            
            const wsUrl = data.data.url;
            console.log('🔌 Conectando a WebSocket:', wsUrl);
            
            // Crear token simple para autenticación
            const token = this.generarTokenSimple();
            
            // Conectar con Socket.IO
            this.socket = io(wsUrl, {
                auth: {
                    token: token
                },
                transports: ['websocket', 'polling']
            });
            
            this.configurarEventosSocket();
            
        } catch (error) {
            console.error('❌ Error conectando WebSocket:', error);
            this.mostrarErrorConexion();
        }
    }
    
    generarTokenSimple() {
        // Token simple para desarrollo - en producción usar JWT real
        return btoa(JSON.stringify({
            userId: this.usuarioActual.id,
            timestamp: Date.now()
        }));
    }
    
    configurarEventosSocket() {
        this.socket.on('connect', () => {
            console.log('✅ Conectado al servidor WebSocket');
            this.isConnected = true;
            this.actualizarEstadoConexion(true);
        });
        
        this.socket.on('disconnect', () => {
            console.log('❌ Desconectado del servidor WebSocket');
            this.isConnected = false;
            this.actualizarEstadoConexion(false);
        });
        
        this.socket.on('connect_error', (error) => {
            console.error('❌ Error de conexión:', error);
            this.mostrarErrorConexion();
        });
        
        // Eventos de mensajería
        this.socket.on('new-message', (data) => {
            console.log('💬 Nuevo mensaje recibido:', data);
            this.procesarNuevoMensaje(data);
        });
        
        this.socket.on('users-online', (users) => {
            console.log('👥 Usuarios online:', users);
            this.actualizarUsuariosOnline(users);
        });
        
        this.socket.on('user-online', (data) => {
            console.log('🟢 Usuario conectado:', data);
            this.agregarUsuarioOnline(data);
        });
        
        this.socket.on('user-offline', (data) => {
            console.log('🔴 Usuario desconectado:', data);
            this.removerUsuarioOnline(data.userId);
        });
        
        this.socket.on('user-typing', (data) => {
            this.mostrarUsuarioEscribiendo(data);
        });
        
        this.socket.on('user-stopped-typing', (data) => {
            this.ocultarUsuarioEscribiendo(data.userId);
        });
        
        this.socket.on('error', (error) => {
            console.error('❌ Error del servidor:', error);
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        });
    }
    
    async cargarDatosIniciales() {
        try {
            // Cargar conversaciones
            await this.cargarConversaciones();
            
            // Cargar usuarios disponibles
            await this.cargarUsuariosDisponibles();
            
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
        }
    }
    
    async cargarConversaciones() {
        try {
            const response = await fetch('/mensajeria/getConversaciones');
            const data = await response.json();
            
            if (data.success) {
                this.conversaciones.clear();
                data.data.forEach(conv => {
                    this.conversaciones.set(conv.id, conv);
                });
                this.mostrarConversaciones(data.data);
            }
        } catch (error) {
            console.error('Error cargando conversaciones:', error);
        }
    }
    
    async cargarUsuariosDisponibles() {
        try {
            const response = await fetch('/mensajeria/getUsuarios');
            const data = await response.json();
            
            if (data.success) {
                this.mostrarUsuariosDisponibles(data.data);
            }
        } catch (error) {
            console.error('Error cargando usuarios:', error);
        }
    }
    
    async cargarMensajes(conversacionId) {
        try {
            const response = await fetch(`/mensajeria/getMensajes/${conversacionId}`);
            const data = await response.json();
            
            if (data.success) {
                this.mensajes.set(conversacionId, data.data);
                this.mostrarMensajes(data.data);
                
                // Unirse a la conversación en WebSocket
                if (this.socket && this.isConnected) {
                    this.socket.emit('join-conversation', conversacionId);
                }
            }
        } catch (error) {
            console.error('Error cargando mensajes:', error);
        }
    }
    
    async enviarMensaje(contenido, destinatarioId = null, conversacionId = null) {
        if (!contenido.trim()) return;
        
        try {
            const formData = new FormData();
            formData.append('contenido', contenido);
            if (destinatarioId) formData.append('destinatario_id', destinatarioId);
            if (conversacionId) formData.append('conversacion_id', conversacionId);
            
            const response = await fetch('/mensajeria/enviarMensaje', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Agregar mensaje a la interfaz
                this.agregarMensaje(data.data, true);
                
                // Enviar también via WebSocket para tiempo real
                if (this.socket && this.isConnected && data.data.conversacion_id) {
                    this.socket.emit('send-message', {
                        conversationId: data.data.conversacion_id,
                        message: contenido,
                        tipo: 'texto'
                    });
                }
                
                return data.data.conversacion_id;
            } else {
                this.mostrarNotificacion('Error: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error enviando mensaje:', error);
            this.mostrarNotificacion('Error al enviar mensaje', 'error');
        }
    }
    
    procesarNuevoMensaje(data) {
        // Verificar si el mensaje es para la conversación actual
        if (this.conversacionActual && data.conversacion_id === this.conversacionActual.id) {
            this.agregarMensaje(data, false);
        }
        
        // Actualizar lista de conversaciones
        this.actualizarConversacionEnLista(data);
        
        // Mostrar notificación si no estamos en esa conversación
        if (!this.conversacionActual || data.conversacion_id !== this.conversacionActual.id) {
            this.mostrarNotificacionMensaje(data);
        }
    }
    
    actualizarConversacionEnLista(mensaje) {
        const conversacionId = mensaje.conversacion_id;
        const conversacion = this.conversaciones.get(conversacionId);
        
        if (conversacion) {
            conversacion.ultimo_mensaje_texto = mensaje.contenido;
            conversacion.ultimo_mensaje_fecha = mensaje.fecha_envio;
            if (mensaje.usuario_id !== this.usuarioActual.id) {
                conversacion.mensajes_no_leidos = (conversacion.mensajes_no_leidos || 0) + 1;
            }
            this.conversaciones.set(conversacionId, conversacion);
            this.actualizarListaConversaciones();
        }
    }
    
    // Métodos de interfaz
    mostrarConversaciones(conversaciones) {
        const container = document.getElementById('lista-conversaciones');
        if (!container) return;
        
        if (conversaciones.length === 0) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-comments fa-2x text-muted mb-2"></i>
                    <p class="text-muted">No hay conversaciones aún</p>
                    <button class="btn btn-primary btn-sm" onclick="mensajeria.abrirModalNuevoMensaje()">
                        <i class="fas fa-plus me-1"></i>Nuevo Mensaje
                    </button>
                </div>
            `;
            return;
        }
        
        let html = '';
        conversaciones.forEach(conv => {
            const fechaFormateada = this.formatearFecha(conv.ultimo_mensaje_fecha);
            const badgeNoLeidos = conv.mensajes_no_leidos > 0 ? 
                `<span class="badge bg-primary rounded-pill">${conv.mensajes_no_leidos}</span>` : '';
            
            html += `
                <div class="conversation-item p-3 border-bottom" onclick="mensajeria.seleccionarConversacion('${conv.id}')">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${conv.contacto_nombre}</h6>
                            <small class="text-muted">${conv.ultimo_mensaje_texto || 'Sin mensajes'}</small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">${fechaFormateada}</small>
                            ${badgeNoLeidos}
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    mostrarMensajes(mensajes) {
        const container = document.getElementById('area-mensajes');
        if (!container) return;
        
        let html = '';
        mensajes.forEach(mensaje => {
            html += this.crearHTMLMensaje(mensaje);
        });
        
        container.innerHTML = html;
        this.scrollToBottom();
    }
    
    agregarMensaje(mensaje, esPropio) {
        const container = document.getElementById('area-mensajes');
        if (!container) return;
        
        const html = this.crearHTMLMensaje(mensaje);
        container.insertAdjacentHTML('beforeend', html);
        this.scrollToBottom();
    }
    
    crearHTMLMensaje(mensaje) {
        const esPropio = mensaje.es_propio || mensaje.usuario_id === this.usuarioActual.id;
        const claseBubble = esPropio ? 'message-sent' : 'message-received';
        const alineacion = esPropio ? 'justify-content-end' : 'justify-content-start';
        
        return `
            <div class="d-flex ${alineacion} mb-2">
                <div class="message-bubble ${claseBubble} p-2 rounded" style="max-width: 70%;">
                    <div class="fw-bold small">${mensaje.usuario_nombre}</div>
                    <div>${this.escapeHtml(mensaje.contenido)}</div>
                    <small class="opacity-75">${mensaje.tiempo}</small>
                </div>
            </div>
        `;
    }
    
    mostrarUsuariosDisponibles(usuarios) {
        const container = document.getElementById('usuarios-disponibles');
        if (!container) return;
        
        let html = '';
        usuarios.forEach(usuario => {
            const estadoColor = usuario.estado === 'online' ? 'success' : 'secondary';
            const estadoIcono = usuario.estado === 'online' ? 'fa-circle' : 'fa-circle';
            
            html += `
                <div class="conversation-item p-3 border-bottom" onclick="mensajeria.iniciarConversacion(${usuario.id}, '${usuario.nombre}')">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${usuario.nombre}</h6>
                            <small class="text-muted">${usuario.cargo}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${estadoColor}">
                                <i class="fas ${estadoIcono}" style="font-size: 8px;"></i> ${usuario.estado}
                            </span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    actualizarUsuariosOnline(usuarios) {
        this.usuariosOnline.clear();
        usuarios.forEach(user => {
            this.usuariosOnline.set(user.userId, user);
        });
        
        // Actualizar contador
        const contador = document.getElementById('contador-online');
        if (contador) {
            contador.textContent = usuarios.length;
        }
        
        // Actualizar lista de usuarios online
        this.actualizarListaUsuariosOnline();
    }
    
    actualizarListaUsuariosOnline() {
        const container = document.getElementById('usuarios-online');
        if (!container) return;
        
        if (this.usuariosOnline.size === 0) {
            container.innerHTML = '<div class="text-center text-muted"><small>No hay usuarios online</small></div>';
            return;
        }
        
        let html = '';
        this.usuariosOnline.forEach(user => {
            html += `
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                    <small>${user.userData.nombres} ${user.userData.apellidos}</small>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    // Métodos de navegación
    seleccionarConversacion(conversacionId) {
        const conversacion = this.conversaciones.get(conversacionId);
        if (!conversacion) return;
        
        this.conversacionActual = conversacion;
        
        // Actualizar header del chat
        this.actualizarHeaderChat(conversacion);
        
        // Mostrar área de escritura
        document.getElementById('area-escritura').style.display = 'block';
        
        // Cargar mensajes
        this.cargarMensajes(conversacionId);
        
        // Marcar como leídos
        this.marcarMensajesLeidos(conversacionId);
    }
    
    iniciarConversacion(usuarioId, nombreUsuario) {
        // Crear nueva conversación
        this.crearNuevaConversacion(usuarioId, nombreUsuario);
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoMensaje'));
        if (modal) modal.hide();
    }
    
    async crearNuevaConversacion(usuarioId, nombreUsuario) {
        try {
            const formData = new FormData();
            formData.append('usuario_id', usuarioId);
            
            const response = await fetch('/mensajeria/crearConversacion', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Crear objeto de conversación
                const conversacion = {
                    id: data.data.id,
                    contacto_nombre: nombreUsuario,
                    contacto_usuario: 'usuario',
                    ultimo_mensaje_texto: '',
                    ultimo_mensaje_fecha: new Date().toISOString(),
                    mensajes_no_leidos: 0,
                    otro_usuario_id: usuarioId
                };
                
                this.conversaciones.set(conversacion.id, conversacion);
                this.seleccionarConversacion(conversacion.id);
                
                // Actualizar lista de conversaciones
                this.actualizarListaConversaciones();
            }
        } catch (error) {
            console.error('Error creando conversación:', error);
        }
    }
    
    actualizarHeaderChat(conversacion) {
        const header = document.getElementById('header-chat');
        const nombreDestinatario = document.getElementById('nombre-destinatario');
        const estadoDestinatario = document.getElementById('estado-destinatario');
        
        if (header) header.style.display = 'block';
        if (nombreDestinatario) nombreDestinatario.textContent = conversacion.contacto_nombre;
        
        // Verificar si el usuario está online
        const usuarioOnline = this.usuariosOnline.get(conversacion.otro_usuario_id);
        if (estadoDestinatario) {
            if (usuarioOnline) {
                estadoDestinatario.innerHTML = '<i class="fas fa-circle text-success" style="font-size: 8px;"></i> En línea';
            } else {
                estadoDestinatario.innerHTML = '<i class="fas fa-circle text-secondary" style="font-size: 8px;"></i> Desconectado';
            }
        }
    }
    
    async marcarMensajesLeidos(conversacionId) {
        try {
            await fetch('/mensajeria/marcarLeido', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversacion_id: conversacionId
                })
            });
            
            // Actualizar contador local
            const conversacion = this.conversaciones.get(conversacionId);
            if (conversacion) {
                conversacion.mensajes_no_leidos = 0;
                this.conversaciones.set(conversacionId, conversacion);
                this.actualizarListaConversaciones();
            }
        } catch (error) {
            console.error('Error marcando mensajes como leídos:', error);
        }
    }
    
    // Métodos de utilidad
    formatearFecha(fecha) {
        if (!fecha) return '';
        
        const ahora = new Date();
        const fechaMensaje = new Date(fecha);
        const diffMs = ahora - fechaMensaje;
        const diffMinutos = Math.floor(diffMs / (1000 * 60));
        const diffHoras = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDias = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        
        if (diffMinutos < 1) return 'Ahora';
        if (diffMinutos < 60) return `${diffMinutos}m`;
        if (diffHoras < 24) return `${diffHoras}h`;
        if (diffDias < 7) return `${diffDias}d`;
        
        return fechaMensaje.toLocaleDateString();
    }
    
    scrollToBottom() {
        const container = document.getElementById('area-mensajes');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    actualizarEstadoConexion(conectado) {
        const indicador = document.getElementById('indicador-conexion');
        if (indicador) {
            indicador.className = conectado ? 'fas fa-circle text-success' : 'fas fa-circle text-danger';
            indicador.title = conectado ? 'Conectado' : 'Desconectado';
        }
    }
    
    mostrarErrorConexion() {
        this.mostrarNotificacion('Error de conexión con el servidor', 'error');
    }
    
    mostrarNotificacionMensaje(mensaje) {
        const titulo = `Nuevo mensaje de ${mensaje.usuario_nombre}`;
        this.mostrarNotificacion(titulo, 'info');
    }
    
    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear notificación simple
        const notification = document.createElement('div');
        notification.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
    
    abrirModalNuevoMensaje() {
        const modal = new bootstrap.Modal(document.getElementById('modalNuevoMensaje'));
        modal.show();
    }
    
    actualizarListaConversaciones() {
        const conversaciones = Array.from(this.conversaciones.values());
        this.mostrarConversaciones(conversaciones);
    }
    
    agregarUsuarioOnline(data) {
        this.usuariosOnline.set(data.userId, data);
        this.actualizarListaUsuariosOnline();
    }
    
    removerUsuarioOnline(userId) {
        this.usuariosOnline.delete(userId);
        this.actualizarListaUsuariosOnline();
    }
    
    mostrarUsuarioEscribiendo(data) {
        // Implementar indicador de "escribiendo"
        console.log(`${data.userData.nombres} está escribiendo...`);
    }
    
    ocultarUsuarioEscribiendo(userId) {
        // Ocultar indicador de "escribiendo"
        console.log(`Usuario ${userId} dejó de escribir`);
    }
    
    configurarEventListeners() {
        // Formulario de envío de mensajes
        const formEnviar = document.getElementById('form-enviar-mensaje');
        if (formEnviar) {
            formEnviar.addEventListener('submit', (e) => {
                e.preventDefault();
                const input = document.getElementById('mensaje-texto');
                const destinatarioId = document.getElementById('destinatario-id').value;
                
                if (input.value.trim()) {
                    this.enviarMensaje(input.value.trim(), destinatarioId, this.conversacionActual?.id);
                    input.value = '';
                }
            });
        }
        
        // Botón nuevo mensaje
        const btnNuevoMensaje = document.getElementById('btn-nuevo-mensaje-main');
        if (btnNuevoMensaje) {
            btnNuevoMensaje.addEventListener('click', () => {
                this.abrirModalNuevoMensaje();
            });
        }
        
        // Búsqueda de conversaciones
        const buscarConversaciones = document.getElementById('buscar-conversaciones');
        if (buscarConversaciones) {
            buscarConversaciones.addEventListener('input', (e) => {
                this.buscarConversaciones(e.target.value);
            });
        }
        
        // Búsqueda de usuarios
        const buscarUsuarios = document.getElementById('buscar-usuarios');
        if (buscarUsuarios) {
            buscarUsuarios.addEventListener('input', (e) => {
                this.buscarUsuarios(e.target.value);
            });
        }
    }
    
    buscarConversaciones(termino) {
        const conversaciones = Array.from(this.conversaciones.values());
        const filtradas = conversaciones.filter(conv => 
            conv.contacto_nombre.toLowerCase().includes(termino.toLowerCase()) ||
            conv.ultimo_mensaje_texto.toLowerCase().includes(termino.toLowerCase())
        );
        this.mostrarConversaciones(filtradas);
    }
    
    buscarUsuarios(termino) {
        // Implementar búsqueda de usuarios
        console.log('Buscando usuarios:', termino);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.mensajeria = new MensajeriaRealtime();
});
