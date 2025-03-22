<?php
/**
 * API para buscar mensagens
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

// Verificar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Verificar parâmetro match_id
if (!isset($_GET['match_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Match ID não fornecido']);
    exit;
}

$db = Database::getInstance();
$match_id = (int)$_GET['match_id'];

// Verificar se o match existe e pertence ao usuário
$match = $db->single(
    "SELECT m.*, 
            u.id as other_user_id,
            u.name,
            u.username,
            u.avatar,
            u.is_online,
            u.last_active
     FROM matches m
     INNER JOIN users u ON (
         CASE 
             WHEN m.user1_id = ? THEN m.user2_id = u.id
             ELSE m.user1_id = u.id
         END
     )
     WHERE m.id = ? 
     AND (m.user1_id = ? OR m.user2_id = ?)
     AND m.status = 'matched'",
    [$_SESSION['user_id'], $match_id, $_SESSION['user_id'], $_SESSION['user_id']]
);

if (!$match) {
    http_response_code(404);
    echo json_encode(['error' => 'Match não encontrado']);
    exit;
}

// Buscar mensagens
$messages = $db->all(
    "SELECT m.*,
            u.name as sender_name,
            u.avatar as sender_avatar
     FROM messages m
     INNER JOIN users u ON m.sender_id = u.id
     WHERE m.match_id = ?
     ORDER BY m.created_at ASC",
    [$match_id]
);

// Marcar mensagens como lidas
$db->query(
    "UPDATE messages 
     SET is_read = 1, 
         read_at = NOW() 
     WHERE match_id = ? 
     AND sender_id != ? 
     AND is_read = 0",
    [$match_id, $_SESSION['user_id']]
);

// Registrar atividade
$db->query(
    "INSERT INTO activity_logs 
     (user_id, action, description, ip_address, user_agent)
     VALUES (?, 'read_messages', 'Leu mensagens da conversa #" . $match_id . "', ?, ?)",
    [
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]
);

// Formatar resposta
$response = [
    'success' => true,
    'match' => [
        'id' => $match['id'],
        'created_at' => $match['created_at'],
        'matched_at' => $match['matched_at']
    ],
    'user' => [
        'id' => $match['other_user_id'],
        'name' => $match['name'],
        'username' => $match['username'],
        'avatar' => !empty($match['avatar']) 
            ? '../uploads/avatars/' . $match['avatar'] 
            : '../assets/images/default-avatar.jpg',
        'is_online' => (bool)$match['is_online'],
        'last_active' => $match['last_active'] 
            ? timeAgo($match['last_active']) 
            : null
    ],
    'messages' => array_map(function($msg) {
        return [
            'id' => $msg['id'],
            'sender_id' => $msg['sender_id'],
            'sender_name' => $msg['sender_name'],
            'sender_avatar' => !empty($msg['sender_avatar']) 
                ? '../uploads/avatars/' . $msg['sender_avatar'] 
                : '../assets/images/default-avatar.jpg',
            'message' => htmlspecialchars($msg['message']),
            'is_read' => (bool)$msg['is_read'],
            'read_at' => $msg['read_at'] 
                ? timeAgo($msg['read_at']) 
                : null,
            'created_at' => timeAgo($msg['created_at'])
        ];
    }, $messages)
];

// Enviar resposta
header('Content-Type: application/json');
echo json_encode($response);