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
            $usuarioActual = session('idusuario') ?? 1; // Fallback para pruebas
            
            // Generar conversaciones específicas para este usuario
            $conversacionesPrueba = $this->generarConversacionesPorUsuario($usuarioActual);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $conversacionesPrueba
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
            // Obtener ID del usuario actual desde la sesión
            $usuarioActual = session('idusuario') ?? 1; // Fallback para pruebas
            
            // Generar mensajes específicos para esta conversación y usuario
            $mensajesPrueba = $this->generarMensajesPorConversacion($conversacionId, $usuarioActual);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $mensajesPrueba
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
            $mensaje = $this->request->getPost('mensaje');
            $tipo = $this->request->getPost('tipo') ?? 'texto';

            // Validaciones
            if (empty($conversacionId) || empty($mensaje)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Datos requeridos faltantes'
                ]);
            }

            // Verificar acceso a la conversación
            if (!$this->tieneAccesoConversacion($conversacionId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'No tienes acceso a esta conversación'
                ]);
            }

            // Guardar mensaje en base de datos
            $mensajeData = [
                'conversacion_id' => $conversacionId,
                'usuario_id' => session('idusuario'),
                'mensaje' => $mensaje,
                'tipo' => $tipo,
                'fecha_envio' => date('Y-m-d H:i:s')
            ];

            $mensajeId = $this->mensajeModel->insertMensajeChat($mensajeData);

            if ($mensajeId) {
                // Obtener mensaje completo con información del usuario
                $mensajeCompleto = $this->mensajeModel->getMensajeChatCompleto($mensajeId);

                // Enviar via WebSocket (opcional - el cliente también puede enviar directamente)
                $this->enviarViaWebSocket([
                    'event' => 'new-message',
                    'data' => $mensajeCompleto,
                    'conversationId' => $conversacionId
                ]);

                return $this->response->setJSON([
                    'success' => true,
                    'mensaje' => $mensajeCompleto
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Error al guardar el mensaje'
                ]);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
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
            $usuarioActual = session('idusuario') ?? 1; // Fallback para pruebas
            
            // Generar usuarios específicos (excluyendo al usuario actual)
            $usuariosPrueba = $this->generarUsuariosDisponibles($usuarioActual);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => $usuariosPrueba
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

            if (empty($usuario2Id)) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Usuario requerido'
                ]);
            }

            $usuario1Id = session('idusuario');

            // Verificar que no existe ya una conversación entre estos usuarios
            $conversacionExistente = $this->conversacionModel->getConversacionEntreUsuarios($usuario1Id, $usuario2Id);

            if ($conversacionExistente) {
                return $this->response->setJSON([
                    'success' => true,
                    'conversacion' => $conversacionExistente,
                    'message' => 'Conversación ya existe'
                ]);
            }

            // Crear nueva conversación
            $conversacionData = [
                'usuario1_id' => $usuario1Id,
                'usuario2_id' => $usuario2Id,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ];

            $conversacionId = $this->conversacionModel->insert($conversacionData);

            if ($conversacionId) {
                $conversacion = $this->conversacionModel->getConversacionCompleta($conversacionId);
                
                return $this->response->setJSON([
                    'success' => true,
                    'conversacion' => $conversacion
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Error al crear conversación'
                ]);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'error' => $e->getMessage()
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
     * Generar conversaciones específicas por usuario
     */
    private function generarConversacionesPorUsuario($usuarioId)
    {
        // Base de datos de usuarios disponibles
        $usuariosDisponibles = [
            1 => ['nombre' => 'Carlos Eduardo García López', 'cargo' => 'Administrador'],
            2 => ['nombre' => 'María González', 'cargo' => 'Gerente'],
            3 => ['nombre' => 'Juan Pérez', 'cargo' => 'Desarrollador'],
            4 => ['nombre' => 'Ana López', 'cargo' => 'Diseñadora'],
            5 => ['nombre' => 'Carmen Rosa González Pérez', 'cargo' => 'Analista'],
            6 => ['nombre' => 'Roberto Silva', 'cargo' => 'Supervisor']
        ];
        
        // Generar conversaciones diferentes según el usuario
        $conversaciones = [];
        $conversacionId = 1;
        
        foreach ($usuariosDisponibles as $id => $usuario) {
            if ($id != $usuarioId) { // No incluir al usuario actual
                $conversaciones[] = [
                    'id' => $conversacionId,
                    'nombre' => $usuario['nombre'],
                    'ultimo_mensaje' => $this->generarUltimoMensaje($usuarioId, $id),
                    'tiempo' => $this->generarTiempoAleatorio(),
                    'no_leidos' => rand(0, 3),
                    'usuario_id' => $id
                ];
                $conversacionId++;
            }
        }
        
        return $conversaciones;
    }
    
    /**
     * Generar mensajes específicos por conversación y usuario
     */
    private function generarMensajesPorConversacion($conversacionId, $usuarioActual)
    {
        // Base de datos de usuarios
        $usuariosDisponibles = [
            1 => 'Carlos Eduardo García López',
            2 => 'María González', 
            3 => 'Juan Pérez',
            4 => 'Ana López',
            5 => 'Carmen Rosa González Pérez',
            6 => 'Roberto Silva'
        ];
        
        // Determinar el otro usuario en la conversación
        $otrosUsuarios = array_keys($usuariosDisponibles);
        $otrosUsuarios = array_filter($otrosUsuarios, function($id) use ($usuarioActual) {
            return $id != $usuarioActual;
        });
        
        $otroUsuarioId = $otrosUsuarios[($conversacionId - 1) % count($otrosUsuarios)];
        $otroUsuarioNombre = $usuariosDisponibles[$otroUsuarioId];
        $usuarioActualNombre = $usuariosDisponibles[$usuarioActual] ?? 'Usuario';
        
        // Generar mensajes específicos para esta conversación
        $mensajes = [
            [
                'id' => 1,
                'contenido' => "Hola {$usuarioActualNombre}, ¿cómo estás?",
                'es_propio' => false,
                'tiempo' => '14:30',
                'usuario_nombre' => $otroUsuarioNombre
            ],
            [
                'id' => 2,
                'contenido' => "Hola {$otroUsuarioNombre}, todo bien gracias. ¿Y tú?",
                'es_propio' => true,
                'tiempo' => '14:32',
                'usuario_nombre' => $usuarioActualNombre
            ],
            [
                'id' => 3,
                'contenido' => "Perfecto, trabajando en el proyecto nuevo",
                'es_propio' => false,
                'tiempo' => '14:35',
                'usuario_nombre' => $otroUsuarioNombre
            ],
            [
                'id' => 4,
                'contenido' => "Excelente, ¿necesitas ayuda con algo?",
                'es_propio' => true,
                'tiempo' => '14:37',
                'usuario_nombre' => $usuarioActualNombre
            ]
        ];
        
        return $mensajes;
    }
    
    /**
     * Generar último mensaje aleatorio
     */
    private function generarUltimoMensaje($usuario1, $usuario2)
    {
        $mensajes = [
            "Hola, ¿cómo estás?",
            "Perfecto, gracias",
            "Nos vemos mañana",
            "¿Tienes tiempo para revisar el proyecto?",
            "Excelente trabajo",
            "¿Podemos reunirnos esta tarde?",
            "Todo listo para la presentación",
            "Gracias por tu ayuda"
        ];
        
        return $mensajes[array_rand($mensajes)];
    }
    
    /**
     * Generar usuarios disponibles (excluyendo al usuario actual)
     */
    private function generarUsuariosDisponibles($usuarioActual)
    {
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
            if ($id != $usuarioActual) { // Excluir al usuario actual
                $usuarios[] = [
                    'id' => $id,
                    'nombre' => $usuario['nombre'],
                    'cargo' => $usuario['cargo'],
                    'estado' => $this->generarEstadoAleatorio(),
                    'ultima_conexion' => $this->generarUltimaConexion()
                ];
            }
        }
        
        return $usuarios;
    }
    
    /**
     * Generar estado aleatorio
     */
    private function generarEstadoAleatorio()
    {
        $estados = ['online', 'away', 'busy'];
        return $estados[array_rand($estados)];
    }
    
    /**
     * Generar tiempo aleatorio
     */
    private function generarTiempoAleatorio()
    {
        $tiempos = ['2 min', '5 min', '1 hora', '2 horas', '3 horas', 'Ayer', '2 días'];
        return $tiempos[array_rand($tiempos)];
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
