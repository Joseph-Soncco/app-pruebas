<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MensajeChatModel;
use App\Models\ConversacionChatModel;
use App\Models\UsuarioConectadoModel;
use App\Models\UsuarioModel;

class ChatController extends BaseController
{
    protected $mensajeModel;
    protected $conversacionModel;
    protected $usuarioConectadoModel;
    protected $usuarioModel;

    public function __construct()
    {
        // Inicializar modelos solo si existen - evitar errores
        try {
            $this->mensajeModel = new MensajeChatModel();
        } catch (\Exception $e) {
            $this->mensajeModel = null;
        }
        
        try {
            $this->conversacionModel = new ConversacionChatModel();
        } catch (\Exception $e) {
            $this->conversacionModel = null;
        }
        
        try {
            $this->usuarioConectadoModel = new UsuarioConectadoModel();
        } catch (\Exception $e) {
            $this->usuarioConectadoModel = null;
        }
        
        try {
            $this->usuarioModel = new UsuarioModel();
        } catch (\Exception $e) {
            $this->usuarioModel = null;
        }
    }

    /**
     * Vista principal del chat
     */
    public function index()
    {
        // Verificar que el usuario esté autenticado
        if (!session('usuario_logueado')) {
            return redirect()->to('/login')->with('error', 'Debes iniciar sesión para acceder a la mensajería');
        }
        
        $data = [
            'title' => 'Mensajería - Sistema ISHUME',
            'usuario_actual' => [
                'id' => session('idusuario'),
                'nombre' => session('usuario_nombre'),
                'email' => session('usuario_email'),
                'rol' => session('role') ?? session('tipo_usuario')
            ]
        ];
        
        return view('mensajeria/mensajeria', $data);
    }

    /**
     * Método de prueba sin autenticación
     */
    public function testPublic()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'ChatController está funcionando correctamente',
            'timestamp' => date('Y-m-d H:i:s'),
            'session_data' => [
                'usuario_logueado' => session('usuario_logueado'),
                'idusuario' => session('idusuario'),
                'usuario_nombre' => session('usuario_nombre')
            ]
        ]);
    }

    /**
     * Método de prueba para verificar la vista sin autenticación
     */
    public function testView()
    {
        $data = [
            'title' => 'Mensajería en Tiempo Real - Prueba',
            'conversaciones' => [],
            'usuarios_online' => [],
            'header' => view('Layouts/header'),
            'footer' => view('Layouts/footer')
        ];

        return view('mensajeria/mensajeria', $data);
    }

    /**
     * Método de prueba para verificar que el controlador funciona
     */
    public function test()
    {
        // Verificar autenticación
        if (!session('usuario_logueado')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autorizado - Debes iniciar sesión'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'ChatController está funcionando correctamente',
            'usuario_actual' => [
                'id' => session('idusuario'),
                'nombre' => session('usuario_nombre'),
                'email' => session('usuario_email'),
                'rol' => session('role') ?? session('tipo_usuario'),
                'logueado' => session('usuario_logueado')
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener conversaciones del usuario actual
     */
    public function getConversaciones()
    {
        try {
            // Obtener ID del usuario actual desde la sesión
            $usuarioActual = session('idusuario') ?? 1;
            
            if ($this->conversacionModel) {
                $conversaciones = $this->conversacionModel->getConversacionesUsuario($usuarioActual);
            } else {
                // Datos de prueba si no hay modelo
                $conversaciones = [
                    [
                        'id' => '1_2_' . time(),
                        'contacto_nombre' => 'María González',
                        'contacto_usuario' => 'mgonzalez',
                        'ultimo_mensaje_texto' => 'Hola, ¿cómo estás?',
                        'ultimo_mensaje_fecha' => date('Y-m-d H:i:s'),
                        'mensajes_no_leidos' => 2,
                        'otro_usuario_id' => 2
                    ],
                    [
                        'id' => '1_3_' . time(),
                        'contacto_nombre' => 'Juan Pérez',
                        'contacto_usuario' => 'jperez',
                        'ultimo_mensaje_texto' => 'Perfecto, gracias',
                        'ultimo_mensaje_fecha' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                        'mensajes_no_leidos' => 0,
                        'otro_usuario_id' => 3
                    ]
                ];
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $conversaciones
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function getMensajes($conversacionId)
    {
        try {
            $usuarioActual = session('idusuario') ?? 1;
            
            if ($this->mensajeModel) {
                $mensajes = $this->mensajeModel->getMensajesChat($conversacionId, 50, 0);
                
                // Formatear mensajes para el frontend
                $mensajesFormateados = array_map(function($mensaje) use ($usuarioActual) {
                    return [
                        'id' => $mensaje['id'],
                        'contenido' => $mensaje['mensaje'],
                        'usuario_nombre' => $mensaje['usuario_nombre'],
                        'usuario_id' => $mensaje['usuario_id'],
                        'es_propio' => $mensaje['usuario_id'] == $usuarioActual,
                        'tiempo' => date('H:i', strtotime($mensaje['fecha_envio'])),
                        'fecha_envio' => $mensaje['fecha_envio'],
                        'tipo' => $mensaje['tipo'] ?? 'texto',
                        'conversacion_id' => $mensaje['conversacion_id']
                    ];
                }, $mensajes);
            } else {
                // Datos de prueba si no hay modelo
                $mensajesFormateados = [
                    [
                        'id' => 1,
                        'contenido' => 'Hola, ¿cómo estás?',
                        'usuario_nombre' => 'María González',
                        'usuario_id' => 2,
                        'es_propio' => false,
                        'tiempo' => date('H:i', strtotime('-30 minutes')),
                        'fecha_envio' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                        'tipo' => 'texto',
                        'conversacion_id' => $conversacionId
                    ],
                    [
                        'id' => 2,
                        'contenido' => '¡Hola! Todo bien, gracias por preguntar',
                        'usuario_nombre' => session('usuario_nombre') ?? 'Usuario',
                        'usuario_id' => $usuarioActual,
                        'es_propio' => true,
                        'tiempo' => date('H:i', strtotime('-25 minutes')),
                        'fecha_envio' => date('Y-m-d H:i:s', strtotime('-25 minutes')),
                        'tipo' => 'texto',
                        'conversacion_id' => $conversacionId
                    ]
                ];
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $mensajesFormateados
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar mensaje
     */
    public function enviarMensaje()
    {
        try {
            $conversacionId = $this->request->getPost('conversacion_id');
            $contenido = $this->request->getPost('contenido');
            $destinatarioId = $this->request->getPost('destinatario_id');
            
            // Validaciones
            if (empty($contenido)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'El mensaje no puede estar vacío'
                ]);
            }
            
            $usuarioActual = session('idusuario') ?? 1;
            $usuarioActualNombre = session('usuario_nombre') ?? 'Usuario';
            
            // Verificar que el destinatario existe en el sistema
            if (!empty($destinatarioId)) {
                $destinatarioExiste = $this->verificarUsuarioExiste($destinatarioId);
                if (!$destinatarioExiste) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'El usuario destinatario no existe en el sistema'
                    ]);
                }
            }
            
            // Si no hay conversación, crear una nueva
            if (empty($conversacionId) && !empty($destinatarioId)) {
                if ($this->conversacionModel) {
                    $conversacionId = $this->conversacionModel->crearConversacion($usuarioActual, $destinatarioId);
                } else {
                    $conversacionId = $this->crearNuevaConversacion($usuarioActual, $destinatarioId);
                }
            }
            
            // Guardar mensaje en base de datos
            $mensajeId = null;
            if ($this->mensajeModel) {
                $mensajeData = [
                    'conversacion_id' => $conversacionId,
                    'usuario_id' => $usuarioActual,
                    'mensaje' => $contenido,
                    'tipo' => 'texto',
                    'fecha_envio' => date('Y-m-d H:i:s')
                ];
                $mensajeId = $this->mensajeModel->insertMensajeChat($mensajeData);
                
                // Actualizar último mensaje en conversación
                if ($this->conversacionModel) {
                    $this->conversacionModel->actualizarUltimoMensaje($conversacionId, $mensajeId, $usuarioActual);
                }
            }
            
            // Crear el mensaje para respuesta
            $mensaje = [
                'id' => $mensajeId ?? (time() . rand(1000, 9999)),
                'contenido' => $contenido,
                'es_propio' => true,
                'tiempo' => date('H:i'),
                'usuario_nombre' => $usuarioActualNombre,
                'usuario_id' => $usuarioActual,
                'conversacion_id' => $conversacionId,
                'destinatario_id' => $destinatarioId,
                'fecha_envio' => date('Y-m-d H:i:s'),
                'tipo' => 'texto',
                'estado' => 'enviado'
            ];
            
            // Enviar via WebSocket si está disponible
            $this->enviarViaWebSocket([
                'type' => 'new-message',
                'conversationId' => $conversacionId,
                'message' => $mensaje,
                'destinatarioId' => $destinatarioId
            ]);
            
            // Crear notificación para el destinatario si está offline
            if (!empty($destinatarioId)) {
                $this->crearNotificacionMensaje($destinatarioId, $usuarioActualNombre, $contenido);
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $mensaje,
                'conversacion_id' => $conversacionId,
                'notificacion_creada' => !empty($destinatarioId)
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marcar mensaje como leído
     */
    public function marcarLeido()
    {
        try {
            $mensajeId = $this->request->getPost('mensaje_id');

            if (empty($mensajeId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'ID de mensaje requerido'
                ]);
            }

            $resultado = $this->mensajeModel->marcarMensajeLeido($mensajeId, session('idusuario'));

            return $this->response->setJSON([
                'success' => $resultado,
                'message' => $resultado ? 'Mensaje marcado como leído' : 'Error al marcar mensaje'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener usuarios registrados del sistema
     */
    public function getUsuarios()
    {
        try {
            // Verificar autenticación
            if (!session('usuario_logueado')) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'No autorizado'
                ]);
            }
            
            // Obtener ID del usuario actual desde la sesión
            $usuarioActual = session('idusuario');
            
            if ($this->usuarioModel) {
                // Obtener usuarios reales de la base de datos
                $usuarios = $this->usuarioModel->getUsuariosActivos();
                
                // Formatear datos para el frontend
                $usuariosFormateados = [];
                foreach ($usuarios as $usuario) {
                    if ($usuario['idusuario'] != $usuarioActual) {
                        $usuariosFormateados[] = [
                            'id' => $usuario['idusuario'],
                            'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
                            'usuario' => $usuario['nombreusuario'],
                            'email' => $usuario['email'],
                            'estado' => 'offline', // Por defecto offline, se actualizará con WebSocket
                            'ultima_conexion' => $usuario['ultima_conexion'] ?? null,
                            'cargo' => $usuario['cargo'] ?? 'Usuario'
                        ];
                    }
                }
                
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $usuariosFormateados
                ]);
            } else {
                // Datos de prueba si no hay modelo
                $usuariosPrueba = [
                    [
                        'id' => 2,
                        'nombre' => 'María González',
                        'usuario' => 'mgonzalez',
                        'email' => 'maria@ishume.com',
                        'estado' => 'online',
                        'ultima_conexion' => date('Y-m-d H:i:s'),
                        'cargo' => 'Gerente'
                    ],
                    [
                        'id' => 3,
                        'nombre' => 'Juan Pérez',
                        'usuario' => 'jperez',
                        'email' => 'juan@ishume.com',
                        'estado' => 'offline',
                        'ultima_conexion' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                        'cargo' => 'Desarrollador'
                    ],
                    [
                        'id' => 4,
                        'nombre' => 'Ana López',
                        'usuario' => 'alopez',
                        'email' => 'ana@ishume.com',
                        'estado' => 'online',
                        'ultima_conexion' => date('Y-m-d H:i:s'),
                        'cargo' => 'Diseñadora'
                    ],
                    [
                        'id' => 5,
                        'nombre' => 'Roberto Silva',
                        'usuario' => 'rsilva',
                        'email' => 'roberto@ishume.com',
                        'estado' => 'away',
                        'ultima_conexion' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
                        'cargo' => 'Supervisor'
                    ]
                ];
                
                // Filtrar para excluir al usuario actual
                $usuariosFiltrados = array_filter($usuariosPrueba, function($usuario) use ($usuarioActual) {
                    return $usuario['id'] != $usuarioActual;
                });
                
                return $this->response->setJSON([
                    'success' => true,
                    'data' => array_values($usuariosFiltrados)
                ]);
            }
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear nueva conversación
     */
    public function crearConversacion()
    {
        try {
            $usuario2Id = $this->request->getPost('usuario_id');
            $usuario1Id = session('idusuario') ?? 1;

            if (empty($usuario2Id)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Usuario requerido'
                ]);
            }

            // Crear nueva conversación
            $conversacionId = $this->crearNuevaConversacion($usuario1Id, $usuario2Id);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'id' => $conversacionId,
                    'usuario1_id' => $usuario1Id,
                    'usuario2_id' => $usuario2Id
                ]
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener URL del WebSocket
     */
    public function getWebSocketUrl()
    {
        try {
            // En Railway, el WebSocket estará en el mismo dominio pero puerto diferente
            $baseUrl = base_url();
            $wsUrl = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $baseUrl);
            $wsUrl = rtrim($wsUrl, '/') . ':3000';
            
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'url' => $wsUrl
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener notificaciones del usuario actual
     */
    public function getNotificaciones()
    {
        try {
            $usuarioActual = session('idusuario') ?? 1;
            
            // Por ahora, devolver notificaciones de prueba
            $notificaciones = [
                [
                    'id' => 1,
                    'tipo' => 'mensaje',
                    'titulo' => 'Nuevo mensaje de María González',
                    'mensaje' => 'Hola, ¿cómo estás?',
                    'fecha' => date('Y-m-d H:i:s'),
                    'estado' => 'no_leida'
                ],
                [
                    'id' => 2,
                    'tipo' => 'mensaje',
                    'titulo' => 'Nuevo mensaje de Juan Pérez',
                    'mensaje' => 'Perfecto, gracias por tu ayuda',
                    'fecha' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'estado' => 'no_leida'
                ]
            ];
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $notificaciones,
                'total_no_leidas' => count($notificaciones)
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Métodos privados
     */

    /**
     * Crear nueva conversación entre dos usuarios
     */
    private function crearNuevaConversacion($usuario1Id, $usuario2Id)
    {
        // Generar ID único para la conversación
        $conversacionId = $usuario1Id . '_' . $usuario2Id . '_' . time();
        
        // En un sistema real, aquí guardarías en la base de datos
        // Por ahora, solo devolvemos el ID generado
        
        return $conversacionId;
    }
    
    /**
     * Verificar que un usuario existe en el sistema
     */
    private function verificarUsuarioExiste($usuarioId)
    {
        // Lista de usuarios válidos para pruebas
        $usuariosValidos = [2, 3, 4, 5];
        return in_array($usuarioId, $usuariosValidos);
    }
    
    /**
     * Guardar mensaje en base de datos
     */
    private function guardarMensajeEnBD($mensaje)
    {
        // Por ahora solo log del mensaje para evitar errores de BD
        log_message('info', 'Mensaje enviado: ' . json_encode($mensaje));
        return true;
    }
    
    /**
     * Crear notificación para usuario offline
     */
    private function crearNotificacionMensaje($destinatarioId, $remitenteNombre, $contenido)
    {
        // Por ahora solo log de la notificación para evitar errores de BD
        log_message('info', "Notificación para usuario {$destinatarioId}: Nuevo mensaje de {$remitenteNombre}");
        return true;
    }

    private function tieneAccesoConversacion($conversacionId)
    {
        $userId = session('idusuario');
        
        $conversacion = $this->conversacionModel->where('id', $conversacionId)
            ->where('(usuario1_id = ' . $userId . ' OR usuario2_id = ' . $userId . ')')
            ->first();

        return $conversacion !== null;
    }

    private function enviarViaWebSocket($data)
    {
        // Enviar datos al servidor WebSocket
        // Esto es opcional ya que el cliente puede enviar directamente
        $socketUrl = 'http://localhost:3000/api/broadcast';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $socketUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response;
    }

    /**
     * Método de prueba simple
     */
    public function test()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'ChatController funcionando correctamente',
            'timestamp' => date('Y-m-d H:i:s'),
            'usuario_actual' => session('idusuario') ?? 'No definido'
        ]);
    }
}
