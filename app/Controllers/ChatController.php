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
            
            // Si no hay conversación, crear una nueva
            if (empty($conversacionId) && !empty($destinatarioId)) {
                $conversacionId = $this->crearNuevaConversacion($usuarioActual, $destinatarioId);
            }
            
            // Crear el mensaje
            $mensaje = [
                'id' => time(), // ID temporal
                'contenido' => $contenido,
                'es_propio' => true,
                'tiempo' => date('H:i'),
                'usuario_nombre' => $usuarioActualNombre,
                'conversacion_id' => $conversacionId,
                'fecha_envio' => date('Y-m-d H:i:s')
            ];
            
            // En un sistema real, aquí guardarías en la base de datos
            // Por ahora, solo devolvemos el mensaje creado
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $mensaje,
                'conversacion_id' => $conversacionId
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
     * Obtener usuarios online
     */
    public function getUsuarios()
    {
        try {
            // Obtener ID del usuario actual desde la sesión
            $usuarioActual = session('idusuario') ?? 1;
            
            // Lista simple de usuarios disponibles (excluyendo al usuario actual)
            $usuariosDisponibles = [
                1 => ['nombre' => 'Carlos Eduardo García López', 'cargo' => 'Administrador'],
                2 => ['nombre' => 'María González', 'cargo' => 'Gerente'],
                3 => ['nombre' => 'Juan Pérez', 'cargo' => 'Desarrollador'],
                4 => ['nombre' => 'Ana López', 'cargo' => 'Diseñadora'],
                5 => ['nombre' => 'Carmen Rosa González Pérez', 'cargo' => 'Analista'],
                6 => ['nombre' => 'Roberto Silva', 'cargo' => 'Supervisor']
            ];
            
            $usuarios = [];
            foreach ($usuariosDisponibles as $id => $usuario) {
                if ($id != $usuarioActual) {
                    $usuarios[] = [
                        'id' => $id,
                        'nombre' => $usuario['nombre'],
                        'cargo' => $usuario['cargo'],
                        'estado' => 'online',
                        'ultima_conexion' => date('Y-m-d H:i:s')
                    ];
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $usuarios
            ]);
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
