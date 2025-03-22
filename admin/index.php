<?php
/**
 * Dashboard Administrativo
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:51:15
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

// Busca estatísticas
try {
    // Total de usuários
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
            COUNT(CASE WHEN is_online = 1 THEN 1 END) as online_users,
            COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked_users
        FROM users
    ");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_assoc();

    // Matches hoje
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_matches
        FROM matches
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $matches = $stmt->get_result()->fetch_assoc();

    // Mensagens hoje
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_messages
        FROM messages
        WHERE DATE(created_at) = CURDATE()
    ");
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_assoc();

    // Denúncias pendentes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_reports
        FROM reports
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_assoc();

    // Últimas atividades
    $stmt = $conn->prepare("
        SELECT a.*, u.username
        FROM activity_logs a
        LEFT JOIN users u ON u.id = a.user_id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $error = "Erro ao carregar dados do dashboard";
}

// Título da página
$pageTitle = "Dashboard Administrativo";
require_once BASE_PATH . '/includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportReport('pdf')">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportReport('excel')">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                <i class="bi bi-calendar3"></i> Esta semana
            </button>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Usuários Totais
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($users['total_users']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Usuários Online
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($users['online_users']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Matches Hoje
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($matches['total_matches']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-heart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Denúncias Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($reports['total_reports']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Usuários Registrados</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Período:</div>
                            <a class="dropdown-item active" href="#">7 dias</a>
                            <a class="dropdown-item" href="#">30 dias</a>
                            <a class="dropdown-item" href="#">90 dias</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">Exportar dados</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Status dos Usuários</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Opções:</div>
                            <a class="dropdown-item" href="#">Ver detalhes</a>
                            <a class="dropdown-item" href="#">Exportar dados</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="userStatusChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="me-2">
                            <i class="bi bi-circle-fill text-primary"></i> Ativos
                        </span>
                        <span class="me-2">
                            <i class="bi bi-circle-fill text-success"></i> Online
                        </span>
                        <span class="me-2">
                            <i class="bi bi-circle-fill text-danger"></i> Bloqueados
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Atividades -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Últimas Atividades</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Ação</th>
                            <th>Descrição</th>
                            <th>IP</th>
                            <th>Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?php echo escape($activity['username'] ?? 'Sistema'); ?></td>
                            <td><?php echo escape($activity['action']); ?></td>
                            <td><?php echo escape($activity['description']); ?></td>
                            <td><?php echo escape($activity['ip_address']); ?></td>
                            <td><?php echo formatDateTime($activity['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Gráfico de Usuários
const userCtx = document.getElementById('userChart').getContext('2d');
const userChart = new Chart(userCtx, {
    type: 'line',
    data: {
        labels: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
        datasets: [{
            label: 'Novos Usuários',
            data: [15, 25, 20, 30, 22, 18, 26],
            borderColor: 'rgb(78, 115, 223)',
            tension: 0.3
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de Status
const statusCtx = document.getElementById('userStatusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Ativos', 'Online', 'Bloqueados'],
        datasets: [{
            data: [
                <?php echo $users['active_users']; ?>,
                <?php echo $users['online_users']; ?>,
                <?php echo $users['blocked_users']; ?>
            ],
            backgroundColor: [
                'rgb(78, 115, 223)',
                'rgb(28, 200, 138)',
                'rgb(231, 74, 59)'
            ]
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

function exportReport(type) {
    // Implementar exportação
    alert('Exportando relatório em ' + type.toUpperCase());
}
</script>

<?php require_once BASE_PATH . '/includes/admin-footer.php'; ?>