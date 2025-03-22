<?php
/**
 * Inicialização do Sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @date 2025-03-22
 */

// Definir BASE_PATH se não estiver definido
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Carregar configurações
require_once BASE_PATH . '/config/config.php';

// Autoloader
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/utils/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Carregar funções auxiliares
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/auth.php';