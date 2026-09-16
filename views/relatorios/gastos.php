<?php
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$anoAtual = (int)date("Y");
$mesAtual = (int)date("n");

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

$dadosMeses = [];

for ($mes = 1; $mes <= 12; $mes++) {
    $dadosMeses[$mes] = [
        'pessoal' => 0,
        'conjunta' => 0,
        'mensal' => 0,
        'unica' => 0,
        'pago' => 0,
        'em_aberto' => 0,
        'total' => 0
    ];
}

$stmt = $conn->prepare("
    SELECT
        MONTH(vencimento) AS mes,
        SUM(valor) AS total,

        SUM(
            CASE
                WHEN categoria = 'pessoal' THEN valor
                ELSE 0
            END
        ) AS pessoal,

        SUM(
            CASE
                WHEN categoria = 'conjunta' THEN valor
                ELSE 0
            END
        ) AS conjunta,

        SUM(
            CASE
                WHEN tipo = 'mensal' THEN valor
                ELSE 0
            END
        ) AS mensal,

        SUM(
            CASE
                WHEN tipo = 'unica' THEN valor
                ELSE 0
            END
        ) AS unica,

        SUM(
            CASE
                WHEN paga = 1 THEN valor
                ELSE 0
            END
        ) AS pago

    FROM contas
    WHERE YEAR(vencimento) = ?
    GROUP BY MONTH(vencimento)
    ORDER BY MONTH(vencimento)
");

$stmt->bind_param("i", $ano);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $mes = (int)$row['mes'];

    $total = (float)$row['total'];
    $pago = (float)$row['pago'];

    $dadosMeses[$mes]['pessoal'] =
        (float)$row['pessoal'];

    $dadosMeses[$mes]['conjunta'] =
        (float)$row['conjunta'];

    $dadosMeses[$mes]['mensal'] =
        (float)$row['mensal'];

    $dadosMeses[$mes]['unica'] =
        (float)$row['unica'];

    $dadosMeses[$mes]['pago'] =
        $pago;

    $dadosMeses[$mes]['em_aberto'] =
        $total - $pago;

    $dadosMeses[$mes]['total'] =
        $total;
}

$stmt->close();

$totalGastos = 0;
$totalPessoal = 0;
$totalConjunta = 0;
$totalMensal = 0;
$totalUnica = 0;
$totalPago = 0;
$totalEmAberto = 0;

foreach ($dadosMeses as $dados) {
    $totalGastos += $dados['total'];
    $totalPessoal += $dados['pessoal'];
    $totalConjunta += $dados['conjunta'];
    $totalMensal += $dados['mensal'];
    $totalUnica += $dados['unica'];
    $totalPago += $dados['pago'];
    $totalEmAberto += $dados['em_aberto'];
}

$mediaMensal = $totalGastos / 12;

$percentualPessoal = $totalGastos > 0
    ? ($totalPessoal / $totalGastos) * 100
    : 0;

$percentualConjunta = $totalGastos > 0
    ? ($totalConjunta / $totalGastos) * 100
    : 0;

$percentualPago = $totalGastos > 0
    ? ($totalPago / $totalGastos) * 100
    : 0;

$maiorMesNumero = null;
$maiorMesValor = 0;

$menorMesNumero = null;
$menorMesValor = null;

foreach ($dadosMeses as $numero => $dados) {
    $totalMes = $dados['total'];

    if ($totalMes > $maiorMesValor) {
        $maiorMesValor = $totalMes;
        $maiorMesNumero = $numero;
    }

    if (
        $totalMes > 0 &&
        (
            $menorMesValor === null ||
            $totalMes < $menorMesValor
        )
    ) {
        $menorMesValor = $totalMes;
        $menorMesNumero = $numero;
    }
}

$maiorMesNome = $maiorMesNumero !== null
    ? $meses[$maiorMesNumero]
    : 'Nenhum gasto registrado';

$menorMesNome = $menorMesNumero !== null
    ? $meses[$menorMesNumero]
    : 'Nenhum gasto registrado';

$anosDisponiveis = [];

$resultAnos = $conn->query("
    SELECT DISTINCT
        YEAR(vencimento) AS ano
    FROM contas
    WHERE vencimento IS NOT NULL
    ORDER BY ano DESC
");

if ($resultAnos) {
    while ($row = $resultAnos->fetch_assoc()) {
        if ($row['ano'] !== null) {
            $anosDisponiveis[] = (int)$row['ano'];
        }
    }
}

if (!in_array($ano, $anosDisponiveis, true)) {
    $anosDisponiveis[] = $ano;
}

if (!in_array($anoAtual, $anosDisponiveis, true)) {
    $anosDisponiveis[] = $anoAtual;
}

rsort($anosDisponiveis);

$labelsGrafico = [];
$pessoalGrafico = [];
$conjuntaGrafico = [];
$totaisGrafico = [];

foreach ($meses as $numero => $nome) {
    $labelsGrafico[] = $nome;
    $pessoalGrafico[] = $dadosMeses[$numero]['pessoal'];
    $conjuntaGrafico[] = $dadosMeses[$numero]['conjunta'];
    $totaisGrafico[] = $dadosMeses[$numero]['total'];
}

function formatarMoeda($valor)
{
    return 'R$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function formatarPercentual($valor)
{
    return number_format(
        (float)$valor,
        2,
        ',',
        '.'
    ) . '%';
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

    <title>Relatório de Gastos</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-gastos.css?v=1"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="gastos-layout">

    <div class="cabecalho-gastos">

        <div>

            <h1>Relatório de Gastos</h1>

            <p>
                Análise das despesas de
                <?= (int)$ano ?>
            </p>

        </div>

        <div class="acoes-gastos">

            <form method="GET" class="filtro-ano">

                <label for="ano">
                    Ano
                </label>

                <select
                    name="ano"
                    id="ano"
                    onchange="this.form.submit()"
                >

                    <?php foreach ($anosDisponiveis as $anoOpcao) { ?>

                        <option
                            value="<?= (int)$anoOpcao ?>"
                            <?= $anoOpcao === $ano
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= (int)$anoOpcao ?>
                        </option>

                    <?php } ?>

                </select>

            </form>

            <button
                type="button"
                class="btn-imprimir"
                onclick="window.print()"
            >
                Imprimir
            </button>

        </div>

    </div>

    <section class="resumo-gastos">

        <div class="card-resumo-gastos">

            <span>Total de Gastos</span>

            <strong>
                <?= formatarMoeda(
                    $totalGastos
                ) ?>
            </strong>

        </div>

        <div class="card-resumo-gastos">

            <span>Total Pago</span>

            <strong>
                <?= formatarMoeda(
                    $totalPago
                ) ?>
            </strong>

        </div>

        <div class="card-resumo-gastos">

            <span>Em Aberto</span>

            <strong>
                <?= formatarMoeda(
                    $totalEmAberto
                ) ?>
            </strong>

        </div>

        <div class="card-resumo-gastos">

            <span>Média Mensal</span>

            <strong>
                <?= formatarMoeda(
                    $mediaMensal
                ) ?>
            </strong>

        </div>

    </section>

    <section class="card-destaques-gastos">

        <div class="titulo-secao-gastos">

            <div>

                <h2>Destaques do ano</h2>

                <span>
                    Principais indicadores de
                    <?= (int)$ano ?>
                </span>

            </div>

        </div>

        <div class="destaques-grid">

            <div class="destaque-item">

                <span>Maior gasto mensal</span>

                <strong>
                    <?= htmlspecialchars(
                        $maiorMesNome
                    ) ?>
                </strong>

                <small>
                    <?= formatarMoeda(
                        $maiorMesValor
                    ) ?>
                </small>

            </div>

            <div class="destaque-item">

                <span>Menor gasto com movimentação</span>

                <strong>
                    <?= htmlspecialchars(
                        $menorMesNome
                    ) ?>
                </strong>

                <small>
                    <?= formatarMoeda(
                        $menorMesValor ?? 0
                    ) ?>
                </small>

            </div>

            <div class="destaque-item">

                <span>Gastos Pessoais</span>

                <strong>
                    <?= formatarMoeda(
                        $totalPessoal
                    ) ?>
                </strong>

                <small>
                    <?= formatarPercentual(
                        $percentualPessoal
                    ) ?>
                    do total
                </small>

            </div>

            <div class="destaque-item">

                <span>Gastos Conjuntos</span>

                <strong>
                    <?= formatarMoeda(
                        $totalConjunta
                    ) ?>
                </strong>

                <small>
                    <?= formatarPercentual(
                        $percentualConjunta
                    ) ?>
                    do total
                </small>

            </div>

            <div class="destaque-item">

                <span>Percentual pago</span>

                <strong>
                    <?= formatarPercentual(
                        $percentualPago
                    ) ?>
                </strong>

                <small>
                    <?= formatarMoeda(
                        $totalPago
                    ) ?>
                    de
                    <?= formatarMoeda(
                        $totalGastos
                    ) ?>
                </small>

            </div>

        </div>

    </section>

    <section class="card-grafico-gastos">

        <div class="titulo-secao-gastos">

            <div>

                <h2>Evolução mensal dos gastos</h2>

                <span>
                    Pessoal x Conjunta em
                    <?= (int)$ano ?>
                </span>

            </div>

        </div>

        <div class="grafico-wrapper-gastos">

            <canvas id="graficoGastos"></canvas>

        </div>

    </section>

    <section class="card-tabela-gastos">

        <div class="titulo-secao-gastos">

            <div>

                <h2>Detalhamento mensal</h2>

                <span>
                    Janeiro a dezembro de
                    <?= (int)$ano ?>
                </span>

            </div>

        </div>

        <div class="tabela-responsiva-gastos">

            <table class="tabela-gastos">

                <thead>

                    <tr>
                        <th>Mês</th>
                        <th>Pessoal</th>
                        <th>Conjunta</th>
                        <th>Mensal</th>
                        <th>Única</th>
                        <th>Pago</th>
                        <th>Em aberto</th>
                        <th>Total</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($meses as $numero => $nome) {

                        $dados = $dadosMeses[$numero];

                        $classeMesAtual =
                            $ano === $anoAtual &&
                            $numero === $mesAtual
                                ? 'mes-atual'
                                : '';

                    ?>

                        <tr class="<?= $classeMesAtual ?>">

                            <td>

                                <?= htmlspecialchars($nome) ?>

                                <?php
                                if (
                                    $ano === $anoAtual &&
                                    $numero === $mesAtual
                                ) {
                                ?>

                                    <span class="indicador-mes-atual">
                                        Atual
                                    </span>

                                <?php } ?>

                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['pessoal']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['conjunta']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['mensal']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['unica']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['pago']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['em_aberto']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['total']
                                ) ?>
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

                <tfoot>

                    <tr>

                        <td>
                            <strong>Total</strong>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalPessoal
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalConjunta
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalMensal
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalUnica
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalPago
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalEmAberto
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalGastos
                            ) ?>
                        </td>

                    </tr>

                </tfoot>

            </table>

        </div>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const contextoGrafico =
    document
        .getElementById('graficoGastos')
        .getContext('2d');

const labelsGrafico =
    <?= json_encode(
        $labelsGrafico,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const pessoalGrafico =
    <?= json_encode(
        $pessoalGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const conjuntaGrafico =
    <?= json_encode(
        $conjuntaGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const totaisGrafico =
    <?= json_encode(
        $totaisGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

new Chart(
    contextoGrafico,
    {
        type: 'bar',

        data: {
            labels: labelsGrafico,

            datasets: [
                {
                    label: 'Pessoal',
                    data: pessoalGrafico,
                    stack: 'gastos'
                },
                {
                    label: 'Conjunta',
                    data: conjuntaGrafico,
                    stack: 'gastos'
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

            scales: {
                x: {
                    stacked: true
                },

                y: {
                    stacked: true,
                    beginAtZero: true,

                    ticks: {
                        callback: function(valor) {
                            return 'R$ ' +
                                Number(valor)
                                    .toLocaleString(
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

            plugins: {
                legend: {
                    position: 'top'
                },

                tooltip: {
                    callbacks: {
                        label: function(contexto) {
                            return contexto.dataset.label +
                                ': R$ ' +
                                Number(contexto.raw)
                                    .toLocaleString(
                                        'pt-BR',
                                        {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2
                                        }
                                    );
                        },

                        afterBody: function(contextos) {
                            if (!contextos.length) {
                                return '';
                            }

                            const indice =
                                contextos[0].dataIndex;

                            return 'Total: R$ ' +
                                Number(
                                    totaisGrafico[indice]
                                ).toLocaleString(
                                    'pt-BR',
                                    {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    }
                                );
                        }
                    }
                }
            }
        }
    }
);
</script>

</body>

</html>