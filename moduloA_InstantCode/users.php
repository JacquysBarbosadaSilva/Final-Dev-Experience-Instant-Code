<?php
require_once 'includes/auth_check.php';
require_once 'includes/db.php';

if ($_SESSION['user_level'] != 'admin') {
    header("Location: home.php");
    exit;
}

$page_title = "Gestão de Usuários";
$page_specific_css = "css/users.css";
$show_sidebar = true;
include 'includes/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['cadastrar_usuario'])) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $nivel_permissao = trim($_POST['nivel_permissao'] ?? '');
        $setor = trim($_POST['setor'] ?? '');

        try {
            $hash_senha = password_hash($senha, PASSWORD_ARGON2ID);
            
            $sql = "INSERT INTO usuarios (nome, email, senha, nivel_permissao, setor) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nome, $email, $hash_senha, $nivel_permissao, $setor]);
            
            echo "<script>showSuccessAlert('Usuário cadastrado!', 'O usuário foi cadastrado com sucesso.');</script>";
        } catch (PDOException $e) {
            echo "<script>showErrorAlert('Erro ao cadastrar', 'Ocorreu um erro ao cadastrar o usuário: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    if (isset($_POST['atualizar_usuario'])) {
        $id = trim($_POST['id'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $nivel_permissao = trim($_POST['nivel_permissao'] ?? '');
        $setor = trim($_POST['setor'] ?? '');

        try {
            $sql = "UPDATE usuarios SET nome = ?, email = ?, nivel_permissao = ?, setor = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$nome, $email, $nivel_permissao, $setor, $id]);
            
            echo "<script>showSuccessAlert('Usuário atualizado!', 'Os dados do usuário foram atualizados com sucesso.');</script>";
        } catch (PDOException $e) {
            echo "<script>showErrorAlert('Erro ao atualizar', 'Ocorreu um erro ao atualizar o usuário: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    if (isset($_POST['resetar_senha'])) {
        $id = trim($_POST['id'] ?? '');
        $nova_senha = trim($_POST['nova_senha'] ?? '');

        try {
            $hash_senha = password_hash($nova_senha, PASSWORD_ARGON2ID);
            
            $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$hash_senha, $id]);
            
            echo "<script>showSuccessAlert('Senha resetada!', 'A senha do usuário foi resetada com sucesso.');</script>";
        } catch (PDOException $e) {
            echo "<script>showErrorAlert('Erro ao resetar senha', 'Ocorreu um erro ao resetar a senha: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

$usuarios = $conn->query("SELECT * FROM usuarios ORDER BY data_criacao DESC")->fetchAll();

$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN nivel_permissao = 'admin' THEN 1 ELSE 0 END) as administradores,
        SUM(CASE WHEN nivel_permissao = 'colaborador' THEN 1 ELSE 0 END) as colaboradores,
        SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as ativos
    FROM usuarios
")->fetch();
?>

<div class="container">
    <div class="page-header animate-fadeIn">
        <div class="header-left">
            <h1>Gestão de Usuários</h1>
            <p>Administre os usuários e permissões do sistema</p>
        </div>
        <button class="btn btn-primary animate-fadeIn" onclick="document.getElementById('modal-cadastro').style.display='flex'">
            <i class="icons icon-add"></i>
            Novo Usuário
        </button>
    </div>

    <div class="kpi-grid">
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-users"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="kpi-label">Total de Usuários</div>
                <div class="kpi-description">Usuários no sistema</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-admin"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['administradores'] ?? 0; ?></div>
                <div class="kpi-label">Administradores</div>
                <div class="kpi-description">Usuários com acesso total</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-people"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['colaboradores'] ?? 0; ?></div>
                <div class="kpi-label">Colaboradores</div>
                <div class="kpi-description">Usuários com acesso limitado</div>
            </div>
        </div>
        <div class="card animate-fadeIn">
            <div class="card-body">
                <div class="kpi-header">
                    <div class="kpi-icon">
                        <i class="icons icon-status"></i>
                    </div>
                </div>
                <div class="kpi-value"><?php echo $stats['ativos'] ?? 0; ?></div>
                <div class="kpi-label">Usuários Ativos</div>
                <div class="kpi-description">Usuários ativos no sistema</div>
            </div>
        </div>
    </div>

    <div class="card animate-fadeIn">
        <div class="card-header">
            <h3>Lista de Usuários</h3>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" placeholder="Buscar usuários..." class="search-input" id="searchInput">
                    <i class="icons icon-search search-icon"></i>
                </div>
                <select class="filter-select" id="filterSelect">
                    <option value="">Todos os níveis</option>
                    <option value="admin">Administrador</option>
                    <option value="colaborador">Colaborador</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>E-mail</th>
                            <th>Setor</th>
                            <th>Permissão</th>
                            <th>Data Cadastro</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-icon">
                                        <i class="icons icon-users"></i>
                                    </div>
                                    <h4>Nenhum usuário cadastrado</h4>
                                    <p>Cadastre o primeiro usuário do sistema</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr class="animate-fadeIn" data-nivel="<?php echo $usuario['nivel_permissao']; ?>">
                                    <td>
                                        <div class="table-user-info">
                                            <div class="table-user-avatar">
                                                <?php echo strtoupper(substr($usuario['nome'], 0, 1)); ?>
                                            </div>
                                            <div class="table-user-details">
                                                <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['setor'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $usuario['nivel_permissao']; ?>">
                                            <?php echo ucfirst($usuario['nivel_permissao']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $usuario['ativo'] ? 'ativo' : 'inativo'; ?>">
                                            <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" title="Editar" onclick="abrirModalEditar(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>', '<?php echo htmlspecialchars($usuario['email']); ?>', '<?php echo $usuario['nivel_permissao']; ?>', '<?php echo htmlspecialchars($usuario['setor'] ?? ''); ?>')">
                                                <i class="icons icon-edit"></i>
                                            </button>
                                            <button class="btn-icon" title="Resetar Senha" onclick="abrirModalResetarSenha(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>')">
                                                <i class="icons icon-password"></i>
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
            <h3>Cadastrar Novo Usuário</h3>
            <button class="modal-close" onclick="document.getElementById('modal-cadastro').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="users.php" id="form-usuario">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="senha">Senha *</label>
                        <input type="password" id="senha" name="senha" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="nivel_permissao">Nível de Permissão *</label>
                        <select id="nivel_permissao" name="nivel_permissao" required>
                            <option value="">Selecione o nível</option>
                            <option value="admin">Administrador</option>
                            <option value="colaborador">Colaborador</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="setor">Setor/Departamento</label>
                    <input type="text" id="setor" name="setor">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-cadastro').style.display='none'">Cancelar</button>
                <button type="submit" name="cadastrar_usuario" class="btn btn-primary">
                    <span class="btn-text">Cadastrar Usuário</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="icons icon-loading"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-editar">
    <div class="modal-content animate-fadeIn">
        <div class="modal-header">
            <h3>Editar Usuário</h3>
            <button class="modal-close" onclick="document.getElementById('modal-editar').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="users.php" id="form-editar">
            <input type="hidden" id="editar_id" name="id">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_nome">Nome Completo *</label>
                        <input type="text" id="editar_nome" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label for="editar_email">E-mail *</label>
                        <input type="email" id="editar_email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="editar_nivel_permissao">Nível de Permissão *</label>
                        <select id="editar_nivel_permissao" name="nivel_permissao" required>
                            <option value="">Selecione o nível</option>
                            <option value="admin">Administrador</option>
                            <option value="colaborador">Colaborador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editar_setor">Setor/Departamento</label>
                        <input type="text" id="editar_setor" name="setor">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-editar').style.display='none'">Cancelar</button>
                <button type="submit" name="atualizar_usuario" class="btn btn-primary">
                    <span class="btn-text">Atualizar Usuário</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="icons icon-loading"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal" id="modal-resetar-senha">
    <div class="modal-content animate-fadeIn">
        <div class="modal-header">
            <h3>Resetar Senha</h3>
            <button class="modal-close" onclick="document.getElementById('modal-resetar-senha').style.display='none'">&times;</button>
        </div>
        <form method="POST" action="users.php" id="form-resetar-senha">
            <input type="hidden" id="resetar_id" name="id">
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="icons icon-info" style="margin-right: 8px;"></i>
                    Você está resetando a senha de <strong id="nome-usuario-resetar"></strong>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha *</label>
                        <input type="password" id="nova_senha" name="nova_senha" required minlength="8" placeholder="Digite a nova senha">
                    </div>
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha *</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="8" placeholder="Confirme a nova senha">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="password-requirements">
                        <small>A senha deve conter pelo menos 8 caracteres</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-resetar-senha').style.display='none'">Cancelar</button>
                <button type="submit" name="resetar_senha" class="btn btn-primary" id="btn-resetar-senha" disabled>
                    <span class="btn-text">Resetar Senha</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="icons icon-loading"></i>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modals = ['modal-cadastro', 'modal-editar', 'modal-resetar-senha'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    });

    const forms = ['form-usuario', 'form-editar', 'form-resetar-senha'];
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoader = submitBtn.querySelector('.btn-loader');
            
            form.addEventListener('submit', function() {
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-block';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    const modalId = formId === 'form-usuario' ? 'modal-cadastro' : 
                                  formId === 'form-editar' ? 'modal-editar' : 'modal-resetar-senha';
                    document.getElementById(modalId).style.display = 'none';
                    btnText.style.display = 'inline-block';
                    btnLoader.style.display = 'none';
                    submitBtn.disabled = false;
                    form.reset();
                }, 1000);
            });
        }
    });

    const novaSenha = document.getElementById('nova_senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    const btnResetar = document.getElementById('btn-resetar-senha');

    function validarSenhas() {
        if (novaSenha.value && confirmarSenha.value && novaSenha.value === confirmarSenha.value && novaSenha.value.length >= 8) {
            btnResetar.disabled = false;
        } else {
            btnResetar.disabled = true;
        }
    }

    if (novaSenha && confirmarSenha) {
        novaSenha.addEventListener('input', validarSenhas);
        confirmarSenha.addEventListener('input', validarSenhas);
    }

    const searchInput = document.getElementById('searchInput');
    const filterSelect = document.getElementById('filterSelect');

    if (searchInput && filterSelect) {
        searchInput.addEventListener('input', filtrarUsuarios);
        filterSelect.addEventListener('change', filtrarUsuarios);
    }

    function filtrarUsuarios() {
        const searchTerm = searchInput.value.toLowerCase();
        const filterValue = filterSelect.value;
        const rows = document.querySelectorAll('.data-table tbody tr');

        rows.forEach(row => {
            const nome = row.querySelector('.table-user-details strong').textContent.toLowerCase();
            const email = row.cells[1].textContent.toLowerCase();
            const nivel = row.getAttribute('data-nivel');
            
            const matchSearch = nome.includes(searchTerm) || email.includes(searchTerm);
            const matchFilter = !filterValue || nivel === filterValue;
            
            row.style.display = matchSearch && matchFilter ? '' : 'none';
        });
    }
});

function abrirModalEditar(id, nome, email, nivel, setor) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_nome').value = nome;
    document.getElementById('editar_email').value = email;
    document.getElementById('editar_nivel_permissao').value = nivel;
    document.getElementById('editar_setor').value = setor || '';
    document.getElementById('modal-editar').style.display = 'flex';
}

function abrirModalResetarSenha(id, nome) {
    document.getElementById('resetar_id').value = id;
    document.getElementById('nome-usuario-resetar').textContent = nome;
    document.getElementById('modal-resetar-senha').style.display = 'flex';
    
    document.getElementById('nova_senha').value = '';
    document.getElementById('confirmar_senha').value = '';
    document.getElementById('btn-resetar-senha').disabled = true;
}
</script>

<?php
$page_specific_js = "js/sweetalert.js";
include 'includes/footer.php';
?>