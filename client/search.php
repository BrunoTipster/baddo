<?php
/**
 * Página de Busca
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 18:45:05
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/init.php';

// Definir constantes caso não existam
if (!defined('ITEMS_PER_PAGE')) define('ITEMS_PER_PAGE', 12);
if (!defined('MIN_AGE')) define('MIN_AGE', 18);
if (!defined('MAX_AGE')) define('MAX_AGE', 99);
if (!defined('MAX_SEARCH_DISTANCE')) define('MAX_SEARCH_DISTANCE', 100);

// Verificar login
if (!checkAuth()) {
    header('Location: ../login.php');
    exit;
}

// Inicializar Database
$db = Database::getInstance();

// Buscar dados do usuário atual
$currentUser = $db->single(
    "SELECT u.*, p.* 
     FROM users u 
     LEFT JOIN user_preferences p ON p.user_id = u.id 
     WHERE u.id = ?",
    [$_SESSION['user_id']]
);

// Parâmetros de busca com valores padrão
$filters = [
    'gender' => filter_input(INPUT_GET, 'gender') ?: 'B',
    'min_age' => filter_input(INPUT_GET, 'min_age', FILTER_VALIDATE_INT) ?: MIN_AGE,
    'max_age' => filter_input(INPUT_GET, 'max_age', FILTER_VALIDATE_INT) ?: MAX_AGE,
    'max_distance' => filter_input(INPUT_GET, 'max_distance', FILTER_VALIDATE_INT) ?: MAX_SEARCH_DISTANCE,
    'only_online' => filter_input(INPUT_GET, 'only_online', FILTER_VALIDATE_BOOLEAN),
    'only_verified' => filter_input(INPUT_GET, 'only_verified', FILTER_VALIDATE_BOOLEAN),
    'has_photo' => filter_input(INPUT_GET, 'has_photo', FILTER_VALIDATE_BOOLEAN),
    'page' => max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1)
];

// Validar e ajustar filtros
$filters['min_age'] = max(MIN_AGE, min(MAX_AGE, $filters['min_age']));
$filters['max_age'] = max(MIN_AGE, min(MAX_AGE, $filters['max_age']));
$filters['max_distance'] = max(1, min(MAX_SEARCH_DISTANCE, $filters['max_distance']));

// Calcular offset para paginação
$limit = ITEMS_PER_PAGE;
$offset = ($filters['page'] - 1) * $limit;

try {
    // Query base para busca de usuários
    $baseQuery = "
        SELECT DISTINCT
            u.*,
            TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) as age,
            (
                6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(COALESCE(u.latitude, ?))) * 
                    cos(radians(COALESCE(u.longitude, ?)) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(COALESCE(u.latitude, ?)))
                )
            ) as distance,
            IF(u.is_online = 1 OR u.last_active >= DATE_SUB(NOW(), INTERVAL 5 MINUTE), 1, 0) as is_really_online,
            IF(u.avatar != 'default.jpg' OR EXISTS(SELECT 1 FROM user_photos WHERE user_id = u.id), 1, 0) as has_photos
        FROM users u
        LEFT JOIN user_preferences p ON p.user_id = u.id
        WHERE u.id != ?
        AND u.status = 'active'
    ";

    // Parâmetros iniciais
    $params = [
        $currentUser['latitude'] ?? DEFAULT_LATITUDE,
        DEFAULT_LATITUDE,
        DEFAULT_LONGITUDE,
        $currentUser['longitude'] ?? DEFAULT_LONGITUDE,
        $currentUser['latitude'] ?? DEFAULT_LATITUDE,
        DEFAULT_LATITUDE,
        $_SESSION['user_id']
    ];

    // Filtros de idade
    $baseQuery .= " AND TIMESTAMPDIFF(YEAR, u.birth_date, CURDATE()) BETWEEN ? AND ?";
    $params[] = $filters['min_age'];
    $params[] = $filters['max_age'];

    // Filtro de gênero
    if ($filters['gender'] !== 'B') {
        $baseQuery .= " AND u.gender = ?";
        $params[] = $filters['gender'];
    }

    // Filtro de interesse mútuo
    $baseQuery .= " AND (
        p.interested_in = ? 
        OR p.interested_in = 'B'
        OR p.interested_in IS NULL
    )";
    $params[] = $currentUser['gender'];

    // Filtros adicionais
    if ($filters['only_online']) {
        $baseQuery .= " AND (
            u.is_online = 1 
            OR u.last_active >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        )";
    }

    if ($filters['only_verified']) {
        $baseQuery .= " AND u.is_verified = 1";
    }

    if ($filters['has_photo']) {
        $baseQuery .= " AND (
            u.avatar != 'default.jpg' 
            OR EXISTS (SELECT 1 FROM user_photos WHERE user_id = u.id)
        )";
    }

    // Excluir bloqueios
    $baseQuery .= "
        AND NOT EXISTS (
            SELECT 1 FROM blocks 
            WHERE (blocker_id = ? AND blocked_id = u.id)
            OR (blocker_id = u.id AND blocked_id = ?)
        )
    ";
    $params[] = $_SESSION['user_id'];
    $params[] = $_SESSION['user_id'];

    // Excluir matches existentes
    $baseQuery .= "
        AND NOT EXISTS (
            SELECT 1 FROM matches 
            WHERE (user1_id = ? AND user2_id = u.id)
            OR (user1_id = u.id AND user2_id = ?)
        )
    ";
    $params[] = $_SESSION['user_id'];
    $params[] = $_SESSION['user_id'];

    // Filtro de distância
    $baseQuery .= " HAVING distance <= ?";
    $params[] = $filters['max_distance'];

    // Ordenação
    $baseQuery .= " ORDER BY 
        u.is_featured DESC,
        is_really_online DESC,
        has_photos DESC,
        u.updated_at DESC,
        distance ASC
    ";

    // Contar total de resultados
    $countQuery = preg_replace('/ORDER BY.*$/i', '', $baseQuery);
    $totalUsers = count($db->all($countQuery, $params));
    $totalPages = ceil($totalUsers / $limit);

    // Adicionar limite
    $baseQuery .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;

    // Executar busca
    $users = $db->all($baseQuery, $params);

} catch (Exception $e) {
    error_log("Search Error: " . $e->getMessage());
    $error = "Erro ao buscar usuários";
}

// Debug
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("Search Query: " . $baseQuery);
    error_log("Search Params: " . print_r($params, true));
    error_log("Found Users: " . count($users ?? []));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Pessoas - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Encontre pessoas interessantes perto de você">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --badoo-pink: #FF77A9;
            --badoo-purple: #7B48B7;
            --badoo-dark: #2C2C2C;
            --badoo-gray: #F5F5F5;
        }

        body {
            background-color: var(--badoo-gray);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--badoo-pink), var(--badoo-purple));
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }

        .user-photo {
            height: 300px;
            object-fit: cover;
        }

        .user-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: white;
        }

        .online-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #4CAF50;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .btn-action {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            margin: 0 5px;
            transition: all 0.3s ease;
        }

        .btn-like {
            background-color: var(--badoo-pink);
        }

        .btn-dislike {
            background-color: #dc3545;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .filters {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .pagination {
            margin-top: 2rem;
        }

        .pagination .page-link {
            border: none;
            margin: 0 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--badoo-dark);
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background-color: var(--badoo-pink);
            color: white;
        }

        .pagination .active .page-link {
            background-color: var(--badoo-purple);
            color: white;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .no-results i {
            font-size: 4rem;
            color: var(--badoo-gray);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
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
                        <a class="nav-link active" href="search.php">
                            <i class="bi bi-search"></i> Buscar
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <img src="<?php echo !empty($currentUser['avatar']) ? '../uploads/avatars/' . $currentUser['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($currentUser['name']); ?>"
                                 class="rounded-circle"
                                 width="30"
                                 height="30">
                            <?php echo htmlspecialchars($currentUser['name']); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Filtros de Busca -->
        <div class="filters">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Gênero</label>
                    <select name="gender" class="form-select">
                        <option value="B" <?php echo $filters['gender'] === 'B' ? 'selected' : ''; ?>>Todos</option>
                        <option value="M" <?php echo $filters['gender'] === 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $filters['gender'] === 'F' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="O" <?php echo $filters['gender'] === 'O' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Idade Mínima</label>
                    <input type="number" 
                           name="min_age" 
                           class="form-control" 
                           value="<?php echo $filters['min_age']; ?>"
                           min="<?php echo MIN_AGE; ?>" 
                           max="<?php echo MAX_AGE; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Idade Máxima</label>
                    <input type="number" 
                           name="max_age" 
                           class="form-control" 
                           value="<?php echo $filters['max_age']; ?>"
                           min="<?php echo MIN_AGE; ?>" 
                           max="<?php echo MAX_AGE; ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Distância (km)</label>
                    <input type="number" 
                           name="max_distance" 
                           class="form-control" 
                           value="<?php echo $filters['max_distance']; ?>"
                           min="1" 
                           max="<?php echo MAX_SEARCH_DISTANCE; ?>">
                </div>

                <div class="col-md-3">
                    <div class="form-check mb-2">
                        <input type="checkbox" 
                               name="only_online" 
                               class="form-check-input"
                               value="1" 
                               <?php echo $filters['only_online'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Apenas Online</label>
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" 
                               name="only_verified" 
                               class="form-check-input"
                               value="1" 
                               <?php echo $filters['only_verified'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Apenas Verificados</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" 
                               name="has_photo" 
                               class="form-check-input"
                               value="1" 
                               <?php echo $filters['has_photo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label">Com Foto</label>
                    </div>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="search.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($users)): ?>
            <div class="no-results">
                <i class="bi bi-search"></i>
                <h4>Nenhum usuário encontrado</h4>
                <p class="text-muted">Tente ajustar seus filtros de busca</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($users as $user): ?>
                    <div class="col-md-4" id="user-<?php echo $user['id']; ?>">
                        <div class="card">
                            <div class="position-relative">
                                <img src="<?php echo !empty($user['avatar']) ? '../uploads/avatars/' . $user['avatar'] : '../assets/images/default-avatar.jpg'; ?>" 
                                     class="card-img-top user-photo" 
                                     alt="<?php echo htmlspecialchars($user['name']); ?>">
                                
                                <?php if ($user['is_really_online']): ?>
                                    <div class="online-indicator"></div>
                                <?php endif; ?>

                                <div class="user-info">
                                    <h5 class="mb-1">
                                        <?php echo htmlspecialchars($user['name']); ?>, 
                                        <?php echo $user['age']; ?>
                                    </h5>
                                    <p class="mb-2">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php if ($user['city'] && $user['country']): ?>
                                            <?php echo htmlspecialchars($user['city']); ?>, 
                                            <?php echo htmlspecialchars($user['country']); ?>
                                            (<?php echo number_format($user['distance'], 1); ?> km)
                                        <?php else: ?>
                                            Localização não informada
                                        <?php endif; ?>
                                    </p>
                                    <div>
                                        <button class="btn-action btn-like" onclick="like(<?php echo $user['id']; ?>)">
                                            <i class="bi bi-heart-fill"></i>
                                        </button>
                                        <button class="btn-action btn-dislike" onclick="dislike(<?php echo $user['id']; ?>)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $filters['page'] <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page']-1])); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $filters['page'] ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $filters['page'] >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($filters, ['page' => $filters['page']+1])); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de Match -->
    <div class="modal fade" id="matchModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="bi bi-heart-fill text-danger display-1 mb-3"></i>
                    <h4>É um Match!</h4>
                    <p id="matchMessage"></p>
                    <button type="button" class="btn btn-primary" id="startChat">
                        <i class="bi bi-chat-fill"></i> Começar Conversa
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

        async function like(userId) {
            try {
                const response = await fetch('actions/like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();

                if (data.success) {
                    const userCard = document.getElementById(`user-${userId}`);
                    if (userCard) {
                        userCard.style.transition = 'all 0.3s ease';
                        userCard.style.opacity = '0';
                        userCard.style.transform = 'scale(0.8)';
                        setTimeout(() => userCard.remove(), 300);
                    }

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

        async function dislike(userId) {
            try {
                const response = await fetch('actions/dislike.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();

                if (data.success) {
                    const userCard = document.getElementById(`user-${userId}`);
                    if (userCard) {
                        userCard.style.transition = 'all 0.3s ease';
                        userCard.style.opacity = '0';
                        userCard.style.transform = 'scale(0.8)';
                        setTimeout(() => userCard.remove(), 300);
                    }
                } else {
                    throw new Error(data.message || 'Erro ao rejeitar usuário');
                }
            } catch (error) {
                console.error('Error:', error);
                alert(error.message);
            }
        }
    </script>
</body>
</html>