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
    <title>Relatórios</title>
    <link rel="stylesheet" href="../assets/css/style-relatorios.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="relatorios-layout">
        <div class="relatorios-container" style="margin-top:30px;">
            
            <!-- Card Despesas -->
            <div class="card-cadastro" onclick="location.href='rel_despesas.php'" style="cursor:pointer;">
                <h2>Despesas</h2>
                <p>Controle de gastos gerais</p>
            </div>

            <!-- Card Receitas -->
            <div class="card-cadastro" onclick="location.href='rel_receitas.php'" style="cursor:pointer;">
                <h2>Receitas</h2>
                <p>Entradas de dinheiro</p>
            </div>

            <!-- Card Cartão de Crédito -->
            <div class="card-cadastro" onclick="location.href='rel_cartao.php'" style="cursor:pointer;">
                <h2>Cartão de Crédito</h2>
                <p>Faturas e limite</p>
            </div>

            <!-- Card Extras -->
            <div class="card-cadastro" onclick="location.href='rel_extras.php'" style="cursor:pointer;">
                <h2>Extras</h2>
                <p>Ganhos e custos não recorrentes</p>
            </div>

            <!-- Card Ações -->
            <div class="card-cadastro" onclick="location.href='rel_acoes.php'" style="cursor:pointer;">
                <h2>Ações</h2>
                <p>Carteira de investimentos</p>
            </div>

            <!-- Card Dividendos -->
            <div class="card-cadastro" onclick="location.href='rel_dividendos.php'" style="cursor:pointer;">
                <h2>Dividendos</h2>
                <p>Valores recebidos de dividendos</p>
            </div>

            <!-- Card Daytrade -->
            <div class="card-cadastro" onclick="location.href='rel_daytrade.php'" style="cursor:pointer;">
                <h2>Daytrade</h2>
                <p>Operações diárias e resultados</p>
            </div>

        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>