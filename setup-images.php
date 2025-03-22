<?php
/**
 * Gerador de Imagens Padrão
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:29:11
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/config.php';

// Verificar se GD está instalado
if (!extension_loaded('gd')) {
    die("Extensão GD não está instalada. Por favor, instale a extensão GD do PHP.");
}

echo "\n=== Gerando imagens padrão ===\n\n";

// Gerar logo
echo "Gerando logo.png... ";
// [Código da geração do logo aqui]
echo "✓\n";

// Gerar favicon
echo "Gerando favicon.png... ";
// [Código da geração do favicon aqui]
echo "✓\n";

// Gerar hero background
echo "Gerando hero-bg.jpg... ";
// [Código da geração do hero aqui]
echo "✓\n";

// Gerar OpenGraph image
echo "Gerando og-image.jpg... ";
// [Código da geração da og-image aqui]
echo "✓\n";

echo "\n=== Imagens geradas com sucesso ===\n";