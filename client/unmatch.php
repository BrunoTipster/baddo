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
if (!isset($data['match_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Match ID não fornecido']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Verificar se o match existe e pertence ao usuário
    $match = $db->single(
        "SELECT * FROM matches WHERE id = ? AND (user1_id = ? OR user2_id = ?)",
        [$data['match_id'], $_SESSION['user_id'], $_SESSION['user_id']]
    );

    if (!$match) {
        throw new Exception('Match não encontrado');
    }

    // Iniciar transação
    $db->getConnection()->beginTransaction();

    // Deletar mensagens
    $db->query(
        "DELETE FROM messages WHERE match_id = ?",
        [$data['match_id']]
    );

    // Deletar match
    $db->query(
        "DELETE FROM matches WHERE id = ?",
        [$data['match_id']]
    );

    // Registrar atividade
    $db->query(
        "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
         VALUES (?, 'unmatch', 'Desfez match #" . $data['match_id'] . "', ?, ?)",
        [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
    );

    // Confirmar transação
    $db->getConnection()->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->getConnection()->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}