<?php
/**
 * Funções de Autenticação
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @date 2025-03-22
 */

/**
 * Iniciar sessão
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurar sessão
        session_name(SESSION_NAME);
        
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => SESSION_PATH,
            'domain' => SESSION_DOMAIN,
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => COOKIE_SAMESITE
        ]);

        session_start();
    }
}

/**
 * Verificar autenticação
 */
function checkAuth() {
    // Iniciar sessão se necessário
    initSession();

    // Verificar se usuário está logado
    if (!isset($_SESSION['user_id'])) {
        // Tentar autenticação por cookie
        if (isset($_COOKIE['remember_token'])) {
            try {
                $db = Database::getInstance();
                $user = $db->single(
                    "SELECT * FROM users 
                     WHERE remember_token = ? 
                     AND remember_expires > NOW() 
                     AND status = 'active'",
                    [$_COOKIE['remember_token']]
                );

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['avatar'] = $user['avatar'];
                    
                    // Atualizar status
                    updateUserStatus($user['id']);
                    
                    return true;
                }
            } catch (Exception $e) {
                error_log("Auth Error: " . $e->getMessage());
            }
        }
        return false;
    }

    // Atualizar status do usuário logado
    updateUserStatus($_SESSION['user_id']);
    return true;
}

/**
 * Atualizar status do usuário
 */
function updateUserStatus($userId) {
    try {
        $db = Database::getInstance();
        $db->query(
            "UPDATE users 
             SET is_online = 1,
                 last_active = NOW() 
             WHERE id = ?",
            [$userId]
        );
    } catch (Exception $e) {
        error_log("Status Update Error: " . $e->getMessage());
    }
}

/**
 * Fazer logout
 */
function logout() {
    // Iniciar sessão se necessário
    initSession();

    // Se o usuário estiver logado
    if (isset($_SESSION['user_id'])) {
        try {
            $db = Database::getInstance();
            
            // Atualizar status do usuário
            $db->query(
                "UPDATE users 
                 SET is_online = 0,
                 last_active = NOW() 
                 WHERE id = ?",
                [$_SESSION['user_id']]
            );

            // Registrar atividade
            $db->query(
                "INSERT INTO activity_logs (
                    user_id, action, description, 
                    ip_address, user_agent
                ) VALUES (
                    ?, 'logout', 'Logout realizado',
                    ?, ?
                )",
                [
                    $_SESSION['user_id'],
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]
            );

        } catch (Exception $e) {
            error_log("Logout Error: " . $e->getMessage());
        }
    }

    // Destruir sessão
    session_destroy();

    // Remover cookies
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// Inicializar sessão
initSession();