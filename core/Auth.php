<?php
/**
 * Classe de autenticação
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:39:45
 */

class Auth {
    private $db;
    private $user;

    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Tenta autenticar usuário
     */
    public function attempt($username, $password) {
        try {
            $conn = $this->db->getConnection();

            // Verificar tentativas de login
            $stmt = $conn->prepare("
                SELECT COUNT(*) as attempts 
                FROM activity_logs 
                WHERE action = 'failed_login' 
                AND ip_address = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt->bind_param('s', $ip);
            $stmt->execute();
            $attempts = $stmt->get_result()->fetch_assoc()['attempts'];

            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                throw new Exception("Muitas tentativas de login. Tente novamente mais tarde.");
            }

            // Buscar usuário
            $stmt = $conn->prepare("
                SELECT * FROM users 
                WHERE username = ? 
                AND status = 'active' 
                LIMIT 1
            ");
            
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!$user || !password_verify($password, $user['password'])) {
                $this->logFailedAttempt($username);
                return false;
            }

            // Login bem sucedido
            $this->user = $user;
            $this->createSession();
            $this->updateLastLogin();

            return true;

        } catch (Exception $e) {
            error_log("Auth Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra tentativa falha de login
     */
    private function logFailedAttempt($username) {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                INSERT INTO activity_logs 
                (action, description, ip_address, user_agent) 
                VALUES ('failed_login', ?, ?, ?)
            ");
            
            $description = "Tentativa de login falha para usuário: " . $username;
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            $stmt->bind_param('sss', $description, $ip, $userAgent);
            $stmt->execute();

        } catch (Exception $e) {
            error_log("Failed Login Log Error: " . $e->getMessage());
        }
    }

    /**
     * Cria sessão do usuário
     */
    private function createSession() {
        $_SESSION['user_id'] = $this->user['id'];
        $_SESSION['username'] = $this->user['username'];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Atualiza último login
     */
    private function updateLastLogin() {
        try {
            $conn = $this->db->getConnection();
            
            $stmt = $conn->prepare("
                UPDATE users 
                SET last_login = NOW(),
                    is_online = TRUE,
                    failed_attempts = 0
                WHERE id = ?
            ");
            
            $stmt->bind_param('i', $this->user['id']);
            $stmt->execute();

            // Log de atividade
            $stmt = $conn->prepare("
                INSERT INTO activity_logs 
                (user_id, action, description, ip_address, user_agent)
                VALUES (?, 'login', 'Login realizado com sucesso', ?, ?)
            ");
            
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            
            $stmt->bind_param('iss', $this->user['id'], $ip, $userAgent);
            $stmt->execute();

        } catch (Exception $e) {
            error_log("Login Update Error: " . $e->getMessage());
        }
    }

    /**
     * Verifica se usuário está logado
     */
    public function check() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Retorna usuário atual
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }

        if (!$this->user) {
            $this->user = (new User())->find($_SESSION['user_id']);
        }

        return $this->user;
    }

    /**
     * Fazer logout
     */
    public function logout() {
        try {
            if ($this->check()) {
                $conn = $this->db->getConnection();
                
                // Atualizar status online
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET is_online = FALSE,
                        last_active = NOW()
                    WHERE id = ?
                ");
                
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();

                // Log de atividade
                $stmt = $conn->prepare("
                    INSERT INTO activity_logs 
                    (user_id, action, description, ip_address, user_agent)
                    VALUES (?, 'logout', 'Logout realizado com sucesso', ?, ?)
                ");
                
                $ip = $_SERVER['REMOTE_ADDR'];
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
                
                $stmt->bind_param('iss', $_SESSION['user_id'], $ip, $userAgent);
                $stmt->execute();
            }

            // Destruir sessão
            session_destroy();
            $this->user = null;
            
            return true;

        } catch (Exception $e) {
            error_log("Logout Error: " . $e->getMessage());
            return false;
        }
    }
}