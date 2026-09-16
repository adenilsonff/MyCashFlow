<?php

include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$anoAtual = (int)date('Y');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : $anoAtual;

if ($ano < 2000 || $ano > 2100) {
    $ano = $anoAtual;
}

$meses = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];

$mesesAbreviados = [
    1 => 'Jan',
    2 => 'Fev',
    3 => 'Mar',
    4 => 'Abr',
    5 => 'Mai',
    6 => 'Jun',
    7 => 'Jul',
    8 => 'Ago',
    9 => 'Set',
    10 => 'Out',
    11 => 'Nov',
    12 => 'Dez'
];

$dadosMensais = [];

foreach ($meses as $numero => $nome) {
    $dadosMensais[$numero] = [
        'mes' => $nome,
        'compras' => 0.0,
        'creditos' => 0.0,
        'liquido' => 0.0,
        'lancamentos' => 0,
        'pago' => 0.0,
        'aberto' => 0.0,
        'pagos_qtd' => 0,
        'abertos_qtd' => 0,
        'situacao' => 'Sem movimento'
    ];
}

$stmt = $conn->prepare("
    SELECT
        MONTH(c.data) AS mes,
        SUM(CASE
            WHEN c.valor > 0 THEN c.valor
            ELSE 0
        END) AS compras,
        SUM(CASE
            WHEN c.valor < 0 THEN c.valor
            ELSE 0
        END) AS creditos,
        SUM(c.valor) AS liquido,
        COUNT(c.id) AS lancamentos,
        SUM(CASE
            WHEN c.paga = 1 THEN c.valor
            ELSE 0
        END) AS pago,
        SUM(CASE
            WHEN c.paga = 0 THEN c.valor
            ELSE 0
        END) AS aberto,
        SUM(CASE
            WHEN c.paga = 1 THEN 1
            ELSE 0
        END) AS pagos_qtd,
        SUM(CASE
            WHEN c.paga = 0 THEN 1
            ELSE 0
        END) AS abertos_qtd
    FROM cartoes c
    WHERE YEAR(c.data) = ?
    GROUP BY MONTH(c.data)
    ORDER BY MONTH(c.data)
");

$stmt->bind_param("i", $ano);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $mes = (int)$row['mes'];

    if (!isset($dadosMensais[$mes])) {
        continue;
    }

    $lancamentos = (int)$row['lancamentos'];
    $pagosQtd = (int)$row['pagos_qtd'];
    $abertosQtd = (int)$row['abertos_qtd'];

    if ($lancamentos === 0) {
        $situacao = 'Sem movimento';
    } elseif ($pagosQtd === $lancamentos) {
        $situacao = 'Paga';
    } elseif ($abertosQtd === $lancamentos) {
        $situacao = 'Em aberto';
    } else {
        $situacao = 'Parcial';
    }

    $dadosMensais[$mes]['compras'] = (float)$row['compras'];
    $dadosMensais[$mes]['creditos'] = abs((float)$row['creditos']);
    $dadosMensais[$mes]['liquido'] = (float)$row['liquido'];
    $dadosMensais[$mes]['lancamentos'] = $lancamentos;
    $dadosMensais[$mes]['pago'] = (float)$row['pago'];
    $dadosMensais[$mes]['aberto'] = (float)$row['aberto'];
    $dadosMensais[$mes]['pagos_qtd'] = $pagosQtd;
    $dadosMensais[$mes]['abertos_qtd'] = $abertosQtd;
    $dadosMensais[$mes]['situacao'] = $situacao;
}

$stmt->close();

$totalLiquido = 0.0;
$totalCompras = 0.0;
$totalCreditos = 0.0;
$totalLancamentos = 0;
$totalPago = 0.0;
$totalAberto = 0.0;

$maiorFatura = null;
$maiorFaturaMes = null;

$menorFatura = null;
$menorFaturaMes = null;

foreach ($dadosMensais as $numeroMes => $dados) {
    $totalLiquido += $dados['liquido'];
    $totalCompras += $dados['compras'];
    $totalCreditos += $dados['creditos'];
    $totalLancamentos += $dados['lancamentos'];
    $totalPago += $dados['pago'];
    $totalAberto += $dados['aberto'];

    if ($dados['lancamentos'] > 0) {
        if (
            $maiorFatura === null ||
            $dados['liquido'] > $maiorFatura
        ) {
            $maiorFatura = $dados['liquido'];
            $maiorFaturaMes = $numeroMes;
        }

        if (
            $menorFatura === null ||
            $dados['liquido'] < $menorFatura
        ) {
            $menorFatura = $dados['liquido'];
            $menorFaturaMes = $numeroMes;
        }
    }
}

$mediaMensal = $totalLiquido / 12;

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT compra_id) AS total
    FROM cartoes
    WHERE YEAR(data) = ?
");

$stmt->bind_param("i", $ano);
$stmt->execute();
$result = $stmt->get_result();

$row = $result->fetch_assoc();

$comprasDistintas = isset($row['total'])
    ? (int)$row['total']
    : 0;

$stmt->close();

$valoresGrafico = [];

foreach ($dadosMensais as $dados) {
    $valoresGrafico[] = round($dados['liquido'], 2);
}

function moeda($valor)
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function classeSituacao($situacao)
{
    if ($situacao === 'Paga') {
        return 'status-pago';
    }

    if ($situacao === 'Parcial') {
        return 'status-parcial';
    }

    if ($situacao === 'Em aberto') {
        return 'status-aberto';
    }

    return 'status-sem-movimento';
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Relatório - Cartão de Crédito</title>

    <link
        rel="stylesheet"
        href="../../assets/css/relatorios/style-cartao.css?v=1"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="relatorio-container">

    <div class="relatorio-cabecalho">

        <div>
            <h1>Cartão de Crédito</h1>

            <p>
                Análise anual das faturas e lançamentos
            </p>
        </div>

        <div class="relatorio-acoes">

            <form method="GET" class="form-filtro">

                <label>
                    Ano

                    <select name="ano">

                        <?php
                        for (
                            $y = $anoAtual - 5;
                            $y <= $anoAtual + 5;
                            $y++
                        ) {
                        ?>

                            <option
                                value="<?= $y ?>"
                                <?= $y === $ano ? 'selected' : '' ?>
                            >
                                <?= $y ?>
                            </option>

                        <?php } ?>

                    </select>
                </label>

                <button type="submit">
                    Filtrar
                </button>

            </form>

            <button
                type="button"
                onclick="window.print()"
                class="btn-imprimir"
            >
                Imprimir
            </button>

        </div>

    </div>

    <section class="cards-resumo">

        <div class="card-resumo">

            <span>
                Total líquido no ano
            </span>

            <strong>
                <?= moeda($totalLiquido) ?>
            </strong>

        </div>

        <div class="card-resumo">

            <span>
                Média mensal
            </span>

            <strong>
                <?= moeda($mediaMensal) ?>
            </strong>

        </div>

        <div class="card-resumo">

            <span>
                Maior fatura
            </span>

            <strong>
                <?= $maiorFatura !== null
                    ? moeda($maiorFatura)
                    : moeda(0)
                ?>
            </strong>

            <small>
                <?= $maiorFaturaMes !== null
                    ? htmlspecialchars($meses[$maiorFaturaMes])
                    : 'Sem movimento'
                ?>
            </small>

        </div>

        <div class="card-resumo">

            <span>
                Lançamentos
            </span>

            <strong>
                <?= number_format(
                    $totalLancamentos,
                    0,
                    ',',
                    '.'
                ) ?>
            </strong>

        </div>

    </section>

    <section class="destaques-relatorio">

        <div class="destaque-item">

            <span>
                Compras/cobranças
            </span>

            <strong>
                <?= moeda($totalCompras) ?>
            </strong>

        </div>

        <div class="destaque-item">

            <span>
                Créditos/estornos
            </span>

            <strong>
                <?= moeda($totalCreditos) ?>
            </strong>

        </div>

        <div class="destaque-item">

            <span>
                Compras distintas
            </span>

            <strong>
                <?= number_format(
                    $comprasDistintas,
                    0,
                    ',',
                    '.'
                ) ?>
            </strong>

        </div>

        <div class="destaque-item">

            <span>
                Menor fatura com movimento
            </span>

            <strong>
                <?= $menorFatura !== null
                    ? moeda($menorFatura)
                    : moeda(0)
                ?>
            </strong>

            <small>
                <?= $menorFaturaMes !== null
                    ? htmlspecialchars($meses[$menorFaturaMes])
                    : 'Sem movimento'
                ?>
            </small>

        </div>

    </section>

    <section class="card-grafico">

        <div class="titulo-secao">

            <div>
                <h2>
                    Evolução mensal da fatura
                </h2>

                <span>
                    <?= $ano ?>
                </span>
            </div>

        </div>

        <div class="grafico-wrapper">

            <canvas id="graficoFaturas"></canvas>

        </div>

    </section>

    <section class="card-tabela">

        <div class="titulo-secao">

            <div>
                <h2>
                    Detalhamento mensal
                </h2>

                <span>
                    Janeiro a dezembro de <?= $ano ?>
                </span>
            </div>

        </div>

        <div class="tabela-responsiva">

            <table>

                <thead>

                    <tr>

                        <th>Mês</th>

                        <th>
                            Compras/Cobranças
                        </th>

                        <th>
                            Créditos/Estornos
                        </th>

                        <th>
                            Fatura Líquida
                        </th>

                        <th>
                            Lançamentos
                        </th>

                        <th>
                            Pago
                        </th>

                        <th>
                            Em Aberto
                        </th>

                        <th>
                            Situação
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($dadosMensais as $dados) { ?>

                    <tr>

                        <td>
                            <strong>
                                <?= htmlspecialchars(
                                    $dados['mes']
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <?= moeda(
                                $dados['compras']
                            ) ?>
                        </td>

                        <td>
                            <?= moeda(
                                $dados['creditos']
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= moeda(
                                    $dados['liquido']
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <?= number_format(
                                $dados['lancamentos'],
                                0,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            <?= moeda(
                                $dados['pago']
                            ) ?>
                        </td>

                        <td>
                            <?= moeda(
                                $dados['aberto']
                            ) ?>
                        </td>

                        <td>

                            <span
                                class="status-relatorio <?= classeSituacao(
                                    $dados['situacao']
                                ) ?>"
                            >
                                <?= htmlspecialchars(
                                    $dados['situacao']
                                ) ?>
                            </span>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

                <tfoot>

                    <tr>

                        <td>
                            <strong>
                                Total
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= moeda($totalCompras) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= moeda($totalCreditos) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= moeda($totalLiquido) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= number_format(
                                    $totalLancamentos,
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= moeda($totalPago) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= moeda($totalAberto) ?>
                            </strong>
                        </td>

                        <td></td>

                    </tr>

                </tfoot>

            </table>

        </div>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>

<script>

const contextoGrafico = document
    .getElementById('graficoFaturas')
    .getContext('2d');

new Chart(contextoGrafico, {

    type: 'line',

    data: {

        labels: <?= json_encode(
            array_values($mesesAbreviados),
            JSON_UNESCAPED_UNICODE
        ) ?>,

        datasets: [

            {
                label: 'Fatura líquida',
                data: <?= json_encode(
                    $valoresGrafico
                ) ?>,
                borderWidth: 2,
                tension: 0.25,
                fill: false
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        interaction: {
            mode: 'index',
            intersect: false
        },

        plugins: {

            legend: {
                display: true
            },

            tooltip: {

                callbacks: {

                    label: function(context) {

                        const valor = context.parsed.y;

                        return 'R$ ' +
                            valor.toLocaleString(
                                'pt-BR',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );
                    }

                }

            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback: function(value) {

                        return 'R$ ' +
                            Number(value).toLocaleString(
                                'pt-BR'
                            );
                    }

                }

            }

        }

    }

});

</script>

</body>
</html>