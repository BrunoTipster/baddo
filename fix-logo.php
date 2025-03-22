<?php
/**
 * Correção do Logo
 * @author BrunoTipster
 */

define('BASE_PATH', __DIR__);

echo "\n=== Corrigindo logo ===\n";

// Verificar GD
if (!extension_loaded('gd')) {
    die("❌ Erro: Extensão GD não está instalada!\n");
}

// Criar logo com tamanho maior
$width = 400;  // Aumentado para 400px
$height = 120; // Aumentado para 120px

$image = imagecreatetruecolor($width, $height);

// Cores
$bg = imagecolorallocate($image, 92, 107, 192); // Azul
$white = imagecolorallocate($image, 255, 255, 255);

// Preencher fundo
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Adicionar texto
$text = "BadooClone";
$fontSize = 5; // Máximo tamanho disponível
$textWidth = imagefontwidth($fontSize) * strlen($text);
$textHeight = imagefontheight($fontSize);

// Centralizar texto
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

// Desenhar texto
imagestring($image, $fontSize, $x, $y, $text, $white);

// Adicionar alguns detalhes
for ($i = 0; $i < 10; $i++) {
    imagefilledellipse(
        $image,
        rand(0, $width),
        rand(0, $height),
        4,
        4,
        $white
    );
}

// Salvar com qualidade máxima
if (imagepng($image, 'assets/images/logo.png', 0)) {
    echo "✓ Novo logo gerado com sucesso!\n";
} else {
    echo "❌ Erro ao gerar logo\n";
}

imagedestroy($image);

echo "\n=== Concluído ===\n";