<?php
/**
 * Funções Auxiliares do Sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:40:14
 */

/**
 * Verifica se o usuário está logado
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário é admin
 */
function isAdmin(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Verifica se o usuário tem uma role específica
 */
function hasRole(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Redireciona para uma URL
 */
function redirect(string $path): void {
    header("Location: " . SITE_URL . "/$path");
    exit;
}

/**
 * Escapa string para saída segura
 */
function escape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Gera token CSRF
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formata data/hora
 */
function formatDateTime(string $datetime): string {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Retorna tempo relativo (há X minutos)
 */
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return 'agora mesmo';
    }
    
    $intervals = [
        31536000 => 'ano',
        2592000 => 'mês',
        604800 => 'semana',
        86400 => 'dia',
        3600 => 'hora',
        60 => 'minuto'
    ];
    
    foreach ($intervals as $seconds => $label) {
        $d = $diff / $seconds;
        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $label . ($r > 1 ? 's' : '') . ' atrás';
        }
    }
}

/**
 * Upload de arquivo
 */
function uploadFile(array $file, string $type = 'photo'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Tipos permitidos por categoria
    $allowedTypes = [
        'photo' => ['image/jpeg', 'image/png', 'image/gif'],
        'document' => ['application/pdf', 'application/msword']
    ];

    if (!isset($allowedTypes[$type]) || !in_array($file['type'], $allowedTypes[$type])) {
        return null;
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return null;
    }

    $filename = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $directory = $type === 'photo' ? 'photos' : 'documents';
    $path = BASE_PATH . "/uploads/$directory/";
    
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $path . $filename)) {
        return $filename;
    }

    return null;
}

/**
 * Gera slug a partir de string
 */
function generateSlug(string $string): string {
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $string = strtolower(trim($string));
    $string = preg_replace('/[\s]+/', '-', $string);
    return $string;
}

/**
 * Verifica permissões de acesso
 */
function checkPermission(string $permission): bool {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    return in_array($permission, $_SESSION['user_permissions']);
}

/**
 * Log de atividade do usuário
 */
function logActivity(string $action, string $description = ''): void {
    if (!isLoggedIn()) {
        return;
    }

    global $conn;
    $userId = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    $stmt = $conn->prepare("
        INSERT INTO activity_logs 
        (user_id, action, description, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param('issss', $userId, $action, $description, $ip, $userAgent);
    $stmt->execute();
}

/**
 * Verifica se é uma requisição AJAX
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Retorna resposta JSON
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Debug helper
 */
function dd($var): void {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    exit;
}