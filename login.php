<?php
/**
 * Página de Login
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: client/dashboard.php');
    exit;
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validações
    if (empty($username)) {
        $errors[] = "Username ou email é obrigatório";
    }

    if (empty($password)) {
        $errors[] = "Senha é obrigatória";
    }

    if (empty($errors)) {
        try {
            $db = Database::getInstance();

            // Verificar se é email ou username
            $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'username';

            // Buscar usuário
            $user = $db->single(
                "SELECT * FROM users WHERE $field = ? AND status = 'active'",
                [$username]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Verificar tentativas falhas
                if ($user['failed_attempts'] >= LOGIN_MAX_ATTEMPTS) {
                    $lastFailedLogin = strtotime($user['updated_at']);
                    $lockoutTime = $lastFailedLogin + LOGIN_LOCKOUT_TIME;
                    
                    if (time() < $lockoutTime) {
                        $timeLeft = ceil(($lockoutTime - time()) / 60);
                        throw new Exception("Conta bloqueada. Tente novamente em $timeLeft minutos");
                    }
                }

                // Limpar tentativas falhas
                $db->query(
                    "UPDATE users 
                     SET failed_attempts = 0,
                         is_online = 1,
                         last_active = NOW()
                     WHERE id = ?",
                    [$user['id']]
                );

                // Registrar atividade
                $db->query(
                    "INSERT INTO activity_logs (
                        user_id, action, description, 
                        ip_address, user_agent
                    ) VALUES (
                        ?, 'login', 'Login realizado com sucesso',
                        ?, ?
                    )",
                    [
                        $user['id'],
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    ]
                );

                // Iniciar sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['avatar'] = $user['avatar'];

                // Configurar cookie de "lembrar-me"
                if ($remember) {
                    $token = generateToken();
                    $expires = time() + (30 * 24 * 60 * 60); // 30 dias
                    
                    setcookie(
                        'remember_token',
                        $token,
                        $expires,
                        '/',
                        '',
                        true, // Apenas HTTPS
                        true  // HTTP only
                    );

                    // Salvar token no banco
                    $db->query(
                        "UPDATE users 
                         SET remember_token = ?,
                             remember_expires = FROM_UNIXTIME(?)
                         WHERE id = ?",
                        [$token, $expires, $user['id']]
                    );
                }

                // Redirecionar
                header('Location: client/dashboard.php');
                exit;

            } else {
                // Incrementar tentativas falhas
                if ($user) {
                    $db->query(
                        "UPDATE users 
                         SET failed_attempts = failed_attempts + 1
                         WHERE id = ?",
                        [$user['id']]
                    );
                }

                throw new Exception("Credenciais inválidas");
            }

        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    $old['username'] = $username;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --badoo-pink: #FF77A9;
            --badoo-purple: #7B48B7;
            --badoo-blue: #1DA1F2;
            --badoo-dark: #2C2C2C;
            --badoo-gray: #F5F5F5;
        }

        body {
            background: var(--badoo-gray);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            background: linear-gradient(45deg, var(--badoo-pink), var(--badoo-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .logo h3 {
            color: var(--badoo-dark);
            font-weight: 700;
        }

        .form-control {
            border: 2px solid var(--badoo-gray);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--badoo-pink);
            box-shadow: 0 0 0 0.2rem rgba(255,119,169,0.25);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--badoo-pink), var(--badoo-purple));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,119,169,0.4);
        }

        .form-check-input:checked {
            background-color: var(--badoo-pink);
            border-color: var(--badoo-pink);
        }

        .alert {
            border-radius: 12px;
            padding: 1rem;
        }

        .alert-danger {
            background: linear-gradient(45deg, #dc3545, #ff4444);
            color: white;
            border: none;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .social-login {
            margin-top: 2rem;
            text-align: center;
        }

        .social-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--badoo-gray);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            color: var(--badoo-dark);
            text-decoration: none;
        }

        .social-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .social-button.facebook:hover {
            color: #1877F2;
            border-color: #1877F2;
        }

        .social-button.google:hover {
            color: #DB4437;
            border-color: #DB4437;
        }

        .social-button.apple:hover {
            color: #000000;
            border-color: #000000;
        }

        .divider {
            margin: 2rem 0;
            text-align: center;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: var(--badoo-gray);
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #666;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="logo">
                <i class="bi bi-heart-fill"></i>
                <h3><?php echo SITE_NAME; ?></h3>
                <p class="text-muted">Encontre pessoas interessantes perto de você</p>
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
                    <label class="form-label">Username ou Email</label>
                    <input type="text" 
                           name="username" 
                           class="form-control"
                           value="<?php echo $old['username'] ?? ''; ?>"
                           required
                           autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input type="password" 
                           name="password" 
                           class="form-control"
                           required>
                </div>

                <div class="login-options">
                    <div class="form-check">
                        <input type="checkbox" 
                               name="remember" 
                               class="form-check-input"
                               id="remember">
                        <label class="form-check-label" for="remember">
                            Lembrar-me
                        </label>
                    </div>
                    <a href="reset-password.php" class="text-decoration-none">
                        Esqueceu a senha?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Entrar
                </button>
            </form>

            <div class="divider">
                <span>ou continue com</span>
            </div>

            <div class="social-login">
                <a href="auth/facebook.php" class="social-button facebook">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="auth/google.php" class="social-button google">
                    <i class="bi bi-google"></i>
                </a>
                <a href="auth/apple.php" class="social-button apple">
                    <i class="bi bi-apple"></i>
                </a>
            </div>

            <p class="text-center mt-4 mb-0">
                Não tem uma conta? 
                <a href="register.php" class="text-decoration-none">Cadastre-se</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>