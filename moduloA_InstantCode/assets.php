<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

$page_title = "Gestão de Ativos";
$page_specific_css = "css/assets.css";
$show_sidebar = true;
include 'includes/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['cadastrar_ativo'])) {
        $nome = trim($_POST['nome'] ?? '');
        $id_categoria = trim($_POST['id_categoria'] ?? '');
        $valor = !empty($_POST['valor']) ? floatval($_POST['valor']) : null;
        $data_aquisicao = !empty($_POST['data_aquisicao']) ? $_POST['data_aquisicao'] : null;
        $numero_serie = !empty($_POST['numero_serie']) ? trim($_POST['numero_serie']) : null;
        $fabricante = trim($_POST['fabricante'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $status = trim($_POST['status'] ?? 'operacional');
        $localizacao = trim($_POST['localizacao'] ?? '');
        $id_usuario_responsavel = !empty($_POST['id_usuario_responsavel']) ? intval($_POST['id_usuario_responsavel']) : null;
        $data_garantia = !empty($_POST['data_garantia']) ? $_POST['data_garantia'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');

        try {
            $sql = "INSERT INTO ativos (nome, id_categoria, valor, data_aquisicao, numero_serie, fabricante, modelo, status, localizacao, id_usuario_responsavel, data_garantia, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nome, 
                $id_categoria, 
                $valor, 
                $data_aquisicao, 
                $numero_serie, 
                $fabricante, 
                $modelo, 
                $status, 
                $localizacao, 
                $id_usuario_responsavel, 
                $data_garantia, 
                $observacoes
            ]);
            
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Ativo cadastrado com sucesso!',
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                }).then(() => {
                    window.location.href = 'assets.php';
                });
            </script>";
            exit;
            
        } catch (PDOException $e) {
            $error_message = $e->getMessage();
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao cadastrar ativo: " . addslashes($error_message) . "',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }

    if (isset($_POST['atualizar_ativo'])) {
        $id = trim($_POST['id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $id_categoria = trim($_POST['id_categoria'] ?? '');
        $valor = !empty($_POST['valor']) ? floatval($_POST['valor']) : null;
        $data_aquisicao = !empty($_POST['data_aquisicao']) ? $_POST['data_aquisicao'] : null;
        $numero_serie = !empty($_POST['numero_serie']) ? trim($_POST['numero_serie']) : null;
        $fabricante = trim($_POST['fabricante'] ?? '');
        $modelo = trim($_POST['modelo'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $localizacao = trim($_POST['localizacao'] ?? '');
        $id_usuario_responsavel = !empty($_POST['id_usuario_responsavel']) ? intval($_POST['id_usuario_responsavel']) : null;
        $data_garantia = !empty($_POST['data_garantia']) ? $_POST['data_garantia'] : null;
        $observacoes = trim($_POST['observacoes'] ?? '');

        try {
            $sql = "UPDATE ativos SET nome = ?, id_categoria = ?, valor = ?, data_aquisicao = ?, numero_serie = ?, fabricante = ?, modelo = ?, status = ?, localizacao = ?, id_usuario_responsavel = ?, data_garantia = ?, observacoes = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nome, $id_categoria, $valor, $data_aquisicao, 
                $numero_serie, $fabricante, $modelo, $status, $localizacao, 
                $id_usuario_responsavel, $data_garantia, $observacoes, $id
            ]);
            
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Ativo atualizado com sucesso!',
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                }).then(() => {
                    window.location.href = 'assets.php';
                });
            </script>";
            exit;
            
        } catch (PDOException $e) {
            $error_message = $e->getMessage();
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao atualizar ativo: " . addslashes($error_message) . "',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }

    if (isset($_POST['excluir_ativo'])) {
        $id = trim($_POST['id'] ?? '');

        try {
            $sql = "DELETE FROM ativos WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: 'Ativo excluído com sucesso!',
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                }).then(() => {
                    window.location.href = 'assets.php';
                });
            </script>";
            exit;
            
        } catch (PDOException $e) {
            $error_message = $e->getMessage();
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao excluir ativo: " . addslashes($error_message) . "',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
    }
}

$ativos = $conn->query("
    SELECT a.*, c.nome as categoria_nome, u.nome as responsavel_nome 
    FROM ativos a 
    LEFT JOIN categorias_ativos c ON a.id_categoria = c.id 
    LEFT JOIN usuarios u ON a.id_usuario_responsavel = u.id 
    ORDER BY a.data_criacao DESC
")->fetchAll();

$categorias = $conn->query("SELECT * FROM categorias_ativos ORDER BY nome")->fetchAll();
$usuarios = $conn->query("SELECT * FROM usuarios WHERE ativo = 1 ORDER BY nome")->fetchAll();

$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'operacional' THEN 1 ELSE 0 END) as operacionais,
        SUM(CASE WHEN status = 'manutencao' THEN 1 ELSE 0 END) as manutencao,
        SUM(CASE WHEN status = 'descartado' THEN 1 ELSE 0 END) as descartados,
        COALESCE(SUM(valor), 0) as valor_total
    FROM ativos
")->fetch();
?>

<div class="container">
    <div class="page-header animate-fadeIn">
        <div class="header-left">
            <h1>Gestão de Ativos</h1>
            <p>Gerencie todos os ativos da empresa</p>
        </div>
        <button class="btn btn-primary animate-fadeIn" onclick="document.getElementById('modal-cadastro').style.display='flex'">
            <i class="icons icon-add"></i>
            Novo Ativo
        </button>
    </div>

    <div class="kpi-grid">
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-assets"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="kpi-label">Total de Ativos</div>
                <div class="kpi-description">Ativos cadastrados</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-status"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['operacionais'] ?? 0; ?></div>
                <div class="kpi-label">Operacionais</div>
                <div class="kpi-description">Ativos em operação</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-maintenance"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['manutencao'] ?? 0; ?></div>
                <div class="kpi-label">Em Manutenção</div>
                <div class="kpi-description">Ativos em manutenção</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-report"></i>
                    </div>
                </div>
                <div class="kpi-value">R$ <?php echo number_format($stats['valor_total'] ?? 0, 2, ',', '.'); ?></div>
                <div class="kpi-label">Valor Total</div>
                <div class="kpi-description">Valor dos ativos</div>
            </div>
        </div>
    </div>

    <div class="card animate-fadeIn">
        <div class="card-header">
            <h3>Lista de Ativos</h3>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" placeholder="Buscar ativos..." class="search-input" id="searchInput">
                    <i class="icons icon-search search-icon"></i>
                </div>
                <select class="filter-select" id="filterSelect">
                    <option value="">Todos os status</option>
                    <option value="operacional">Operacional</option>
                    <option value="manutencao">Manutenção</option>
                    <option value="descartado">Descartado</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ativo</th>
                            <th>Categoria</th>
                            <th>Nº Série</th>
                            <th>Localização</th>
                            <th>Responsável</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ativos)): ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <div class="empty-icon">
                                        <i class="icons icon-assets"></i>
                                    </div>
                                    <h4>Nenhum ativo cadastrado</h4>
                                    <p>Cadastre o primeiro ativo do sistema</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ativos as $ativo): ?>
                                <tr class="animate-fadeIn" data-status="<?php echo $ativo['status']; ?>">
                                    <td>
                                        <div class="asset-info">
                                            <div class="asset-icon">
                                                <i class="icons icon-computer"></i>
                                            </div>
                                            <div class="asset-details">
                                                <strong><?php echo htmlspecialchars($ativo['nome']); ?></strong>
                                                <small><?php echo htmlspecialchars($ativo['fabricante'] . ' ' . $ativo['modelo']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($ativo['categoria_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($ativo['numero_serie'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($ativo['localizacao']); ?></td>
                                    <td><?php echo htmlspecialchars($ativo['responsavel_nome'] ?? 'N/A'); ?></td>
                                    <td><?php echo $ativo['valor'] ? 'R$ ' . number_format($ativo['valor'], 2, ',', '.') : 'N/A'; ?></td>
                                    <td>
                                        <span class="status status-<?php echo $ativo['status']; ?>">
                                            <?php echo ucfirst($ativo['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" title="Editar" onclick="abrirModalEditar(<?php echo $ativo['id']; ?>, '<?php echo htmlspecialchars($ativo['nome']); ?>', <?php echo $ativo['id_categoria']; ?>, '<?php echo $ativo['valor'] ?? ''; ?>', '<?php echo $ativo['data_aquisicao'] ?? ''; ?>', '<?php echo htmlspecialchars($ativo['numero_serie'] ?? ''); ?>', '<?php echo htmlspecialchars($ativo['fabricante']); ?>', '<?php echo htmlspecialchars($ativo['modelo']); ?>', '<?php echo $ativo['status']; ?>', '<?php echo htmlspecialchars($ativo['localizacao']); ?>', <?php echo $ativo['id_usuario_responsavel'] ?? 'null'; ?>, '<?php echo $ativo['data_garantia'] ?? ''; ?>', `<?php echo htmlspecialchars($ativo['observacoes'] ?? ''); ?>`)">
                                                <i class="icons icon-edit"></i>
                                            </button>
                                            <button class="btn-icon btn-danger" title="Excluir" onclick="confirmarExclusao(<?php echo $ativo['id']; ?>, '<?php echo htmlspecialchars($ativo['nome']); ?>')">
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
            <h3>Cadastrar Novo Ativo</h3>
            <button class="modal-close" onclick="document.getElementById('modal-cadastro').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="assets.php" id="form-ativo">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome do Ativo *</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="id_categoria">Categoria *</label>
                        <select id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione a categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fabricante">Fabricante</label>
                        <input type="text" id="fabricante" name="fabricante">
                    </div>
                    <div class="form-group">
                        <label for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero_serie">Número de Série</label>
                        <input type="text" id="numero_serie" name="numero_serie">
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor (R$)</label>
                        <input type="number" id="valor" name="valor" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_aquisicao">Data de Aquisição</label>
                        <input type="date" id="data_aquisicao" name="data_aquisicao">
                    </div>
                    <div class="form-group">
                        <label for="data_garantia">Data da Garantia</label>
                        <input type="date" id="data_garantia" name="data_garantia">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="localizacao">Localização *</label>
                        <input type="text" id="localizacao" name="localizacao" required>
                    </div>
                    <div class="form-group">
                        <label for="id_usuario_responsavel">Responsável</label>
                        <select id="id_usuario_responsavel" name="id_usuario_responsavel">
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="operacional">Operacional</option>
                            <option value="manutencao">Manutenção</option>
                            <option value="descartado">Descartado</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="4" placeholder="Observações sobre o ativo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-cadastro').style.display='none'">Cancelar</button>
                <button type="submit" name="cadastrar_ativo" class="btn btn-primary">Cadastrar Ativo</button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-editar">
    <div class="modal-content animate-fadeIn">
        <div class="modal-header">
            <h3>Editar Ativo</h3>
            <button class="modal-close" onclick="document.getElementById('modal-editar').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="assets.php" id="form-editar">
            <input type="hidden" id="editar_id" name="id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_nome">Nome do Ativo *</label>
                        <input type="text" id="editar_nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="editar_id_categoria">Categoria *</label>
                        <select id="editar_id_categoria" name="id_categoria" required>
                            <option value="">Selecione a categoria</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_fabricante">Fabricante</label>
                        <input type="text" id="editar_fabricante" name="fabricante">
                    </div>
                    <div class="form-group">
                        <label for="editar_modelo">Modelo</label>
                        <input type="text" id="editar_modelo" name="modelo">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_numero_serie">Número de Série</label>
                        <input type="text" id="editar_numero_serie" name="numero_serie">
                    </div>
                    <div class="form-group">
                        <label for="editar_valor">Valor (R$)</label>
                        <input type="number" id="editar_valor" name="valor" step="0.01" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_data_aquisicao">Data de Aquisição</label>
                        <input type="date" id="editar_data_aquisicao" name="data_aquisicao">
                    </div>
                    <div class="form-group">
                        <label for="editar_data_garantia">Data da Garantia</label>
                        <input type="date" id="editar_data_garantia" name="data_garantia">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_localizacao">Localização *</label>
                        <input type="text" id="editar_localizacao" name="localizacao" required>
                    </div>
                    <div class="form-group">
                        <label for="editar_id_usuario_responsavel">Responsável</label>
                        <select id="editar_id_usuario_responsavel" name="id_usuario_responsavel">
                            <option value="">Selecione o responsável</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_status">Status *</label>
                        <select id="editar_status" name="status" required>
                            <option value="operacional">Operacional</option>
                            <option value="manutencao">Manutenção</option>
                            <option value="descartado">Descartado</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="editar_observacoes">Observações</label>
                    <textarea id="editar_observacoes" name="observacoes" rows="4" placeholder="Observações sobre o ativo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-editar').style.display='none'">Cancelar</button>
                <button type="submit" name="atualizar_ativo" class="btn btn-primary">Atualizar Ativo</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="assets.php" id="form-excluir" style="display: none;">
    <input type="hidden" id="excluir_id" name="id">
    <input type="hidden" name="excluir_ativo" value="1">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modals = ['modal-cadastro', 'modal-editar'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    });

    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');

    if (searchInput && filterSelect) {
        searchInput.addEventListener('input', filtrarAtivos);
        filterSelect.addEventListener('change', filtrarAtivos);
    }

    function filtrarAtivos() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = filterSelect.value;
        const rows = document.querySelectorAll('.data-table tbody tr');

        rows.forEach(row => {
            const nome = row.querySelector('.asset-details strong').textContent.toLowerCase();
            const categoria = row.cells[1].textContent.toLowerCase();
            const numeroSerie = row.cells[2].textContent.toLowerCase();
            const status = row.getAttribute('data-status');
            
            const matchSearch = nome.includes(searchTerm) || categoria.includes(searchTerm) || numeroSerie.includes(searchTerm);
            const matchFilter = !filterValue || status === filterValue;
            
            row.style.display = matchSearch && matchFilter ? '' : 'none';
        });
    }
});

function abrirModalEditar(id, nome, id_categoria, valor, data_aquisicao, numero_serie, fabricante, modelo, status, localizacao, id_usuario_responsavel, data_garantia, observacoes) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_id_categoria').value = id_categoria;
    document.getElementById('editar_valor').value = valor || '';
    document.getElementById('editar_data_aquisicao').value = data_aquisicao || '';
    document.getElementById('editar_numero_serie').value = numero_serie || '';
    document.getElementById('editar_fabricante').value = fabricante;
    document.getElementById('editar_modelo').value = modelo;
    document.getElementById('editar_status').value = status;
    document.getElementById('editar_localizacao').value = localizacao;
    document.getElementById('editar_id_usuario_responsavel').value = id_usuario_responsavel || '';
    document.getElementById('editar_data_garantia').value = data_garantia || '';
    document.getElementById('editar_observacoes').value = observacoes || '';
    document.getElementById('modal-editar').style.display = 'flex';
}

function confirmarExclusao(id, nome) {
    Swal.fire({
        title: 'Excluir Ativo?',
        text: `Tem certeza que deseja excluir o ativo "${nome}"? Esta ação não pode ser desfeita.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63031',
        cancelButtonColor: '#636e72',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('excluir_id').value = id;
            document.getElementById('form-excluir').submit();
        }
    });
}
</script>

<?php
include 'includes/footer.php';