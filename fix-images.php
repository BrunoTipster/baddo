<?php
/**
 * Correção de Imagens do Sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:38:11
 */

session_start();
define('BASE_PATH', __DIR__);

// Verificar se está sendo executado via CLI ou web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    // Se for web, verificar autenticação
    require_once BASE_PATH . '/config/config.php';
    require_once BASE_PATH . '/includes/functions.php';
    
    if (!isAdmin()) {
        die("Acesso negado. Apenas administradores podem executar este script.");
    }

    // Header HTML
    echo '<!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Correção de Imagens - ' . SITE_NAME . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
        <style>
            .progress { height: 25px; }
            .progress-bar { transition: width 0.5s ease-in-out; }
            .image-preview {
                max-width: 150px;
                max-height: 150px;
                object-fit: contain;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                padding: 5px;
            }
            .status-icon {
                font-size: 1.5rem;
                margin-right: 0.5rem;
            }
        </style>
    </head>
    <body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Correção de Imagens</h1>
            <div>
                <a href="verify-images.php" class="btn btn-info">
                    <i class="bi bi-check-circle"></i> Verificar Imagens
                </a>
                <a href="admin/index.php" class="btn btn-secondary">
                    <i class="bi bi-gear"></i> Admin
                </a>
            </div>
        </div>';
}

// Verificar GD
if (!extension_loaded('gd')) {
    $error = "Erro: Extensão GD não está instalada. Por favor, instale a extensão GD do PHP.";
    if ($isCli) {
        die($error . "\n");
    } else {
        die('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' . $error . '</div></div></body></html>');
    }
}

// Função para log
function log_message($message, $type = 'info') {
    global $isCli;
    if ($isCli) {
        echo $message . "\n";
    } else {
        $icon = $type === 'success' ? 'check-circle-fill' : ($type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill');
        $class = $type === 'success' ? 'success' : ($type === 'error' ? 'danger' : 'info');
        echo "<div class='alert alert-{$class}'><i class='bi bi-{$icon} me-2'></i>{$message}</div>";
    }
}

if (!$isCli) {
    echo '<div class="card">
          <div class="card-body">
          <h5 class="card-title mb-4">Progresso da Correção</h5>';
}

try {
    // Array de imagens para processar
    $images = [
        'logo' => [
            'path' => 'assets/images/logo.png',
            'width' => 200,
            'height' => 60
        ],
        'favicon' => [
            'path' => 'assets/images/icons/favicon.png',
            'width' => 32,
            'height' => 32
        ],
        'hero' => [
            'path' => 'assets/images/hero-bg.jpg',
            'width' => 1920,
            'height' => 1080
        ],
        'og' => [
            'path' => 'assets/images/og-image.jpg',
            'width' => 1200,
            'height' => 630
        ]
    ];

    $total = count($images);
    $current = 0;

    foreach ($images as $name => $config) {
        $current++;
        $percent = ($current / $total) * 100;

        if (!$isCli) {
            echo "<div class='mb-4'>";
            echo "<h6 class='mb-3'>Processando {$name}</h6>";
            echo "<div class='progress mb-3'>";
            echo "<div class='progress-bar progress-bar-striped progress-bar-animated' 
                      role='progressbar' 
                      style='width: {$percent}%' 
                      aria-valuenow='{$percent}' 
                      aria-valuemin='0' 
                      aria-valuemax='100'>{$percent}%</div>";
            echo "</div>";
        }

        // Criar imagem
        $img = imagecreatetruecolor($config['width'], $config['height']);
        $bg = imagecolorallocate($img, 92, 107, 192);
        $white = imagecolorallocate($img, 255, 255, 255);
        
        imagefilledrectangle($img, 0, 0, $config['width'], $config['height'], $bg);

        // Personalizar cada tipo de imagem
        switch ($name) {
            case 'logo':
                $text = SITE_NAME;
                imagestring($img, 5, 10, ($config['height'] - 16) / 2, $text, $white);
                break;
                
            case 'favicon':
                imagefilledellipse($img, 16, 16, 24, 24, $white);
                break;
                
            case 'hero':
                // Adicionar padrão de pontos
                for ($i = 0; $i < 200; $i++) {
                    imagefilledellipse(
                        $img,
                        rand(0, $config['width']),
                        rand(0, $config['height']),
                        2,
                        2,
                        $white
                    );
                }
                break;
                
            case 'og':
                $text = SITE_NAME;
                $fontSize = 5;
                $textWidth = imagefontwidth($fontSize) * strlen($text);
                $x = ($config['width'] - $textWidth) / 2;
                $y = ($config['height'] - imagefontheight($fontSize)) / 2;
                imagestring($img, $fontSize, $x, $y, $text, $white);
                break;
        }

        // Salvar imagem
        $fullPath = BASE_PATH . '/' . $config['path'];
        $dir = dirname($fullPath);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $success = false;
        if (pathinfo($config['path'], PATHINFO_EXTENSION) === 'jpg') {
            $success = imagejpeg($img, $fullPath, 90);
        } else {
            $success = imagepng($img, $fullPath);
        }
        
        imagedestroy($img);

        if ($success) {
            log_message("✓ {$name} gerada com sucesso", 'success');
            if (!$isCli) {
                echo "<div class='mt-2'><img src='{$config['path']}' class='image-preview' alt='{$name}'></div>";
            }
        } else {
            throw new Exception("Erro ao gerar {$name}");
        }

        if (!$isCli) {
            echo "</div>";
        }
    }

    log_message("\n✅ Todas as imagens foram corrigidas com sucesso!", 'success');

} catch (Exception $e) {
    log_message("❌ " . $e->getMessage(), 'error');
}

if (!$isCli) {
    echo '</div>'; // card-body
    echo '<div class="card-footer">';
    echo '<div class="d-flex justify-content-between align-items-center">';
    echo '<span>Última atualização: ' . date('d/m/Y H:i:s') . '</span>';
    echo '<a href="verify-images.php" class="btn btn-primary">Verificar Imagens</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>'; // card
    echo '</div>'; // container
    echo '</body></html>';
}