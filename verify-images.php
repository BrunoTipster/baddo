<?php
/**
 * Verificador de Imagens
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:34:12
 */

define('BASE_PATH', __DIR__);

// Array de imagens para verificar
$images = [
    'assets/images/logo.png' => [
        'required' => true,
        'mime' => 'image/png',
        'min_size' => 1024, // 1KB
        'dimensions' => ['min_width' => 100, 'min_height' => 30]
    ],
    'assets/images/icons/favicon.png' => [
        'required' => true,
        'mime' => 'image/png',
        'dimensions' => ['width' => 32, 'height' => 32]
    ],
    'assets/images/hero-bg.jpg' => [
        'required' => true,
        'mime' => 'image/jpeg',
        'min_size' => 10240, // 10KB
        'dimensions' => ['min_width' => 1200, 'min_height' => 600]
    ],
    'assets/images/og-image.jpg' => [
        'required' => true,
        'mime' => 'image/jpeg',
        'dimensions' => ['width' => 1200, 'height' => 630]
    ]
];

echo "\n=== Verificando imagens ===\n\n";

$allValid = true;

foreach ($images as $path => $requirements) {
    $fullPath = BASE_PATH . '/' . $path;
    echo "Verificando {$path}... ";
    
    // Verificar se existe
    if (!file_exists($fullPath)) {
        if ($requirements['required']) {
            echo "❌ Arquivo não encontrado!\n";
            $allValid = false;
        } else {
            echo "⚠️ Arquivo opcional não encontrado\n";
        }
        continue;
    }

    $valid = true;
    $issues = [];

    // Verificar MIME type
    $mimeType = mime_content_type($fullPath);
    if ($mimeType !== $requirements['mime']) {
        $valid = false;
        $issues[] = "tipo incorreto ($mimeType)";
    }

    // Verificar tamanho
    $size = filesize($fullPath);
    if (isset($requirements['min_size']) && $size < $requirements['min_size']) {
        $valid = false;
        $issues[] = "tamanho muito pequeno (" . number_format($size/1024, 2) . "KB)";
    }

    // Verificar dimensões
    $imageInfo = getimagesize($fullPath);
    if ($imageInfo) {
        list($width, $height) = $imageInfo;
        
        if (isset($requirements['dimensions']['width']) && 
            $width !== $requirements['dimensions']['width']) {
            $valid = false;
            $issues[] = "largura incorreta ($width)";
        }
        
        if (isset($requirements['dimensions']['height']) && 
            $height !== $requirements['dimensions']['height']) {
            $valid = false;
            $issues[] = "altura incorreta ($height)";
        }
        
        if (isset($requirements['dimensions']['min_width']) && 
            $width < $requirements['dimensions']['min_width']) {
            $valid = false;
            $issues[] = "largura muito pequena ($width)";
        }
        
        if (isset($requirements['dimensions']['min_height']) && 
            $height < $requirements['dimensions']['min_height']) {
            $valid = false;
            $issues[] = "altura muito pequena ($height)";
        }
    } else {
        $valid = false;
        $issues[] = "não é uma imagem válida";
    }

    if ($valid) {
        echo "✓ OK\n";
    } else {
        echo "❌ Problemas encontrados: " . implode(", ", $issues) . "\n";
        $allValid = false;
    }
}

echo "\n=== Resultado da verificação ===\n";
if ($allValid) {
    echo "✅ Todas as imagens estão corretas!\n";
} else {
    echo "❌ Foram encontrados problemas com algumas imagens.\n";
    echo "Execute setup-images.php novamente para corrigir.\n";
}

// Verificar permissões dos diretórios
echo "\n=== Verificando permissões ===\n";
$directories = [
    'assets/images',
    'assets/images/avatars',
    'assets/images/photos',
    'assets/images/icons'
];

foreach ($directories as $dir) {
    $fullPath = BASE_PATH . '/' . $dir;
    echo "Verificando {$dir}... ";
    
    if (!is_dir($fullPath)) {
        echo "❌ Diretório não encontrado!\n";
        continue;
    }

    $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
    $isWritable = is_writable($fullPath);
    
    echo $isWritable ? "✓ " : "❌ ";
    echo "Permissões: {$perms}\n";
}