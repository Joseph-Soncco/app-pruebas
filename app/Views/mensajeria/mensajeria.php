<?= $header ?>

<div class="container-fluid h-100">
    <div class="row h-100">
        <!-- Panel izquierdo - Lista de conversaciones -->
        <div class="col-md-4 col-lg-3 p-0 border-end bg-white">
            <div class="d-flex flex-column h-100">
                <!-- Header del usuario -->
                <div class="p-4 border-bottom bg-light">
                    <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3 bg-primary">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold"><?= session('usuario_nombre') ?? 'Usuario' ?></h6>
                            <small class="text-muted"><?= session('usuario_email') ?? 'usuario@sistema.com' ?></small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="btn-configuracion">
                                    <i class="fas fa-cog me-2"></i>Configuración
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" id="btn-nuevo-mensaje">
                                    <i class="fas fa-plus me-2"></i>Nuevo Mensaje
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Barra de búsqueda -->
                <div class="p-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" 
                               placeholder="Buscar conversaciones..." id="buscar-conversaciones">
                    </div>
                </div>

                <!-- Lista de conversaciones -->
                <div class="flex-grow-1 overflow-auto scrollbar-custom panel-conversaciones" id="lista-conversaciones">
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-2">Cargando conversaciones...</p>
                    </div>
                </div>

                <!-- Panel de usuarios online -->
                <div class="border-top p-3">
                    <h6 class="text-muted mb-2">
                        <i class="fas fa-users me-1"></i> Usuarios Online
                        <span class="badge bg-success ms-2" id="contador-online">0</span>
                    </h6>
                    <div class="usuarios-online" id="usuarios-online">
                        <div class="text-center text-muted">
                            <small>Cargando usuarios...</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel derecho - Chat -->
        <div class="col-md-8 col-lg-9 p-0">
            <div class="d-flex flex-column h-100 bg-light">
                <!-- Header del chat -->
                <div class="p-3 border-bottom bg-white" id="header-chat" style="display: none;">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary btn-sm me-3 d-md-none" id="btn-toggle-sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div class="avatar-circle me-3 bg-success">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fw-bold" id="nombre-destinatario">Selecciona una conversación</h6>
                            <small class="text-success" id="estado-destinatario">
                                <i class="fas fa-circle text-success" style="font-size: 8px;"></i> En línea
                            </small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="btn-ver-perfil">
                                    <i class="fas fa-user me-2"></i>Ver Perfil
                                </a></li>
                                <li><a class="dropdown-item" href="#" id="btn-eliminar-conversacion">
                                    <i class="fas fa-trash me-2"></i>Eliminar Conversación
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Área de mensajes -->
                <div class="flex-grow-1 overflow-auto p-3 scrollbar-custom panel-mensajes" id="area-mensajes">
                    <div class="text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                        </div>
                        <h4 class="text-muted mb-3">Selecciona una conversación</h4>
                        <p class="text-muted mb-4">Elige una conversación de la lista para comenzar a chatear</p>
                        <a href="#" class="btn btn-primary btn-lg" id="btn-nuevo-mensaje-main">
                            <i class="fas fa-plus me-2"></i>Nuevo Mensaje
                        </a>
                    </div>
                </div>

                <!-- Área de escritura -->
                <div class="p-3 border-top bg-white area-escritura" id="area-escritura" style="display: none;">
                    <form id="form-enviar-mensaje">
                        <input type="hidden" id="destinatario-id" name="destinatario_id">
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="btn-adjuntar">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="text" class="form-control" id="mensaje-texto" 
                                   placeholder="Escribe tu mensaje..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="btn-emoji">
                                <i class="fas fa-smile"></i>
                            </button>
                            <button type="submit" class="btn btn-primary" id="btn-enviar">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para móviles -->
<div class="mobile-overlay" id="mobile-overlay"></div>

<!-- Modal para Nuevo Mensaje -->
<div class="modal fade" id="modalNuevoMensaje" tabindex="-1" aria-labelledby="modalNuevoMensajeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoMensajeLabel">
                    <i class="fas fa-plus me-2"></i>Nuevo Mensaje
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Búsqueda de usuarios -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="buscar-usuarios" placeholder="Buscar usuarios...">
                    </div>
                </div>
                
                <!-- Lista de usuarios -->
                <div class="usuarios-disponibles" id="usuarios-disponibles" style="max-height: 400px; overflow-y: auto;">
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="text-muted mt-2">Cargando usuarios...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/mensajeria.js') ?>"></script>

<?= $footer ?>