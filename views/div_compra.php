<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações Nacionais</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <!-- Card de Entrada de Capital -->
            <div class="card-cadastro-renda">
                <h2>Valor a Investir</h2>
                <form method="POST" class="form-rendas">
                    <input type="number" step="0.01" name="capital" placeholder="Digite o valor (R$)" required>
                    <button type="submit">Simular</button>
                </form>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>