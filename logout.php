<?php
/**
 * Página de Logout
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

// Definir caminho base
define('BASE_PATH', __DIR__);

// Carregar dependências
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

// Iniciar sessão
session_start();

// Se houver usuário logado
if (isset($_SESSION['user_id'])) {
    try {
        $db = Database::getInstance();
        
        // Atualizar status online
        $db->query(
            "UPDATE users SET is_online = 0, last_active = NOW() WHERE id = ?",
            [$_SESSION['user_id']]
        );

        // Registrar logout
        $db->query(
            "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
             VALUES (?, 'logout', 'Logout realizado', ?, ?)",
            [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
        );
    } catch (PDOException $e) {
        // Ignora erros no logout
    }
}

// Destruir sessão
session_unset();
session_destroy();

// Redirecionar para login
header('Location: login.php');
exit;