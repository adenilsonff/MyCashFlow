<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__ . '/../../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$anoAtual = (int)date('Y');
$mesAtual = (int)date('n');

$ano = isset($_GET['ano'])
    ? (int)$_GET['ano']
    : $anoAtual;

if ($ano < 2000 || $ano > 2100) {
    $ano = $anoAtual;
}

$corretoraId = isset($_GET['corretora_id'])
    ? (int)$_GET['corretora_id']
    : 0;

if ($corretoraId < 0) {
    $corretoraId = 0;
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

function relDayTradeEscape($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function relDayTradeBRL($valor)
{
    return 'R$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function relDayTradeNumero($valor)
{
    return number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function relDayTradeInteiro($valor)
{
    return number_format(
        (int)$valor,
        0,
        ',',
        '.'
    );
}

function relDayTradeData($data)
{
    if ($data === null || $data === '') {
        return '-';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y', $timestamp);
}

function relDayTradeClasseResultado($valor)
{
    $valor = (float)$valor;

    if ($valor > 0.00001) {
        return 'resultado-positivo';
    }

    if ($valor < -0.00001) {
        return 'resultado-negativo';
    }

    return 'resultado-neutro';
}

$erroRelatorio = '';

$anosDisponiveis = [];

try {
    $stmt = $conn->prepare("
        SELECT DISTINCT YEAR(data) AS ano
        FROM (SELECT * FROM operacoes WHERE usuario_id = @mcf_usuario_id) AS operacoes
        WHERE data IS NOT NULL
        ORDER BY ano DESC
    ");

    $stmt->execute();

    $resultadoAnos = $stmt->get_result();

    while ($row = $resultadoAnos->fetch_assoc()) {
        $anoBanco = (int)$row['ano'];

        if ($anoBanco >= 2000 && $anoBanco <= 2100) {
            $anosDisponiveis[] = $anoBanco;
        }
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log(
        'MyCashFlow relatório day trade anos: ' .
        mcfMensagemErro($e)
    );
}

if (!in_array($ano, $anosDisponiveis, true)) {
    $anosDisponiveis[] = $ano;
}

if (!in_array($anoAtual, $anosDisponiveis, true)) {
    $anosDisponiveis[] = $anoAtual;
}

rsort($anosDisponiveis);

$corretorasDisponiveis = [];

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            nome
        FROM (SELECT * FROM corretoras WHERE usuario_id = @mcf_usuario_id) AS corretoras
        ORDER BY nome ASC
    ");

    $stmt->execute();

    $resultadoCorretoras = $stmt->get_result();

    while ($row = $resultadoCorretoras->fetch_assoc()) {
        $corretorasDisponiveis[] = [
            'id' => (int)$row['id'],
            'nome' => $row['nome']
        ];
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log(
        'MyCashFlow relatório day trade corretoras: ' .
        mcfMensagemErro($e)
    );
}

if ($corretoraId > 0) {
    $corretoraValida = false;

    foreach ($corretorasDisponiveis as $corretora) {
        if ($corretora['id'] === $corretoraId) {
            $corretoraValida = true;
            break;
        }
    }

    if (!$corretoraValida) {
        $corretoraId = 0;
    }
}

$operacoes = [];

try {
    $sql = "
        SELECT
            o.id,
            o.corretora_id,
            c.nome AS corretora_nome,
            o.data,
            o.acao,
            o.quantidade,
            o.valor_compra,
            o.valor_venda,
            o.total_compra,
            o.total_venda,
            o.valor_operacao,
            o.lucro_bruto,
            o.taxas,
            o.deducao_1,
            o.imposto_20,
            o.lucro_desc,
            o.darf,
            o.lucro_final
        FROM (SELECT * FROM operacoes WHERE usuario_id = @mcf_usuario_id) o
        INNER JOIN (SELECT * FROM corretoras WHERE usuario_id = @mcf_usuario_id) c
            ON c.id = o.corretora_id
        WHERE YEAR(o.data) = ?
    ";

    if ($corretoraId > 0) {
        $sql .= "
            AND o.corretora_id = ?
        ";
    }

    $sql .= "
        ORDER BY
            o.data DESC,
            o.id DESC
    ";

    $stmt = $conn->prepare($sql);

    if ($corretoraId > 0) {
        $stmt->bind_param(
            'ii',
            $ano,
            $corretoraId
        );
    } else {
        $stmt->bind_param(
            'i',
            $ano
        );
    }

    $stmt->execute();

    $operacoes = $stmt
        ->get_result()
        ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (Throwable $e) {
    $erroRelatorio =
        'Não foi possível carregar as operações de Day Trade do período selecionado.';

    error_log(
        'MyCashFlow relatório day trade: ' .
        mcfMensagemErro($e)
    );
}

$totalVolume = 0.0;
$totalResultadoBruto = 0.0;
$totalTaxas = 0.0;
$totalDarf = 0.0;
$totalResultadoFinal = 0.0;

$totalRegistros = count($operacoes);
$totalOperacoesConcluidas = 0;

$dadosMensais = [];

for ($mes = 1; $mes <= 12; $mes++) {
    $dadosMensais[$mes] = [
        'operacoes' => 0,
        'registros' => 0,
        'volume' => 0.0,
        'resultado_bruto' => 0.0,
        'taxas' => 0.0,
        'darf' => 0.0,
        'resultado_final' => 0.0
    ];
}

$dadosAtivos = [];

foreach ($operacoes as $operacao) {
    $totalCompra =
        (float)$operacao['total_compra'];

    $totalVenda =
        (float)$operacao['total_venda'];

    $volume =
        (float)$operacao['valor_operacao'];

    $resultadoBruto =
        (float)$operacao['lucro_bruto'];

    $taxas =
        (float)$operacao['taxas'];

    $darf =
        (float)$operacao['darf'];

    $resultadoFinal =
        (float)$operacao['lucro_final'];

    $operacaoConcluida =
        $totalCompra > 0 &&
        $totalVenda > 0;

    $totalVolume += $volume;
    $totalResultadoBruto += $resultadoBruto;
    $totalTaxas += $taxas;
    $totalDarf += $darf;
    $totalResultadoFinal += $resultadoFinal;

    if ($operacaoConcluida) {
        $totalOperacoesConcluidas++;
    }

    $mesOperacao = (int)date(
        'n',
        strtotime($operacao['data'])
    );

    if (
        $mesOperacao >= 1 &&
        $mesOperacao <= 12
    ) {
        $dadosMensais[$mesOperacao]['registros']++;

        if ($operacaoConcluida) {
            $dadosMensais[$mesOperacao]['operacoes']++;
        }

        $dadosMensais[$mesOperacao]['volume'] +=
            $volume;

        $dadosMensais[$mesOperacao]['resultado_bruto'] +=
            $resultadoBruto;

        $dadosMensais[$mesOperacao]['taxas'] +=
            $taxas;

        $dadosMensais[$mesOperacao]['darf'] +=
            $darf;

        $dadosMensais[$mesOperacao]['resultado_final'] +=
            $resultadoFinal;
    }

    $ativo = strtoupper(
        trim((string)$operacao['acao'])
    );

    if ($ativo === '') {
        $ativo = 'SEM ATIVO';
    }

    if (!isset($dadosAtivos[$ativo])) {
        $dadosAtivos[$ativo] = [
            'operacoes' => 0,
            'registros' => 0,
            'volume' => 0.0,
            'resultado_bruto' => 0.0,
            'taxas' => 0.0,
            'darf' => 0.0,
            'resultado_final' => 0.0
        ];
    }

    $dadosAtivos[$ativo]['registros']++;

    if ($operacaoConcluida) {
        $dadosAtivos[$ativo]['operacoes']++;
    }

    $dadosAtivos[$ativo]['volume'] +=
        $volume;

    $dadosAtivos[$ativo]['resultado_bruto'] +=
        $resultadoBruto;

    $dadosAtivos[$ativo]['taxas'] +=
        $taxas;

    $dadosAtivos[$ativo]['darf'] +=
        $darf;

    $dadosAtivos[$ativo]['resultado_final'] +=
        $resultadoFinal;
}

uksort(
    $dadosAtivos,
    function ($a, $b) {
        return strnatcasecmp($a, $b);
    }
);

$labelsGrafico = [];
$resultadosGrafico = [];

foreach ($meses as $numero => $nome) {
    $labelsGrafico[] = $nome;

    $resultadosGrafico[] =
        round(
            $dadosMensais[$numero]['resultado_final'],
            2
        );
}

$conferenciaMensal = [
    'volume' => 0.0,
    'resultado_bruto' => 0.0,
    'taxas' => 0.0,
    'darf' => 0.0,
    'resultado_final' => 0.0
];

foreach ($dadosMensais as $dadosMes) {
    $conferenciaMensal['volume'] +=
        $dadosMes['volume'];

    $conferenciaMensal['resultado_bruto'] +=
        $dadosMes['resultado_bruto'];

    $conferenciaMensal['taxas'] +=
        $dadosMes['taxas'];

    $conferenciaMensal['darf'] +=
        $dadosMes['darf'];

    $conferenciaMensal['resultado_final'] +=
        $dadosMes['resultado_final'];
}

$conferenciaAtivos = [
    'volume' => 0.0,
    'resultado_bruto' => 0.0,
    'taxas' => 0.0,
    'darf' => 0.0,
    'resultado_final' => 0.0
];

foreach ($dadosAtivos as $dadosAtivo) {
    $conferenciaAtivos['volume'] +=
        $dadosAtivo['volume'];

    $conferenciaAtivos['resultado_bruto'] +=
        $dadosAtivo['resultado_bruto'];

    $conferenciaAtivos['taxas'] +=
        $dadosAtivo['taxas'];

    $conferenciaAtivos['darf'] +=
        $dadosAtivo['darf'];

    $conferenciaAtivos['resultado_final'] +=
        $dadosAtivo['resultado_final'];
}

$toleranciaConferencia = 0.01;

$conferenciaCorreta =
    abs(
        $totalVolume -
        $conferenciaMensal['volume']
    ) < $toleranciaConferencia &&
    abs(
        $totalResultadoBruto -
        $conferenciaMensal['resultado_bruto']
    ) < $toleranciaConferencia &&
    abs(
        $totalTaxas -
        $conferenciaMensal['taxas']
    ) < $toleranciaConferencia &&
    abs(
        $totalDarf -
        $conferenciaMensal['darf']
    ) < $toleranciaConferencia &&
    abs(
        $totalResultadoFinal -
        $conferenciaMensal['resultado_final']
    ) < $toleranciaConferencia &&
    abs(
        $totalVolume -
        $conferenciaAtivos['volume']
    ) < $toleranciaConferencia &&
    abs(
        $totalResultadoBruto -
        $conferenciaAtivos['resultado_bruto']
    ) < $toleranciaConferencia &&
    abs(
        $totalTaxas -
        $conferenciaAtivos['taxas']
    ) < $toleranciaConferencia &&
    abs(
        $totalDarf -
        $conferenciaAtivos['darf']
    ) < $toleranciaConferencia &&
    abs(
        $totalResultadoFinal -
        $conferenciaAtivos['resultado_final']
    ) < $toleranciaConferencia;
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Relatório de Day Trade</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-daytrade.css?v=1"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="daytrade-rel-layout">

    <div class="daytrade-rel-cabecalho">

        <div>
            <h1>Relatório de Day Trade</h1>

            <p>
                Consolidação das operações registradas em
                <?= (int)$ano ?>.
            </p>
        </div>

        <div class="daytrade-rel-acoes">

            <form
                method="GET"
                class="daytrade-rel-filtro"
            >

                <div class="daytrade-rel-campo">
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

                <div class="daytrade-rel-campo">
                    <label for="corretora_id">
                        Corretora
                    </label>

                    <select
                        name="corretora_id"
                        id="corretora_id"
                    >
                        <option value="0">
                            Todas
                        </option>

                        <?php foreach (
                            $corretorasDisponiveis as $corretora
                        ) { ?>

                            <option
                                value="<?= (int)$corretora['id'] ?>"
                                <?= $corretoraId === $corretora['id']
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= relDayTradeEscape(
                                    $corretora['nome']
                                ) ?>
                            </option>

                        <?php } ?>
                    </select>
                </div>

                <button
                    type="submit"
                    class="daytrade-rel-filtrar"
                >
                    Filtrar
                </button>

            </form>

            <button
                type="button"
                class="daytrade-rel-imprimir"
                onclick="window.print()"
            >
                Imprimir
            </button>

        </div>

    </div>

    <?php if ($erroRelatorio !== '') { ?>

        <p class="daytrade-rel-aviso">
            <?= relDayTradeEscape(
                $erroRelatorio
            ) ?>
        </p>

    <?php } ?>

    <section class="daytrade-rel-secao">

        <div class="daytrade-rel-titulo">
            <div>
                <h2>Resumo do período</h2>

                <span>
                    Valores históricos armazenados nas
                    operações de Day Trade.
                </span>
            </div>
        </div>

        <div class="daytrade-rel-resumo">

            <div class="daytrade-rel-card">
                <span>Volume Negociado</span>

                <strong>
                    <?= relDayTradeBRL(
                        $totalVolume
                    ) ?>
                </strong>
            </div>

            <div class="daytrade-rel-card">
                <span>Resultado Bruto</span>

                <strong
                    class="<?= relDayTradeClasseResultado(
                        $totalResultadoBruto
                    ) ?>"
                >
                    <?= relDayTradeBRL(
                        $totalResultadoBruto
                    ) ?>
                </strong>
            </div>

            <div class="daytrade-rel-card">
                <span>Taxas</span>

                <strong>
                    <?= relDayTradeBRL(
                        $totalTaxas
                    ) ?>
                </strong>
            </div>

            <div class="daytrade-rel-card">
                <span>DARF</span>

                <strong>
                    <?= relDayTradeBRL(
                        $totalDarf
                    ) ?>
                </strong>
            </div>

            <div class="daytrade-rel-card">
                <span>Resultado Final</span>

                <strong
                    class="<?= relDayTradeClasseResultado(
                        $totalResultadoFinal
                    ) ?>"
                >
                    <?= relDayTradeBRL(
                        $totalResultadoFinal
                    ) ?>
                </strong>
            </div>

        </div>

    </section>

    <section class="daytrade-rel-secao">

        <div class="daytrade-rel-titulo">
            <div>
                <h2>Evolução mensal</h2>

                <span>
                    Resultado Final registrado em cada mês
                    do ano selecionado.
                </span>
            </div>
        </div>

        <div class="daytrade-rel-grafico">
            <div class="daytrade-rel-grafico-wrapper">

                <canvas
                    id="graficoDayTrade"
                ></canvas>

            </div>
        </div>

    </section>

    <section class="daytrade-rel-secao">

        <div class="daytrade-rel-titulo">
            <div>
                <h2>Resumo mensal</h2>

                <span>
                    Operações são contabilizadas quando
                    possuem compra e venda registradas.
                </span>
            </div>
        </div>

        <div class="daytrade-rel-tabela-wrapper">

            <table class="daytrade-rel-tabela">

                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Operações</th>
                        <th>Volume</th>
                        <th>Resultado Bruto</th>
                        <th>Taxas</th>
                        <th>DARF</th>
                        <th>Resultado Final</th>
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
                            <?= relDayTradeEscape(
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
                            <?= relDayTradeInteiro(
                                $dadosMensais[$numero]['operacoes']
                            ) ?>
                        </td>

                        <td>
                            <?= relDayTradeBRL(
                                $dadosMensais[$numero]['volume']
                            ) ?>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $dadosMensais[$numero]['resultado_bruto']
                            ) ?>"
                        >
                            <?= relDayTradeBRL(
                                $dadosMensais[$numero]['resultado_bruto']
                            ) ?>
                        </td>

                        <td>
                            <?= relDayTradeBRL(
                                $dadosMensais[$numero]['taxas']
                            ) ?>
                        </td>

                        <td>
                            <?= relDayTradeBRL(
                                $dadosMensais[$numero]['darf']
                            ) ?>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $dadosMensais[$numero]['resultado_final']
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $dadosMensais[$numero]['resultado_final']
                                ) ?>
                            </strong>
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
                                <?= relDayTradeInteiro(
                                    $totalOperacoesConcluidas
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalVolume
                                ) ?>
                            </strong>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $totalResultadoBruto
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalResultadoBruto
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalTaxas
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalDarf
                                ) ?>
                            </strong>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $totalResultadoFinal
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalResultadoFinal
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </section>

    <section class="daytrade-rel-secao">

        <div class="daytrade-rel-titulo">
            <div>
                <h2>Resultado por ativo</h2>

                <span>
                    Consolidação das operações por ativo
                    no período selecionado.
                </span>
            </div>
        </div>

        <div class="daytrade-rel-tabela-wrapper">

            <table class="daytrade-rel-tabela">

                <thead>
                    <tr>
                        <th>Ativo</th>
                        <th>Operações</th>
                        <th>Volume</th>
                        <th>Resultado Bruto</th>
                        <th>Taxas</th>
                        <th>DARF</th>
                        <th>Resultado Final</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($dadosAtivos)) { ?>

                    <tr>
                        <td colspan="7">
                            Nenhuma operação encontrada
                            para os filtros selecionados.
                        </td>
                    </tr>

                <?php } else { ?>

                    <?php foreach (
                        $dadosAtivos as $ativo => $dadosAtivo
                    ) { ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= relDayTradeEscape(
                                        $ativo
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= relDayTradeInteiro(
                                    $dadosAtivo['operacoes']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $dadosAtivo['volume']
                                ) ?>
                            </td>

                            <td
                                class="<?= relDayTradeClasseResultado(
                                    $dadosAtivo['resultado_bruto']
                                ) ?>"
                            >
                                <?= relDayTradeBRL(
                                    $dadosAtivo['resultado_bruto']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $dadosAtivo['taxas']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $dadosAtivo['darf']
                                ) ?>
                            </td>

                            <td
                                class="<?= relDayTradeClasseResultado(
                                    $dadosAtivo['resultado_final']
                                ) ?>"
                            >
                                <strong>
                                    <?= relDayTradeBRL(
                                        $dadosAtivo['resultado_final']
                                    ) ?>
                                </strong>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

                </tbody>

                <tfoot>
                    <tr>
                        <td>
                            <strong>Total</strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeInteiro(
                                    $totalOperacoesConcluidas
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalVolume
                                ) ?>
                            </strong>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $totalResultadoBruto
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalResultadoBruto
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalTaxas
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalDarf
                                ) ?>
                            </strong>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $totalResultadoFinal
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalResultadoFinal
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </section>

    <section class="daytrade-rel-secao">

        <div class="daytrade-rel-titulo">
            <div>
                <h2>Detalhamento das operações</h2>

                <span>
                    Cada linha representa um registro
                    existente no módulo Day Trade.
                </span>
            </div>
        </div>

        <div class="daytrade-rel-tabela-wrapper">

            <table
                class="daytrade-rel-tabela daytrade-rel-tabela-detalhes"
            >

                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Corretora</th>
                        <th>Ativo</th>
                        <th>Qtd.</th>
                        <th>Total Compra</th>
                        <th>Total Venda</th>
                        <th>Volume</th>
                        <th>Resultado Bruto</th>
                        <th>Taxas</th>
                        <th>DARF</th>
                        <th>Resultado Final</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($operacoes)) { ?>

                    <tr>
                        <td colspan="11">
                            Nenhum registro de Day Trade
                            encontrado para os filtros
                            selecionados.
                        </td>
                    </tr>

                <?php } else { ?>

                    <?php foreach (
                        $operacoes as $operacao
                    ) { ?>

                        <tr>

                            <td>
                                <?= relDayTradeEscape(
                                    relDayTradeData(
                                        $operacao['data']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeEscape(
                                    $operacao['corretora_nome']
                                ) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= relDayTradeEscape(
                                        strtoupper(
                                            trim(
                                                (string)$operacao['acao']
                                            )
                                        )
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <?= relDayTradeInteiro(
                                    $operacao['quantidade']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $operacao['total_compra']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $operacao['total_venda']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $operacao['valor_operacao']
                                ) ?>
                            </td>

                            <td
                                class="<?= relDayTradeClasseResultado(
                                    $operacao['lucro_bruto']
                                ) ?>"
                            >
                                <?= relDayTradeBRL(
                                    $operacao['lucro_bruto']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $operacao['taxas']
                                ) ?>
                            </td>

                            <td>
                                <?= relDayTradeBRL(
                                    $operacao['darf']
                                ) ?>
                            </td>

                            <td
                                class="<?= relDayTradeClasseResultado(
                                    $operacao['lucro_final']
                                ) ?>"
                            >
                                <strong>
                                    <?= relDayTradeBRL(
                                        $operacao['lucro_final']
                                    ) ?>
                                </strong>
                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="3">
                            <strong>Total</strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeInteiro(
                                    $totalRegistros
                                ) ?>
                                registros
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    array_sum(
                                        array_map(
                                            function ($operacao) {
                                                return (float)$operacao[
                                                    'total_compra'
                                                ];
                                            },
                                            $operacoes
                                        )
                                    )
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    array_sum(
                                        array_map(
                                            function ($operacao) {
                                                return (float)$operacao[
                                                    'total_venda'
                                                ];
                                            },
                                            $operacoes
                                        )
                                    )
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalVolume
                                ) ?>
                            </strong>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $totalResultadoBruto
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalResultadoBruto
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalTaxas
                                ) ?>
                            </strong>
                        </td>

                        <td>
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalDarf
                                ) ?>
                            </strong>
                        </td>

                        <td
                            class="<?= relDayTradeClasseResultado(
                                $totalResultadoFinal
                            ) ?>"
                        >
                            <strong>
                                <?= relDayTradeBRL(
                                    $totalResultadoFinal
                                ) ?>
                            </strong>
                        </td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </section>

    <?php if (!$conferenciaCorreta) { ?>

        <p class="daytrade-rel-aviso">
            Atenção: foi encontrada divergência
            na conferência interna dos totais do relatório.
        </p>

    <?php } ?>

</main>

<?php include("../../includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labelsDayTrade =
    <?= json_encode(
        $labelsGrafico,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const resultadosDayTrade =
    <?= json_encode(
        $resultadosGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

function formatarBRLDayTrade(valor) {
    return Number(valor).toLocaleString(
        'pt-BR',
        {
            style: 'currency',
            currency: 'BRL',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );
}

const canvasDayTrade =
    document.getElementById(
        'graficoDayTrade'
    );

if (canvasDayTrade) {
    new Chart(
        canvasDayTrade.getContext('2d'),
        {
            type: 'bar',

            data: {
                labels: labelsDayTrade,

                datasets: [
                    {
                        label: 'Resultado Final',
                        data: resultadosDayTrade,

                        backgroundColor: function(contexto) {
                            const valor =
                                Number(contexto.raw);

                            if (valor > 0) {
                                return 'rgba(46, 125, 50, 0.70)';
                            }

                            if (valor < 0) {
                                return 'rgba(198, 40, 40, 0.70)';
                            }

                            return 'rgba(108, 117, 125, 0.55)';
                        },

                        borderColor: function(contexto) {
                            const valor =
                                Number(contexto.raw);

                            if (valor > 0) {
                                return 'rgba(46, 125, 50, 1)';
                            }

                            if (valor < 0) {
                                return 'rgba(198, 40, 40, 1)';
                            }

                            return 'rgba(108, 117, 125, 1)';
                        },

                        borderWidth: 1
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
                                return formatarBRLDayTrade(
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
                                    'Resultado Final: ' +
                                    formatarBRLDayTrade(
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