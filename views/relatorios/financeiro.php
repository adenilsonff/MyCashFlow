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
        'receita_prevista' => 0,
        'receita_recebida' => 0,
        'despesa_prevista' => 0,
        'despesa_paga' => 0,
        'saldo_previsto' => 0,
        'saldo_realizado' => 0
    ];
}

$stmt = $conn->prepare("
    SELECT
        MONTH(data) AS mes,
        SUM(valor) AS receita_prevista,
        SUM(
            CASE
                WHEN recebido = 1 THEN valor
                ELSE 0
            END
        ) AS receita_recebida
    FROM rendas
    WHERE YEAR(data) = ?
    GROUP BY MONTH(data)
    ORDER BY MONTH(data)
");

$stmt->bind_param("i", $ano);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $mes = (int)$row['mes'];

    $dadosMeses[$mes]['receita_prevista'] =
        (float)$row['receita_prevista'];

    $dadosMeses[$mes]['receita_recebida'] =
        (float)$row['receita_recebida'];
}

$stmt->close();

$stmt = $conn->prepare("
    SELECT
        MONTH(vencimento) AS mes,
        SUM(valor) AS despesa_prevista,
        SUM(
            CASE
                WHEN paga = 1 THEN valor
                ELSE 0
            END
        ) AS despesa_paga
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

    $dadosMeses[$mes]['despesa_prevista'] =
        (float)$row['despesa_prevista'];

    $dadosMeses[$mes]['despesa_paga'] =
        (float)$row['despesa_paga'];
}

$stmt->close();

$totalReceitaPrevista = 0;
$totalReceitaRecebida = 0;
$totalDespesaPrevista = 0;
$totalDespesaPaga = 0;

foreach ($dadosMeses as $mes => &$dados) {
    $dados['saldo_previsto'] =
        $dados['receita_prevista'] -
        $dados['despesa_prevista'];

    $dados['saldo_realizado'] =
        $dados['receita_recebida'] -
        $dados['despesa_paga'];

    $totalReceitaPrevista += $dados['receita_prevista'];
    $totalReceitaRecebida += $dados['receita_recebida'];
    $totalDespesaPrevista += $dados['despesa_prevista'];
    $totalDespesaPaga += $dados['despesa_paga'];
}

unset($dados);

$saldoPrevisto =
    $totalReceitaPrevista -
    $totalDespesaPrevista;

$saldoRealizado =
    $totalReceitaRecebida -
    $totalDespesaPaga;

$anosDisponiveis = [];

$sqlAnos = "
    SELECT ano
    FROM (
        SELECT DISTINCT YEAR(data) AS ano
        FROM rendas

        UNION

        SELECT DISTINCT YEAR(vencimento) AS ano
        FROM contas
    ) AS anos
    WHERE ano IS NOT NULL
    ORDER BY ano DESC
";

$resultAnos = $conn->query($sqlAnos);

if ($resultAnos) {
    while ($row = $resultAnos->fetch_assoc()) {
        $anosDisponiveis[] = (int)$row['ano'];
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
$receitasGrafico = [];
$despesasGrafico = [];

foreach ($meses as $numero => $nome) {
    $labelsGrafico[] = $nome;
    $receitasGrafico[] =
        $dadosMeses[$numero]['receita_prevista'];

    $despesasGrafico[] =
        $dadosMeses[$numero]['despesa_prevista'];
}

function classeSaldo($valor)
{
    if ($valor > 0) {
        return 'valor-positivo';
    }

    if ($valor < 0) {
        return 'valor-negativo';
    }

    return 'valor-neutro';
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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Visão Financeira</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-financeiro.css?v=1"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="financeiro-layout">

    <div class="cabecalho-financeiro">

        <div>
            <h1>Visão Financeira</h1>

            <p>
                Receitas, despesas e saldos de
                <?= (int)$ano ?>
            </p>
        </div>

        <div class="acoes-financeiro">

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

    <section class="resumo-principal">

        <div class="card-resumo">
            <span>Receitas previstas</span>

            <strong>
                <?= formatarMoeda(
                    $totalReceitaPrevista
                ) ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Despesas previstas</span>

            <strong>
                <?= formatarMoeda(
                    $totalDespesaPrevista
                ) ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Saldo previsto</span>

            <strong class="<?= classeSaldo(
                $saldoPrevisto
            ) ?>">
                <?= formatarMoeda(
                    $saldoPrevisto
                ) ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Saldo realizado</span>

            <strong class="<?= classeSaldo(
                $saldoRealizado
            ) ?>">
                <?= formatarMoeda(
                    $saldoRealizado
                ) ?>
            </strong>
        </div>

    </section>

    <section class="resumo-secundario">

        <div class="card-resumo-secundario">
            <span>Receitas recebidas</span>

            <strong>
                <?= formatarMoeda(
                    $totalReceitaRecebida
                ) ?>
            </strong>
        </div>

        <div class="card-resumo-secundario">
            <span>Despesas pagas</span>

            <strong>
                <?= formatarMoeda(
                    $totalDespesaPaga
                ) ?>
            </strong>
        </div>

    </section>

    <section class="card-grafico">

        <div class="titulo-secao">
            <div>
                <h2>Evolução Financeira</h2>

                <span>
                    Receitas previstas x despesas previstas
                </span>
            </div>
        </div>

        <div class="grafico-wrapper">
            <canvas id="graficoFinanceiro"></canvas>
        </div>

    </section>

    <section class="card-tabela">

        <div class="titulo-secao">

            <div>
                <h2>Resumo mensal</h2>

                <span>
                    Janeiro a dezembro de
                    <?= (int)$ano ?>
                </span>
            </div>

        </div>

        <div class="tabela-responsiva">

            <table class="tabela-financeira">

                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Receita prevista</th>
                        <th>Recebido</th>
                        <th>Despesa prevista</th>
                        <th>Pago</th>
                        <th>Saldo previsto</th>
                        <th>Saldo realizado</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($meses as $numero => $nome) {

                        $dados =
                            $dadosMeses[$numero];

                    ?>

                        <tr>

                            <td class="nome-mes">
                                <?= htmlspecialchars($nome) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['receita_prevista']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['receita_recebida']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['despesa_prevista']
                                ) ?>
                            </td>

                            <td>
                                <?= formatarMoeda(
                                    $dados['despesa_paga']
                                ) ?>
                            </td>

                            <td class="<?= classeSaldo(
                                $dados['saldo_previsto']
                            ) ?>">
                                <?= formatarMoeda(
                                    $dados['saldo_previsto']
                                ) ?>
                            </td>

                            <td class="<?= classeSaldo(
                                $dados['saldo_realizado']
                            ) ?>">
                                <?= formatarMoeda(
                                    $dados['saldo_realizado']
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
                            <strong>
                                <?= formatarMoeda(
                                    $totalReceitaPrevista
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= formatarMoeda(
                                    $totalReceitaRecebida
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= formatarMoeda(
                                    $totalDespesaPrevista
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= formatarMoeda(
                                    $totalDespesaPaga
                                ) ?>
                            </strong>
                        </td>

                        <td class="<?= classeSaldo(
                            $saldoPrevisto
                        ) ?>">
                            <strong>
                                <?= formatarMoeda(
                                    $saldoPrevisto
                                ) ?>
                            </strong>
                        </td>

                        <td class="<?= classeSaldo(
                            $saldoRealizado
                        ) ?>">
                            <strong>
                                <?= formatarMoeda(
                                    $saldoRealizado
                                ) ?>
                            </strong>
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
        .getElementById('graficoFinanceiro')
        .getContext('2d');

const labelsGrafico =
    <?= json_encode(
        $labelsGrafico,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const receitasGrafico =
    <?= json_encode(
        $receitasGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const despesasGrafico =
    <?= json_encode(
        $despesasGrafico,
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
                    label: 'Receitas previstas',
                    data: receitasGrafico
                },
                {
                    label: 'Despesas previstas',
                    data: despesasGrafico
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
                y: {
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