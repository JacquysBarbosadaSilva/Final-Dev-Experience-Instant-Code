<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "youtan_monitor";
$charset = "utf8mb4";

$dsn = "mysql:host=$servidor;dbname=$banco;charset=$charset";

$opcoes = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $conn = new PDO($dsn, $usuario, $senha, $opcoes);
} catch (PDOException $e) {
     die("Falha na conexão com o banco de dados: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>