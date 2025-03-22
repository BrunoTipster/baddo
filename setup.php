<?php
/**
 * Script de Configuração Inicial
 * Cria estrutura de diretórios e arquivos necessários
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:27:30
 */

// Definir diretório base
define('BASE_PATH', __DIR__);

// Array com diretórios a serem criados
$directories = [
    'assets/images/avatars',
    'assets/images/photos',
    'assets/images/icons'
];

// Array com arquivos a serem criados
$files = [
    'assets/images/icons/favicon.png',
    'assets/images/logo.png',
    'assets/images/hero-bg.jpg',
    'assets/images/og-image.jpg'
];

// Função para criar diretórios
function createDirectories($dirs) {
    foreach ($dirs as $dir) {
        $path = BASE_PATH . '/' . $dir;
        if (!is_dir($path)) {
            if (mkdir($path, 0755, true)) {
                echo "✓ Diretório criado: {$dir}\n";
            } else {
                echo "✗ Erro ao criar diretório: {$dir}\n";
            }
        } else {
            echo "• Diretório já existe: {$dir}\n";
        }
    }
}

// Função para criar arquivos
function createFiles($files) {
    foreach ($files as $file) {
        $path = BASE_PATH . '/' . $file;
        if (!file_exists($path)) {
            if (touch($path)) {
                echo "✓ Arquivo criado: {$file}\n";
            } else {
                echo "✗ Erro ao criar arquivo: {$file}\n";
            }
        } else {
            echo "• Arquivo já existe: {$file}\n";
        }
    }
}

// Executar criação
echo "\n=== Iniciando configuração ===\n\n";

echo "Criando diretórios...\n";
createDirectories($directories);

echo "\nCriando arquivos...\n";
createFiles($files);

echo "\n=== Configuração concluída ===\n";