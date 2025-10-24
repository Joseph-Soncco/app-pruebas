<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajería - Sistema ISHUME</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .chat-container { height: 100vh; }
        .conversation-item { cursor: pointer; }
        .conversation-item:hover { background-color: #f8f9fa; }
        .message-bubble { max-width: 70%; }
        .message-sent { background-color: #007bff; color: white; margin-left: auto; }
        .message-received { background-color: #e9ecef; color: black; }
    </style>
</head>
<body>
    <div class="container-fluid chat-container">
        <div class="row h-100">
            <!-- Panel izquierdo - Lista de conversaciones -->
            <div class="col-md-4 col-lg-3 p-0 border-end bg-white">
                <div class="d-flex flex-column h-100">
                    <!-- Header del usuario -->
                    <div class="p-4 border-bottom bg-light">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-user-circle fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold">Usuario del Sistema</h6>
                                <small class="text-muted">usuario@sistema.com</small>
                            </div>
                            <div class="text-end">
                                <i id="indicador-conexion" class="fas fa-circle text-success" title="Conectado"></i>
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
                    <div class="flex-grow-1 overflow-auto" id="lista-conversaciones">
                        <div class="text-center p-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted mt-2">Cargando conversaciones...</p>
                        </div>
                    </div>

                    <!-- Panel de usuarios online -->
                    <div class="border-top p-3">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-users me-1"></i> Usuarios Disponibles
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
                            <div class="me-3">
                                <i class="fas fa-user-circle fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold" id="nombre-destinatario">Selecciona una conversación</h6>
                                <small class="text-success" id="estado-destinatario">
                                    <i class="fas fa-circle text-success" style="font-size: 8px;"></i> En línea
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Área de mensajes -->
                    <div class="flex-grow-1 overflow-auto p-3" id="area-mensajes">
                        <div class="text-center p-5">
                            <div class="mb-4">
                                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            </div>
                            <h4 class="text-muted mb-3">Selecciona una conversación</h4>
                            <p class="text-muted mb-4">Elige una conversación de la lista para comenzar a chatear</p>
                            <button class="btn btn-primary btn-lg" id="btn-nuevo-mensaje-main">
                                <i class="fas fa-plus me-2"></i>Nuevo Mensaje
                            </button>
                        </div>
                    </div>

                    <!-- Área de escritura -->
                    <div class="p-3 border-top bg-white" id="area-escritura" style="display: none;">
                        <form id="form-enviar-mensaje">
                            <input type="hidden" id="destinatario-id" name="destinatario_id">
                            <div class="input-group">
                                <input type="text" class="form-control" id="mensaje-texto" 
                                       placeholder="Escribe tu mensaje..." autocomplete="off">
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
    <script src="<?= base_url('assets/js/mensajeria-realtime.js') ?>"></script>
</body>
</html>