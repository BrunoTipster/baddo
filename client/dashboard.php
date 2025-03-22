<?php
/**
 * Dashboard do Usuário
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

try {
    // Buscar dados do usuário
    $user = $db->single(
        "SELECT u.*, p.*
         FROM users u
         LEFT JOIN user_preferences p ON p.user_id = u.id
         WHERE u.id = ?",
        [$_SESSION['user_id']]
    );

    if (!$user) {
        throw new Exception("Usuário não encontrado");
    }

    // Atualizar status online
    $db->query(
        "UPDATE users 
         SET is_online = 1,
             last_active = NOW() 
         WHERE id = ?",
        [$_SESSION['user_id']]
    );

    // Buscar notificações não lidas
    $notifications = $db->all(
        "SELECT * FROM notifications
         WHERE user_id = ?
         AND is_read = 0
         ORDER BY created_at DESC
         LIMIT 5",
        [$_SESSION['user_id']]
    );

    // Buscar matches recentes
    $matches = $db->all(
        "SELECT m.*, u.name, u.avatar, u.city, u.country,
                (SELECT COUNT(*) FROM messages 
                 WHERE match_id = m.id 
                 AND sender_id != ? 
                 AND is_read = 0) as unread_count
         FROM matches m
         JOIN users u ON (
             CASE 
                 WHEN m.user1_id = ? THEN u.id = m.user2_id
                 ELSE u.id = m.user1_id
             END
         )
         WHERE (m.user1_id = ? OR m.user2_id = ?)
         AND m.status = 'matched'
         ORDER BY m.matched_at DESC
         LIMIT 6",
        [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]
    );

    // Buscar sugestões
    $suggestions = $db->all(
        "SELECT u.*, 
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(?)) + 
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance
         FROM users u
         WHERE u.id != ?
         AND u.status = 'active'
         AND NOT EXISTS (
             SELECT 1 FROM matches m
             WHERE (m.user1_id = ? AND m.user2_id = u.id)
             OR (m.user1_id = u.id AND m.user2_id = ?)
         )
         AND NOT EXISTS (
             SELECT 1 FROM blocks b
             WHERE (b.blocker_id = ? AND b.blocked_id = u.id)
             OR (b.blocker_id = u.id AND b.blocked_id = ?)
         )
         AND u.gender = CASE 
             WHEN ? = 'B' THEN u.gender
             ELSE ?
         END
         HAVING distance <= ?
         ORDER BY u.is_featured DESC, RAND()
         LIMIT 8",
        [
            $user['latitude'] ?? DEFAULT_LATITUDE,
            $user['longitude'] ?? DEFAULT_LONGITUDE,
            $user['latitude'] ?? DEFAULT_LATITUDE,
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $_SESSION['user_id'],
            $user['interested_in'],
            $user['interested_in'],
            $user['max_distance'] ?? MAX_SEARCH_DISTANCE
        ]
    );

    // Buscar visitantes recentes
    $visitors = $db->all(
        "SELECT DISTINCT v.*, u.name, u.avatar, u.city, u.country
         FROM visits v
         JOIN users u ON u.id = v.visitor_id
         WHERE v.visited_id = ?
         ORDER BY v.created_at DESC
         LIMIT 6",
        [$_SESSION['user_id']]
    );

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $error = "Erro ao carregar dashboard";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
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
            background-color: var(--badoo-gray);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--badoo-pink) 0%, var(--badoo-purple) 100%);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .suggestion-card {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            height: 300px;
        }

        .suggestion-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .suggestion-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
        }

        .online-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #4CAF50;
            display: inline-block;
            margin-right: 5px;
            box-shadow: 0 0 0 2px white;
        }

        .offline-indicator {
            background-color: #757575;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--badoo-pink);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            z-index: 1;
        }

        .verified-badge {
            color: var(--badoo-blue);
            margin-left: 5px;
        }

        .action-buttons {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-like {
            background-color: var(--badoo-pink);
        }

        .btn-chat {
            background-color: var(--badoo-purple);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--badoo-pink);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .matches-container {
            display: flex;
            overflow-x: auto;
            padding: 1rem;
            gap: 1rem;
            scrollbar-width: none;
        }

        .matches-container::-webkit-scrollbar {
            display: none;
        }

        .match-card {
            flex: 0 0 auto;
            width: 150px;
            text-align: center;
        }

        .match-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
        }

        .section-title {
            color: var(--badoo-dark);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--badoo-pink);
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
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="matches.php">
                            <i class="bi bi-heart"></i> Matches
                            <?php if (!empty($matches)): ?>
                                <span class="badge bg-danger">
                                    <?php echo count($matches); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages.php">
                            <i class="bi bi-chat"></i> Mensagens
                            <?php 
                            $unread = array_sum(array_column($matches, 'unread_count'));
                            if ($unread > 0):
                            ?>
                                <span class="badge bg-danger"><?php echo $unread; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php">
                            <i class="bi bi-search"></i> Buscar
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" 
                           href="#" 
                           data-bs-toggle="dropdown">
                            <img src="<?php echo !empty($user['avatar']) ? '../uploads/avatars/' . $user['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                 class="avatar me-2"
                                 alt="<?php echo htmlspecialchars($user['name']); ?>">
                            <?php echo htmlspecialchars($user['name']); ?>
                            <?php if ($user['is_verified']): ?>
                                <i class="bi bi-patch-check-fill verified-badge ms-1"></i>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="edit-profile.php">
                                    <i class="bi bi-person"></i> Editar Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="bi bi-gear"></i> Configurações
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            </div>
        <?php else: ?>
            <!-- Matches Recentes -->
            <?php if (!empty($matches)): ?>
                <h4 class="section-title">
                    <i class="bi bi-hearts"></i> 
                    Matches Recentes
                </h4>
                <div class="matches-container mb-4">
                    <?php foreach ($matches as $match): ?>
                        <div class="match-card">
                            <a href="messages.php?match_id=<?php echo $match['id']; ?>" 
                               class="text-decoration-none">
                                <div class="position-relative">
                                    <img src="<?php echo !empty($match['avatar']) ? '../uploads/avatars/' . $match['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                         class="match-avatar"
                                         alt="<?php echo htmlspecialchars($match['name']); ?>">
                                    <?php if ($match['unread_count'] > 0): ?>
                                        <span class="notification-badge">
                                            <?php echo $match['unread_count']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <h6 class="mb-0 text-dark">
                                    <?php echo htmlspecialchars($match['name']); ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo $match['city']; ?>
                                </small>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Sugestões -->
            <?php if (!empty($suggestions)): ?>
                <h4 class="section-title">
                    <i class="bi bi-people"></i>
                    Sugestões para Você
                </h4>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
                    <?php foreach ($suggestions as $suggestion): ?>
                        <div class="col">
                            <div class="suggestion-card">
                                <?php if ($suggestion['is_featured']): ?>
                                    <div class="featured-badge">
                                        <i class="bi bi-star-fill"></i> Destaque
                                    </div>
                                <?php endif; ?>
                                
                                <img src="<?php echo !empty($suggestion['avatar']) ? '../uploads/avatars/' . $suggestion['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                     class="suggestion-image"
                                     alt="<?php echo htmlspecialchars($suggestion['name']); ?>">
                                
                                <div class="suggestion-info">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($suggestion['name']); ?>, 
                                        <?php echo calculateAge($suggestion['birth_date']); ?>
                                        <?php if ($suggestion['is_verified']): ?>
                                            <i class="bi bi-patch-check-fill verified-badge"></i>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="mb-2">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php if ($suggestion['city'] && $suggestion['country']): ?>
                                            <?php echo htmlspecialchars($suggestion['city']); ?>, 
                                            <?php echo htmlspecialchars($suggestion['country']); ?>
                                            (<?php echo number_format($suggestion['distance'], 1); ?> km)
                                        <?php else: ?>
                                            Localização não informada
                                        <?php endif; ?>
                                    </p>
                                    <div class="action-buttons">
                                        <button type="button" 
                                                class="btn-action btn-like"
                                                onclick="like(<?php echo $suggestion['id']; ?>)">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                        <a href="profile.php?id=<?php echo $suggestion['id']; ?>" 
                                           class="btn-action btn-chat">
                                            <i class="bi bi-person"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Visitantes -->
            <?php if (!empty($visitors)): ?>
                <h4 class="section-title">
                    <i class="bi bi-eye"></i>
                    Visitantes Recentes
                </h4>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-6 g-4">
                    <?php foreach ($visitors as $visitor): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="<?php echo !empty($visitor['avatar']) ? '../uploads/avatars/' . $visitor['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                         class="avatar mb-3"
                                         alt="<?php echo htmlspecialchars($visitor['name']); ?>">
                                    <h6 class="card-title mb-1">
                                        <?php echo htmlspecialchars($visitor['name']); ?>
                                    </h6>
                                    <p class="text-muted small mb-2">
                                        <?php echo timeAgo($visitor['created_at']); ?>
                                    </p>
                                    <a href="profile.php?id=<?php echo $visitor['visitor_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Ver Perfil
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Match -->
    <div class="modal fade" id="matchModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-heart-fill text-danger display-1 mb-3"></i>
                    <h4 class="modal-title">É um Match!</h4>
                    <p id="matchMessage"></p>
                    <button type="button" class="btn btn-primary" id="startChat">
                        <i class="bi bi-chat"></i> Começar Conversa
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Continuar Buscando
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const matchModal = new bootstrap.Modal(document.getElementById('matchModal'));

        // Like
        async function like(userId) {
            try {
                const response = await fetch('like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();

                if (data.success) {
                    // Remover card
                    const card = document.querySelector(`[data-user-id="${userId}"]`);
                    if (card) {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.8)';
                        setTimeout(() => card.remove(), 300);
                    }

                    // Se for match
                    if (data.match) {
                        document.getElementById('matchMessage').textContent = 
                            `Você e ${data.match.name} se curtiram!`;
                        document.getElementById('startChat').onclick = () => {
                            window.location.href = `messages.php?match_id=${data.match.id}`;
                        };
                        matchModal.show();
                    }
                } else {
                    throw new Error(data.message || 'Erro ao curtir usuário');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
            }
        }

        // Atualizar status online
        function updateOnlineStatus() {
            fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
        }

        // Atualizar a cada 5 minutos
        setInterval(updateOnlineStatus, 5 * 60 * 1000);

        // Atualizar ao voltar para a página
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updateOnlineStatus();
            }
        });
    </script>
</body>
</html>