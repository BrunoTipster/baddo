<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do usuário não fornecido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Verificar se já existe match
    $match = $db->single(
        "SELECT * FROM matches 
         WHERE (user1_id = ? AND user2_id = ?)
         OR (user1_id = ? AND user2_id = ?)",
        [$_SESSION['user_id'], $data['user_id'], $data['user_id'], $_SESSION['user_id']]
    );

    if ($match) {
        throw new Exception('Você já interagiu com este usuário');
    }

    // Criar match
    $db->query(
        "INSERT INTO matches (user1_id, user2_id, status, matched_at)
         VALUES (?, ?, ?, NOW())",
        [$_SESSION['user_id'], $data['user_id'], 'pending']
    );

    // Verificar se é match mútuo
    $otherLike = $db->single(
        "SELECT m.*, u.name, u.avatar 
         FROM matches m 
         JOIN users u ON u.id = m.user1_id
         WHERE m.user1_id = ? AND m.user2_id = ? 
         AND m.status = 'pending'",
        [$data['user_id'], $_SESSION['user_id']]
    );

    if ($otherLike) {
        // Atualizar status dos matches
        $db->query(
            "UPDATE matches 
             SET status = 'matched', matched_at = NOW() 
             WHERE (user1_id = ? AND user2_id = ?)
             OR (user1_id = ? AND user2_id = ?)",
            [
                $_SESSION['user_id'], 
                $data['user_id'], 
                $data['user_id'], 
                $_SESSION['user_id']
            ]
        );

        // Criar notificações
        $db->query(
            "INSERT INTO notifications (user_id, type, title, message, related_id)
             VALUES (?, 'match', 'Novo Match!', 'Você tem um novo match!', ?)",
            [$data['user_id'], $_SESSION['user_id']]
        );

        $db->query(
            "INSERT INTO notifications (user_id, type, title, message, related_id)
             VALUES (?, 'match', 'Novo Match!', 'Você tem um novo match!', ?)",
            [$_SESSION['user_id'], $data['user_id']]
        );

        echo json_encode([
            'success' => true,
            'match' => [
                'id' => $otherLike['id'],
                'name' => $otherLike['name'],
                'avatar' => $otherLike['avatar']
            ]
        ]);
    } else {
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}