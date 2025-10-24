<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=accessdenied");
    exit;
}

$session_duration = 3600;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_duration)) {
    session_unset();
    session_destroy();
    header("Location: index.php?error=sessionexpired");
    exit;
}

$_SESSION['last_activity'] = time();

if (isset($_SESSION['user_id'])) {
    require_once 'db.php';
    try {
        $stmt = $conn->prepare("SELECT id, ativo FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $usuario = $stmt->fetch();
        
        if (!$usuario || $usuario['ativo'] == 0) {
            session_unset();
            session_destroy();
            header("Location: index.php?error=usernotfound");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar usuário: " . $e->getMessage());
    }
}
?>