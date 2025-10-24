<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

$total_ativos = 0;
$total_manutencoes = 0;
$total_alertas = 0;
$total_manutencoes_agendadas = 0;
$erro_dashboard = '';

try {
    // Contar ativos operacionais (excluindo descartados)
    $stmt_ativos = $conn->query("SELECT COUNT(id) FROM ativos WHERE status != 'descartado'");
    $total_ativos = $stmt_ativos->fetchColumn();

    // Contar manutenções concluídas
    $stmt_manut = $conn->query("SELECT COUNT(id) FROM manutencoes WHERE status = 'concluida'");
    $total_manutencoes = $stmt_manut->fetchColumn();

    // Contar alertas pendentes
    $stmt_alertas = $conn->query("SELECT COUNT(id) FROM alertas WHERE status = 'pendente'");
    $total_alertas = $stmt_alertas->fetchColumn();

    // Contar manutenções agendadas
    $stmt_manut_agendadas = $conn->query("SELECT COUNT(id) FROM manutencoes WHERE status = 'agendada'");
    $total_manutencoes_agendadas = $stmt_manut_agendadas->fetchColumn();

    // Buscar ativos recentes com joins para categoria e responsável
    $ativos_recentes = $conn->query("
        SELECT a.nome, c.nome as categoria, a.localizacao, a.status, 
            u.nome as nome_responsavel
        FROM ativos a
        LEFT JOIN categorias_ativos c ON a.id_categoria = c.id
        LEFT JOIN usuarios u ON a.id_usuario_responsavel = u.id
        WHERE a.status != 'descartado'
        ORDER BY a.data_criacao DESC
        LIMIT 8
    ")->fetchAll();

    // Buscar alertas recentes com joins para ativo e manutenção
    $alertas_recentes = $conn->query("
        SELECT a.tipo, a.titulo, a.descricao, a.severidade, a.data_criacao,
            atv.nome as nome_ativo,
            m.id as id_manutencao
        FROM alertas a
        LEFT JOIN ativos atv ON a.id_ativo = atv.id
        LEFT JOIN manutencoes m ON a.id_manutencao = m.id
        WHERE a.status = 'pendente'
        ORDER BY 
            CASE a.severidade 
                WHEN 'critica' THEN 1
                WHEN 'alta' THEN 2
                WHEN 'media' THEN 3
                ELSE 4
            END,
            a.data_criacao DESC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    $erro_dashboard = "Não foi possível carregar os indicadores: " . $e->getMessage();
}

$page_title = "Dashboard";
$page_specific_css = "css/dashboard.css";
$show_sidebar = true;
include 'includes/header.php';
?>

<div class="container">
    <?php if ($erro_dashboard): ?>
        <script>showErrorAlert('Erro no Dashboard', '<?php echo $erro_dashboard; ?>');</script>
    <?php endif; ?>

    <div class="kpi-grid">
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-assets"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $total_ativos; ?></div>
                <div class="kpi-label">Ativos Operacionais</div>
                <div class="kpi-description">Total de ativos em operação</div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-maintenance"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $total_manutencoes; ?></div>
                <div class="kpi-label">Manutenções Concluídas</div>
                <div class="kpi-description">Manutenções realizadas</div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-alerts"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $total_alertas; ?></div>
                <div class="kpi-label">Alertas Pendentes</div>
                <div class="kpi-description">Requerem atenção</div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-scheduled"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $total_manutencoes_agendadas; ?></div>
                <div class="kpi-label">Manutenções Agendadas</div>
                <div class="kpi-description">Próximas manutenções</div>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="card animate-fadeIn">
            <div class="card-header">
                <h3>Localização de Ativos</h3>
                <a href="assets.php" class="btn btn-outline">Ver Todos</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ativo</th>
                                <th>Categoria</th>
                                <th>Localização</th>
                                <th>Responsável</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ativos_recentes)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <div class="empty-icon">
                                            <i class="icons icon-assets"></i>
                                        </div>
                                        <h4>Nenhum ativo cadastrado</h4>
                                        <p>Comece cadastrando seu primeiro ativo</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ativos_recentes as $ativo): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ativo['nome']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($ativo['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($ativo['localizacao']); ?></td>
                                        <td><?php echo htmlspecialchars($ativo['nome_responsavel'] ?? 'Não atribuído'); ?></td>
                                        <td>
                                            <span class="status status-<?php echo $ativo['status']; ?>">
                                                <?php echo ucfirst($ativo['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card animate-fadeIn">
            <div class="card-header">
                <h3>Alertas Recentes</h3>
                <span class="badge badge-error"><?php echo $total_alertas; ?> pendentes</span>
            </div>
            <div class="card-body">
                <?php if (empty($alertas_recentes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="icons icon-alerts"></i>
                        </div>
                        <h4>Nenhum alerta pendente</h4>
                        <p>Todos os sistemas operando normalmente</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($alertas_recentes as $alerta): ?>
                        <?php 
                        $severidade_class = 'alert-' . ($alerta['severidade'] == 'critica' ? 'error' : 
                                        ($alerta['severidade'] == 'alta' ? 'warning' : 'info'));
                        ?>
                        <div class="alert <?php echo $severidade_class; ?>">
                            <span class="alert-icon">
                                <i class="icons icon-warning"></i>
                            </span>
                            <div class="alert-content">
                                <strong>[<?php echo strtoupper($alerta['tipo']); ?>] <?php echo htmlspecialchars($alerta['titulo']); ?></strong>
                                <p><?php echo htmlspecialchars($alerta['descricao']); ?></p>
                                <small>
                                    <?php echo htmlspecialchars($alerta['nome_ativo'] ?? 'Sistema'); ?> • 
                                    <?php echo date('d/m/Y H:i', strtotime($alerta['data_criacao'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>