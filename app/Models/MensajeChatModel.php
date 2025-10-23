<?php

namespace App\Models;

use CodeIgniter\Model;

class MensajeChatModel extends Model
{
    protected $table = 'mensajes_chat';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'conversacion_id',
        'usuario_id',
        'mensaje',
        'tipo',
        'archivo_nombre',
        'archivo_ruta',
        'archivo_tamaño',
        'mensaje_referencia_id',
        'editado',
        'fecha_edicion',
        'eliminado',
        'fecha_eliminacion',
        'fecha_envio'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    /**
     * Insertar mensaje de chat
     */
    public function insertMensajeChat($data)
    {
        return $this->insert($data);
    }

    /**
     * Obtener mensajes de una conversación
     */
    public function getMensajesChat($conversacionId, $limit = 50, $offset = 0)
    {
        return $this->db->query("
            SELECT 
                mc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                u.email as usuario_email,
                CASE 
                    WHEN c.usuario1_id = mc.usuario_id THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as destinatario_id
            FROM mensajes_chat mc
            JOIN usuarios u ON mc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            JOIN conversaciones c ON mc.conversacion_id = c.id
            WHERE mc.conversacion_id = ? 
            AND mc.eliminado = FALSE 
            ORDER BY mc.fecha_envio DESC 
            LIMIT ? OFFSET ?
        ", [$conversacionId, $limit, $offset])->getResultArray();
    }

    /**
     * Obtener mensaje completo con información del usuario
     */
    public function getMensajeChatCompleto($mensajeId)
    {
        $result = $this->db->query("
            SELECT 
                mc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                u.email as usuario_email,
                c.usuario1_id,
                c.usuario2_id,
                CASE 
                    WHEN c.usuario1_id = mc.usuario_id THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as destinatario_id
            FROM mensajes_chat mc
            JOIN usuarios u ON mc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            JOIN conversaciones c ON mc.conversacion_id = c.id
            WHERE mc.id = ?
        ", [$mensajeId])->getRowArray();

        return $result;
    }

    /**
     * Marcar mensaje como leído
     */
    public function marcarMensajeLeido($mensajeId, $usuarioId)
    {
        $sql = "
            INSERT INTO mensajes_leidos_chat (mensaje_id, usuario_id, fecha_lectura) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE fecha_lectura = NOW()
        ";
        
        return $this->db->query($sql, [$mensajeId, $usuarioId]);
    }

    /**
     * Obtener mensajes no leídos de un usuario
     */
    public function getMensajesNoLeidos($usuarioId)
    {
        return $this->db->query("
            SELECT 
                mc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                c.id as conversacion_id
            FROM mensajes_chat mc
            JOIN usuarios u ON mc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            JOIN conversaciones c ON mc.conversacion_id = c.id
            WHERE mc.usuario_id != ? 
            AND mc.eliminado = FALSE
            AND mc.id NOT IN (
                SELECT mensaje_id 
                FROM mensajes_leidos_chat 
                WHERE usuario_id = ?
            )
            ORDER BY mc.fecha_envio DESC
        ", [$usuarioId, $usuarioId])->getResultArray();
    }

    /**
     * Obtener estadísticas de mensajes
     */
    public function getEstadisticasMensajes($usuarioId)
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total_mensajes,
                COUNT(CASE WHEN DATE(fecha_envio) = CURDATE() THEN 1 END) as mensajes_hoy,
                COUNT(CASE WHEN DATE(fecha_envio) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as mensajes_ayer,
                COUNT(CASE WHEN usuario_id = ? THEN 1 END) as mensajes_enviados,
                COUNT(CASE WHEN usuario_id != ? THEN 1 END) as mensajes_recibidos
            FROM mensajes_chat mc
            JOIN conversaciones c ON mc.conversacion_id = c.id
            WHERE (c.usuario1_id = ? OR c.usuario2_id = ?)
            AND mc.eliminado = FALSE
        ", [$usuarioId, $usuarioId, $usuarioId, $usuarioId])->getRowArray();

        return $result;
    }

    /**
     * Buscar mensajes por texto
     */
    public function buscarMensajes($usuarioId, $termino, $limit = 20)
    {
        return $this->db->query("
            SELECT 
                mc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                c.id as conversacion_id
            FROM mensajes_chat mc
            JOIN usuarios u ON mc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            JOIN conversaciones c ON mc.conversacion_id = c.id
            WHERE (c.usuario1_id = ? OR c.usuario2_id = ?)
            AND mc.mensaje LIKE ?
            AND mc.eliminado = FALSE
            ORDER BY mc.fecha_envio DESC
            LIMIT ?
        ", [$usuarioId, $usuarioId, "%{$termino}%", $limit])->getResultArray();
    }

    /**
     * Editar mensaje
     */
    public function editarMensaje($mensajeId, $nuevoMensaje, $usuarioId)
    {
        // Verificar que el mensaje pertenece al usuario
        $mensaje = $this->where('id', $mensajeId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$mensaje) {
            return false;
        }

        return $this->update($mensajeId, [
            'mensaje' => $nuevoMensaje,
            'editado' => true,
            'fecha_edicion' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Eliminar mensaje (soft delete)
     */
    public function eliminarMensaje($mensajeId, $usuarioId)
    {
        // Verificar que el mensaje pertenece al usuario
        $mensaje = $this->where('id', $mensajeId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$mensaje) {
            return false;
        }

        return $this->update($mensajeId, [
            'eliminado' => true,
            'fecha_eliminacion' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener mensajes recientes de todas las conversaciones del usuario
     */
    public function getMensajesRecientes($usuarioId, $limit = 10)
    {
        return $this->db->query("
            SELECT 
                mc.*,
                CONCAT(p.nombres, ' ', p.apellidos) as usuario_nombre,
                u.nombreusuario as usuario_usuario,
                c.id as conversacion_id,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as otro_usuario_id
            FROM mensajes_chat mc
            JOIN usuarios u ON mc.usuario_id = u.idusuario
            JOIN personas p ON u.idpersona = p.idpersona
            JOIN conversaciones c ON mc.conversacion_id = c.id
            WHERE (c.usuario1_id = ? OR c.usuario2_id = ?)
            AND mc.eliminado = FALSE
            ORDER BY mc.fecha_envio DESC
            LIMIT ?
        ", [$usuarioId, $usuarioId, $usuarioId, $limit])->getResultArray();
    }
}
