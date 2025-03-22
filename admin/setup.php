<?php
/**
 * Interface de Configuração
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:27:30
 */

session_start();
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Verificar se é admin
if (!isAdmin()) {
    redirect('login.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Diretórios necessários
    $directories = [
        'assets/images/avatars',
        'assets/images/photos',
        'assets/images/icons'
    ];

    // Arquivos necessários
    $files = [
        'assets/images/icons/favicon.png',
        'assets/images/logo.png',
        'assets/images/hero-bg.jpg',
        'assets/images/og-image.jpg'
    ];

    try {
        // Criar diretórios
        foreach ($directories as $dir) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new Exception("Erro ao criar diretório: {$dir}");
                }
            }
        }

        // Criar arquivos
        foreach ($files as $file) {
            $path = BASE_PATH . '/' . $file;
            if (!file_exists($path)) {
                if (!touch($path)) {
                    throw new Exception("Erro ao criar arquivo: {$file}");
                }
            }
        }

        $message = "Estrutura de arquivos criada com sucesso!";

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = "Configuração do Sistema";
require_once BASE_PATH . '/includes/admin-header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Configuração do Sistema</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <p>Este processo irá criar a estrutura básica de diretórios e arquivos necessários para o funcionamento do sistema.</p>
                        
                        <div class="mb-4">
                            <h5>Diretórios a serem criados:</h5>
                            <ul class="list-unstyled ms-3">
                                <li><i class="bi bi-folder me-2"></i>assets/images/avatars</li>
                                <li><i class="bi bi-folder me-2"></i>assets/images/photos</li>
                                <li><i class="bi bi-folder me-2"></i>assets/images/icons</li>
                            </ul>
                        </div>

                        <div class="mb-4">
                            <h5>Arquivos a serem criados:</h5>
                            <ul class="list-unstyled ms-3">
                                <li><i class="bi bi-file-earmark me-2"></i>assets/images/icons/favicon.png</li>
                                <li><i class="bi bi-file-earmark me-2"></i>assets/images/logo.png</li>
                                <li><i class="bi bi-file-earmark me-2"></i>assets/images/hero-bg.jpg</li>
                                <li><i class="bi bi-file-earmark me-2"></i>assets/images/og-image.jpg</li>
                            </ul>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-gear-fill me-2"></i>Criar Estrutura
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once BASE_PATH . '/includes/admin-footer.php'; ?>