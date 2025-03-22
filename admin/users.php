<?php
/**
 * Gerenciamento de Usuários
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:55:03
 */

session_start();
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/includes/functions.php';

// Verifica se é admin
if (!isAdmin()) {
    redirect('login.php');
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Inicializar variáveis
$error = '';
$success = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        
        if (!$userId) {
            throw new Exception("ID de usuário inválido");
        }

        switch ($action) {
            case 'block':
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET status = 'blocked',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $userId);
                if ($stmt->execute()) {
                    logActivity($userId, 'block', 'Usuário bloqueado pelo admin');
                    $success = "Usuário bloqueado com sucesso";
                }
                break;

            case 'unblock':
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET status = 'active',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $userId);
                if ($stmt->execute()) {
                    logActivity($userId, 'unblock', 'Usuário desbloqueado pelo admin');
                    $success = "Usuário desbloqueado com sucesso";
                }
                break;

            case 'delete':
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET status = 'deleted',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $userId);
                if ($stmt->execute()) {
                    logActivity($userId, 'delete', 'Usuário deletado pelo admin');
                    $success = "Usuário deletado com sucesso";
                }
                break;

            case 'verify':
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET is_verified = 1,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $userId);
                if ($stmt->execute()) {
                    logActivity($userId, 'verify', 'Usuário verificado pelo admin');
                    $success = "Usuário verificado com sucesso";
                }
                break;

            default:
                throw new Exception("Ação inválida");
        }
    } catch (Exception $e) {
        error_log("Admin Users Error: " . $e->getMessage());
        $error = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Parâmetros de busca e paginação
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Construir query
$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(username LIKE ? OR email LIKE ? OR name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Buscar usuários
try {
    // Total de registros
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM users 
        $whereClause
    ");

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    // Buscar usuários
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM user_photos WHERE user_id = u.id) as photos_count,
            (SELECT COUNT(*) FROM matches WHERE (user1_id = u.id OR user2_id = u.id) AND status = 'matched') as matches_count,
            (SELECT COUNT(*) FROM reports WHERE reported_id = u.id) as reports_count
        FROM users u
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT ?, ?
    ");

    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Admin Users Error: " . $e->getMessage());
    $error = "Erro ao carregar usuários: " . $e->getMessage();
    $users = [];
    $totalPages = 0;
}

$pageTitle = "Gerenciar Usuários";
require_once BASE_PATH . '/includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gerenciar Usuários</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportUsers('pdf')">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportUsers('excel')">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           value="<?php echo escape($search); ?>" 
                           placeholder="Buscar por nome, email ou username">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Ativos</option>
                        <option value="blocked" <?php echo $status === 'blocked' ? 'selected' : ''; ?>>Bloqueados</option>
                        <option value="deleted" <?php echo $status === 'deleted' ? 'selected' : ''; ?>>Deletados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="?" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

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

    <!-- Lista de Usuários -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (empty($users)): ?>
                <div class="text-center py-4">
                    <p class="text-muted">Nenhum usuário encontrado</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Avatar</th>
                                <th>Username</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Fotos</th>
                                <th>Matches</th>
                                <th>Denúncias</th>
                                <th>Registrado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <img src="<?php echo SITE_URL . '/assets/images/avatars/' . ($user['avatar'] ?: 'default.jpg'); ?>" 
                                         class="rounded-circle" 
                                         width="40" 
                                         height="40"
                                         alt="Avatar">
                                </td>
                                <td>
                                    <?php echo escape($user['username']); ?>
                                    <?php if ($user['is_verified']): ?>
                                        <i class="bi bi-patch-check-fill text-primary" title="Verificado"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo escape($user['name']); ?></td>
                                <td><?php echo escape($user['email']); ?></td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'active' => 'success',
                                        'blocked' => 'danger',
                                        'deleted' => 'secondary'
                                    ][$user['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['photos_count']; ?></td>
                                <td><?php echo $user['matches_count']; ?></td>
                                <td>
                                    <?php if ($user['reports_count'] > 0): ?>
                                        <span class="badge bg-warning">
                                            <?php echo $user['reports_count']; ?>
                                        </span>
                                    <?php else: ?>
                                        0
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatDateTime($user['created_at']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                            Ações
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="view.php?id=<?php echo $user['id']; ?>">
                                                    <i class="bi bi-eye"></i> Ver Perfil
                                                </a>
                                            </li>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="block">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-lock"></i> Bloquear
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            <?php if ($user['status'] === 'blocked'): ?>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="unblock">
                                                        <button type="submit" class="dropdown-item text-success">
                                                            <i class="bi bi-unlock"></i> Desbloquear
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            <?php if (!$user['is_verified']): ?>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="verify">
                                                        <button type="submit" class="dropdown-item text-primary">
                                                            <i class="bi bi-patch-check"></i> Verificar
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Tem certeza que deseja deletar este usuário?')">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash"></i> Deletar
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                Anterior
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                Próximo
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script para exportação -->
<script>
function exportUsers(format) {
    const params = new URLSearchParams(window.location.search);
    const search = params.get('search') || '';
    const status = params.get('status') || '';
    
    window.location.href = `export.php?format=${format}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
}
</script>

<?php require_once BASE_PATH . '/includes/admin-footer.php'; ?>