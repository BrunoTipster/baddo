<?php
/**
 * Gerenciamento de Denúncias
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:59:51
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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $reportId = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

        if (!$reportId) {
            throw new Exception("ID da denúncia inválido");
        }

        switch ($action) {
            case 'block_user':
                $conn->begin_transaction();

                // Atualizar status da denúncia
                $stmt = $conn->prepare("
                    UPDATE reports 
                    SET status = 'resolved',
                        resolution = 'blocked',
                        resolved_by = ?,
                        resolved_at = NOW(),
                        notes = ?
                    WHERE id = ?
                ");
                $stmt->bind_param('isi', $_SESSION['user_id'], $notes, $reportId);
                $stmt->execute();

                // Buscar usuário denunciado
                $stmt = $conn->prepare("
                    SELECT reported_id 
                    FROM reports 
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $reportId);
                $stmt->execute();
                $reportedId = $stmt->get_result()->fetch_assoc()['reported_id'];

                // Bloquear usuário
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET status = 'blocked',
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('i', $reportedId);
                $stmt->execute();

                $conn->commit();
                $success = "Usuário bloqueado e denúncia resolvida";
                break;

            case 'dismiss':
                $stmt = $conn->prepare("
                    UPDATE reports 
                    SET status = 'resolved',
                        resolution = 'dismissed',
                        resolved_by = ?,
                        resolved_at = NOW(),
                        notes = ?
                    WHERE id = ?
                ");
                $stmt->bind_param('isi', $_SESSION['user_id'], $notes, $reportId);
                if ($stmt->execute()) {
                    $success = "Denúncia descartada";
                }
                break;

            case 'reviewing':
                $stmt = $conn->prepare("
                    UPDATE reports 
                    SET status = 'reviewing',
                        reviewer_id = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param('ii', $_SESSION['user_id'], $reportId);
                if ($stmt->execute()) {
                    $success = "Denúncia marcada para revisão";
                }
                break;

            default:
                throw new Exception("Ação inválida");
        }
    } catch (Exception $e) {
        error_log("Reports Error: " . $e->getMessage());
        $error = "Erro ao processar ação: " . $e->getMessage();
    }
}

// Parâmetros de busca e paginação
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?: 'pending';
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Construir query
$where = [];
$params = [];
$types = '';

if ($status) {
    $where[] = "r.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($type) {
    $where[] = "r.type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($search) {
    $where[] = "(u1.username LIKE ? OR u2.username LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
    $types .= 'ss';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Buscar denúncias
try {
    // Total de registros
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM reports r
        LEFT JOIN users u1 ON u1.id = r.reporter_id
        LEFT JOIN users u2 ON u2.id = r.reported_id
        $whereClause
    ");

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    // Buscar denúncias
    $stmt = $conn->prepare("
        SELECT 
            r.*,
            u1.username as reporter_username,
            u2.username as reported_username,
            u2.avatar as reported_avatar,
            u3.username as resolver_username,
            (SELECT COUNT(*) FROM reports WHERE reported_id = r.reported_id) as total_reports
        FROM reports r
        LEFT JOIN users u1 ON u1.id = r.reporter_id
        LEFT JOIN users u2 ON u2.id = r.reported_id
        LEFT JOIN users u3 ON u3.id = r.resolved_by
        $whereClause
        ORDER BY 
            CASE r.status
                WHEN 'pending' THEN 1
                WHEN 'reviewing' THEN 2
                ELSE 3
            END,
            r.created_at DESC
        LIMIT ?, ?
    ");

    $params[] = $offset;
    $params[] = $limit;
    $types .= 'ii';

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Reports Error: " . $e->getMessage());
    $error = "Erro ao carregar denúncias";
    $reports = [];
    $totalPages = 0;
}

$pageTitle = "Gerenciar Denúncias";
require_once BASE_PATH . '/includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Gerenciar Denúncias</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportReports('pdf')">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportReports('excel')">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="text" 
                           class="form-control" 
                           name="search" 
                           value="<?php echo escape($search); ?>" 
                           placeholder="Buscar por usuário">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="reviewing" <?php echo $status === 'reviewing' ? 'selected' : ''; ?>>Em Revisão</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolvidas</option>
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Todos os tipos</option>
                        <option value="fake" <?php echo $type === 'fake' ? 'selected' : ''; ?>>Perfil Falso</option>
                        <option value="inappropriate" <?php echo $type === 'inappropriate' ? 'selected' : ''; ?>>Conteúdo Impróprio</option>
                        <option value="harassment" <?php echo $type === 'harassment' ? 'selected' : ''; ?>>Assédio</option>
                        <option value="spam" <?php echo $type === 'spam' ? 'selected' : ''; ?>>Spam</option>
                        <option value="other" <?php echo $type === 'other' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Lista de Denúncias -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <?php if (empty($reports)): ?>
                <div class="text-center py-4">
                    <p class="text-muted">Nenhuma denúncia encontrada</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Denunciante</th>
                                <th>Denunciado</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Descrição</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo $report['id']; ?></td>
                                <td>
                                    <a href="../profile.php?username=<?php echo urlencode($report['reporter_username']); ?>">
                                        <?php echo escape($report['reporter_username']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo SITE_URL . '/assets/images/avatars/' . ($report['reported_avatar'] ?: 'default.jpg'); ?>" 
                                             class="rounded-circle me-2" 
                                             width="32" 
                                             height="32"
                                             alt="Avatar">
                                        <div>
                                            <a href="../profile.php?username=<?php echo urlencode($report['reported_username']); ?>">
                                                <?php echo escape($report['reported_username']); ?>
                                            </a>
                                            <?php if ($report['total_reports'] > 1): ?>
                                                <span class="badge bg-warning ms-1" title="Total de denúncias">
                                                    <?php echo $report['total_reports']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'fake' => 'Perfil Falso',
                                        'inappropriate' => 'Conteúdo Impróprio',
                                        'harassment' => 'Assédio',
                                        'spam' => 'Spam',
                                        'other' => 'Outro'
                                    ];
                                    echo $typeLabels[$report['type']] ?? $report['type'];
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'reviewing' => 'info',
                                        'resolved' => 'success'
                                    ][$report['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($report['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo escape($report['description']); ?>
                                </td>
                                <td><?php echo formatDateTime($report['created_at']); ?></td>
                                <td>
                                    <?php if ($report['status'] !== 'resolved'): ?>
                                        <div class="btn-group">
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                Ações
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <?php if ($report['status'] === 'pending'): ?>
                                                    <li>
                                                        <form method="POST">
                                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                            <input type="hidden" name="action" value="reviewing">
                                                            <button type="submit" class="dropdown-item text-info">
                                                                <i class="bi bi-eye"></i> Marcar Em Revisão
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                <li>
                                                    <button type="button" 
                                                            class="dropdown-item text-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#blockUserModal"
                                                            data-report-id="<?php echo $report['id']; ?>"
                                                            data-username="<?php echo $report['reported_username']; ?>">
                                                        <i class="bi bi-lock"></i> Bloquear Usuário
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" 
                                                            class="dropdown-item"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#dismissModal"
                                                            data-report-id="<?php echo $report['id']; ?>">
                                                        <i class="bi bi-x-circle"></i> Descartar
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailsModal"
                                                data-report-id="<?php echo $report['id']; ?>"
                                                data-resolution="<?php echo $report['resolution']; ?>"
                                                data-notes="<?php echo $report['notes']; ?>"
                                                data-resolver="<?php echo $report['resolver_username']; ?>"
                                                data-resolved-at="<?php echo formatDateTime($report['resolved_at']); ?>">
                                            <i class="bi bi-info-circle"></i> Detalhes
                                        </button>
                                    <?php endif; ?>
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
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>">
                                Anterior
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>">
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

<!-- Modal de Bloquear Usuário -->
<div class="modal fade" id="blockUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="report_id" id="blockReportId">
                <input type="hidden" name="action" value="block_user">
                
                <div class="modal-header">
                    <h5 class="modal-title">Bloquear Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja bloquear o usuário <strong id="blockUsername"></strong>?</p>
                    <div class="mb-3">
                        <label for="blockNotes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="blockNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Bloquear</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Descartar -->
<div class="modal fade" id="dismissModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="report_id" id="dismissReportId">
                <input type="hidden" name="action" value="dismiss">
                
                <div class="modal-header">
                    <h5 class="modal-title">Descartar Denúncia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dismissNotes" class="form-label">Motivo do Descarte</label>
                        <textarea class="form-control" id="dismissNotes" name="notes" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Resolução</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Resolução</dt>
                    <dd class="col-sm-8" id="detailsResolution"></dd>

                    <dt class="col-sm-4">Resolvido por</dt>
                    <dd class="col-sm-8" id="detailsResolver"></dd>

                    <dt class="col-sm-4">Data</dt>
                    <dd class="col-sm-8" id="detailsResolvedAt"></dd>

                    <dt class="col-sm-4">Notas</dt>
                    <dd class="col-sm-8" id="detailsNotes"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Manipulação dos modais
document.addEventListener('DOMContentLoaded', function() {
    // Modal de bloquear usuário
    const blockUserModal = document.getElementById('blockUserModal');
    blockUserModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const reportId = button.dataset.reportId;
        const username = button.dataset.username;
        
        document.getElementById('blockReportId').value = reportId;
        document.getElementById('blockUsername').textContent = username;
    });

    // Modal de descartar
    const dismissModal = document.getElementById('dismissModal');
    dismissModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const reportId = button.dataset.reportId;
        
        document.getElementById('dismissReportId').value = reportId;
    });

    // Modal de detalhes
    const detailsModal = document.getElementById('detailsModal');
    detailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const resolution = button.dataset.resolution;
        const notes = button.dataset.notes;
        const resolver = button.dataset.resolver;
        const resolvedAt = button.dataset.resolvedAt;
        
        document.getElementById('detailsResolution').textContent = resolution;
        document.getElementById('detailsResolver').textContent = resolver;
        document.getElementById('detailsResolvedAt').textContent = resolvedAt;
        document.getElementById('detailsNotes').textContent = notes || 'Nenhuma nota adicionada';
    });
});

// Função para exportação
function exportReports(format) {
    const params = new URLSearchParams(window.location.search);
    window.location.href = `export-reports.php?format=${format}&${params.toString()}`;
}
</script>

<?php require_once BASE_PATH . '/includes/admin-footer.php'; ?>