<?php
include __DIR__ . '/../../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$usuario_id = (int)$_SESSION['usuario_id'];

$anoAtual = (int)date('Y');
$mesAtual = (int)date('n');

$ano = isset($_GET['ano'])
    ? (int)$_GET['ano']
    : $anoAtual;

if ($ano < 2000 || $ano > 2100) {
    $ano = $anoAtual;
}

$tiposAtivoPermitidos = [
    'acao' => 'Ação',
    'fii' => 'FII',
    'etf' => 'ETF',
    'bdr' => 'BDR'
];

$tipoAtivo = isset($_GET['tipo_ativo'])
    ? strtolower(trim((string)$_GET['tipo_ativo']))
    : '';

if (
    $tipoAtivo !== '' &&
    !array_key_exists(
        $tipoAtivo,
        $tiposAtivoPermitidos
    )
) {
    $tipoAtivo = '';
}

$ticker = isset($_GET['ticker'])
    ? strtoupper(trim((string)$_GET['ticker']))
    : '';

if (
    $ticker !== '' &&
    !preg_match('/^[A-Z0-9.\-]{1,20}$/', $ticker)
) {
    $ticker = '';
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

function relProventoEscape($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function relProventoBRL($valor)
{
    return 'R$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function relProventoValorUnidade($valor)
{
    $formatado = number_format(
        (float)$valor,
        8,
        ',',
        '.'
    );

    $formatado = rtrim($formatado, '0');
    $formatado = rtrim($formatado, ',');

    return 'R$ ' . $formatado;
}

function relProventoData($data)
{
    if ($data === null || $data === '') {
        return 'A definir';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return 'A definir';
    }

    return date('d/m/Y', $timestamp);
}

function relProventoTipoAtivo($tipo)
{
    $tipos = [
        'acao' => 'Ação',
        'fii' => 'FII',
        'etf' => 'ETF',
        'bdr' => 'BDR'
    ];

    return $tipos[$tipo] ?? $tipo;
}

function relProventoTipo($tipo)
{
    $tipos = [
        'DIV' => 'Dividendo',
        'JCP' => 'JCP',
        'REND' => 'Rendimento'
    ];

    return $tipos[$tipo] ?? $tipo;
}

$erroRelatorio = '';

$anosDisponiveis = [];

try {
    $stmt = $conn->prepare("
        SELECT DISTINCT YEAR(datacom) AS ano
        FROM div_datacom
        WHERE usuario_id = ?
          AND datacom IS NOT NULL
        ORDER BY ano DESC
    ");

    $stmt->bind_param(
        'i',
        $usuario_id
    );

    $stmt->execute();

    $resultadoAnos = $stmt->get_result();

    while ($row = $resultadoAnos->fetch_assoc()) {
        $anosDisponiveis[] = (int)$row['ano'];
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log(
        'MyCashFlow relatório proventos anos: ' .
        $e->getMessage()
    );
}

if (
    !in_array(
        $ano,
        $anosDisponiveis,
        true
    )
) {
    $anosDisponiveis[] = $ano;
}

if (
    !in_array(
        $anoAtual,
        $anosDisponiveis,
        true
    )
) {
    $anosDisponiveis[] = $anoAtual;
}

rsort($anosDisponiveis);

$ativosDisponiveis = [];

try {
    $sqlAtivos = "
        SELECT DISTINCT
            UPPER(TRIM(ticker)) AS ticker,
            tipo_ativo
        FROM div_datacom
        WHERE usuario_id = ?
          AND YEAR(datacom) = ?
    ";

    if ($tipoAtivo !== '') {
        $sqlAtivos .= "
            AND tipo_ativo = ?
        ";
    }

    $sqlAtivos .= "
        ORDER BY ticker, tipo_ativo
    ";

    $stmt = $conn->prepare($sqlAtivos);

    if ($tipoAtivo !== '') {
        $stmt->bind_param(
            'iis',
            $usuario_id,
            $ano,
            $tipoAtivo
        );
    } else {
        $stmt->bind_param(
            'ii',
            $usuario_id,
            $ano
        );
    }

    $stmt->execute();

    $resultadoAtivos = $stmt->get_result();

    while ($row = $resultadoAtivos->fetch_assoc()) {
        $ativosDisponiveis[] = [
            'ticker' => strtoupper(
                trim($row['ticker'])
            ),
            'tipo_ativo' => $row['tipo_ativo']
        ];
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log(
        'MyCashFlow relatório proventos ativos: ' .
        $e->getMessage()
    );
}

if ($ticker !== '') {
    $tickerValido = false;

    foreach ($ativosDisponiveis as $ativo) {
        if ($ativo['ticker'] === $ticker) {
            $tickerValido = true;
            break;
        }
    }

    if (!$tickerValido) {
        $ticker = '';
    }
}

$eventos = [];

try {
    $sql = "
        SELECT
            d.id,
            d.ticker,
            d.tipo_ativo,
            d.datacom,
            d.datapag,
            d.valor,
            d.tipo,
            COALESCE(
                (
                    SELECT SUM(
                        CASE
                            WHEN a.tipo_operacao = 'compra'
                                THEN ABS(a.quantidade)
                            WHEN a.tipo_operacao = 'venda'
                                THEN -ABS(a.quantidade)
                            ELSE 0
                        END
                    )
                    FROM investimentos_nacionais a
                    WHERE a.usuario_id = d.usuario_id
                      AND UPPER(TRIM(a.ticker)) =
                          UPPER(TRIM(d.ticker))
                      AND a.tipo_ativo = d.tipo_ativo
                      AND a.data <= d.datacom
                ),
                0
            ) AS quantidade_elegivel
        FROM div_datacom d
        WHERE d.usuario_id = ?
          AND YEAR(d.datacom) = ?
    ";

    $tiposBind = 'ii';
    $parametros = [
        $usuario_id,
        $ano
    ];

    if ($tipoAtivo !== '') {
        $sql .= "
            AND d.tipo_ativo = ?
        ";

        $tiposBind .= 's';
        $parametros[] = $tipoAtivo;
    }

    if ($ticker !== '') {
        $sql .= "
            AND UPPER(TRIM(d.ticker)) = ?
        ";

        $tiposBind .= 's';
        $parametros[] = $ticker;
    }

    $sql .= "
        ORDER BY
            d.datacom DESC,
            d.ticker ASC,
            d.id DESC
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        $tiposBind,
        ...$parametros
    );

    $stmt->execute();

    $eventosBanco = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    foreach ($eventosBanco as $evento) {
        $quantidadeElegivel =
            (int)$evento['quantidade_elegivel'];

        if ($quantidadeElegivel <= 0) {
            continue;
        }

        $valorUnidade =
            (float)$evento['valor'];

        $totalEvento =
            $quantidadeElegivel *
            $valorUnidade;

        $evento['ticker'] = strtoupper(
            trim($evento['ticker'])
        );

        $evento['quantidade_elegivel'] =
            $quantidadeElegivel;

        $evento['total_evento'] =
            $totalEvento;

        $eventos[] = $evento;
    }

} catch (Throwable $e) {
    $erroRelatorio =
        'Não foi possível carregar os proventos do período selecionado.';

    error_log(
        'MyCashFlow relatório proventos: ' .
        $e->getMessage()
    );
}

$totalProventos = 0.0;
$totalDividendos = 0.0;
$totalJCP = 0.0;
$totalRendimentos = 0.0;

$totaisClasse = [
    'acao' => 0.0,
    'fii' => 0.0,
    'etf' => 0.0,
    'bdr' => 0.0
];

$dadosMensais = [];

for ($mes = 1; $mes <= 12; $mes++) {
    $dadosMensais[$mes] = [
        'total' => 0.0,
        'div' => 0.0,
        'jcp' => 0.0,
        'rend' => 0.0,
        'eventos' => 0
    ];
}

foreach ($eventos as $evento) {
    $total =
        (float)$evento['total_evento'];

    $totalProventos += $total;

    if ($evento['tipo'] === 'DIV') {
        $totalDividendos += $total;
    } elseif ($evento['tipo'] === 'JCP') {
        $totalJCP += $total;
    } elseif ($evento['tipo'] === 'REND') {
        $totalRendimentos += $total;
    }

    if (
        isset(
            $totaisClasse[
                $evento['tipo_ativo']
            ]
        )
    ) {
        $totaisClasse[
            $evento['tipo_ativo']
        ] += $total;
    }

    $mesEvento = (int)date(
        'n',
        strtotime($evento['datacom'])
    );

    $dadosMensais[$mesEvento]['total'] +=
        $total;

    $dadosMensais[$mesEvento]['eventos']++;

    if ($evento['tipo'] === 'DIV') {
        $dadosMensais[$mesEvento]['div'] +=
            $total;
    } elseif ($evento['tipo'] === 'JCP') {
        $dadosMensais[$mesEvento]['jcp'] +=
            $total;
    } elseif ($evento['tipo'] === 'REND') {
        $dadosMensais[$mesEvento]['rend'] +=
            $total;
    }
}

$labelsGrafico = [];
$totaisGrafico = [];

foreach ($meses as $numero => $nome) {
    $labelsGrafico[] = $nome;

    $totaisGrafico[] =
        $dadosMensais[$numero]['total'];
}

$totalPorTipo =
    $totalDividendos +
    $totalJCP +
    $totalRendimentos;

$totalPorClasse =
    array_sum($totaisClasse);

$diferencaTipo =
    abs($totalProventos - $totalPorTipo);

$diferencaClasse =
    abs($totalProventos - $totalPorClasse);

$conferenciaCorreta =
    $diferencaTipo < 0.01 &&
    $diferencaClasse < 0.01;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Relatório de Proventos</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-proventos.css?v=1"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="proventos-rel-layout">

    <div class="proventos-rel-cabecalho">

        <div>
            <h1>Relatório de Proventos</h1>

            <p>
                Direitos calculados pela posição histórica
                na Data COM em <?= (int)$ano ?>.
            </p>
        </div>

        <div class="proventos-rel-acoes">

            <form
                method="GET"
                class="proventos-rel-filtro"
            >

                <div class="proventos-rel-campo">
                    <label for="ano">
                        Ano
                    </label>

                    <select
                        name="ano"
                        id="ano"
                    >
                        <?php foreach (
                            $anosDisponiveis as $anoOpcao
                        ) { ?>

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
                </div>

                <div class="proventos-rel-campo">
                    <label for="tipo_ativo">
                        Tipo de Ativo
                    </label>

                    <select
                        name="tipo_ativo"
                        id="tipo_ativo"
                    >
                        <option value="">
                            Todos
                        </option>

                        <?php foreach (
                            $tiposAtivoPermitidos
                            as $valorTipo => $nomeTipo
                        ) { ?>

                            <option
                                value="<?= relProventoEscape(
                                    $valorTipo
                                ) ?>"
                                <?= $tipoAtivo === $valorTipo
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= relProventoEscape(
                                    $nomeTipo
                                ) ?>
                            </option>

                        <?php } ?>
                    </select>
                </div>

                <div class="proventos-rel-campo">
                    <label for="ticker">
                        Ativo
                    </label>

                    <select
                        name="ticker"
                        id="ticker"
                    >
                        <option value="">
                            Todos
                        </option>

                        <?php foreach (
                            $ativosDisponiveis as $ativo
                        ) { ?>

                            <option
                                value="<?= relProventoEscape(
                                    $ativo['ticker']
                                ) ?>"
                                <?= $ticker === $ativo['ticker']
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= relProventoEscape(
                                    $ativo['ticker']
                                ) ?>
                                -
                                <?= relProventoEscape(
                                    relProventoTipoAtivo(
                                        $ativo['tipo_ativo']
                                    )
                                ) ?>
                            </option>

                        <?php } ?>
                    </select>
                </div>

                <button
                    type="submit"
                    class="proventos-rel-filtrar"
                >
                    Filtrar
                </button>

            </form>

            <button
                type="button"
                class="proventos-rel-imprimir"
                onclick="window.print()"
            >
                Imprimir
            </button>

        </div>

    </div>

    <?php if ($erroRelatorio !== '') { ?>

        <p class="proventos-rel-aviso">
            <?= relProventoEscape(
                $erroRelatorio
            ) ?>
        </p>

    <?php } ?>

    <section class="proventos-rel-secao">

        <div class="proventos-rel-titulo">
            <div>
                <h2>Resumo do período</h2>

                <span>
                    Valores calculados pela Data COM.
                    A data de pagamento não representa
                    confirmação de recebimento.
                </span>
            </div>
        </div>

        <div class="proventos-rel-resumo">

            <div class="proventos-rel-card">
                <span>Total de Proventos</span>

                <strong>
                    <?= relProventoBRL(
                        $totalProventos
                    ) ?>
                </strong>
            </div>

            <div class="proventos-rel-card">
                <span>Dividendos</span>

                <strong>
                    <?= relProventoBRL(
                        $totalDividendos
                    ) ?>
                </strong>
            </div>

            <div class="proventos-rel-card">
                <span>JCP</span>

                <strong>
                    <?= relProventoBRL(
                        $totalJCP
                    ) ?>
                </strong>
            </div>

            <div class="proventos-rel-card">
                <span>Rendimentos</span>

                <strong>
                    <?= relProventoBRL(
                        $totalRendimentos
                    ) ?>
                </strong>
            </div>

        </div>

    </section>

    <section class="proventos-rel-secao">

        <div class="proventos-rel-titulo">
            <div>
                <h2>Proventos por classe</h2>

                <span>
                    Distribuição dos direitos por
                    tipo de investimento.
                </span>
            </div>
        </div>

        <div class="proventos-rel-classes">

            <?php foreach (
                $tiposAtivoPermitidos
                as $codigoTipo => $nomeTipo
            ) { ?>

                <div class="proventos-rel-classe">

                    <span
                        class="av-tipo-ativo av-tipo-<?= relProventoEscape(
                            $codigoTipo
                        ) ?>"
                    >
                        <?= relProventoEscape(
                            $nomeTipo
                        ) ?>
                    </span>

                    <strong>
                        <?= relProventoBRL(
                            $totaisClasse[$codigoTipo]
                        ) ?>
                    </strong>

                </div>

            <?php } ?>

        </div>

    </section>

    <section class="proventos-rel-secao">

        <div class="proventos-rel-titulo">
            <div>
                <h2>Evolução mensal</h2>

                <span>
                    Distribuição dos proventos conforme
                    o mês da Data COM.
                </span>
            </div>
        </div>

        <div class="proventos-rel-grafico">
            <div class="proventos-rel-grafico-wrapper">

                <canvas
                    id="graficoProventos"
                ></canvas>

            </div>
        </div>

    </section>

    <section class="proventos-rel-secao">

        <div class="proventos-rel-titulo">
            <div>
                <h2>Resumo mensal</h2>

                <span>
                    Conferência dos valores utilizados
                    na evolução anual.
                </span>
            </div>
        </div>

        <div class="proventos-rel-tabela-wrapper">

            <table class="proventos-rel-tabela">

                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Dividendos</th>
                        <th>JCP</th>
                        <th>Rendimentos</th>
                        <th>Total</th>
                        <th>Eventos</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach (
                    $meses as $numero => $nome
                ) { ?>

                    <?php
                    $classeMesAtual =
                        $ano === $anoAtual &&
                        $numero === $mesAtual
                            ? 'mes-atual'
                            : '';
                    ?>

                    <tr class="<?= $classeMesAtual ?>">

                        <td>
                            <?= relProventoEscape(
                                $nome
                            ) ?>

                            <?php if (
                                $ano === $anoAtual &&
                                $numero === $mesAtual
                            ) { ?>

                                <span
                                    class="indicador-mes-atual"
                                >
                                    Atual
                                </span>

                            <?php } ?>
                        </td>

                        <td>
                            <?= relProventoBRL(
                                $dadosMensais[$numero]['div']
                            ) ?>
                        </td>

                        <td>
                            <?= relProventoBRL(
                                $dadosMensais[$numero]['jcp']
                            ) ?>
                        </td>

                        <td>
                            <?= relProventoBRL(
                                $dadosMensais[$numero]['rend']
                            ) ?>
                        </td>

                        <td>
                            <strong>
                                <?= relProventoBRL(
                                    $dadosMensais[$numero]['total']
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <?= (int)$dadosMensais[
                                $numero
                            ]['eventos'] ?>
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
                                <?= relProventoBRL(
                                    $totalDividendos
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relProventoBRL(
                                    $totalJCP
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relProventoBRL(
                                    $totalRendimentos
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relProventoBRL(
                                    $totalProventos
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= count($eventos) ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </section>

    <section class="proventos-rel-secao">

        <div class="proventos-rel-titulo">
            <div>
                <h2>Detalhamento dos Proventos</h2>

                <span>
                    Cada linha representa um evento
                    individual cadastrado no módulo
                    Proventos.
                </span>
            </div>
        </div>

        <div class="proventos-rel-tabela-wrapper">

            <table class="proventos-rel-tabela">

                <thead>
                    <tr>
                        <th>Ativo</th>
                        <th>Tipo</th>
                        <th>Provento</th>
                        <th>Data COM</th>
                        <th>Pagamento</th>
                        <th>Quantidade</th>
                        <th>Valor por Unidade</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($eventos)) { ?>

                    <tr>
                        <td colspan="8">
                            Nenhum provento elegível
                            encontrado para os filtros
                            selecionados.
                        </td>
                    </tr>

                <?php } else { ?>

                    <?php foreach (
                        $eventos as $evento
                    ) { ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= relProventoEscape(
                                        $evento['ticker']
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <span
                                    class="av-tipo-ativo av-tipo-<?= relProventoEscape(
                                        $evento['tipo_ativo']
                                    ) ?>"
                                >
                                    <?= relProventoEscape(
                                        relProventoTipoAtivo(
                                            $evento['tipo_ativo']
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= relProventoEscape(
                                    relProventoTipo(
                                        $evento['tipo']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= relProventoEscape(
                                    relProventoData(
                                        $evento['datacom']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= relProventoEscape(
                                    relProventoData(
                                        $evento['datapag']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (int)$evento[
                                        'quantidade_elegivel'
                                    ],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= relProventoEscape(
                                    relProventoValorUnidade(
                                        $evento['valor']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= relProventoBRL(
                                        $evento['total_evento']
                                    ) ?>
                                </strong>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="7">
                            <strong>Total</strong>
                        </td>

                        <td>
                            <strong>
                                <?= relProventoBRL(
                                    $totalProventos
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </section>

    <?php if (!$conferenciaCorreta) { ?>

        <p class="proventos-rel-aviso">
            Atenção: foi encontrada divergência
            na conferência interna dos totais.
        </p>

    <?php } ?>

</main>

<?php include("../../includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labelsProventos =
    <?= json_encode(
        $labelsGrafico,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const totaisProventos =
    <?= json_encode(
        $totaisGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

function formatarBRLProventos(valor) {
    return 'R$ ' +
        Number(valor).toLocaleString(
            'pt-BR',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
}

const canvasProventos =
    document.getElementById(
        'graficoProventos'
    );

if (canvasProventos) {
    new Chart(
        canvasProventos.getContext('2d'),
        {
            type: 'bar',

            data: {
                labels: labelsProventos,

                datasets: [
                    {
                        label: 'Proventos',
                        data: totaisProventos
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
                                return formatarBRLProventos(
                                    valor
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
                                return (
                                    'Proventos: ' +
                                    formatarBRLProventos(
                                        contexto.raw
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
    );
}
</script>

</body>
</html>