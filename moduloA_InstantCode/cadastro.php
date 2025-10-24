<?php
require_once 'includes/db.php';

$mensagem_erro = '';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $nivel_permissao = 'colaborador'; 

    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem_erro = "Todos os campos são obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Formato de e-mail inválido.";
    } elseif (strlen($senha) < 8) {
        $mensagem_erro = "A senha deve ter no mínimo 8 caracteres.";
    } else {
        try {
            $sql_check = "SELECT id FROM usuarios WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$email]);
            
            if ($stmt_check->fetch()) {
                $mensagem_erro = "Este e-mail já está cadastrado.";
            } else {
                $hash_senha = password_hash($senha, PASSWORD_ARGON2ID);

                $sql_insert = "INSERT INTO usuarios (nome, email, senha, nivel_permissao) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                
                if ($stmt_insert->execute([$nome, $email, $hash_senha, $nivel_permissao])) {
                    echo "<script>
                        showSuccessAlert('Cadastro realizado!', 'Sua conta foi criada com sucesso. Redirecionando para o login...', 3000);
                        setTimeout(() => { window.location.href = 'index.php?success=registered'; }, 3000);
                    </script>";
                    exit;
                } else {
                    $mensagem_erro = "Ocorreu um erro ao criar a conta.";
                }
            }
        } catch (PDOException $e) {
            $mensagem_erro = "Erro no banco de dados. Tente novamente.";
        }
    }
}

$page_title = "Cadastro";
$page_specific_css = "css/cadastro.css";
$show_sidebar = false;
include 'includes/header.php';
?>

<div class="auth-container animate-fadeIn">
    <div class="auth-logo">
        <a href="index.php">
            <img src="imgs/logo_youtan.png" alt="Youtan" class="logo-image">
        </a>
    </div>
    
    <div class="auth-header">
        <h2>Crie sua conta</h2>
        <p>Preencha os dados abaixo para se cadastrar no sistema</p>
    </div>

    <?php if ($mensagem_erro): ?>
        <script>showErrorAlert('Erro no Cadastro', '<?php echo $mensagem_erro; ?>');</script>
    <?php endif; ?>

    <form id="cadastro-form" action="cadastro.php" method="POST" novalidate>
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" required placeholder="Digite seu nome completo" class="animate-fadeIn">
            <div class="error-message" id="nome-error">Por favor, preencha seu nome completo</div>
        </div>
        
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required placeholder="seu@email.com" class="animate-fadeIn">
            <div class="error-message" id="email-error">Por favor, insira um e-mail válido</div>
        </div>
        
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required placeholder="Crie uma senha segura" class="animate-fadeIn">
            <small>Mínimo de 8 caracteres. Use letras, números e símbolos para maior segurança.</small>
            <div class="error-message" id="senha-error">A senha deve ter no mínimo 8 caracteres</div>
        </div>
        
        <button type="submit" class="auth-button animate-fadeIn">
            <span class="btn-text">Criar minha conta</span>
            <span class="btn-loader" style="display: none;">
                <i class="icon-loading"></i>
            </span>
        </button>
    </form>

    <div class="auth-footer">
        <p>Já tem uma conta? <a href="index.php" class="auth-link">Faça login aqui</a></p>
    </div>
</div>

<script src="js/cadastro.js"></script>

<?php
include 'includes/footer.php';
?>