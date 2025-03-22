<?php
/**
 * Página de Registro
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
$success = false;
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitização
    $old = [
        'username' => trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS)),
        'email' => trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'name' => trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS)),
        'gender' => trim(filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_SPECIAL_CHARS)),
        'birth_date' => trim(filter_input(INPUT_POST, 'birth_date', FILTER_SANITIZE_SPECIAL_CHARS)),
        'bio' => trim(filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_SPECIAL_CHARS)),
        'city' => trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_SPECIAL_CHARS)),
        'country' => trim(filter_input(INPUT_POST, 'country', FILTER_SANITIZE_SPECIAL_CHARS))
    ];

    // Validações
    if (empty($old['username'])) {
        $errors[] = "Username é obrigatório";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $old['username'])) {
        $errors[] = "Username deve ter entre 3 e 20 caracteres e conter apenas letras, números e _";
    }

    if (empty($old['email'])) {
        $errors[] = "Email é obrigatório";
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }

    if (empty($old['password'])) {
        $errors[] = "Senha é obrigatória";
    } elseif (strlen($old['password']) < 8) {
        $errors[] = "Senha deve ter no mínimo 8 caracteres";
    }

    if ($old['password'] !== $old['confirm_password']) {
        $errors[] = "As senhas não conferem";
    }

    if (empty($old['name'])) {
        $errors[] = "Nome é obrigatório";
    } elseif (strlen($old['name']) < 3) {
        $errors[] = "Nome deve ter no mínimo 3 caracteres";
    }

    if (!in_array($old['gender'], ['M', 'F', 'O'])) {
        $errors[] = "Gênero inválido";
    }

    if (empty($old['birth_date'])) {
        $errors[] = "Data de nascimento é obrigatória";
    } else {
        $birth = new DateTime($old['birth_date']);
        $today = new DateTime();
        $age = $birth->diff($today)->y;
        if ($age < 18) {
            $errors[] = "Você precisa ter pelo menos 18 anos";
        }
    }

    // Se não houver erros, tenta registrar
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Iniciar transação
            $db->getConnection()->beginTransaction();

            // Verificar username/email únicos
            $existing = $db->single(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$old['username'], $old['email']]
            );

            if ($existing) {
                throw new Exception("Username ou email já estão em uso");
            }

            // Inserir usuário
            $db->query(
                "INSERT INTO users (
                    username, email, password, name, gender,
                    birth_date, bio, city, country, status,
                    is_online, last_active, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, 'active',
                    1, NOW(), NOW()
                )",
                [
                    $old['username'],
                    $old['email'],
                    password_hash($old['password'], PASSWORD_DEFAULT),
                    $old['name'],
                    $old['gender'],
                    $old['birth_date'],
                    $old['bio'],
                    $old['city'],
                    $old['country']
                ]
            );

            $userId = $db->lastInsertId();

            // Inserir preferências padrão
            $interested_in = $old['gender'] === 'M' ? 'F' : ($old['gender'] === 'F' ? 'M' : 'B');
            
            $db->query(
                "INSERT INTO user_preferences (
                    user_id, interested_in, min_age, max_age,
                    max_distance, show_online, show_distance,
                    notifications_enabled, email_notifications,
                    created_at
                ) VALUES (
                    ?, ?, 18, 99,
                    50, 1, 1,
                    1, 1,
                    NOW()
                )",
                [$userId, $interested_in]
            );

            // Registrar atividade
            $db->query(
                "INSERT INTO activity_logs (
                    user_id, action, description,
                    ip_address, user_agent, created_at
                ) VALUES (
                    ?, 'register', 'Novo usuário registrado',
                    ?, ?, NOW()
                )",
                [
                    $userId,
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT']
                ]
            );

            // Confirmar transação
            $db->getConnection()->commit();

            $success = true;
            
            // Limpar dados do formulário
            $old = [];

        } catch (Exception $e) {
            // Reverter transação
            $db->getConnection()->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?php echo SITE_NAME; ?></title>
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
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin: 2rem auto;
            max-width: 800px;
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

        .form-control, .form-select {
            border: 2px solid var(--badoo-gray);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--badoo-pink);
            box-shadow: 0 0 0 0.2rem rgba(255,119,169,0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--badoo-dark);
            margin-bottom: 0.5rem;
        }

        .form-text {
            color: #666;
            font-size: 0.875rem;
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

        .alert-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            border: none;
        }

        .alert-danger {
            background: linear-gradient(45deg, #dc3545, #ff4444);
            color: white;
            border: none;
        }

        .register-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--badoo-gray);
            color: var(--badoo-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 1rem;
            position: relative;
        }

        .step.active {
            background: linear-gradient(45deg, var(--badoo-pink), var(--badoo-purple));
            color: white;
        }

        .step::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: var(--badoo-gray);
            right: -100%;
            top: 50%;
            transform: translateY(-50%);
        }

        .step:last-child::after {
            display: none;
        }

        .gender-select {
            display: flex;
            gap: 1rem;
        }

        .gender-option {
            flex: 1;
            text-align: center;
            padding: 1rem;
            border: 2px solid var(--badoo-gray);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gender-option:hover {
            border-color: var(--badoo-pink);
        }

        .gender-option.active {
            border-color: var(--badoo-pink);
            background: rgba(255,119,169,0.1);
        }

        .gender-option i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--badoo-pink);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="logo">
                <i class="bi bi-heart-fill"></i>
                <h3><?php echo SITE_NAME; ?></h3>
                <p class="text-muted">Encontre pessoas interessantes perto de você</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Cadastro realizado com sucesso!
                    <div class="mt-2">
                        <small>Você será redirecionado para o login em instantes...</small>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 style="width: 100%"></div>
                        </div>
                    </div>
                </div>
                <script>
                    setTimeout(() => window.location.href = 'login.php', 2000);
                </script>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="register-steps">
                    <div class="step active">1</div>
                    <div class="step">2</div>
                    <div class="step">3</div>
                </div>

                <form method="POST" action="" autocomplete="off" novalidate>
                    <div class="row g-4">
                        <div class="col-12 text-center mb-4">
                            <h4>Sobre você</h4>
                            <p class="text-muted">Conte-nos um pouco mais sobre você</p>
                        </div>

                        <!-- Nome -->
                        <div class="col-md-6">
                            <label class="form-label">Nome</label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control"
                                   value="<?php echo $old['name'] ?? ''; ?>"
                                   required>
                            <div class="form-text">
                                Como você quer ser chamado
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" 
                                   name="username" 
                                   class="form-control" 
                                   value="<?php echo $old['username'] ?? ''; ?>"
                                   required
                                   pattern="^[a-zA-Z0-9_]{3,20}$">
                            <div class="form-text">
                                Seu identificador único
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control"
                                   value="<?php echo $old['email'] ?? ''; ?>"
                                   required>
                        </div>

                        <!-- Data de Nascimento -->
                        <div class="col-md-6">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" 
                                   name="birth_date" 
                                   class="form-control"
                                   value="<?php echo $old['birth_date'] ?? ''; ?>"
                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                                   required>
                        </div>

                        <!-- Gênero -->
                        <div class="col-12">
                            <label class="form-label d-block mb-3">Gênero</label>
                            <div class="gender-select">
                                <div class="gender-option" data-value="M">
                                    <i class="bi bi-gender-male"></i>
                                    <div>Masculino</div>
                                </div>
                                <div class="gender-option" data-value="F">
                                    <i class="bi bi-gender-female"></i>
                                    <div>Feminino</div>
                                </div>
                                <div class="gender-option" data-value="O">
                                    <i class="bi bi-gender-trans"></i>
                                    <div>Outro</div>
                                </div>
                            </div>
                            <input type="hidden" name="gender" id="gender" value="<?php echo $old['gender'] ?? ''; ?>">
                        </div>

                        <!-- Localização -->
                        <div class="col-md-6">
                            <label class="form-label">Cidade</label>
                            <input type="text" 
                                   name="city" 
                                   class="form-control"
                                   value="<?php echo $old['city'] ?? ''; ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">País</label>
                            <select name="country" class="form-select">
                                <option value="">Selecione...</option>
                                <option value="BR" <?php echo ($old['country'] ?? '') === 'BR' ? 'selected' : ''; ?>>
                                    Brasil
                                </option>
                                <!-- Adicione mais países conforme necessário -->
                            </select>
                        </div>

                        <!-- Senha -->
                        <div class="col-md-6">
                            <label class="form-label">Senha</label>
                            <input type="password" 
                                   name="password" 
                                   class="form-control"
                                   required
                                   minlength="8">
                            <div class="form-text">
                                Mínimo de 8 caracteres
                            </div>
                        </div>

                        <!-- Confirmar Senha -->
                        <div class="col-md-6">
                            <label class="form-label">Confirmar Senha</label>
                            <input type="password" 
                                   name="confirm_password" 
                                   class="form-control"
                                   required>
                        </div>

                        <!-- Bio -->
                        <div class="col-12">
                            <label class="form-label">Biografia</label>
                            <textarea name="bio" 
                                    class="form-control" 
                                    rows="3"
                                    placeholder="Conte um pouco sobre você..."><?php echo $old['bio'] ?? ''; ?></textarea>
                            <div class="form-text">
                                Opcional, mas ajuda as pessoas a te conhecerem melhor
                            </div>
                        </div>

                        <!-- Termos -->
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" 
                                       name="terms" 
                                       class="form-check-input"
                                       required>
                                <label class="form-check-label">
                                    Li e aceito os 
                                    <a href="terms.php" target="_blank">Termos de Uso</a> e 
                                    <a href="privacy.php" target="_blank">Política de Privacidade</a>
                                </label>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-person-plus me-2"></i> 
                                Criar Conta
                            </button>
                            <p class="mt-3 mb-0">
                                Já tem uma conta? 
                                <a href="login.php" class="text-decoration-none">Faça login</a>
                            </p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Seletor de gênero
        document.querySelectorAll('.gender-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.gender-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                this.classList.add('active');
                document.getElementById('gender').value = this.dataset.value;
            });

            // Ativar opção salva
            const savedGender = document.getElementById('gender').value;
            if (savedGender === option.dataset.value) {
                option.classList.add('active');
            }
        });

        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]');
            const confirm = document.querySelector('input[name="confirm_password"]');
            const gender = document.getElementById('gender');
            
            if (password.value !== confirm.value) {
                e.preventDefault();
                alert('As senhas não conferem');
                return;
            }

            if (!gender.value) {
                e.preventDefault();
                alert('Selecione um gênero');
                return;
            }
        });

        // Validação da data de nascimento
        document.querySelector('input[name="birth_date"]').addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            if (age < 18) {
                this.setCustomValidity('Você precisa ter pelo menos 18 anos');
            } else {
                this.setCustomValidity('');
            }
        });

        // Máscaras e validações extras podem ser adicionadas aqui
    </script>
</body>
</html>