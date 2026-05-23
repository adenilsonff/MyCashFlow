<?php
include __DIR__ . '/../../config.php'; // sobe 2 níveis até a raiz
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php"); // também sobe 2 níveis
    exit;
}
?>

// Consulta consolidada por categoria
$sql = "SELECT categoria, SUM(valor) AS total FROM rendas GROUP BY categoria";
$result = $conn->query($sql);

$dados_rendas = [];
$total_rendas = 0;
while($row = $result->fetch_assoc()) {
    $dados_rendas[] = [
        'categoria' => $row['categoria'],
        'total' => $row['total']
    ];
    $total_rendas += $row['total'];
}
$json_rendas = json_encode($dados_rendas);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Rendas</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <!-- Conteúdo principal -->
    <main class="rendas-layout">
        <h1>Relatório de Rendas</h1>

        <!-- KPIs -->
        <section class="kpis">
            <div class="kpi-card">
                <h3>Total de Rendas</h3>
                <p>R$ <?= number_format($total_rendas, 2, ',', '.') ?></p>
            </div>
            <div class="kpi-card">
                <h3>Maior Categoria</h3>
                <p>
                    <?php 
                    if (!empty($dados_rendas)) {
                        $maior = max(array_column($dados_rendas, 'total'));
                        $cat = array_search($maior, array_column($dados_rendas, 'total'));
                        echo $dados_rendas[$cat]['categoria'] . " (R$ " . number_format($maior, 2, ',', '.') . ")";
                    }
                    ?>
                </p>
            </div>
        </section>

        <!-- Gráfico -->
        <section class="grafico">
            <h2>Distribuição por Categoria</h2>
            <canvas id="graficoRendas"></canvas>
        </section>

        <!-- Tabela -->
        <section class="tabela">
            <h2>Detalhes das Rendas</h2>
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dados_rendas as $renda) { ?>
                        <tr>
                            <td><?= $renda['categoria'] ?></td>
                            <td>R$ <?= number_format($renda['total'], 2, ',', '.') ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </main>

    <!-- Rodapé -->
    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>

    <!-- Script do gráfico -->
    <script>
        const dadosRendas = <?= $json_rendas ?>;
        const ctx = document.getElementById('graficoRendas').getContext('2d');
        const categorias = dadosRendas.map(item => item.categoria);
        const valores = dadosRendas.map(item => item.total);

        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: categorias,
                datasets: [{
                    data: valores,
                    backgroundColor: ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribuição das Rendas por Categoria'
                    }
                }
            }
        });
    </script>
</body>
</html>