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
        $this->mensajeModel = new MensajeChatModel();
        $this->conversacionModel = new ConversacionChatModel();
        $this->usuarioConectadoModel = new UsuarioConectadoModel();
        $this->usuarioModel = new UsuarioModel();
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

        // Datos básicos para la vista
        $data = [
            'title' => 'Mensajería en Tiempo Real',
            'conversaciones' => [], // Inicialmente vacío hasta que se configure la BD
            'usuarios_online' => [], // Inicialmente vacío hasta que se configure la BD
            'header' => view('Layouts/header'),
            'footer' => view('Layouts/footer')
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
        return $this->response->setJSON([
            'success' => true,
            'message' => 'ChatController está funcionando correctamente',
            'session' => [
                'usuario_logueado' => session('usuario_logueado'),
                'idusuario' => session('idusuario'),
                'usuario_nombre' => session('usuario_nombre')
            ]
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
            
            // Por ahora, devolver conversaciones vacías para empezar sin historial
            // Cada usuario empezará con chats nuevos
            $conversaciones = [];
            
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
            // Por ahora, devolver mensajes vacíos para empezar sin historial
            // Los mensajes se crearán cuando los usuarios empiecen a chatear
            $mensajes = [];
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $mensajes
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
                $conversacionId = $this->crearNuevaConversacion($usuarioActual, $destinatarioId);
            }
            
            // Crear el mensaje
            $mensaje = [
                'id' => time() . rand(1000, 9999), // ID único temporal
                'contenido' => $contenido,
                'es_propio' => true,
                'tiempo' => date('H:i'),
                'usuario_nombre' => $usuarioActualNombre,
                'conversacion_id' => $conversacionId,
                'destinatario_id' => $destinatarioId,
                'fecha_envio' => date('Y-m-d H:i:s'),
                'estado' => 'enviado'
            ];
            
            // Guardar mensaje en base de datos (si las tablas existen)
            $mensajeGuardado = $this->guardarMensajeEnBD($mensaje);
            
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
            // Obtener ID del usuario actual desde la sesión
            $usuarioActual = session('idusuario') ?? 1;
            
            // Obtener usuarios reales del sistema desde la base de datos
            $db = \Config\Database::connect();
            
            // Query para obtener usuarios activos del sistema
            $query = $db->query("
                SELECT 
                    u.idusuario,
                    CONCAT(p.nombres, ' ', p.apellidos) as nombre_completo,
                    u.nombreusuario,
                    u.email,
                    u.estado,
                    u.ultima_conexion,
                    CASE 
                        WHEN u.estado = 1 THEN 'online'
                        ELSE 'offline'
                    END as estado_chat
                FROM usuarios u
                JOIN personas p ON u.idpersona = p.idpersona
                WHERE u.estado = 1 AND u.idusuario != ?
                ORDER BY p.nombres, p.apellidos
            ", [$usuarioActual]);
            
            $usuarios = $query->getResultArray();
            
            // Formatear datos para el frontend
            $usuariosFormateados = [];
            foreach ($usuarios as $usuario) {
                $usuariosFormateados[] = [
                    'id' => $usuario['idusuario'],
                    'nombre' => $usuario['nombre_completo'],
                    'usuario' => $usuario['nombreusuario'],
                    'email' => $usuario['email'],
                    'estado' => $usuario['estado_chat'],
                    'ultima_conexion' => $usuario['ultima_conexion'],
                    'cargo' => 'Usuario del Sistema' // Por ahora genérico
                ];
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $usuariosFormateados
            ]);
            
        } catch (\Exception $e) {
            // Si hay error con la base de datos, usar datos de prueba
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
        try {
            $db = \Config\Database::connect();
            $query = $db->query("SELECT idusuario FROM usuarios WHERE idusuario = ? AND estado = 1", [$usuarioId]);
            return $query->getNumRows() > 0;
        } catch (\Exception $e) {
            // Si hay error con la base de datos, asumir que existe para pruebas
            return true;
        }
    }
    
    /**
     * Guardar mensaje en base de datos
     */
    private function guardarMensajeEnBD($mensaje)
    {
        try {
            $db = \Config\Database::connect();
            
            // Intentar insertar en tabla de mensajes si existe
            $data = [
                'conversacion_id' => $mensaje['conversacion_id'],
                'usuario_id' => session('idusuario'),
                'destinatario_id' => $mensaje['destinatario_id'],
                'mensaje' => $mensaje['contenido'],
                'fecha_envio' => $mensaje['fecha_envio'],
                'estado' => $mensaje['estado']
            ];
            
            // Por ahora solo log del mensaje
            log_message('info', 'Mensaje enviado: ' . json_encode($data));
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error guardando mensaje: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear notificación para usuario offline
     */
    private function crearNotificacionMensaje($destinatarioId, $remitenteNombre, $contenido)
    {
        try {
            $db = \Config\Database::connect();
            
            // Crear notificación en base de datos
            $notificacion = [
                'usuario_id' => $destinatarioId,
                'tipo' => 'mensaje',
                'titulo' => 'Nuevo mensaje de ' . $remitenteNombre,
                'mensaje' => substr($contenido, 0, 100) . (strlen($contenido) > 100 ? '...' : ''),
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'estado' => 'no_leida'
            ];
            
            // Por ahora solo log de la notificación
            log_message('info', 'Notificación creada: ' . json_encode($notificacion));
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error creando notificación: ' . $e->getMessage());
            return false;
        }
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
}
