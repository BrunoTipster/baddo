<?php
/**
 * Funções Auxiliares
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

if (!function_exists('isDebug')) {
    function isDebug() {
        return defined('DEBUG_MODE') && DEBUG_MODE;
    }
}

if (!function_exists('getSystemInfo')) {
    function getSystemInfo() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'database' => 'MySQL ' . (new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS
            ))->getAttribute(PDO::ATTR_SERVER_VERSION),
            'timezone' => date_default_timezone_get(),
            'debug_mode' => DEBUG_MODE ? 'On' : 'Off'
        ];
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'agora mesmo';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hora' . ($hours > 1 ? 's' : '');
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' dia' . ($days > 1 ? 's' : '');
        } else {
            return date('d/m/Y', $time);
        }
    }
}

if (!function_exists('calculateAge')) {
    function calculateAge($birthDate) {
        return date_diff(date_create($birthDate), date_create('today'))->y;
    }
}

if (!function_exists('escape')) {
    function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatDistance')) {
    function formatDistance($distance) {
        if ($distance < 1) {
            return 'Menos de 1km';
        }
        return number_format($distance, 1) . 'km';
    }
}

if (!function_exists('isOnline')) {
    function isOnline($lastActive) {
        return strtotime($lastActive) > strtotime('-5 minutes');
    }
}

if (!function_exists('generateToken')) {
    function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validateUsername')) {
    function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
    }
}

if (!function_exists('validatePassword')) {
    function validatePassword($password) {
        return strlen($password) >= PASSWORD_MIN_LENGTH;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('getCurrentUrl')) {
    function getCurrentUrl() {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
               "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
}

if (!function_exists('isAjax')) {
    function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        return SITE_URL . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        $parts = explode('.', $key);
        $filename = array_shift($parts);
        $path = CONFIG_PATH . '/' . $filename . '.php';
        
        if (!file_exists($path)) {
            return $default;
        }

        $config = require $path;
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}