<?php
session_start();
include("config.php");

// Se não estiver logado, redireciona para login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: views/login.php");
    exit;
}

// Se estiver logado, mostra o dashboard
include("views/dashboard.php");
?>