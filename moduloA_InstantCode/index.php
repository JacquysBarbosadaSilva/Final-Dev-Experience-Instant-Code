<?php
require_once 'includes/db.php';

$mensagem_erro = '';
$mensagem_sucesso = '';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'accessdenied') {
        $mensagem_erro = "Você precisa fazer login para acessar essa página.";
    }
}
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'registered') {
        $mensagem_sucesso = "Cadastro realizado com sucesso! Faça o login.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $mensagem_erro = "Por favor, preencha o e-mail e a senha.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem_erro = "Formato de e-mail inválido.";
    } else {
        try {
            $sql = "SELECT id, nome, senha, nivel_permissao FROM usuarios WHERE email = ? AND ativo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_name'] = $usuario['nome'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_level'] = $usuario['nivel_permissao'];
                
                header("Location: home.php");
                exit;

            } else {
                $mensagem_erro = "E-mail ou senha inválidos.";
            }

        } catch (PDOException $e) {
            $mensagem_erro = "Ocorreu um erro no servidor. Tente novamente.";
        }
    }
}

$page_title = "Login";
$page_specific_css = "css/login.css";
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
        <h2>Acesse sua conta</h2>
        <p>Entre com suas credenciais para acessar o sistema</p>
    </div>

    <?php if ($mensagem_erro): ?>
        <script>showErrorAlert('Erro no Login', '<?php echo $mensagem_erro; ?>');</script>
    <?php endif; ?>
    
    <?php if ($mensagem_sucesso): ?>
        <script>showSuccessAlert('Cadastro realizado!', '<?php echo $mensagem_sucesso; ?>');</script>
    <?php endif; ?>

    <form id="login-form" action="index.php" method="POST" novalidate>
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required placeholder="seu@email.com" class="animate-fadeIn">
        </div>
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required placeholder="Sua senha" class="animate-fadeIn">
        </div>
        <button type="submit" class="auth-button animate-fadeIn">
            <span class="btn-text">Entrar</span>
            <span class="btn-loader" style="display: none;">
                <i class="icon-loading"></i>
            </span>
        </button>
    </form>

    <div class="auth-footer">
        <p>Não tem uma conta? <a href="cadastro.php" class="auth-link">Cadastre-se</a></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const submitBtn = loginForm.querySelector('.auth-button');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');
    
    loginForm.addEventListener('submit', function() {
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-block';
        submitBtn.disabled = true;
    });
});
</script>

<?php
$page_specific_js = "js/sweetalert.js";
include 'includes/footer.php';
?>