<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar valores consolidados
$sql_nacional = "SELECT SUM(quantidade * valor_unitario) AS total_nacional FROM acoes_nacionais";
$result_nacional = $conn->query($sql_nacional);
$total_nacional = $result_nacional->fetch_assoc()['total_nacional'] ?? 0;

$sql_internacional = "SELECT SUM(quantidade * valor_unitario) AS total_internacional FROM acoes_internacionais";
$result_internacional = $conn->query($sql_internacional);
$total_internacional = $result_internacional->fetch_assoc()['total_internacional'] ?? 0;

$total_geral = $total_nacional + $total_internacional;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações</title>
    <link rel="stylesheet" href="../assets/css/style-acoes.css?v=6">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="acoes-layout">
        <h2>Resumo dos Investimentos em Ações</h2>
        <div class="card-lista">
            <table class="tabela-acoes">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Total Investido</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nacional</td>
                        <td>R$ <?= number_format($total_nacional, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td>Internacional</td>
                        <td>R$ <?= number_format($total_internacional, 2, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Consolidado</strong></td>
                        <td><strong>R$ <?= number_format($total_geral, 2, ',', '.') ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="acoes-container" style="margin-top:30px;">
            <!-- Card Nacional -->
            <div class="card-cadastro" onclick="location.href='acoes_nacionais.php'" style="cursor:pointer;">
                <h2>Ações Nacionais</h2>
                <p>Gerencie seus investimentos na B3</p>
            </div>

            <!-- Card Internacional -->
            <div class="card-cadastro" onclick="location.href='acoes_internacionais.php'" style="cursor:pointer;">
                <h2>Ações Internacionais</h2>
                <p>Gerencie seus ativos no exterior</p>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>