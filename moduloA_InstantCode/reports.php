<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

$page_title = "Relatórios e Analytics";
$page_specific_css = "css/reports.css";
$show_sidebar = true;
include 'includes/header.php';

try {
    $ativos_stats = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'operacional' THEN 1 ELSE 0 END) as operacionais,
            SUM(CASE WHEN status = 'manutencao' THEN 1 ELSE 0 END) as manutencao
        FROM ativos
    ")->fetch();

    $manutencoes_stats = $conn->query("
        SELECT 
            SUM(custo) as custo_total,
            SUM(CASE WHEN tipo = 'preventiva' THEN 1 ELSE 0 END) as preventivas,
            SUM(CASE WHEN tipo = 'corretiva' THEN 1 ELSE 0 END) as corretivas
        FROM manutencoes
        WHERE status = 'concluida'
    ")->fetch();

    $alertas_stats = $conn->query("
        SELECT 
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN severidade = 'critica' AND status = 'pendente' THEN 1 ELSE 0 END) as criticos,
            COUNT(*) as total,
            SUM(CASE WHEN status = 'resolvido' THEN 1 ELSE 0 END) as resolvidos
        FROM alertas
    ")->fetch();

    $financeiro_stats = $conn->query("
        SELECT 
            SUM(valor) as valor_total
        FROM ativos
        WHERE status != 'descartado'
    ")->fetch();

    $custo_mensal = $conn->query("
        SELECT AVG(custo_mensal) as custo_medio_mensal
        FROM (
            SELECT 
                YEAR(data_manutencao) as ano,
                MONTH(data_manutencao) as mes,
                SUM(custo) as custo_mensal
            FROM manutencoes 
            WHERE data_manutencao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(data_manutencao), MONTH(data_manutencao)
        ) as monthly_costs
    ")->fetch();

} catch (PDOException $e) {
    error_log("Erro ao buscar dados para relatórios: " . $e->getMessage());
}

$percentual_resolvidos = 0;
if ($alertas_stats['total'] > 0) {
    $percentual_resolvidos = round(($alertas_stats['resolvidos'] / $alertas_stats['total']) * 100);
}
?>

<div class="container">
    <div class="page-header animate-fadeIn">
        <div class="header-left">
            <h1>Relatórios e Analytics</h1>
            <p>Analise dados e métricas do sistema</p>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-assets"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $ativos_stats['total'] ?? 0; ?></div>
                <div class="kpi-label">Total de Ativos</div>
                <div class="kpi-description"><?php echo $ativos_stats['operacionais'] ?? 0; ?> operacionais, <?php echo $ativos_stats['manutencao'] ?? 0; ?> em manutenção</div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-maintenance"></i>
                    </div>
                </div>
                <div class="kpi-value">R$ <?php echo number_format($manutencoes_stats['custo_total'] ?? 0, 2, ',', '.'); ?></div>
                <div class="kpi-label">Custo em Manutenções</div>
                <div class="kpi-description"><?php echo $manutencoes_stats['preventivas'] ?? 0; ?> preventivas, <?php echo $manutencoes_stats['corretivas'] ?? 0; ?> corretivas</div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-alerts"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $alertas_stats['pendentes'] ?? 0; ?></div>
                <div class="kpi-label">Alertas Pendentes</div>
                <div class="kpi-description"><?php echo $alertas_stats['criticos'] ?? 0; ?> críticos, <?php echo $percentual_resolvidos; ?>% resolvidos</div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-finance"></i>
                    </div>
                </div>
                <div class="kpi-value">R$ <?php echo number_format($financeiro_stats['valor_total'] ?? 0, 2, ',', '.'); ?></div>
                <div class="kpi-label">Valor Total dos Ativos</div>
                <div class="kpi-description">Custo médio: R$ <?php echo number_format($custo_mensal['custo_medio_mensal'] ?? 0, 2, ',', '.'); ?>/mês</div>
            </div>
        </div>
    </div>
</div>

<?php
$page_specific_js = "js/sweetalert.js";
include 'includes/footer.php';
?>