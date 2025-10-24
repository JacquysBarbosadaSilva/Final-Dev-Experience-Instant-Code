<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {
    $_SESSION = [];
    session_destroy();
    
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
}

$_SESSION = [];
session_destroy();
header("Location: index.php");
exit;
?>