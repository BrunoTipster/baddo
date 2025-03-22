<?php
/**
 * Configurações do banco de dados
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:47:22
 */

// Prevenir acesso direto ao arquivo
defined('BASE_PATH') or exit('No direct script access allowed');

// Ambiente de desenvolvimento
if ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1') {
    // Configurações locais
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'badoo_clone');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATE', 'utf8mb4_unicode_ci');
    define('DB_PREFIX', '');
} else {
    // Configurações de produção
    define('DB_HOST', 'seu_host');
    define('DB_USER', 'seu_usuario');
    define('DB_PASS', 'sua_senha');
    define('DB_NAME', 'seu_banco');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
    define('DB_COLLATE', 'utf8mb4_unicode_ci');
    define('DB_PREFIX', '');
}

// Configurações do PDO
define('DB_DSN', "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET);
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ".DB_CHARSET." COLLATE ".DB_COLLATE
]);

// Configurações de backup
define('DB_BACKUP_PATH', BASE_PATH . '/backups');
define('DB_BACKUP_FILES', 5); // Número de backups a manter

// Configurações de conexão persistente
define('DB_PERSISTENT', false);

// Configurações de timeout
define('DB_TIMEOUT', 30); // segundos

// Configurações de reconexão
define('DB_MAX_TRIES', 3);
define('DB_RETRY_INTERVAL', 100000); // microssegundos

// Debug de queries
define('DB_DEBUG', DEBUG_MODE);
define('DB_LOG_PATH', LOG_PATH . '/sql.log');

// Limites de consulta
define('DB_MAX_JOIN_SIZE', 1000000);
define('DB_MAX_EXECUTION_TIME', 30); // segundos

// Cache de queries
define('DB_QUERY_CACHE', true);
define('DB_QUERY_CACHE_TIME', 3600); // 1 hora