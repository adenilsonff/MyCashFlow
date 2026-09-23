<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
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
        'mensal' => 0,
        'unica' => 0,
        'recebido' => 0,
        'a_receber' => 0,
        'total' => 0
    ];
}

$stmt = $conn->prepare("
    SELECT
        MONTH(data) AS mes,
        SUM(valor) AS total,

        SUM(
            CASE
                WHEN tipo IN ('mensal', 'parcelada', 'recorrente') THEN valor
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
                WHEN recebido = 1 THEN valor
                ELSE 0
            END
        ) AS recebido

    FROM (SELECT * FROM rendas WHERE usuario_id = @mcf_usuario_id) AS rendas
    WHERE YEAR(data) = ?
    GROUP BY MONTH(data)
    ORDER BY MONTH(data)
");

$stmt->bind_param("i", $ano);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $mes = (int)$row['mes'];

    $total = (float)$row['total'];
    $recebido = (float)$row['recebido'];

    $dadosMeses[$mes]['mensal'] =
        (float)$row['mensal'];

    $dadosMeses[$mes]['unica'] =
        (float)$row['unica'];

    $dadosMeses[$mes]['recebido'] =
        $recebido;

    $dadosMeses[$mes]['a_receber'] =
        $total - $recebido;

    $dadosMeses[$mes]['total'] =
        $total;
}

$stmt->close();

$totalReceitas = 0;
$totalMensal = 0;
$totalUnica = 0;
$totalRecebido = 0;
$totalAReceber = 0;
$mesesComReceita = 0;

foreach ($dadosMeses as $dados) {
    $totalReceitas += $dados['total'];
    $totalMensal += $dados['mensal'];
    $totalUnica += $dados['unica'];
    $totalRecebido += $dados['recebido'];
    $totalAReceber += $dados['a_receber'];

    if ($dados['total'] > 0) {
        $mesesComReceita++;
    }
}

$mediaMensal = $totalReceitas / 12;

$percentualMensal = $totalReceitas > 0
    ? ($totalMensal / $totalReceitas) * 100
    : 0;

$percentualUnica = $totalReceitas > 0
    ? ($totalUnica / $totalReceitas) * 100
    : 0;

$percentualRecebido = $totalReceitas > 0
    ? ($totalRecebido / $totalReceitas) * 100
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
    : 'Nenhuma receita registrada';

$menorMesNome = $menorMesNumero !== null
    ? $meses[$menorMesNumero]
    : 'Nenhuma receita registrada';

$concentracaoMaiorMes = $totalReceitas > 0
    ? ($maiorMesValor / $totalReceitas) * 100
    : 0;

$anosDisponiveis = [];

$resultAnos = $conn->query("
    SELECT DISTINCT
        YEAR(data) AS ano
    FROM (SELECT * FROM rendas WHERE usuario_id = @mcf_usuario_id) AS rendas
    WHERE data IS NOT NULL
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
$recebidoGrafico = [];
$aReceberGrafico = [];
$totaisGrafico = [];

foreach ($meses as $numero => $nome) {
    $labelsGrafico[] = $nome;
    $recebidoGrafico[] = $dadosMeses[$numero]['recebido'];
    $aReceberGrafico[] = $dadosMeses[$numero]['a_receber'];
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

    <title>Relatório de Receitas</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-receitas.css?v=1"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="receitas-layout">

    <div class="cabecalho-receitas">

        <div>

            <h1>Relatório de Receitas</h1>

            <p>
                Análise das receitas de
                <?= (int)$ano ?>
            </p>

        </div>

        <div class="acoes-receitas">

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

    <section class="resumo-receitas">

        <div class="card-resumo-receitas">

            <span>Receitas Previstas</span>

            <strong>
                <?= formatarMoeda(
                    $totalReceitas
                ) ?>
            </strong>

        </div>

        <div class="card-resumo-receitas">

            <span>Total Recebido</span>

            <strong>
                <?= formatarMoeda(
                    $totalRecebido
                ) ?>
            </strong>

        </div>

        <div class="card-resumo-receitas">

            <span>A Receber</span>

            <strong>
                <?= formatarMoeda(
                    $totalAReceber
                ) ?>
            </strong>

        </div>

        <div class="card-resumo-receitas">

            <span>Média Mensal</span>

            <strong>
                <?= formatarMoeda(
                    $mediaMensal
                ) ?>
            </strong>

        </div>

    </section>

    <section class="card-destaques-receitas">

        <div class="titulo-secao-receitas">

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

                <span>Maior receita mensal</span>

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

                <span>Menor mês com receita</span>

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

                <span>Receitas Parceladas / Recorrentes</span>

                <strong>
                    <?= formatarMoeda(
                        $totalMensal
                    ) ?>
                </strong>

                <small>
                    <?= formatarPercentual(
                        $percentualMensal
                    ) ?>
                    do total
                </small>

            </div>

            <div class="destaque-item">

                <span>Receitas Únicas</span>

                <strong>
                    <?= formatarMoeda(
                        $totalUnica
                    ) ?>
                </strong>

                <small>
                    <?= formatarPercentual(
                        $percentualUnica
                    ) ?>
                    do total
                </small>

            </div>

            <div class="destaque-item">

                <span>Percentual recebido</span>

                <strong>
                    <?= formatarPercentual(
                        $percentualRecebido
                    ) ?>
                </strong>

                <small>
                    <?= formatarMoeda(
                        $totalRecebido
                    ) ?>
                    de
                    <?= formatarMoeda(
                        $totalReceitas
                    ) ?>
                </small>

            </div>

            <div class="destaque-item">

                <span>Meses com receita</span>

                <strong>
                    <?= (int)$mesesComReceita ?> de 12
                </strong>

                <small>
                    meses com movimentação
                </small>

            </div>

            <div class="destaque-item">

                <span>Concentração no maior mês</span>

                <strong>
                    <?= formatarPercentual(
                        $concentracaoMaiorMes
                    ) ?>
                </strong>

                <small>
                    do total anual em
                    <?= htmlspecialchars(
                        $maiorMesNome
                    ) ?>
                </small>

            </div>

        </div>

    </section>

    <section class="card-grafico-receitas">

        <div class="titulo-secao-receitas">

            <div>

                <h2>Evolução mensal das receitas</h2>

                <span>
                    Recebido x A Receber em
                    <?= (int)$ano ?>
                </span>

            </div>

        </div>

        <div class="grafico-wrapper-receitas">

            <canvas id="graficoReceitas"></canvas>

        </div>

    </section>

    <section class="card-tabela-receitas">

        <div class="titulo-secao-receitas">

            <div>

                <h2>Detalhamento mensal</h2>

                <span>
                    Janeiro a dezembro de
                    <?= (int)$ano ?>
                </span>

            </div>

        </div>

        <div class="tabela-responsiva-receitas">

            <table class="tabela-receitas">

                <thead>

                    <tr>
                        <th>Mês</th>
                        <th>Parceladas / Recorrentes</th>
                        <th>Única</th>
                        <th>Recebido</th>
                        <th>A Receber</th>
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
                                    $dados['recebido']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['a_receber']
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
                                $totalRecebido
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalAReceber
                            ) ?>
                        </td>

                        <td>
                            <?= formatarMoeda(
                                $totalReceitas
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
        .getElementById('graficoReceitas')
        .getContext('2d');

const labelsGrafico =
    <?= json_encode(
        $labelsGrafico,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const recebidoGrafico =
    <?= json_encode(
        $recebidoGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const aReceberGrafico =
    <?= json_encode(
        $aReceberGrafico,
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
                    label: 'Recebido',
                    data: recebidoGrafico,
                    stack: 'receitas'
                },
                {
                    label: 'A Receber',
                    data: aReceberGrafico,
                    stack: 'receitas'
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