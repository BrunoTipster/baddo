<?php
/**
 * Navbar principal do sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:37:10
 */

// Verificar se usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);

// Buscar notificações se estiver logado
$notifications = [];
$unreadNotifications = 0;

if ($isLoggedIn) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as unread 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $unreadNotifications = $stmt->get_result()->fetch_assoc()['unread'];

        // Buscar últimas 5 notificações
        $stmt = $conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Navbar Error: " . $e->getMessage());
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <?php if ($isLoggedIn): ?>
                <!-- Menu para usuários logados -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/client/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/client/matches.php">
                            <i class="bi bi-heart-fill me-1"></i>Matches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/client/chat.php">
                            <i class="bi bi-chat-dots-fill me-1"></i>Chat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/client/search.php">
                            <i class="bi bi-search me-1"></i>Buscar
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <!-- Notificações -->
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="notificationsDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell-fill"></i>
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge bg-danger"><?php echo $unreadNotifications; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <h6 class="dropdown-header">Notificações</h6>
                            <?php if (!empty($notifications)): ?>
                                <?php foreach ($notifications as $notification): ?>
                                    <a class="dropdown-item <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>" 
                                       href="#">
                                        <small class="text-muted d-block">
                                            <?php echo timeAgo($notification['created_at']); ?>
                                        </small>
                                        <?php echo $notification['message']; ?>
                                    </a>
                                <?php endforeach; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-center" href="#">Ver todas</a>
                            <?php else: ?>
                                <div class="dropdown-item text-muted">Nenhuma notificação</div>
                            <?php endif; ?>
                        </div>
                    </li>

                    <!-- Menu do usuário -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i><?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/client/profile.php">
                                    <i class="bi bi-person-fill me-2"></i>Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/client/settings.php">
                                    <i class="bi bi-gear-fill me-2"></i>Configurações
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <!-- Menu para visitantes -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">
                            <i class="bi bi-person-plus-fill me-1"></i>Cadastrar
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>