<?php
/**
 * Lista de Matches
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

// Parâmetros de busca e paginação
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    // Total de matches
    $params = [$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']];
    $searchWhere = "";
    
    if ($search) {
        $searchWhere = "AND (u.name LIKE ? OR u.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $total = $db->single(
        "SELECT COUNT(*) as total
         FROM matches m
         JOIN users u ON (
             CASE 
                 WHEN m.user1_id = ? THEN u.id = m.user2_id
                 ELSE u.id = m.user1_id
             END
         )
         WHERE (m.user1_id = ? OR m.user2_id = ?)
         AND m.status = 'matched'
         AND u.status = 'active'
         $searchWhere",
        $params
    )['total'];

    $totalPages = ceil($total / $limit);

    // Buscar matches
    $params = [
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id'],
        $_SESSION['user_id']
    ];

    if ($search) {
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $params[] = $offset;
    $params[] = $limit;

    $matches = $db->all(
        "SELECT m.*,
                u.username, u.name, u.avatar, u.birth_date, 
                u.city, u.country, u.is_online, u.last_active,
                (
                    SELECT message 
                    FROM messages 
                    WHERE match_id = m.id 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) as last_message,
                (
                    SELECT created_at 
                    FROM messages 
                    WHERE match_id = m.id 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ) as last_message_at,
                (
                    SELECT COUNT(*) 
                    FROM messages 
                    WHERE match_id = m.id
                ) as messages_count,
                (
                    SELECT COUNT(*) 
                    FROM messages 
                    WHERE match_id = m.id 
                    AND sender_id != ? 
                    AND is_read = 0
                ) as unread_count
         FROM matches m
         JOIN users u ON (
             CASE 
                 WHEN m.user1_id = ? THEN u.id = m.user2_id
                 ELSE u.id = m.user1_id
             END
         )
         WHERE (m.user1_id = ? OR m.user2_id = ?)
         AND m.status = 'matched'
         AND u.status = 'active'
         $searchWhere
         ORDER BY COALESCE(last_message_at, m.matched_at) DESC
         LIMIT ?, ?",
        $params
    );

} catch (Exception $e) {
    error_log("Matches Error: " . $e->getMessage());
    $error = "Erro ao carregar matches";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Matches - <?php echo SITE_NAME; ?></title>
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

        .card {
            transition: transform 0.3s ease;
            border: none;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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

        .message-preview {
            height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--primary-color);
            opacity: 0.5;
        }

        .btn-action {
            border-radius: 20px;
            padding: 0.5rem 1.5rem;
        }

        .match-stats {
            background: rgba(0,0,0,0.05);
            padding: 0.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
        }

        .pagination .page-link {
            color: var(--primary-color);
        }

        .pagination .active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
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
                        <a class="nav-link active" href="matches.php">
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
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Meus Matches</h1>
            
            <form class="d-flex" method="GET">
                <input type="search" 
                       class="form-control me-2" 
                       name="search" 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Buscar match...">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($matches)): ?>
            <div class="text-center py-5 empty-state">
                <i class="bi bi-heart-break mb-4"></i>
                <h4>Nenhum match encontrado</h4>
                <p class="text-muted">Comece a interagir com outras pessoas para fazer matches!</p>
                <a href="search.php" class="btn btn-primary mt-3 btn-action">
                    <i class="bi bi-search"></i> Encontrar Pessoas
                </a>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($matches as $match): ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo !empty($match['avatar']) ? '../uploads/avatars/' . $match['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                         class="avatar me-3"
                                         alt="<?php echo htmlspecialchars($match['name']); ?>">
                                    
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <?php echo htmlspecialchars($match['name']); ?>, 
                                            <?php echo calculateAge($match['birth_date']); ?>
                                        </h5>
                                        <p class="text-muted small mb-0">
                                            <?php if ($match['city'] && $match['country']): ?>
                                                <?php echo htmlspecialchars($match['city']); ?>, 
                                                <?php echo htmlspecialchars($match['country']); ?>
                                            <?php else: ?>
                                                Localização não informada
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <?php if ($match['is_online']): ?>
                                        <span class="online-indicator"></span>
                                    <?php endif; ?>
                                </div>

                                <div class="match-stats mb-3">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">
                                            Match há <?php echo timeAgo($match['matched_at']); ?>
                                        </small>
                                        <div>
                                            <span class="badge bg-light text-dark">
                                                <i class="bi bi-chat-dots"></i> 
                                                <?php echo $match['messages_count']; ?>
                                            </span>
                                            <?php if ($match['unread_count'] > 0): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <?php echo $match['unread_count']; ?> nova(s)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($match['last_message']): ?>
                                    <div class="message-preview mb-3">
                                        <small class="text-muted">
                                            <i class="bi bi-quote"></i>
                                            <?php echo htmlspecialchars($match['last_message']); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-grid gap-2">
                                    <a href="messages.php?match_id=<?php echo $match['id']; ?>" 
                                       class="btn btn-primary btn-action">
                                        <i class="bi bi-chat"></i> Conversar
                                    </a>
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-action"
                                            onclick="unmatch(<?php echo $match['id']; ?>, '<?php echo htmlspecialchars($match['name']); ?>')">
                                        <i class="bi bi-heart-break"></i> Desfazer Match
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" 
                                   href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="unmatchModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-heart-break text-danger"></i> 
                        Desfazer Match
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja desfazer o match com <strong id="unmatchName"></strong>?</p>
                    <p class="text-muted small">
                        <i class="bi bi-exclamation-triangle text-warning"></i>
                        Esta ação não pode ser desfeita e todo o histórico de conversas será perdido.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmUnmatch">
                        <i class="bi bi-heart-break"></i> Desfazer Match
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let unmatchId = null;
        const modal = new bootstrap.Modal(document.getElementById('unmatchModal'));

        function unmatch(matchId, name) {
            unmatchId = matchId;
            document.getElementById('unmatchName').textContent = name;
            modal.show();
        }

        document.getElementById('confirmUnmatch').addEventListener('click', function() {
            if (!unmatchId) return;

            const button = this;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';

            fetch('unmatch.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ match_id: unmatchId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao desfazer match');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar sua solicitação');
            })
            .finally(() => {
                button.disabled = false;
                button.innerHTML = '<i class="bi bi-heart-break"></i> Desfazer Match';
                modal.hide();
                unmatchId = null;
            });
        });
    </script>
</body>
</html>