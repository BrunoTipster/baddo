<?php
/**
 * Estatísticas do Sistema
 * 
 * @package BadooClone
 * @version 1.0.0
 * @author BrunoTipster
 * @last_modified 2025-03-22 15:57:25
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

try {
    // Estatísticas gerais
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
            (SELECT COUNT(*) FROM users WHERE status = 'blocked') as blocked_users,
            (SELECT COUNT(*) FROM users WHERE is_verified = 1) as verified_users,
            (SELECT COUNT(*) FROM matches WHERE status = 'matched') as total_matches,
            (SELECT COUNT(*) FROM messages) as total_messages,
            (SELECT COUNT(*) FROM reports WHERE status = 'pending') as pending_reports
    ");
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();

    // Usuários por gênero
    $stmt = $conn->prepare("
        SELECT gender, COUNT(*) as count
        FROM users
        WHERE status = 'active'
        GROUP BY gender
    ");
    $stmt->execute();
    $genderStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Usuários por idade
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN age < 25 THEN '18-24'
                WHEN age BETWEEN 25 AND 34 THEN '25-34'
                WHEN age BETWEEN 35 AND 44 THEN '35-44'
                ELSE '45+'
            END as age_range,
            COUNT(*) as count
        FROM users
        WHERE status = 'active'
        GROUP BY age_range
        ORDER BY age_range
    ");
    $stmt->execute();
    $ageStats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Novos usuários por mês
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute();
    $userGrowth = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Matches por mês
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM matches
        WHERE status = 'matched'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute();
    $matchGrowth = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Statistics Error: " . $e->getMessage());
    $error = "Erro ao carregar estatísticas";
}

$pageTitle = "Estatísticas do Sistema";
require_once BASE_PATH . '/includes/admin-header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Estatísticas do Sistema</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportStats('pdf')">
                    <i class="bi bi-file-pdf"></i> Exportar PDF
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportStats('excel')">
                    <i class="bi bi-file-excel"></i> Exportar Excel
                </button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-calendar3"></i> Este Mês
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Cards de Estatísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Usuários Ativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['active_users']); ?>
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
                                Total de Matches
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_matches']); ?>
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Mensagens Trocadas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_messages']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-chat-dots fa-2x text-gray-300"></i>
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
                                <?php echo number_format($stats['pending_reports']); ?>
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
        <!-- Crescimento de Usuários -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Crescimento de Usuários</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribuição por Gênero -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribuição por Gênero</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="genderChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Distribuição por Idade -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribuição por Idade</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="ageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matches por Mês -->
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Matches por Mês</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="matchesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Dados para os gráficos
const userGrowthData = <?php echo json_encode($userGrowth); ?>;
const genderData = <?php echo json_encode($genderStats); ?>;
const ageData = <?php echo json_encode($ageStats); ?>;
const matchData = <?php echo json_encode($matchGrowth); ?>;

// Configuração dos gráficos
const userGrowthChart = new Chart(
    document.getElementById('userGrowthChart'),
    {
        type: 'line',
        data: {
            labels: userGrowthData.map(item => item.month),
            datasets: [{
                label: 'Novos Usuários',
                data: userGrowthData.map(item => item.count),
                borderColor: 'rgb(78, 115, 223)',
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false
        }
    }
);

const genderChart = new Chart(
    document.getElementById('genderChart'),
    {
        type: 'doughnut',
        data: {
            labels: genderData.map(item => 
                item.gender === 'M' ? 'Masculino' : 
                item.gender === 'F' ? 'Feminino' : 'Outro'
            ),
            datasets: [{
                data: genderData.map(item => item.count),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
            }]
        },
        options: {
            maintainAspectRatio: false
        }
    }
);

const ageChart = new Chart(
    document.getElementById('ageChart'),
    {
        type: 'bar',
        data: {
            labels: ageData.map(item => item.age_range),
            datasets: [{
                label: 'Usuários',
                data: ageData.map(item => item.count),
                backgroundColor: '#4e73df'
            }]
        },
        options: {
            maintainAspectRatio: false
        }
    }
);

const matchesChart = new Chart(
    document.getElementById('matchesChart'),
    {
        type: 'bar',
        data: {
            labels: matchData.map(item => item.month),
            datasets: [{
                label: 'Matches',
                data: matchData.map(item => item.count),
                backgroundColor: '#1cc88a'
            }]
        },
        options: {
            maintainAspectRatio: false
        }
    }
);

// Função para exportação
function exportStats(format) {
    const params = new URLSearchParams(window.location.search);
    window.location.href = `export-stats.php?format=${format}`;
}
</script>

<?php require_once BASE_PATH . '/includes/admin-footer.php'; ?>