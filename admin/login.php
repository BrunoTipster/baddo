
<?php
/**
 * Login Administrativo
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/Database.php';
require_once BASE_PATH . '/utils/helpers.php';

session_start();

// Se já estiver logado, redireciona
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    
    try {
        $db = Database::getInstance();
        
        // Buscar admin
        $admin = $db->single(
            "SELECT * FROM admins WHERE username = ? AND status = 'active'",
            [$username]
        );

        if ($admin && password_verify($password, $admin['password'])) {
            // Verificar tentativas falhas
            if ($admin['failed_attempts'] >= LOGIN_MAX_ATTEMPTS) {
                $lastFailedLogin = strtotime($admin['updated_at']);
                $lockoutTime = $lastFailedLogin + LOGIN_LOCKOUT_TIME;
                
                if (time() < $lockoutTime) {
                    $timeLeft = ceil(($lockoutTime - time()) / 60);
                    throw new Exception("Conta bloqueada. Tente novamente em $timeLeft minutos");
                }
            }

            // Limpar tentativas falhas
            $db->query(
                "UPDATE admins 
                 SET failed_attempts = 0,
                     last_login = NOW()
                 WHERE id = ?",
                [$admin['id']]
            );

            // Registrar atividade
            $db->query(
                "INSERT INTO activity_logs (
                    admin_id, action, description, 
                    ip_address, user_agent
                ) VALUES (
                    ?, 'login', 'Login administrativo realizado',
                    ?, ?
                )",
                [
                    $admin['id'],
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]
            );

            // Iniciar sessão
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];

            // Redirecionar
            header('Location: index.php');
            exit;

        } else {
            // Incrementar tentativas falhas
            if ($admin) {
                $db->query(
                    "UPDATE admins 
                     SET failed_attempts = failed_attempts + 1
                     WHERE id = ?",
                    [$admin['id']]
                );
            }

            throw new Exception("Credenciais inválidas");
        }

    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 4rem;
            color: #764ba2;
            margin-bottom: 1rem;
        }

        .form-control {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 0.2rem rgba(118,75,162,0.25);
        }

        .btn-primary {
            background: #764ba2;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <i class="bi bi-shield-lock"></i>
                <h4>Área Administrativa</h4>
                <p class="text-muted"><?php echo SITE_NAME; ?></p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Usuário</label>
                    <input type="text" 
                           name="username" 
                           class="form-control"
                           required
                           autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label">Senha</label>
                    <input type="password" 
                           name="password" 
                           class="form-control"
                           required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Entrar
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>