<?php
/**
 * Arquivo principal do sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:53:47 UTC
 */

// Definir caminho base
define('BASE_PATH', __DIR__);

// Carregar configurações e helpers
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';

// Informações do sistema
$systemInfo = [
    'date_utc' => formatDateTime(),
    'user' => 'BrunoTipster',
    'version' => '1.0.0',
    'environment' => DEBUG_MODE ? 'Development' : 'Production'
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Meta tags -->
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <meta name="author" content="<?php echo DEVELOPER_NAME; ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo SITE_DESCRIPTION; ?>">
    <meta property="og:image" content="<?php echo image('og-image.jpg'); ?>">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo image('icons/favicon.png'); ?>">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #5C6BC0 0%, #3F51B5 100%);
            color: #fff;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .hero {
            background: url('<?php echo image('hero-bg.jpg'); ?>') center/cover;
            padding: 100px 0;
            position: relative;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
        }
        .hero > * {
            position: relative;
        }
        .logo {
            max-height: 60px;
            width: auto;
        }
        .card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .system-info {
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <img src="<?php echo image('logo.png'); ?>" alt="<?php echo SITE_NAME; ?>" class="logo">
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-4 mb-4"><?php echo SITE_NAME; ?></h1>
            <p class="lead mb-4"><?php echo SITE_DESCRIPTION; ?></p>
        </div>
    </section>

    <!-- Sistema Info -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        Informações do Sistema
                    </div>
                    <div class="card-body system-info">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <tbody>
                                    <tr>
                                        <td>Data/Hora (UTC):</td>
                                        <td><?php echo $systemInfo['date_utc']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Usuário:</td>
                                        <td><?php echo $systemInfo['user']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Versão:</td>
                                        <td><?php echo $systemInfo['version']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Ambiente:</td>
                                        <td><?php echo $systemInfo['environment']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Desenvolvedor:</td>
                                        <td><?php echo DEVELOPER_NAME; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Última Atualização:</td>
                                        <td><?php echo LAST_UPDATE; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Status dos Diretórios -->
                <div class="card mt-4">
                    <div class="card-header">
                        Status dos Diretórios
                    </div>
                    <div class="card-body system-info">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <tbody>
                                    <?php
                                    $directories = [
                                        'Images' => IMAGES_PATH,
                                        'Avatars' => AVATARS_PATH,
                                        'Photos' => PHOTOS_PATH,
                                        'Icons' => ICONS_PATH,
                                        'Cache' => CACHE_PATH,
                                        'Logs' => LOG_PATH
                                    ];
                                    
                                    foreach ($directories as $name => $path):
                                        $exists = is_dir($path);
                                        $writable = is_writable($path);
                                        $status = $exists && $writable ? 'OK' : 'Erro';
                                        $statusClass = $exists && $writable ? 'success' : 'danger';
                                    ?>
                                    <tr>
                                        <td><?php echo $name; ?>:</td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-center text-white py-4 mt-5">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - 
                Desenvolvido por <?php echo DEVELOPER_NAME; ?>
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>