<?php
/**
 * Perfil do Usuário
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 16:02:31
 */

session_start();
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/includes/functions.php';

// Verifica se está logado
if (!isLoggedIn()) {
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

        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
        $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        $country = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);
        $birthDate = filter_input(INPUT_POST, 'birth_date', FILTER_SANITIZE_STRING);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
        $interests = filter_input(INPUT_POST, 'interests', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];

        // Validações
        if (empty($name) || strlen($name) < 3) {
            throw new Exception('Nome inválido');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }

        if (strlen($bio) > 500) {
            throw new Exception('Bio muito longa');
        }

        // Calcular idade
        $age = calculateAge($birthDate);
        if ($age < MIN_AGE) {
            throw new Exception('Idade mínima: ' . MIN_AGE . ' anos');
        }

        // Processar avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatar = uploadImage($_FILES['avatar'], 'avatar');
            if (!$avatar) {
                throw new Exception('Erro ao fazer upload do avatar');
            }
        }

        // Atualizar perfil
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?,
                email = ?,
                bio = ?,
                city = ?,
                country = ?,
                birth_date = ?,
                age = ?,
                gender = ?,
                interests = ?,
                " . (isset($avatar) ? "avatar = ?," : "") . "
                updated_at = NOW()
            WHERE id = ?
        ");

        $interests = implode(',', $interests);
        $params = [$name, $email, $bio, $city, $country, $birthDate, $age, $gender, $interests];
        
        if (isset($avatar)) {
            $params[] = $avatar;
        }
        
        $params[] = $_SESSION['user_id'];
        
        $stmt->bind_param(
            str_repeat('s', count($params)), 
            ...$params
        );

        if (!$stmt->execute()) {
            throw new Exception('Erro ao atualizar perfil');
        }

        // Processar fotos
        if (isset($_FILES['photos'])) {
            $photoCount = count($_FILES['photos']['name']);
            $currentPhotos = getUserPhotosCount($_SESSION['user_id']);

            if (($currentPhotos + $photoCount) > MAX_PHOTOS_PER_USER) {
                throw new Exception('Número máximo de fotos excedido');
            }

            for ($i = 0; $i < $photoCount; $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $photo = [
                        'name' => $_FILES['photos']['name'][$i],
                        'type' => $_FILES['photos']['type'][$i],
                        'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                        'error' => $_FILES['photos']['error'][$i],
                        'size' => $_FILES['photos']['size'][$i]
                    ];

                    $filename = uploadImage($photo, 'photo');
                    if ($filename) {
                        $stmt = $conn->prepare("
                            INSERT INTO user_photos 
                            (user_id, filename, is_primary, order_position)
                            VALUES (?, ?, 0, (
                                SELECT COALESCE(MAX(order_position), 0) + 1
                                FROM user_photos
                                WHERE user_id = ?
                            ))
                        ");
                        
                        $stmt->bind_param('isi', $_SESSION['user_id'], $filename, $_SESSION['user_id']);
                        $stmt->execute();
                    }
                }
            }
        }

        $success = 'Perfil atualizado com sucesso';

    } catch (Exception $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Buscar dados do usuário
try {
    $stmt = $conn->prepare("
        SELECT u.*,
               (SELECT filename 
                FROM user_photos 
                WHERE user_id = u.id 
                AND is_primary = 1 
                LIMIT 1) as primary_photo
        FROM users u
        WHERE u.id = ?
    ");
    
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Buscar fotos do usuário
    $stmt = $conn->prepare("
        SELECT * 
        FROM user_photos 
        WHERE user_id = ? 
        ORDER BY is_primary DESC, order_position ASC
    ");
    
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    $error = "Erro ao carregar perfil";
}

$pageTitle = "Meu Perfil";
require_once BASE_PATH . '/includes/header.php';
?>

<div class="container py-5">
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

    <div class="row">
        <!-- Menu Lateral -->
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <img src="<?php echo SITE_URL . '/assets/images/avatars/' . ($user['avatar'] ?: 'default.jpg'); ?>" 
                             class="rounded-circle mb-3" 
                             width="150" 
                             height="150"
                             alt="Avatar">
                        <h5 class="mb-0"><?php echo escape($user['name']); ?></h5>
                        <p class="text-muted mb-2">@<?php echo escape($user['username']); ?></p>
                        
                        <?php if ($user['is_verified']): ?>
                            <span class="badge bg-primary">
                                <i class="bi bi-patch-check-fill"></i> Verificado
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="list-group">
                        <a href="#profile" 
                           class="list-group-item list-group-item-action active" 
                           data-bs-toggle="list">
                            <i class="bi bi-person me-2"></i> Perfil
                        </a>
                        <a href="#photos" 
                           class="list-group-item list-group-item-action" 
                           data-bs-toggle="list">
                            <i class="bi bi-images me-2"></i> Fotos
                        </a>
                        <a href="#preferences" 
                           class="list-group-item list-group-item-action" 
                           data-bs-toggle="list">
                            <i class="bi bi-gear me-2"></i> Preferências
                        </a>
                        <a href="#privacy" 
                           class="list-group-item list-group-item-action" 
                           data-bs-toggle="list">
                            <i class="bi bi-shield-lock me-2"></i> Privacidade
                        </a>
                    </div>
                </div>
            </div>

            <!-- Status do Perfil -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">Status do Perfil</h6>
                    
                    <?php
                    $totalFields = 8;
                    $completedFields = 0;
                    
                    if (!empty($user['avatar'])) $completedFields++;
                    if (!empty($user['bio'])) $completedFields++;
                    if (!empty($user['city'])) $completedFields++;
                    if (!empty($user['interests'])) $completedFields++;
                    if (!empty($photos)) $completedFields++;
                    if (!empty($user['birth_date'])) $completedFields++;
                    if (!empty($user['gender'])) $completedFields++;
                    if (!empty($user['email'])) $completedFields++;
                    
                    $percentage = ($completedFields / $totalFields) * 100;
                    ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Completado</span>
                        <span><?php echo number_format($percentage); ?>%</span>
                    </div>
                    
                    <div class="progress mb-4" style="height: 5px;">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?php echo $percentage; ?>%" 
                             aria-valuenow="<?php echo $percentage; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>

                    <?php if ($percentage < 100): ?>
                        <small class="text-muted">
                            Complete seu perfil para aumentar suas chances de match!
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Perfil -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Informações do Perfil</h5>

                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nome Completo</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               value="<?php echo escape($user['name']); ?>" 
                                               required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo escape($user['email']); ?>" 
                                               required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label for="bio" class="form-label">Sobre Mim</label>
                                        <textarea class="form-control" 
                                                  id="bio" 
                                                  name="bio" 
                                                  rows="4"><?php echo escape($user['bio']); ?></textarea>
                                        <div class="form-text">Máximo 500 caracteres</div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">Cidade</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="city" 
                                               name="city" 
                                               value="<?php echo escape($user['city']); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="country" class="form-label">País</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="country" 
                                               name="country" 
                                               value="<?php echo escape($user['country']); ?>">
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="birth_date" class="form-label">Data de Nascimento</label>
                                        <input type="date" 
                                               class="