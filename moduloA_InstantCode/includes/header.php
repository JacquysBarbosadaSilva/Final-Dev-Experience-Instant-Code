<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_atual = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? "Monitoramento Youtan"); ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/sweetalert.css">
    
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/icons.css">
    
    <?php if ($show_sidebar ?? false): ?>
        <link rel="stylesheet" href="css/navbar.css">
    <?php endif; ?>
    
    <?php if (isset($page_specific_css)): ?>
        <link rel="stylesheet" href="<?php echo $page_specific_css; ?>">
    <?php endif; ?>
</head>
<body>

<?php if ($show_sidebar ?? false): ?>
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="home.php" class="sidebar-logo">
                <h1>Youtan</h1>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-label">Principal</div>
                <ul>
                    <li>
                        <a href="home.php" class="<?php echo ($pagina_atual == 'home.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <i class="icons icon-dashboard"></i>
                            </span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="assets.php" class="<?php echo ($pagina_atual == 'assets.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <i class="icons icon-assets"></i>
                            </span>
                            Ativos
                        </a>
                    </li>
                    <li>
                        <a href="maintenance.php" class="<?php echo ($pagina_atual == 'maintenance.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <i class="icons icon-maintenance"></i>
                            </span>
                            Manutenção
                        </a>
                    </li>
                </ul>
            </div>
            
            <?php if (isset($_SESSION['user_level']) && $_SESSION['user_level'] == 'admin'): ?>
            <div class="nav-section">
                <div class="nav-label">Administração</div>
                <ul>
                    <li>
                        <a href="users.php" class="<?php echo ($pagina_atual == 'users.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <i class="icons icon-users"></i>
                            </span>
                            Usuários
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="<?php echo ($pagina_atual == 'reports.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">
                                <i class="icons icon-reports"></i>
                            </span>
                            Relatórios
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></div>
                    <div class="user-role">
                        <?php 
                        $nivel = $_SESSION['user_level'] ?? 'colaborador';
                        echo $nivel == 'admin' ? 'Administrador' : 
                             ($nivel == 'colaborador' ? 'Colaborador' : 'Usuário');
                        ?>
                    </div>
                </div>
                <a href="#" class="logout-btn" title="Sair" onclick="confirmLogout(event)">
                    <i class="icons icon-logout"></i>
                </a>
            </div>
        </div>
    </aside>
    
    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                <div class="breadcrumb">
                    <span>Monitoramento</span>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($page_title); ?></span>
                </div>
            </div>
        </header>
        <div class="content-wrapper">
<?php endif; ?>

<script>
function confirmLogout(event) {
    event.preventDefault();
    
    showConfirmAlert('Confirmar Saída', 'Tem certeza que deseja sair do sistema?', 'Sair', 'Cancelar')
    .then((result) => {
        if (result.isConfirmed) {
            showSuccessAlert('Saindo...', 'Redirecionando para o login.', 1500);
            
            const formData = new FormData();
            formData.append('confirm_logout', 'true');
            formData.append('ajax', 'true');
            
            fetch('logout.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            });
        }
    });
}
</script>