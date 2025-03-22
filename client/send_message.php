<?php
/**
 * API para enviar mensagens
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

// Verificar método e tipo de conteúdo
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    !isset($_SERVER['CONTENT_TYPE']) || 
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Método ou tipo de conteúdo inválido']);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);

// Validar dados
if (!isset($data['match_id']) || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$match_id = (int)$data['match_id'];
$message = trim($data['message']);

// Validar mensagem
if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensagem vazia']);
    exit;
}

if (mb_strlen($message) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensagem muito longa']);
    exit;
}

$db = Database::getInstance();

// Verificar se o match existe e pertence ao usuário
$match = $db->single(
    "SELECT m.*, 
            u.id as recipient_id,
            u.notifications_enabled
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

try {
    // Iniciar transação
    $db->getConnection()->beginTransaction();

    // Inserir mensagem
    $db->query(
        "INSERT INTO messages 
         (match_id, sender_id, message) 
         VALUES (?, ?, ?)",
        [$match_id, $_SESSION['user_id'], $message]
    );
    
    $message_id = $db->lastInsertId();

    // Criar notificação se o usuário tiver habilitado
    if ($match['notifications_enabled']) {
        $db->query(
            "INSERT INTO notifications 
             (user_id, type, title, message, related_id)
             VALUES (?, 'message', 'Nova mensagem', ?, ?)",
            [
                $match['recipient_id'],
                'Você recebeu uma nova mensagem',
                $message_id
            ]
        );
    }

    // Registrar atividade
    $db->query(
        "INSERT INTO activity_logs 
         (user_id, action, description, ip_address, user_agent)
         VALUES (?, 'send_message', 'Enviou mensagem na conversa #" . $match_id . "', ?, ?)",
        [
            $_SESSION['user_id'],
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]
    );

    // Atualizar última atividade do usuário
    $db->query(
        "UPDATE users 
         SET last_active = NOW() 
         WHERE id = ?",
        [$_SESSION['user_id']]
    );

    // Confirmar transação
    $db->getConnection()->commit();

    // Buscar mensagem inserida
    $message = $db->single(
        "SELECT m.*,
                u.name as sender_name,
                u.avatar as sender_avatar
         FROM messages m
         INNER JOIN users u ON m.sender_id = u.id
         WHERE m.id = ?",
        [$message_id]
    );

    // Formatar resposta
    $response = [
        'success' => true,
        'message' => [
            'id' => $message['id'],
            'sender_id' => $message['sender_id'],
            'sender_name' => $message['sender_name'],
            'sender_avatar' => !empty($message['sender_avatar']) 
                ? '../uploads/avatars/' . $message['sender_avatar'] 
                : '../assets/images/default-avatar.jpg',
            'message' => htmlspecialchars($message['message']),
            'is_read' => (bool)$message['is_read'],
            'created_at' => timeAgo($message['created_at'])
        ]
    ];

    // Enviar resposta
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    // Reverter transação em caso de erro
    $db->getConnection()->rollBack();

    // Log do erro
    logMessage("Erro ao enviar mensagem: " . $e->getMessage(), 'error');

    // Resposta de erro
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao enviar mensagem',
        'message' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}