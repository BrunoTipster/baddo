<?php
/**
 * Página de Configurações
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Buscar dados do usuário e preferências
$user = $db->single(
    "SELECT u.*, p.*
     FROM users u
     LEFT JOIN user_preferences p ON p.user_id = u.id
     WHERE u.id = ?",
    [$_SESSION['user_id']]
);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->getConnection()->beginTransaction();

        // Atualizar preferências
        $db->query(
            "INSERT INTO user_preferences (
                user_id, interested_in, min_age, max_age, 
                max_distance, show_online, show_distance,
                notifications_enabled, email_notifications
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                interested_in = VALUES(interested_in),
                min_age = VALUES(min_age),
                max_age = VALUES(max_age),
                max_distance = VALUES(max_distance),
                show_online = VALUES(show_online),
                show_distance = VALUES(show_distance),
                notifications_enabled = VALUES(notifications_enabled),
                email_notifications = VALUES(email_notifications)",
            [
                $_SESSION['user_id'],
                $_POST['interested_in'],
                (int)$_POST['min_age'],
                (int)$_POST['max_age'],
                (int)$_POST['max_distance'],
                isset($_POST['show_online']) ? 1 : 0,
                isset($_POST['show_distance']) ? 1 : 0,
                isset($_POST['notifications_enabled']) ? 1 : 0,
                isset($_POST['email_notifications']) ? 1 : 0
            ]
        );

        // Atualizar senha se fornecida
        if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
            if (!password_verify($_POST['current_password'], $user['password'])) {
                throw new Exception('Senha atual incorreta');
            }

            if (strlen($_POST['new_password']) < 8) {
                throw new Exception('A nova senha deve ter pelo menos 8 caracteres');
            }

            $db->query(
                "UPDATE users SET password = ? WHERE id = ?",
                [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $_SESSION['user_id']]
            );
        }

        // Registrar atividade
        $db->query(
            "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
             VALUES (?, 'update_settings', 'Configurações atualizadas', ?, ?)",
            [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
        );

        $db->getConnection()->commit();
        $success = "Configurações atualizadas com sucesso!";

        // Atualizar dados em memória
        $user = $db->single(
            "SELECT u.*, p.*
             FROM users u
             LEFT JOIN user_preferences p ON p.user_id = u.id
             WHERE u.id = ?",
            [$_SESSION['user_id']]
        );

    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #5C6BC0;
            --secondary-color: #3F51B5;
            --accent-color: #FF4081;
        }
        
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .card {
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: 500;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .settings-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .settings-section h5 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-heart-fill"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="matches.php">
                            <i class="bi bi-heart"></i> Matches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat"></i> Mensagens
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="edit-profile.php">
                            <i class="bi bi-person"></i> Perfil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="bi bi-gear"></i> Configurações
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container settings-container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Preferências de Busca -->
            <div class="settings-section">
                <h5><i class="bi bi-search me-2"></i>Preferências de Busca</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Interessado em</label>
                        <select name="interested_in" class="form-select">
                            <option value="M" <?php echo $user['interested_in'] === 'M' ? 'selected' : ''; ?>>Homens</option>
                            <option value="F" <?php echo $user['interested_in'] === 'F' ? 'selected' : ''; ?>>Mulheres</option>
                            <option value="B" <?php echo $user['interested_in'] === 'B' ? 'selected' : ''; ?>>Ambos</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Distância Máxima (km)</label>
                        <input type="number" 
                               name="max_distance" 
                               class="form-control" 
                               value="<?php echo $user['max_distance']; ?>"
                               min="1" 
                               max="100">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Idade Mínima</label>
                        <input type="number" 
                               name="min_age" 
                               class="form-control" 
                               value="<?php echo $user['min_age']; ?>"
                               min="18" 
                               max="99">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Idade Máxima</label>
                        <input type="number" 
                               name="max_age" 
                               class="form-control" 
                               value="<?php echo $user['max_age']; ?>"
                               min="18" 
                               max="99">
                    </div>
                </div>
            </div>

            <!-- Privacidade -->
            <div class="settings-section">
                <h5><i class="bi bi-shield-lock me-2"></i>Privacidade</h5>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               name="show_online" 
                               class="form-check-input" 
                               <?php echo $user['show_online'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Mostrar status online</label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               name="show_distance" 
                               class="form-check-input" 
                               <?php echo $user['show_distance'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Mostrar distância</label>
                    </div>
                </div>
            </div>

            <!-- Notificações -->
            <div class="settings-section">
                <h5><i class="bi bi-bell me-2"></i>Notificações</h5>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               name="notifications_enabled" 
                               class="form-check-input" 
                               <?php echo $user['notifications_enabled'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Notificações no site</label>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="checkbox" 
                               name="email_notifications" 
                               class="form-check-input" 
                               <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Notificações por e-mail</label>
                    </div>
                </div>
            </div>

            <!-- Segurança -->
            <div class="settings-section">
                <h5><i class="bi bi-key me-2"></i>Alterar Senha</h5>
                <div class="mb-3">
                    <label class="form-label">Senha Atual</label>
                    <input type="password" name="current_password" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" name="new_password" class="form-control">
                    <div class="form-text">Mínimo de 8 caracteres</div>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Salvar Alterações
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i> Cancelar
                </a>
            </div>
        </form>

        <!-- Área de Perigo -->
        <div class="settings-section mt-4">
            <h5 class="text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Zona de Perigo</h5>
            <p class="text-muted">
                Estas ações são irreversíveis. Tenha certeza antes de prosseguir.
            </p>
            <div class="d-grid gap-2">
                <button type="button" 
                        class="btn btn-outline-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteAccountModal">
                    <i class="bi bi-trash"></i> Excluir Conta
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Excluir Conta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita.</p>
                    <p class="text-muted small">
                        Ao excluir sua conta:
                        <ul>
                            <li>Todos os seus dados serão permanentemente removidos</li>
                            <li>Seus matches e mensagens serão excluídos</li>
                            <li>Você não poderá recuperar sua conta depois</li>
                        </ul>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="deleteAccount()">
                        <i class="bi bi-trash"></i> Excluir Conta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validação de idade
        document.querySelector('input[name="min_age"]').addEventListener('change', function() {
            const maxAge = document.querySelector('input[name="max_age"]');
            if (parseInt(this.value) > parseInt(maxAge.value)) {
                maxAge.value = this.value;
            }
        });

        document.querySelector('input[name="max_age"]').addEventListener('change', function() {
            const minAge = document.querySelector('input[name="min_age"]');
            if (parseInt(this.value) < parseInt(minAge.value)) {
                minAge.value = this.value;
            }
        });

        // Excluir conta
        function deleteAccount() {
            if (confirm('Esta ação é irreversível. Digite "EXCLUIR" para confirmar:') === 'EXCLUIR') {
                fetch('delete_account.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '../logout.php';
                    } else {
                        alert(data.message || 'Erro ao excluir conta');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao processar sua solicitação');
                });
            }
        }
    </script>
</body>
</html>