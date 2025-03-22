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

try {
    $db = Database::getInstance();
    
    // Iniciar transação
    $db->getConnection()->beginTransaction();

    // Registrar atividade
    $db->query(
        "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
         VALUES (?, 'delete_account', 'Conta excluída', ?, ?)",
        [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
    );

    // Marcar conta como excluída
    $db->query(
        "UPDATE users 
         SET status = 'deleted',
             email = CONCAT('deleted_', id, '_', email),
             username = CONCAT('deleted_', id, '_', username)
         WHERE id = ?",
        [$_SESSION['user_id']]
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