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
    <title>Dividendos</title>
    <link rel="stylesheet" href="../assets/css/style-dividendos.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="acoes-layout">
        <div class="acoes-container" style="margin-top:30px;">
            <!-- Card DataCOM -->
            <div class="card-cadastro" onclick="location.href='div_datacom.php'" style="cursor:pointer;">
                <h2>DataCOM</h2>
                <p>Data maxima para direito aos dividendos</p>
            </div>

            <!-- Card Dividendos -->
            <div class="card-cadastro" onclick="location.href='dividendos.php'" style="cursor:pointer;">
                <h2>Dividendos</h2>
                <p>Valores a receber de dividendo</p>
            </div>

            <!-- Card Compra -->
            <div class="card-cadastro" onclick="location.href='compra_div.php'" style="cursor:pointer;">
                <h2>Compra</h2>
                <p>Calculo a recever de dividendos</p>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>