<?php
/**
 * Configurações Globais
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 18:23:10
 */

// Prevenir acesso direto
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Configurações de Ambiente
define('ENV', 'development'); // development, staging, production
define('DEBUG_MODE', ENV === 'development');
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_MESSAGE', 'Sistema em manutenção. Voltaremos em breve!');

// Configurações do Site
define('SITE_NAME', 'BadooClone');
define('SITE_DESCRIPTION', 'Encontre pessoas interessantes perto de você');
define('SITE_URL', 'http://localhost/badoo-clone');
define('SITE_AUTHOR', 'BrunoTipster');
define('SITE_VERSION', '1.0.0');
define('SITE_EMAIL', 'contato@badooclone.com');
define('SITE_PHONE', '+55 11 99999-9999');
define('SITE_TIMEZONE', 'America/Sao_Paulo');

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'badoo_clone');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');
define('DB_PREFIX', '');

// Configurações de Sessão
define('SESSION_NAME', 'BADOOCLONE');
define('SESSION_LIFETIME', 7200); // 2 horas
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', false);
define('SESSION_HTTPONLY', true);
define('SESSION_SAVE_PATH', BASE_PATH . '/storage/sessions');

// Configurações de Cookie
define('COOKIE_PREFIX', 'bc_');
define('COOKIE_LIFETIME', 30 * 24 * 60 * 60); // 30 dias
define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '');
define('COOKIE_SECURE', false);
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');

// Configurações de Cache
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file'); // file, redis, memcached
define('CACHE_PREFIX', 'bc_cache_');
define('CACHE_PATH', BASE_PATH . '/storage/cache');
define('CACHE_TIME', 3600); // 1 hora

// Configurações de Upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('AVATAR_PATH', UPLOAD_PATH . '/avatars');
define('PHOTOS_PATH', UPLOAD_PATH . '/photos');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIMES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
]);

// Configurações de Email
define('MAIL_DRIVER', 'smtp');
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'seu@email.com');
define('MAIL_PASSWORD', 'sua_senha');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', 'noreply@badooclone.com');
define('MAIL_FROM_NAME', SITE_NAME);

// Configurações de API
define('API_VERSION', '1.0.0');
define('API_PREFIX', 'api/v1');
define('API_DEBUG', DEBUG_MODE);
define('API_KEY', 'sua_chave_secreta_aqui');
define('API_RATE_LIMIT', 60); // requisições por minuto
define('API_TIMEOUT', 30); // segundos

// Configurações de Segurança
define('SECURITY_SALT', 'sua_chave_secreta_muito_longa_e_aleatoria');
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 15 * 60); // 15 minutos
define('TOKEN_LIFETIME', 60 * 60); // 1 hora
define('CSRF_PROTECTION', true);
define('XSS_PROTECTION', true);
define('CORS_ENABLED', true);
define('CORS_ORIGINS', ['http://localhost']);

// Configurações de Geolocalização
define('DEFAULT_LATITUDE', -23.550520);
define('DEFAULT_LONGITUDE', -46.633308);
define('MAX_SEARCH_DISTANCE', 100); // km
define('DISTANCE_UNIT', 'km'); // km ou mi

// Configurações de Usuário
define('MIN_AGE', 18);
define('MAX_AGE', 99);
define('DEFAULT_ITEMS_PER_PAGE', 12);
define('MAX_PHOTOS', 6);
define('BIO_MAX_LENGTH', 500);
define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 20);
define('NAME_MIN_LENGTH', 2);
define('NAME_MAX_LENGTH', 50);

// Configurações do Chat
define('CHAT_MAX_MESSAGES', 100);
define('CHAT_MESSAGE_TIMEOUT', 30); // segundos
define('CHAT_MAX_LENGTH', 1000);
define('CHAT_FLOOD_CONTROL', 3); // segundos entre mensagens
define('CHAT_MEDIA_ENABLED', true);
define('CHAT_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('CHAT_MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

// Configurações de Log
define('LOG_ENABLED', true);
define('LOG_PATH', BASE_PATH . '/storage/logs');
define('LOG_LEVEL', DEBUG_MODE ? 'debug' : 'error');
define('LOG_MAX_FILES', 30);
define('LOG_FORMAT', '[%datetime%] %level_name%: %message%');

// Configurações de Assets
define('ASSETS_URL', SITE_URL . '/assets');
define('ASSETS_VERSION', '1.0.0');
define('ASSETS_CACHE', true);
define('CSS_PATH', '/css');
define('JS_PATH', '/js');
define('IMG_PATH', '/img');
define('FONTS_PATH', '/fonts');

// Configurações de Redes Sociais
define('SOCIAL_LOGIN_ENABLED', true);
define('FACEBOOK_APP_ID', '');
define('FACEBOOK_APP_SECRET', '');
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('APPLE_CLIENT_ID', '');
define('APPLE_CLIENT_SECRET', '');

// Configurações de Notificações
define('NOTIFICATIONS_ENABLED', true);
define('PUSH_ENABLED', false);
define('PUSH_KEY', '');
define('PUSH_AUTH', '');
define('PUSH_ENDPOINT', '');
define('EMAIL_NOTIFICATIONS', true);
define('SMS_NOTIFICATIONS', false);

// Configurações de PWA
define('PWA_ENABLED', true);
define('PWA_NAME', SITE_NAME);
define('PWA_SHORT_NAME', 'BC');
define('PWA_DESCRIPTION', SITE_DESCRIPTION);
define('PWA_THEME_COLOR', '#FF77A9');
define('PWA_BACKGROUND_COLOR', '#FFFFFF');

// Inicialização do Sistema
date_default_timezone_set(SITE_TIMEZONE);
error_reporting(DEBUG_MODE ? E_ALL : 0);
ini_set('display_errors', DEBUG_MODE ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php-errors.log');

// Configurações de Sessão
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);
ini_set('session.cookie_path', SESSION_PATH);
ini_set('session.cookie_domain', SESSION_DOMAIN);
ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
ini_set('session.cookie_samesite', COOKIE_SAMESITE);
ini_set('session.save_path', SESSION_SAVE_PATH);
ini_set('session.name', SESSION_NAME);

// Criar diretórios necessários
$directories = [
    SESSION_SAVE_PATH,
    CACHE_PATH,
    LOG_PATH,
    UPLOAD_PATH,
    AVATAR_PATH,
    PHOTOS_PATH
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Funções Globais
function isDebug() {
    return DEBUG_MODE;
}

function asset($path) {
    return ASSETS_URL . '/' . ltrim($path, '/') . 
           (ASSETS_CACHE ? '?v=' . ASSETS_VERSION : '');
}

function config($key, $default = null) {
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

function env($key, $default = null) {
    return getenv($key) ?: $default;
}

// Verificar requisitos mínimos
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('PHP 8.0+ é necessário');
}

if (!extension_loaded('pdo_mysql')) {
    die('Extensão PDO MySQL é necessária');
}

if (!extension_loaded('gd')) {
    die('Extensão GD é necessária');
}

// Registrar handler de erros em produção
if (!DEBUG_MODE) {
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log(sprintf(
            "Erro [%d]: %s\nArquivo: %s\nLinha: %d\n",
            $errno,
            $errstr,
            $errfile,
            $errline
        ));
        return true;
    });
}