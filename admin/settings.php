<?php
/**
 * Configurações do Sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:57:25
 */

session_start();
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/includes/functions.php';

// Verifica se é admin
if (!isAdmin()) {
    redirect('login.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Inicializar variáveis
$error = '';
$success = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar token CSRF
        if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido');
        }

        // Configurações do site
        $siteName = filter_input(INPUT_POST, 'site_name', FILTER_SANITIZE_STRING);
        $siteEmail = filter_input(INPUT_POST, 'site_email', FILTER_SANITIZE_EMAIL);
        $siteDescription = filter_input(INPUT_POST, 'site_description', FILTER_SANITIZE_STRING);

        // Configurações de usuário
        $minAge = filter_input(INPUT_POST, 'min_age', FILTER_VALIDATE_INT);
        $maxPhotos = filter_input(INPUT_POST, 'max_photos', FILTER_VALIDATE_INT);
        $maxLoginAttempts = filter_input(INPUT_POST, 'max_login_attempts', FILTER_VALIDATE_INT);

        // Validações
        if (!$siteName || strlen($siteName) < 3) {
            throw new Exception('Nome do site inválido');
        }

        if (!filter_var($siteEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email do site inválido');
        }

        if ($minAge < 18 || $minAge > 99) {
            throw new Exception('Idade mínima deve ser entre 18 e 99');
        }

        if ($maxPhotos < 1 || $maxPhotos > 10) {
            throw new Exception('Número máximo de fotos deve ser entre 1 e 10');
        }

        if ($maxLoginAttempts < 3 || $maxLoginAttempts > 10) {
            throw new Exception('Tentativas de login deve ser entre 3 e 10');
        }

        // Atualizar arquivo de configuração
        $configFile = BASE_PATH . '/config/config.php';
        $configContent = file_get_contents($configFile);

        // Atualizar valores
        $configContent = preg_replace(
            "/define\('SITE_NAME',\s*'.*?'\);/",
            "define('SITE_NAME', '$siteName');",
            $configContent
        );

        $configContent = preg_replace(
            "/define\('SITE_EMAIL',\s*'.*?'\);/",
            "define('SITE_EMAIL', '$siteEmail');",
            $configContent
        );

        $configContent = preg_replace(
            "/define\('SITE_DESCRIPTION',\s*'.*?'\);/",
            "define('SITE_DESCRIPTION', '$siteDescription');",
            $configContent
        );

        $configContent = preg_replace(
            "/define\('MIN_AGE',\s*\d+\);/",
            "define('MIN_AGE', $minAge);",
            $configContent
        );

        $configContent = preg_replace(
            "/define\('MAX_PHOTOS_PER_USER',\s*\d+\);/",
            "define('MAX_PHOTOS_PER_USER', $maxPhotos);",
            $configContent
        );

        $configContent = preg_replace(
            "/define\('MAX_LOGIN_ATTEMPTS',\s*\d+\);/",
            "define('MAX_LOGIN_ATTEMPTS', $maxLoginAttempts);",
            $configContent
        );

        // Salvar arquivo
        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception('Erro ao salvar configurações');
        }

        // Log da atividade
        logActivity($_SESSION['user_id'], 'settings_update', 'Configurações do sistema atualizadas');

        $success = 'Configurações atualizadas com sucesso';

    } catch (Exception $e) {
        error_log("Settings Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

$pageTitle = "Configurações do Sistema";
require_once BASE_PATH . '/includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Configurações do Sistema</h1>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                <!-- Configurações do Site -->
                <h5 class="mb-4">Configurações do Site</h5>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="site_name" class="form-label">Nome do Site</label>
                        <input type="text" 
                               class="form-control" 
                               id="site_name" 
                               name="site_name" 
                               value="<?php echo SITE_NAME; ?>" 
                               required>
                        <div class="invalid-feedback">
                            Nome do site é obrigatório
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="site_email" class="form-label">Email do Site</label>
                        <input type="email" 
                               class="form-control" 
                               id="site_email" 
                               name="site_email" 
                               value="<?php echo SITE_EMAIL; ?>" 
                               required>
                        <div class="invalid-feedback">
                            Email válido é obrigatório
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="site_description" class="form-label">Descrição do Site</label>
                        <input type="text" 
                               class="form-control" 
                               id="site_description" 
                               name="site_description" 
                               value="<?php echo SITE_DESCRIPTION; ?>" 
                               required>
                        <div class="invalid-feedback">
                            Descrição do site é obrigatória
                        </div>
                    </div>
                </div>

                <!-- Configurações de Usuário -->
                <h5 class="mb-4 mt-5">Configurações de Usuário</h5>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="min_age" class="form-label">Idade Mínima</label>
                        <input type="number" 
                               class="form-control" 
                               id="min_age" 
                               name="min_age" 
                               value="<?php echo MIN_AGE; ?>" 
                               min="18" 
                               max="99" 
                               required>
                        <div class="invalid-feedback">
                            Idade mínima deve ser entre 18 e 99
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="max_photos" class="form-label">Máximo de Fotos por Usuário</label>
                        <input type="number" 
                               class="form-control" 
                               id="max_photos" 
                               name="max_photos" 
                               value="<?php echo MAX_PHOTOS_PER_USER; ?>" 
                               min="1" 
                               max="10" 
                               required>
                        <div class="invalid-feedback">
                            Número de fotos deve ser entre 1 e 10
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="max_login_attempts" class="form-label">Tentativas de Login</label>
                        <input type="number" 
                               class="form-control" 
                               id="max_login_attempts" 
                               name="max_login_attempts" 
                               value="<?php echo MAX_LOGIN_ATTEMPTS; ?>" 
                               min="3" 
                               max="10" 
                               required>
                        <div class="invalid-feedback">
                            Tentativas deve ser entre 3 e 10
                        </div>
                    </div>
                </div>

                <!-- Configurações de Email -->
                <h5 class="mb-4 mt-5">Configurações de Email</h5>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_host" 
                               value="<?php echo SMTP_HOST; ?>" 
                               disabled>
                    </div>

                    <div class="col-md-3">
                        <label for="smtp_port" class="form-label">SMTP Porta</label>
                        <input type="number" 
                               class="form-control" 
                               id="smtp_port" 
                               value="<?php echo SMTP_PORT; ?>" 
                               disabled>
                    </div>

                    <div class="col-md-3">
                        <label for="smtp_user" class="form-label">SMTP Usuário</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_user" 
                               value="<?php echo SMTP_USER; ?>" 
                               disabled>
                    </div>

                    <div class="col-md-3">
                        <label for="smtp_secure" class="form-label">SMTP Segurança</label>
                        <input type="text" 
                               class="form-control" 
                               id="smtp_secure" 
                               value="<?php echo SMTP_SECURE; ?>" 
                               disabled>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    As configurações de email só podem ser alteradas diretamente no arquivo de configuração
                </div>

                <hr class="my-5">

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Configurações
                        </button>
                        <a href="?" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrões
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'

    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php require_once BASE_PATH . '/includes/admin-footer.php'; ?>