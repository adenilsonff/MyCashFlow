<?php
include __DIR__ . '/../../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

$sql = "SELECT 
            ticker,
            SUM(quantidade) AS quantidade_total,
            SUM(quantidade * valor_unitario) AS total_investido,
            MAX(valor_mercado) AS valor_mercado_atual,
            (MAX(valor_mercado) * SUM(quantidade)) - SUM(quantidade * valor_unitario) AS resultado
        FROM acoes_nacionais
        GROUP BY ticker
        HAVING quantidade_total > 0";
$result = $conn->query($sql);

$dados_acoes = [];
$total_investido = 0;
$total_resultado = 0;

while($row = $result->fetch_assoc()) {
    $dados_acoes[] = [
        'ticker' => $row['ticker'],
        'investido' => $row['total_investido'],
        'mercado' => $row['valor_mercado_atual'] * $row['quantidade_total'],
        'resultado' => $row['resultado']
    ];
    $total_investido += $row['total_investido'];
    $total_resultado += $row['resultado'];
}
$json_acoes = json_encode($dados_acoes);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Ações Nacionais</title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/style-relatorios.css?v=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            align-items: flex-start;
        }
        .grafico-container {
            flex: 1;
            max-width: 500px;
            height: 300px;
            margin: 0 auto;
        }
        canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .cards-container {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr)); /* 3 colunas fixas */
            gap: 20px;
        }
        .card-acao {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s;
        }
        .card-acao:hover {
            transform: translateY(-5px);
        }
        .card-acao h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .valor {
            font-size: 14px;
            margin: 5px 0;
        }
        .positivo {
            color: green;
            font-weight: bold;
        }
        .negativo {
            color: red;
            font-weight: bold;
        }

    </style>


</head>
<body>
    <?php include("../../includes/header.php"); ?>
    <?php include("../../includes/menu.php"); ?>

    <main class="acoes-layout">
        <h1>Relatório de Ações Nacionais</h1>

        <!-- KPIs -->
        <section class="kpis">
            <div class="kpi-card">
                <h3>Total Investido</h3>
                <p>R$ <?= number_format($total_investido, 2, ',', '.') ?></p>
            </div>
            <div class="kpi-card">
                <h3>Resultado Consolidado</h3>
                <p>
                    <?php if ($total_resultado >= 0) { ?>
                        <span class="positivo">+ R$ <?= number_format($total_resultado, 2, ',', '.') ?></span>
                    <?php } else { ?>
                        <span class="negativo">- R$ <?= number_format(abs($total_resultado), 2, ',', '.') ?></span>
                    <?php } ?>
                </p>
            </div>
        </section>

        <!-- Gráfico e cards lado a lado -->
        <div class="dashboard">
            <div class="grafico-container">
                <h2>Visualização das Ações</h2>
                <label for="tipoGrafico">Tipo de gráfico:</label>
                <select id="tipoGrafico">
                    <option value="bar" selected>Barras</option>
                    <option value="pie">Pizza</option>
                    <option value="line">Linha</option>
                </select>
                <canvas id="graficoAcoes"></canvas>
            </div>

            <div class="cards-container">
                <?php foreach($dados_acoes as $acao) { ?>
                    <div class="card-acao">
                        <h3><?= $acao['ticker'] ?></h3>
                        <p class="valor">Investido: R$ <?= number_format($acao['investido'], 2, ',', '.') ?></p>
                        <p class="valor">Mercado: R$ <?= number_format($acao['mercado'], 2, ',', '.') ?></p>
                        <p class="valor">
                            <?php if ($acao['resultado'] >= 0) { ?>
                                <span class="positivo">+ R$ <?= number_format($acao['resultado'], 2, ',', '.') ?></span>
                            <?php } else { ?>
                                <span class="negativo">- R$ <?= number_format(abs($acao['resultado']), 2, ',', '.') ?></span>
                            <?php } ?>
                        </p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>

    <!-- Script do gráfico -->
    <script>
        const dadosAcoes = <?= $json_acoes ?>;
        const ctx = document.getElementById('graficoAcoes').getContext('2d');

        const tickers = dadosAcoes.map(item => item.ticker);
        const investido = dadosAcoes.map(item => item.investido);
        const mercado = dadosAcoes.map(item => item.mercado);

        let chartConfig = {
            type: 'bar',
            data: {
                labels: tickers,
                datasets: [
                    {
                        label: 'Total Investido',
                        data: investido,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)'
                    },
                    {
                        label: 'Valor de Mercado',
                        data: mercado,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Comparativo Investido vs Mercado'
                    }
                }
            }
        };

        let grafico = new Chart(ctx, chartConfig);

        document.getElementById('tipoGrafico').addEventListener('change', function() {
            grafico.destroy();
            chartConfig.type = this.value;

            if (this.value === 'pie') {
                chartConfig.data = {
                    labels: tickers,
                    datasets: [{
                        data: mercado,
                        backgroundColor: ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF']
                    }]
                };
            } else {
                chartConfig.data = {
                    labels: tickers,
                    datasets: [
                        {
                            label: 'Total Investido',
                            data: investido,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)'
                        },
                        {
                            label: 'Valor de Mercado',
                            data: mercado,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)'
                        }
                    ]
                };
            }

            grafico = new Chart(ctx, chartConfig);
        });
    </script>
</body>
</html>
