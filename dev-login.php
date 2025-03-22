<?php
/**
 * Login de Desenvolvimento
 * NÃO USE EM PRODUÇÃO!
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:40:14
 */

session_start();

// Simular login de admin para desenvolvimento
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_permissions'] = ['all'];
$_SESSION['user_name'] = 'Admin';

header('Location: fix-images.php');
exit;