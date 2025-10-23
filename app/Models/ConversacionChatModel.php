<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversacionChatModel extends Model
{
    protected $table = 'conversaciones';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'usuario1_id',
        'usuario2_id',
        'ultimo_mensaje_id',
        'fecha_ultimo_mensaje',
        'mensajes_no_leidos_usuario1',
        'mensajes_no_leidos_usuario2'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    /**
     * Obtener conversaciones de un usuario
     */
    public function getConversacionesUsuario($usuarioId)
    {
        return $this->db->query("
            SELECT 
                c.*,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as otro_usuario_id,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as contacto_id,
                CONCAT(p.nombres, ' ', p.apellidos) as contacto_nombre,
                u_contacto.nombreusuario as contacto_usuario,
                u_contacto.email as contacto_email,
                mc.mensaje as ultimo_mensaje_texto,
                mc.tipo as ultimo_mensaje_tipo,
                mc.fecha_envio as ultimo_mensaje_fecha,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.mensajes_no_leidos_usuario1
                    ELSE c.mensajes_no_leidos_usuario2
                END as mensajes_no_leidos
            FROM conversaciones c
            JOIN usuarios u_contacto ON (
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END = u_contacto.idusuario
            )
            JOIN personas p ON u_contacto.idpersona = p.idpersona
            LEFT JOIN mensajes_chat mc ON c.ultimo_mensaje_id = mc.id
            WHERE (c.usuario1_id = ? OR c.usuario2_id = ?)
            ORDER BY c.fecha_ultimo_mensaje DESC
        ", [
            $usuarioId, $usuarioId, $usuarioId, $usuarioId, $usuarioId, $usuarioId
        ])->getResultArray();
    }

    /**
     * Obtener conversación completa con información de usuarios
     */
    public function getConversacionCompleta($conversacionId)
    {
        return $this->db->query("
            SELECT 
                c.*,
                CONCAT(p1.nombres, ' ', p1.apellidos) as usuario1_nombre,
                u1.nombreusuario as usuario1_usuario,
                u1.email as usuario1_email,
                CONCAT(p2.nombres, ' ', p2.apellidos) as usuario2_nombre,
                u2.nombreusuario as usuario2_usuario,
                u2.email as usuario2_email,
                mc.mensaje as ultimo_mensaje_texto,
                mc.tipo as ultimo_mensaje_tipo,
                mc.fecha_envio as ultimo_mensaje_fecha
            FROM conversaciones c
            JOIN usuarios u1 ON c.usuario1_id = u1.idusuario
            JOIN personas p1 ON u1.idpersona = p1.idpersona
            JOIN usuarios u2 ON c.usuario2_id = u2.idusuario
            JOIN personas p2 ON u2.idpersona = p2.idpersona
            LEFT JOIN mensajes_chat mc ON c.ultimo_mensaje_id = mc.id
            WHERE c.id = ?
        ", [$conversacionId])->getRowArray();
    }

    /**
     * Obtener conversación entre dos usuarios específicos
     */
    public function getConversacionEntreUsuarios($usuario1Id, $usuario2Id)
    {
        return $this->db->query("
            SELECT 
                c.*,
                CONCAT(p1.nombres, ' ', p1.apellidos) as usuario1_nombre,
                u1.nombreusuario as usuario1_usuario,
                CONCAT(p2.nombres, ' ', p2.apellidos) as usuario2_nombre,
                u2.nombreusuario as usuario2_usuario
            FROM conversaciones c
            JOIN usuarios u1 ON c.usuario1_id = u1.idusuario
            JOIN personas p1 ON u1.idpersona = p1.idpersona
            JOIN usuarios u2 ON c.usuario2_id = u2.idusuario
            JOIN personas p2 ON u2.idpersona = p2.idpersona
            WHERE (c.usuario1_id = ? AND c.usuario2_id = ?) 
            OR (c.usuario1_id = ? AND c.usuario2_id = ?)
        ", [$usuario1Id, $usuario2Id, $usuario2Id, $usuario1Id])->getRowArray();
    }

    /**
     * Crear nueva conversación entre dos usuarios
     */
    public function crearConversacion($usuario1Id, $usuario2Id)
    {
        // Verificar que no existe ya una conversación
        $existente = $this->getConversacionEntreUsuarios($usuario1Id, $usuario2Id);
        
        if ($existente) {
            return $existente['id'];
        }

        $data = [
            'usuario1_id' => $usuario1Id,
            'usuario2_id' => $usuario2Id,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }

    /**
     * Actualizar último mensaje de una conversación
     */
    public function actualizarUltimoMensaje($conversacionId, $mensajeId, $usuarioId)
    {
        $conversacion = $this->find($conversacionId);
        
        if (!$conversacion) {
            return false;
        }

        $data = [
            'ultimo_mensaje_id' => $mensajeId,
            'fecha_ultimo_mensaje' => date('Y-m-d H:i:s')
        ];

        // Incrementar contador de mensajes no leídos para el destinatario
        if ($conversacion['usuario1_id'] == $usuarioId) {
            $data['mensajes_no_leidos_usuario2'] = $conversacion['mensajes_no_leidos_usuario2'] + 1;
        } else {
            $data['mensajes_no_leidos_usuario1'] = $conversacion['mensajes_no_leidos_usuario1'] + 1;
        }

        return $this->update($conversacionId, $data);
    }

    /**
     * Marcar mensajes como leídos
     */
    public function marcarMensajesLeidos($conversacionId, $usuarioId)
    {
        $conversacion = $this->find($conversacionId);
        
        if (!$conversacion) {
            return false;
        }

        $data = [];

        // Resetear contador de mensajes no leídos
        if ($conversacion['usuario1_id'] == $usuarioId) {
            $data['mensajes_no_leidos_usuario1'] = 0;
        } else {
            $data['mensajes_no_leidos_usuario2'] = 0;
        }

        return $this->update($conversacionId, $data);
    }

    /**
     * Obtener conversaciones con mensajes no leídos
     */
    public function getConversacionesConMensajesNoLeidos($usuarioId)
    {
        return $this->db->query("
            SELECT 
                c.*,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as contacto_id,
                CONCAT(p.nombres, ' ', p.apellidos) as contacto_nombre,
                u_contacto.nombreusuario as contacto_usuario,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.mensajes_no_leidos_usuario1
                    ELSE c.mensajes_no_leidos_usuario2
                END as mensajes_no_leidos
            FROM conversaciones c
            JOIN usuarios u_contacto ON (
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END = u_contacto.idusuario
            )
            JOIN personas p ON u_contacto.idpersona = p.idpersona
            WHERE (c.usuario1_id = ? OR c.usuario2_id = ?)
            AND (
                (c.usuario1_id = ? AND c.mensajes_no_leidos_usuario1 > 0) OR
                (c.usuario2_id = ? AND c.mensajes_no_leidos_usuario2 > 0)
            )
            ORDER BY c.fecha_ultimo_mensaje DESC
        ", [
            $usuarioId, $usuarioId, $usuarioId, $usuarioId, $usuarioId, $usuarioId, $usuarioId
        ])->getResultArray();
    }

    /**
     * Obtener estadísticas de conversaciones
     */
    public function getEstadisticasConversaciones($usuarioId)
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total_conversaciones,
                COUNT(CASE WHEN fecha_ultimo_mensaje >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as conversaciones_activas_hoy,
                COUNT(CASE WHEN fecha_ultimo_mensaje >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as conversaciones_activas_semana,
                SUM(CASE WHEN usuario1_id = ? THEN mensajes_no_leidos_usuario1 ELSE mensajes_no_leidos_usuario2 END) as total_mensajes_no_leidos
            FROM conversaciones
            WHERE usuario1_id = ? OR usuario2_id = ?
        ", [$usuarioId, $usuarioId, $usuarioId])->getRowArray();

        return $result;
    }

    /**
     * Buscar conversaciones por nombre de usuario
     */
    public function buscarConversaciones($usuarioId, $termino)
    {
        return $this->db->query("
            SELECT 
                c.*,
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END as contacto_id,
                CONCAT(p.nombres, ' ', p.apellidos) as contacto_nombre,
                u_contacto.nombreusuario as contacto_usuario,
                u_contacto.email as contacto_email,
                mc.mensaje as ultimo_mensaje_texto,
                mc.fecha_envio as ultimo_mensaje_fecha
            FROM conversaciones c
            JOIN usuarios u_contacto ON (
                CASE 
                    WHEN c.usuario1_id = ? THEN c.usuario2_id 
                    ELSE c.usuario1_id 
                END = u_contacto.idusuario
            )
            JOIN personas p ON u_contacto.idpersona = p.idpersona
            LEFT JOIN mensajes_chat mc ON c.ultimo_mensaje_id = mc.id
            WHERE (c.usuario1_id = ? OR c.usuario2_id = ?)
            AND (
                CONCAT(p.nombres, ' ', p.apellidos) LIKE ? OR
                u_contacto.nombreusuario LIKE ? OR
                u_contacto.email LIKE ?
            )
            ORDER BY c.fecha_ultimo_mensaje DESC
        ", [
            $usuarioId, $usuarioId, $usuarioId, $usuarioId,
            "%{$termino}%", "%{$termino}%", "%{$termino}%"
        ])->getResultArray();
    }

    /**
     * Eliminar conversación (soft delete)
     */
    public function eliminarConversacion($conversacionId, $usuarioId)
    {
        $conversacion = $this->find($conversacionId);
        
        if (!$conversacion) {
            return false;
        }

        // Verificar que el usuario tiene acceso a esta conversación
        if ($conversacion['usuario1_id'] != $usuarioId && $conversacion['usuario2_id'] != $usuarioId) {
            return false;
        }

        // Marcar como eliminada para el usuario específico
        // Esto requeriría una tabla adicional para tracking de eliminaciones por usuario
        // Por ahora, simplemente retornamos true
        return true;
    }
}
