<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

$page_title = "Gestão de Manutenções";
$page_specific_css = "css/maintenance.css";
$show_sidebar = true;
include 'includes/header.php';

$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("=== FORMULÁRIO POST RECEBIDO ===");
    error_log("POST data: " . print_r($_POST, true));
    
    if (isset($_POST['cadastrar_manutencao'])) {
        $id_ativo = $_POST['id_ativo'] ?? '';
        $tipo = $_POST['tipo'] ?? '';
        $data_manutencao = $_POST['data_manutencao'] ?? '';
        $data_agendamento = $_POST['data_agendamento'] ?? '';
        $custo = $_POST['custo'] ?? '';
        $tecnico_responsavel = $_POST['tecnico_responsavel'] ?? '';
        $descricao = $_POST['descricao'] ?? '';

        error_log("Tentando cadastrar manutenção:");
        error_log("ID Ativo: $id_ativo");
        error_log("Tipo: $tipo");
        error_log("Data Manutenção: $data_manutencao");
        error_log("Descrição: $descricao");

        if (empty($id_ativo) || empty($tipo) || empty($data_manutencao) || empty($descricao)) {
            $error_msg = "Por favor, preencha todos os campos obrigatórios.";
            echo "<script>showErrorAlert('Erro de Validação', '$error_msg');</script>";
        } else {
            try {
                $stmt = $conn->prepare("SELECT id, nome FROM ativos WHERE id = ?");
                $stmt->execute([$id_ativo]);
                $ativo = $stmt->fetch();
                
                if (!$ativo) {
                    throw new Exception("Ativo não encontrado!");
                }

                $custo_value = !empty($custo) ? floatval($custo) : null;
                $data_agendamento_value = !empty($data_agendamento) ? $data_agendamento : null;

                $sql = "INSERT INTO manutencoes 
                        (id_ativo, tipo, data_manutencao, data_agendamento, custo, tecnico_responsavel, descricao, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'agendada')";
                
                $stmt = $conn->prepare($sql);
                $success = $stmt->execute([
                    $id_ativo,
                    $tipo,
                    $data_manutencao,
                    $data_agendamento_value,
                    $custo_value,
                    $tecnico_responsavel,
                    $descricao
                ]);

                if ($success) {
                    $update_stmt = $conn->prepare("UPDATE ativos SET status = 'manutencao' WHERE id = ?");
                    $update_stmt->execute([$id_ativo]);
                    
                    echo "<script>
                        showSuccessAlert('Sucesso!', 'Manutenção cadastrada com sucesso.');
                        setTimeout(() => { window.location.href = 'maintenance.php'; }, 1500);
                    </script>";
                } else {
                    throw new Exception("Falha ao inserir no banco de dados.");
                }

            } catch (PDOException $e) {
                $error_msg = "Erro no banco de dados: " . $e->getMessage();
                error_log("PDO Error: " . $e->getMessage());
                echo "<script>showErrorAlert('Erro no Banco de Dados', '$error_msg');</script>";
            } catch (Exception $e) {
                $error_msg = $e->getMessage();
                echo "<script>showErrorAlert('Erro', '$error_msg');</script>";
            }
        }
    }

    if (isset($_POST['atualizar_status'])) {
        $manutencao_id = $_POST['manutencao_id'] ?? '';
        $novo_status = $_POST['novo_status'] ?? '';
        
        if (!empty($manutencao_id) && !empty($novo_status)) {
            try {
                $sql = "UPDATE manutencoes SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$novo_status, $manutencao_id]);
                
                if ($novo_status == 'concluida' || $novo_status == 'cancelada') {
                    $update_ativo = $conn->prepare("
                        UPDATE ativos SET status = 'operacional' 
                        WHERE id = (SELECT id_ativo FROM manutencoes WHERE id = ?)
                    ");
                    $update_ativo->execute([$manutencao_id]);
                }
                
                echo "<script>
                    showSuccessAlert('Status Atualizado!', 'Status da manutenção atualizado com sucesso.');
                    setTimeout(() => { window.location.reload(); }, 1000);
                </script>";
                
            } catch (PDOException $e) {
                error_log("Erro ao atualizar status: " . $e->getMessage());
                echo "<script>showErrorAlert('Erro', 'Falha ao atualizar status.');</script>";
            }
        }
    }
}

try {
    $ativos = $conn->query("
        SELECT a.*, c.nome as categoria_nome 
        FROM ativos a 
        LEFT JOIN categorias_ativos c ON a.id_categoria = c.id 
        WHERE a.status IN ('operacional', 'manutencao') 
        ORDER BY a.nome
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar ativos: " . $e->getMessage());
    $ativos = [];
}

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(a.nome LIKE ? OR m.tecnico_responsavel LIKE ? OR m.descricao LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($filter_type)) {
    $where_conditions[] = "m.tipo = ?";
    $params[] = $filter_type;
}

if (!empty($filter_status)) {
    $where_conditions[] = "m.status = ?";
    $params[] = $filter_status;
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$query_manutencoes = "
    SELECT m.*, 
           a.nome as ativo_nome, 
           a.numero_serie, 
           a.localizacao,
           c.nome as categoria_nome
    FROM manutencoes m 
    LEFT JOIN ativos a ON m.id_ativo = a.id 
    LEFT JOIN categorias_ativos c ON a.id_categoria = c.id
    $where_clause
    ORDER BY m.data_manutencao DESC, m.data_criacao DESC
";

try {
    $stmt = $conn->prepare($query_manutencoes);
    $stmt->execute($params);
    $manutencoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar manutenções: " . $e->getMessage());
    $manutencoes = [];
}

try {
    $stats_result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN tipo = 'preventiva' THEN 1 ELSE 0 END) as preventivas,
            SUM(CASE WHEN tipo = 'corretiva' THEN 1 ELSE 0 END) as corretivas,
            SUM(CASE WHEN tipo = 'preditiva' THEN 1 ELSE 0 END) as preditivas,
            SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as agendadas,
            SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as em_andamento,
            SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as concluidas,
            SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            COALESCE(SUM(custo), 0) as custo_total
        FROM manutencoes
    ");
    
    $stats = $stats_result->fetch(PDO::FETCH_ASSOC);
    if (!$stats) {
        $stats = [
            'total' => 0, 'preventivas' => 0, 'corretivas' => 0, 'preditivas' => 0,
            'agendadas' => 0, 'em_andamento' => 0, 'concluidas' => 0, 'canceladas' => 0,
            'custo_total' => 0
        ];
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    $stats = [
        'total' => 0, 'preventivas' => 0, 'corretivas' => 0, 'preditivas' => 0,
        'agendadas' => 0, 'em_andamento' => 0, 'concluidas' => 0, 'canceladas' => 0,
        'custo_total' => 0
    ];
}
?>

<div class="container">
    <div class="page-header animate-fadeIn">
        <div class="header-left">
            <h1>Gestão de Manutenções</h1>
            <p>Registre e acompanhe todas as manutenções</p>
        </div>
        <button class="btn btn-primary animate-fadeIn" onclick="abrirModalCadastro()">
            <i class="icons icon-add"></i>
            Nova Manutenção
        </button>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-maintenance"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['preventivas']; ?></div>
                <div class="kpi-label">Preventivas</div>
                <div class="kpi-description">Manutenções preventivas</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-warning"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['corretivas']; ?></div>
                <div class="kpi-label">Corretivas</div>
                <div class="kpi-description">Manutenções corretivas</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-scheduled"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['agendadas']; ?></div>
                <div class="kpi-label">Agendadas</div>
                <div class="kpi-description">Manutenções agendadas</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-report"></i>
                    </div>
                </div>
                <div class="kpi-value">R$ <?php echo number_format($stats['custo_total'], 2, ',', '.'); ?></div>
                <div class="kpi-label">Custo Total</div>
                <div class="kpi-description">Total gasto em manutenções</div>
            </div>
        </div>
    </div>

    <div class="card animate-fadeIn">
        <div class="card-header">
            <h3>Histórico de Manutenções</h3>
            <div class="header-actions">
                <form method="GET" action="maintenance.php" class="search-filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Buscar por ativo, técnico..." 
                               class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                        <i class="icons icon-search search-icon"></i>
                    </div>
                    <select class="filter-select" name="filter_type">
                        <option value="">Todos os tipos</option>
                        <option value="preventiva" <?php echo $filter_type == 'preventiva' ? 'selected' : ''; ?>>Preventiva</option>
                        <option value="corretiva" <?php echo $filter_type == 'corretiva' ? 'selected' : ''; ?>>Corretiva</option>
                        <option value="preditiva" <?php echo $filter_type == 'preditiva' ? 'selected' : ''; ?>>Preditiva</option>
                    </select>
                    <select class="filter-select" name="filter_status">
                        <option value="">Todos os status</option>
                        <option value="agendada" <?php echo $filter_status == 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                        <option value="em_andamento" <?php echo $filter_status == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="concluida" <?php echo $filter_status == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                        <option value="cancelada" <?php echo $filter_status == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                    <button type="submit" class="btn btn-outline">Filtrar</button>
                    <?php if ($search || $filter_type || $filter_status): ?>
                        <a href="maintenance.php" class="btn btn-outline">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ativo</th>
                            <th>Tipo</th>
                            <th>Data Manutenção</th>
                            <th>Técnico</th>
                            <th>Custo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($manutencoes)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-icon">
                                        <i class="icons icon-maintenance"></i>
                                    </div>
                                    <h4>Nenhuma manutenção encontrada</h4>
                                    <p><?php echo ($search || $filter_type || $filter_status) ? 'Tente ajustar os filtros de pesquisa' : 'Registre a primeira manutenção do sistema'; ?></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($manutencoes as $manutencao): ?>
                                <tr class="animate-fadeIn">
                                    <td>
                                        <strong><?php echo htmlspecialchars($manutencao['ativo_nome']); ?></strong>
                                        <?php if ($manutencao['numero_serie']): ?>
                                            <br><small>Série: <?php echo htmlspecialchars($manutencao['numero_serie']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($manutencao['categoria_nome']): ?>
                                            <br><small><?php echo htmlspecialchars($manutencao['categoria_nome']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $manutencao['tipo']; ?>">
                                            <?php echo ucfirst($manutencao['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($manutencao['data_manutencao'])); ?>
                                        <?php if ($manutencao['data_agendamento']): ?>
                                            <br><small>Agendada: <?php echo date('d/m/Y', strtotime($manutencao['data_agendamento'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($manutencao['tecnico_responsavel'] ?? 'N/A'); ?></td>
                                    <td><?php echo $manutencao['custo'] ? 'R$ ' . number_format($manutencao['custo'], 2, ',', '.') : 'N/A'; ?></td>
                                    <td>
                                        <form method="POST" action="maintenance.php" class="status-form">
                                            <input type="hidden" name="manutencao_id" value="<?php echo $manutencao['id']; ?>">
                                            <select name="novo_status" class="status-select" onchange="this.form.submit()">
                                                <option value="agendada" <?php echo $manutencao['status'] == 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                                                <option value="em_andamento" <?php echo $manutencao['status'] == 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                                <option value="concluida" <?php echo $manutencao['status'] == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                                                <option value="cancelada" <?php echo $manutencao['status'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                            </select>
                                            <input type="hidden" name="atualizar_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" title="Detalhes" onclick="showDetalhesManutencao(<?php echo htmlspecialchars(json_encode($manutencao)); ?>)">
                                                <i class="icons icon-view"></i>
                                            </button>
                                            <button class="btn-icon btn-danger" title="Excluir" onclick="confirmarExclusao(<?php echo $manutencao['id']; ?>)">
                                                <i class="icons icon-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="modal-cadastro">
    <div class="modal-content animate-fadeIn">
        <div class="modal-header">
            <h3>Registrar Nova Manutenção</h3>
            <button type="button" class="modal-close" onclick="fecharModalCadastro()">&times;</button>
        </div>
        <form method="POST" action="maintenance.php" id="form-manutencao">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_ativo">Ativo *</label>
                        <select id="id_ativo" name="id_ativo" required>
                            <option value="">Selecione um ativo</option>
                            <?php foreach ($ativos as $ativo): ?>
                                <option value="<?php echo $ativo['id']; ?>">
                                    <?php echo htmlspecialchars($ativo['nome']); ?>
                                    <?php if ($ativo['numero_serie']): ?>
                                        (<?php echo htmlspecialchars($ativo['numero_serie']); ?>)
                                    <?php endif; ?>
                                    - <?php echo htmlspecialchars($ativo['categoria_nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($ativos)): ?>
                            <small style="color: #e74c3c;">Nenhum ativo disponível para manutenção.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo de Manutenção *</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecione o tipo</option>
                            <option value="preventiva">Preventiva</option>
                            <option value="corretiva">Corretiva</option>
                            <option value="preditiva">Preditiva</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_manutencao">Data da Manutenção *</label>
                        <input type="date" id="data_manutencao" name="data_manutencao" required>
                    </div>
                    <div class="form-group">
                        <label for="data_agendamento">Data de Agendamento</label>
                        <input type="date" id="data_agendamento" name="data_agendamento">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="custo">Custo (R$)</label>
                        <input type="number" id="custo" name="custo" step="0.01" min="0" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="tecnico_responsavel">Técnico Responsável</label>
                        <input type="text" id="tecnico_responsavel" name="tecnico_responsavel" placeholder="Nome do técnico responsável">
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição do Serviço *</label>
                    <textarea id="descricao" name="descricao" rows="4" placeholder="Descreva os serviços a serem realizados..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="fecharModalCadastro()">Cancelar</button>
                <button type="submit" name="cadastrar_manutencao" class="btn btn-primary" <?php echo empty($ativos) ? 'disabled' : ''; ?>>
                    Registrar Manutenção
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCadastro() {
    document.getElementById('modal-cadastro').style.display = 'flex';
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('data_manutencao').min = today;
    document.getElementById('data_agendamento').min = today;
}

function fecharModalCadastro() {
    document.getElementById('modal-cadastro').style.display = 'none';
    document.getElementById('form-manutencao').reset();
}

function showDetalhesManutencao(manutencao) {
    const descricao = manutencao.descricao || 'Sem descrição detalhada.';
    const custo = manutencao.custo ? 'R$ ' + parseFloat(manutencao.custo).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : 'Não informado';
    const tecnico = manutencao.tecnico_responsavel || 'Não atribuído';
    const dataCriacao = new Date(manutencao.data_criacao).toLocaleString('pt-BR');
    
    const html = `
        <div style="text-align: left;">
            <p><strong>Ativo:</strong> ${manutencao.ativo_nome}</p>
            <p><strong>Tipo:</strong> ${manutencao.tipo}</p>
            <p><strong>Data da Manutenção:</strong> ${new Date(manutencao.data_manutencao).toLocaleDateString('pt-BR')}</p>
            ${manutencao.data_agendamento ? `<p><strong>Data de Agendamento:</strong> ${new Date(manutencao.data_agendamento).toLocaleDateString('pt-BR')}</p>` : ''}
            <p><strong>Técnico:</strong> ${tecnico}</p>
            <p><strong>Custo:</strong> ${custo}</p>
            <p><strong>Status:</strong> ${manutencao.status}</p>
            <p><strong>Descrição:</strong> ${descricao}</p>
            <p><strong>Registrado em:</strong> ${dataCriacao}</p>
        </div>
    `;
    
    showInfoAlert('Detalhes da Manutenção', html);
}

function confirmarExclusao(manutencaoId) {
    showConfirmAlert(
        'Excluir Manutenção?',
        'Tem certeza que deseja excluir esta manutenção? Esta ação não pode ser desfeita.',
        'Sim, excluir',
        'Cancelar'
    ).then((result) => {
        if (result.isConfirmed) {
            showInfoAlert('Funcionalidade em Desenvolvimento', 'A exclusão de manutenções estará disponível em breve.');
        }
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('modal-cadastro');
    if (event.target == modal) {
        fecharModalCadastro();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('Página de manutenções carregada');
});
</script>

<?php
$page_specific_js = "js/sweetalert.js";
include 'includes/footer.php';
?>