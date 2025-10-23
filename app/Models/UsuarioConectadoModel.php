<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioConectadoModel extends Model
{
    protected $table = 'usuarios_conectados';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'usuario_id',
        'socket_id',
        'ultima_conexion',
        'estado',
        'dispositivo',
        'ip_address',
        'user_agent'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    /**
     * Registrar usuario como conectado
     */
    public function registrarConexion($usuarioId, $socketId, $estado = 'online', $dispositivo = 'web', $ipAddress = null, $userAgent = null)
    {
        $data = [
            'usuario_id' => $usuarioId,
            'socket_id' => $socketId,
            'estado' => $estado,
            'dispositivo' => $dispositivo,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'ultima_conexion' => date('Y-m-d H:i:s')
        ];

        // Usar INSERT ... ON DUPLICATE KEY UPDATE para actualizar si ya existe
        $sql = "
            INSERT INTO usuarios_conectados (usuario_id, socket_id, estado, dispositivo, ip_address, user_agent, ultima_conexion) 
            VALUES (?, ?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
                socket_id = VALUES(socket_id),
                estado = VALUES(estado),
                dispositivo = VALUES(dispositivo),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                ultima_conexion = VALUES(ultima_conexion)
        ";

        return $this->db->query($sql, [
            $usuarioId, $socketId, $estado, $dispositivo, $ipAddress, $userAgent, $data['ultima_conexion']
        ]);
    }

    /**
     * Desconectar usuario
     */
    public function desconectarUsuario($socketId)
    {
        return $this->where('socket_id', $socketId)->delete();
    }

    /**
     * Obtener usuarios online
     */
    public function getUsuariosOnline()
    {
        return $this->db->query("
            SELECT 
                uc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                u.email as usuario_email,
                u.ultima_conexion as ultima_conexion_usuario
            FROM usuarios_conectados uc
            JOIN usuarios u ON uc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            WHERE uc.estado = 'online'
            ORDER BY uc.ultima_conexion DESC
        ")->getResultArray();
    }

    /**
     * Obtener usuarios conectados con información completa
     */
    public function getUsuariosConectadosCompletos()
    {
        return $this->db->query("
            SELECT 
                uc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                u.email as usuario_email,
                u.estado as estado_usuario,
                u.ultima_conexion as ultima_conexion_usuario
            FROM usuarios_conectados uc
            JOIN usuarios u ON uc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            WHERE u.estado = 1
            ORDER BY uc.estado DESC, uc.ultima_conexion DESC
        ")->getResultArray();
    }

    /**
     * Verificar si un usuario está online
     */
    public function estaUsuarioOnline($usuarioId)
    {
        $result = $this->where('usuario_id', $usuarioId)
            ->where('estado', 'online')
            ->first();

        return $result !== null;
    }

    /**
     * Obtener estado de conexión de un usuario
     */
    public function getEstadoUsuario($usuarioId)
    {
        $result = $this->where('usuario_id', $usuarioId)
            ->orderBy('ultima_conexion', 'DESC')
            ->first();

        if (!$result) {
            return 'offline';
        }

        // Si la última conexión fue hace más de 5 minutos, considerar offline
        $ultimaConexion = strtotime($result['ultima_conexion']);
        $ahora = time();
        $diferencia = $ahora - $ultimaConexion;

        if ($diferencia > 300) { // 5 minutos
            return 'offline';
        }

        return $result['estado'];
    }

    /**
     * Actualizar estado de usuario
     */
    public function actualizarEstado($usuarioId, $estado)
    {
        return $this->where('usuario_id', $usuarioId)
            ->set('estado', $estado)
            ->set('ultima_conexion', date('Y-m-d H:i:s'))
            ->update();
    }

    /**
     * Limpiar conexiones inactivas
     */
    public function limpiarConexionesInactivas($minutosInactivo = 5)
    {
        $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$minutosInactivo} minutes"));
        
        return $this->where('ultima_conexion <', $fechaLimite)->delete();
    }

    /**
     * Obtener estadísticas de conexiones
     */
    public function getEstadisticasConexiones()
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total_conexiones,
                COUNT(CASE WHEN estado = 'online' THEN 1 END) as usuarios_online,
                COUNT(CASE WHEN estado = 'away' THEN 1 END) as usuarios_away,
                COUNT(CASE WHEN estado = 'busy' THEN 1 END) as usuarios_busy,
                COUNT(CASE WHEN dispositivo = 'web' THEN 1 END) as dispositivos_web,
                COUNT(CASE WHEN dispositivo = 'mobile' THEN 1 END) as dispositivos_mobile,
                COUNT(CASE WHEN dispositivo = 'desktop' THEN 1 END) as dispositivos_desktop
            FROM usuarios_conectados
            WHERE ultima_conexion >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ")->getRowArray();

        return $result;
    }

    /**
     * Obtener usuarios por dispositivo
     */
    public function getUsuariosPorDispositivo($dispositivo)
    {
        return $this->db->query("
            SELECT 
                uc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario
            FROM usuarios_conectados uc
            JOIN usuarios u ON uc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            WHERE uc.dispositivo = ? AND uc.estado = 'online'
            ORDER BY uc.ultima_conexion DESC
        ", [$dispositivo])->getResultArray();
    }

    /**
     * Obtener historial de conexiones de un usuario
     */
    public function getHistorialConexiones($usuarioId, $limit = 20)
    {
        return $this->db->query("
            SELECT 
                uc.*,
                uc.ultima_conexion as fecha_conexion
            FROM usuarios_conectados uc
            WHERE uc.usuario_id = ?
            ORDER BY uc.ultima_conexion DESC
            LIMIT ?
        ", [$usuarioId, $limit])->getResultArray();
    }

    /**
     * Obtener usuarios conectados en un rango de tiempo
     */
    public function getUsuariosConectadosEnRango($fechaInicio, $fechaFin)
    {
        return $this->db->query("
            SELECT 
                uc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario
            FROM usuarios_conectados uc
            JOIN usuarios u ON uc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            WHERE uc.ultima_conexion BETWEEN ? AND ?
            ORDER BY uc.ultima_conexion DESC
        ", [$fechaInicio, $fechaFin])->getResultArray();
    }

    /**
     * Obtener usuarios que estuvieron online en las últimas 24 horas
     */
    public function getUsuariosActivos24h()
    {
        return $this->db->query("
            SELECT 
                uc.usuario_id,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                MAX(uc.ultima_conexion) as ultima_conexion,
                COUNT(*) as total_conexiones
            FROM usuarios_conectados uc
            JOIN usuarios u ON uc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            WHERE uc.ultima_conexion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY uc.usuario_id, p.nombres, p.apellidos, u.nombreusuario
            ORDER BY ultima_conexion DESC
        ")->getResultArray();
    }
}
