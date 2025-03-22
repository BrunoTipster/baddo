<?php
/**
 * Página de Edição de Perfil
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/utils/helpers.php';
require_once BASE_PATH . '/utils/Database.php';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Obter dados do usuário
$db = Database::getInstance();
$user = $db->single(
    "SELECT * FROM users WHERE id = ?", 
    [$_SESSION['user_id']]
);

// Obter preferências do usuário
$preferences = $db->single(
    "SELECT * FROM user_preferences WHERE user_id = ?",
    [$_SESSION['user_id']]
);

$errors = [];
$success = false;

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar dados
    $name = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $bio = trim(htmlspecialchars($_POST['bio'] ?? '', ENT_QUOTES, 'UTF-8'));
    $city = trim(htmlspecialchars($_POST['city'] ?? '', ENT_QUOTES, 'UTF-8'));
    $country = trim(htmlspecialchars($_POST['country'] ?? '', ENT_QUOTES, 'UTF-8'));
    $interested_in = $_POST['interested_in'] ?? $preferences['interested_in'];
    $min_age = (int)($_POST['min_age'] ?? $preferences['min_age']);
    $max_age = (int)($_POST['max_age'] ?? $preferences['max_age']);
    $max_distance = (int)($_POST['max_distance'] ?? $preferences['max_distance']);
    $show_online = isset($_POST['show_online']) ? 1 : 0;
    $show_distance = isset($_POST['show_distance']) ? 1 : 0;
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;

    // Upload de avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $newname = uniqid('avatar_') . '.' . $filetype;
            $uploadfile = AVATARS_PATH . '/' . $newname;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadfile)) {
                // Atualizar nome do avatar no banco
                $db->query(
                    "UPDATE users SET avatar = ? WHERE id = ?",
                    [$newname, $_SESSION['user_id']]
                );
            } else {
                $errors[] = "Erro ao fazer upload do avatar";
            }
        } else {
            $errors[] = "Tipo de arquivo não permitido para avatar";
        }
    }

    if (empty($errors)) {
        try {
            // Atualizar dados do usuário
            $db->query(
                "UPDATE users SET 
                    name = ?, bio = ?, city = ?, country = ?
                WHERE id = ?",
                [$name, $bio, $city, $country, $_SESSION['user_id']]
            );

            // Atualizar preferências
            $db->query(
                "UPDATE user_preferences SET 
                    interested_in = ?, min_age = ?, max_age = ?,
                    max_distance = ?, show_online = ?, show_distance = ?,
                    notifications_enabled = ?
                WHERE user_id = ?",
                [
                    $interested_in, $min_age, $max_age,
                    $max_distance, $show_online, $show_distance,
                    $notifications_enabled, $_SESSION['user_id']
                ]
            );

            $success = true;

            // Registrar atividade
            $db->query(
                "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
                VALUES (?, 'profile_update', 'Perfil atualizado', ?, ?)",
                [$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]
            );

            // Recarregar dados
            $user = $db->single(
                "SELECT * FROM users WHERE id = ?", 
                [$_SESSION['user_id']]
            );
            $preferences = $db->single(
                "SELECT * FROM user_preferences WHERE user_id = ?",
                [$_SESSION['user_id']]
            );
        } catch (PDOException $e) {
            $errors[] = "Erro ao atualizar perfil: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: #f5f5f5;
            padding-top: 2rem;
        }
        .profile-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-form">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="bi bi-person-circle"></i> Editar Perfil</h2>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Perfil atualizado com sucesso!
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Avatar -->
                        <div class="text-center mb-4">
                            <img src="<?php echo !empty($user['avatar']) ? '../uploads/avatars/' . $user['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                 alt="Avatar" class="avatar-preview" id="avatar-preview">
                            <div class="mb-3">
                                <label class="form-label">Alterar Avatar</label>
                                <input type="file" class="form-control" name="avatar" accept="image/*" 
                                       onchange="previewAvatar(this)">
                            </div>
                        </div>

                        <!-- Informações Básicas -->
                        <h5 class="mb-3">Informações Básicas</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       disabled>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Bio</label>
                            <textarea class="form-control" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Cidade</label>
                                <input type="text" class="form-control" name="city" 
                                       value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">País</label>
                                <input type="text" class="form-control" name="country" 
                                       value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Preferências -->
                        <h5 class="mb-3">Preferências</h5>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Interesse em</label>
                                <select class="form-select" name="interested_in">
                                    <option value="M" <?php echo $preferences['interested_in'] === 'M' ? 'selected' : ''; ?>>Homens</option>
                                    <option value="F" <?php echo $preferences['interested_in'] === 'F' ? 'selected' : ''; ?>>Mulheres</option>
                                    <option value="B" <?php echo $preferences['interested_in'] === 'B' ? 'selected' : ''; ?>>Ambos</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Distância Máxima (km)</label>
                                <input type="number" class="form-control" name="max_distance" 
                                       value="<?php echo $preferences['max_distance']; ?>" min="1" max="100">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Idade Mínima</label>
                                <input type="number" class="form-control" name="min_age" 
                                       value="<?php echo $preferences['min_age']; ?>" min="18" max="99">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Idade Máxima</label>
                                <input type="number" class="form-control" name="max_age" 
                                       value="<?php echo $preferences['max_age']; ?>" min="18" max="99">
                            </div>
                        </div>

                        <!-- Privacidade -->
                        <h5 class="mb-3">Privacidade</h5>
                        <div class="mb-4">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="show_online" 
                                       <?php echo $preferences['show_online'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Mostrar quando estou online</label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="show_distance" 
                                       <?php echo $preferences['show_distance'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Mostrar distância</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="notifications_enabled" 
                                       <?php echo $preferences['notifications_enabled'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Receber notificações</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview do avatar
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>