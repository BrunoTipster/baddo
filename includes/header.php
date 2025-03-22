<?php
/**
 * Header padrão do sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:37:10
 */

// Verificar se SITE_NAME está definido
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'BadooClone');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Encontre pessoas interessantes perto de você'; ?>">
    <meta name="author" content="BrunoTipster">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/images/icons/favicon.png">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    
    <?php if (isset($additionalCss)): ?>
        <?php foreach ($additionalCss as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Encontre pessoas interessantes perto de você'; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/images/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">

    <!-- Custom CSS -->
    <style>
        .main-container {
            min-height: calc(100vh - 60px - 80px);
            padding: 20px 0;
        }
        .navbar-brand img {
            height: 40px;
        }
    </style>

    <?php if (isset($additionalStyles)): ?>
        <style>
            <?php echo $additionalStyles; ?>
        </style>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <?php include 'navbar.php'; ?>
    
    <div class="main-container">