<?php
declare(strict_types=1);

/**
 * Modelo de matches entre usuários
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:44:58
 */

class UserMatch
{
    private Database $db;

    /**
     * Construtor com injeção de dependência
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Cria um novo match entre usuários
     * 
     * @param int $user1Id ID do primeiro usuário
     * @param int $user2Id ID do segundo usuário
     * @return int|false ID do match criado ou false em caso de erro
     */
    public function create(int $user1Id, int $user2Id): int|false
    {
        try {
            $conn = $this->db->getConnection();

            // Verifica se já existe match
            $stmt = $conn->prepare("
                SELECT id FROM matches 
                WHERE (user1_id = ? AND user2_id = ?)
                   OR (user1_id = ? AND user2_id = ?)
                LIMIT 1
            ");
            
            $stmt->bind_param('iiii', $user1Id, $user2Id, $user2Id, $user1Id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                return false;
            }

            // Cria novo match
            $stmt = $conn->prepare("
                INSERT INTO matches 
                (user1_id, user2_id, status, created_at)
                VALUES (?, ?, 'pending', NOW())
            ");
            
            $stmt->bind_param('ii', $user1Id, $user2Id);
            
            return $stmt->execute() ? $stmt->insert_id : false;

        } catch (Exception $e) {
            error_log("Match Create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aceita um match
     * 
     * @param int $matchId ID do match
     * @param int $userId ID do usuário que está aceitando
     * @return bool Sucesso ou falha da operação
     */
    public function accept(int $matchId, int $userId): bool
    {
        try {
            $conn = $this->db->getConnection();

            $this->db->beginTransaction();

            // Atualiza status do match
            $stmt = $conn->prepare("
                UPDATE matches 
                SET status = 'matched',
                    matched_at = NOW(),
                    updated_at = NOW()
                WHERE id = ? 
                AND (user1_id = ? OR user2_id = ?)
                AND status = 'pending'
            ");
            
            $stmt->bind_param('iii', $matchId, $userId, $userId);
            
            if (!$stmt->execute() || $stmt->affected_rows === 0) {
                $this->db->rollback();
                return false;
            }

            // Busca dados do match
            $stmt = $conn->prepare("
                SELECT user1_id, user2_id 
                FROM matches 
                WHERE id = ?
                LIMIT 1
            ");
            
            $stmt->bind_param('i', $matchId);
            $stmt->execute();
            
            $match = $stmt->get_result()->fetch_assoc();

            if (!$match) {
                $this->db->rollback();
                return false;
            }

            // Determina o outro usuário
            $otherUserId = $match['user1_id'] == $userId ? 
                          $match['user2_id'] : 
                          $match['user1_id'];

            // Cria notificação
            $stmt = $conn->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, related_id, created_at)
                VALUES (?, 'match', 'Novo Match!', 'Você tem um novo match!', ?, NOW())
            ");
            
            $stmt->bind_param('ii', $otherUserId, $matchId);
            
            if (!$stmt->execute()) {
                $this->db->rollback();
                return false;
            }

            // Log da atividade
            $stmt = $conn->prepare("
                INSERT INTO activity_logs 
                (user_id, action, description, ip_address, user_agent, created_at)
                VALUES (?, 'match_accept', 'Match aceito', ?, ?, NOW())
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            $stmt->bind_param('iss', $userId, $ip, $userAgent);
            
            if (!$stmt->execute()) {
                $this->db->rollback();
                return false;
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Match Accept Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rejeita um match
     * 
     * @param int $matchId ID do match
     * @param int $userId ID do usuário que está rejeitando
     * @return bool Sucesso ou falha da operação
     */
    public function reject(int $matchId, int $userId): bool
    {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE matches 
                SET status = 'rejected',
                    updated_at = NOW()
                WHERE id = ? 
                AND (user1_id = ? OR user2_id = ?)
                AND status = 'pending'
            ");
            
            $stmt->bind_param('iii', $matchId, $userId, $userId);
            return $stmt->execute() && $stmt->affected_rows > 0;

        } catch (Exception $e) {
            error_log("Match Reject Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca matches pendentes
     * 
     * @param int $userId ID do usuário
     * @param int $limit Limite de resultados
     * @return array Lista de matches pendentes
     */
    public function getPending(int $userId, int $limit = 10): array
    {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT 
                    m.*, 
                    u.username,
                    u.name,
                    u.avatar,
                    u.age,
                    u.city,
                    u.country,
                    u.is_online,
                    (SELECT COUNT(*) FROM user_photos WHERE user_id = u.id) as photos_count
                FROM matches m
                JOIN users u ON (
                    CASE 
                        WHEN m.user1_id = ? THEN u.id = m.user2_id
                        ELSE u.id = m.user1_id
                    END
                )
                WHERE (m.user1_id = ? OR m.user2_id = ?)
                AND m.status = 'pending'
                AND u.status = 'active'
                ORDER BY m.created_at DESC
                LIMIT ?
            ");
            
            $stmt->bind_param('iiii', $userId, $userId, $userId, $limit);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("Match Pending Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca matches ativos
     * 
     * @param int $userId ID do usuário
     * @param int $limit Limite de resultados
     * @return array Lista de matches ativos
     */
    public function getMatched(int $userId, int $limit = 10): array
    {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT 
                    m.*, 
                    u.username,
                    u.name,
                    u.avatar,
                    u.age,
                    u.city,
                    u.country,
                    u.is_online,
                    u.last_active,
                    (SELECT COUNT(*) FROM user_photos WHERE user_id = u.id) as photos_count,
                    (SELECT COUNT(*) FROM messages WHERE match_id = m.id) as messages_count,
                    (SELECT COUNT(*) 
                     FROM messages 
                     WHERE match_id = m.id 
                     AND sender_id != ? 
                     AND is_read = 0) as unread_count,
                    (SELECT message 
                     FROM messages 
                     WHERE match_id = m.id 
                     ORDER BY created_at DESC 
                     LIMIT 1) as last_message,
                    (SELECT created_at 
                     FROM messages 
                     WHERE match_id = m.id 
                     ORDER BY created_at DESC 
                     LIMIT 1) as last_message_at
                FROM matches m
                JOIN users u ON (
                    CASE 
                        WHEN m.user1_id = ? THEN u.id = m.user2_id
                        ELSE u.id = m.user1_id
                    END
                )
                WHERE (m.user1_id = ? OR m.user2_id = ?)
                AND m.status = 'matched'
                AND u.status = 'active'
                ORDER BY last_message_at DESC NULLS LAST, 
                         m.matched_at DESC
                LIMIT ?
            ");
            
            $stmt->bind_param('iiiii', $userId, $userId, $userId, $userId, $limit);
            $stmt->execute();
            
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            error_log("Match List Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Conta matches não lidos
     * 
     * @param int $userId ID do usuário
     * @return int Número de matches não lidos
     */
    public function countUnread(int $userId): int
    {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT m.id) as count
                FROM matches m
                JOIN messages msg ON msg.match_id = m.id
                WHERE (m.user1_id = ? OR m.user2_id = ?)
                AND m.status = 'matched'
                AND msg.sender_id != ?
                AND msg.is_read = 0
            ");
            
            $stmt->bind_param('iii', $userId, $userId, $userId);
            $stmt->execute();
            
            return (int) $stmt->get_result()->fetch_assoc()['count'];

        } catch (Exception $e) {
            error_log("Match Count Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica se dois usuários têm match
     * 
     * @param int $user1Id ID do primeiro usuário
     * @param int $user2Id ID do segundo usuário
     * @return bool|array False se não houver match, ou dados do match se existir
     */
    public function checkMatch(int $user1Id, int $user2Id): bool|array
    {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                SELECT *
                FROM matches 
                WHERE ((user1_id = ? AND user2_id = ?)
                    OR (user1_id = ? AND user2_id = ?))
                AND status = 'matched'
                LIMIT 1
            ");
            
            $stmt->bind_param('iiii', $user1Id, $user2Id, $user2Id, $user1Id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->num_rows > 0 ? $result->fetch_assoc() : false;

        } catch (Exception $e) {
            error_log("Match Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desfaz um match
     * 
     * @param int $matchId ID do match
     * @param int $userId ID do usuário que está desfazendo
     * @return bool Sucesso ou falha da operação
     */
    public function unmatch(int $matchId, int $userId): bool
    {
        try {
            $conn = $this->db->getConnection();

            $this->db->beginTransaction();

            // Verifica se o match existe e pertence ao usuário
            $stmt = $conn->prepare("
                SELECT user1_id, user2_id
                FROM matches 
                WHERE id = ?
                AND (user1_id = ? OR user2_id = ?)
                AND status = 'matched'
                LIMIT 1
            ");
            
            $stmt->bind_param('iii', $matchId, $userId, $userId);
            $stmt->execute();
            
            $match = $stmt->get_result()->fetch_assoc();

            if (!$match) {
                $this->db->rollback();
                return false;
            }

            // Atualiza status do match
            $stmt = $conn->prepare("
                UPDATE matches 
                SET status = 'unmatched',
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->bind_param('i', $matchId);
            
            if (!$stmt->execute()) {
                $this->db->rollback();
                return false;
            }

            // Notifica o outro usuário
            $otherUserId = $match['user1_id'] == $userId ? 
                          $match['user2_id'] : 
                          $match['user1_id'];

            $stmt = $conn->prepare("
                INSERT INTO notifications 
                (user_id, type, title, message, related_id, created_at)
                VALUES (?, 'unmatch', 'Match desfeito', 'Um usuário desfez o match com você', ?, NOW())
            ");
            
            $stmt->bind_param('ii', $otherUserId, $matchId);
            
            if (!$stmt->execute()) {
                $this->db->rollback();
                return false;
            }

            // Log da atividade
            $stmt = $conn->prepare("
                INSERT INTO activity_logs 
                (user_id, action, description, ip_address, user_agent, created_at)
                VALUES (?, 'unmatch', 'Match desfeito', ?, ?, NOW())
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            $stmt->bind_param('iss', $userId, $ip, $userAgent);
            
            if (!$stmt->execute()) {
                $this->db->rollback();
                return false;
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Unmatch Error: " . $e->getMessage());
            return false;
        }
    }
}