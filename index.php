<?php
require_once __DIR__.'/includes/seguranca.php';
mcfIniciarSessao();
if (empty($_SESSION['usuario_id'])) { header('Location: views/login/login.php'); exit; }
require_once __DIR__.'/config.php';
header('Location: views/dashboard.php'); exit;
