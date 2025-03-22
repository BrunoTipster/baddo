<?php
/**
 * Modelo de mensagens
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:39:45
 */

class Message {
    private $db;

    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Envia uma mensagem
     */
    public function send($matchId, $senderId, $message) {
        try {
            $conn = $this->db->getConnection();

            $this->db->beginTransaction();

            // Verifica se match existe e está ativo
            $stmt = $conn->prepare("
                SELECT user1_id, user2_id 
                FROM matches 
                WHERE id = ? 
                AND status = 'matched'
                AND (user1_id = ? OR user2_id = ?)
            ");
            
            $stmt->bind_param('iii', $matchId, $senderId, $senderId);
            $stmt->execute();
            
            $match = $stmt->get_result()->fetch_assoc();

            if (!$match) {
                throw new Exception("Match não encontrado ou inativo");
            }

            // Envia mensagem
            $stmt = $conn->prepare("
                INSERT INTO messages 
                (match_id, sender_id, message)
                VALUES (?, ?, ?)
            ");
            
            $stmt->bind_param('iis', $matchId, $senderId, $message);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao enviar mensagem");
            }

            $messageId = $stmt->insert_id;

            // Cria notificação
            $receiverId = $match['user1_id'] == $senderId ? 
                         $match['user2_id'] : 
                         $match['user1_id'];

            $stmt = $conn->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, related_id)
                VALUES (?, 'message', 'Nova mensagem', ?, ?)
            ");
            
            $notificationMessage = "Você recebeu uma nova mensagem";
            $stmt->bind_param('isi', $receiverId, $notificationMessage, $messageId);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao criar notificação");
            }

            $this->db->commit();
            return $messageId;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Message Send Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca mensagens de um match
     */
    public function getMessages($matchId, $userId, $limit = 50, $before = null) {
        try {
            $conn = $this->db->getConnection();

            $sql = "
                SELECT m.*,
                       u.username, u.avatar
                FROM messages m
                JOIN users u ON u.id = m.sender_id
                JOIN matches mt ON mt.id = m.match_id
                WHERE m.match_id = ?
                AND mt.status = 'matched'
                AND (mt.user1_id = ? OR mt.user2_id = ?)
            ";

            if ($before) {
                $sql .= " AND m.id < ?";
            }

            $sql .= " ORDER BY m.created_at DESC LIMIT ?";

            $stmt = $conn->prepare($sql);

            if ($before) {
                $stmt->bind_param('iiiii', $matchId, $userId, $userId, $before, $limit);
            } else {
                $stmt->bind_param('iiii', $matchId, $userId, $userId, $limit);
            }

            $stmt->execute();
            return array_reverse($stmt->get_result()->fetch_all(MYSQLI_ASSOC));

        } catch (Exception $e) {
            error_log("Message List Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marca mensagens como lidas
     */
    public function markAsRead($matchId, $userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_read = TRUE,
                    read_at = NOW()
                WHERE match_id = ?
                AND sender_id != ?
                AND is_read = FALSE
            ");
            
            $stmt->bind_param('ii', $matchId, $userId);
            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Message Mark Read Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conta mensagens não lidas
     */
    public function countUnread($userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM messages m
                JOIN matches mt ON mt.id = m.match_id
                WHERE (mt.user1_id = ? OR mt.user2_id = ?)
                AND m.sender_id != ?
                AND m.is_read = FALSE
                AND mt.status = 'matched'
            ");
            
            $stmt->bind_param('iii', $userId, $userId, $userId);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_assoc()['count'];

        } catch (Exception $e) {
            error_log("Message Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Deleta mensagem
     */
    public function delete($messageId, $userId) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                DELETE FROM messages 
                WHERE id = ?
                AND sender_id = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            
            $stmt->bind_param('ii', $messageId, $userId);
            return $stmt->execute() && $stmt->affected_rows > 0;

        } catch (Exception $e) {
            error_log("Message Delete Error: " . $e->getMessage());
            return false;
        }
    }
}