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

require_once __DIR__ . '/../../includes/mercado_api.php';

$usuario_id = mcfDonoId();

$anoAtual = (int)date('Y');
$mesAtual = (int)date('n');

$ano = isset($_GET['ano'])
    ? (int)$_GET['ano']
    : $anoAtual;

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

function relInvestEscape($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function relInvestTipoNacional($tipo)
{
    $tipos = [
        'acao' => 'Ação',
        'fii' => 'FII',
        'etf' => 'ETF',
        'bdr' => 'BDR'
    ];

    return $tipos[$tipo] ?? $tipo;
}

function relInvestTipoInternacional($tipo)
{
    $tipos = [
        'stock' => 'Stock',
        'etf' => 'ETF',
        'reit' => 'REIT',
        'adr' => 'ADR'
    ];

    return $tipos[$tipo] ?? $tipo;
}

function relInvestBRL($valor)
{
    return 'R$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function relInvestUSD($valor)
{
    return 'US$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function relInvestPercentual($valor)
{
    if ($valor === null) {
        return 'Indisponível';
    }

    return
        ((float)$valor > 0 ? '+' : '') .
        number_format(
            (float)$valor,
            2,
            ',',
            '.'
        ) .
        '%';
}

function relInvestQuantidadeInternacional($valor)
{
    $formatado = number_format(
        (float)$valor,
        8,
        ',',
        '.'
    );

    $formatado = rtrim($formatado, '0');
    $formatado = rtrim($formatado, ',');

    return $formatado;
}

function relInvestClasseResultado($valor)
{
    if ($valor === null) {
        return '';
    }

    if ((float)$valor > 0) {
        return 'resultado-positivo';
    }

    if ((float)$valor < 0) {
        return 'resultado-negativo';
    }

    return '';
}

function relInvestConsolidarNacional(array $operacoes)
{
    usort(
        $operacoes,
        function ($a, $b) {
            return strcmp(
                $a['data'],
                $b['data']
            ) ?: (
                (int)$a['id'] <=>
                (int)$b['id']
            );
        }
    );

    $posicoes = [];

    foreach ($operacoes as $op) {
        $ticker = strtoupper(
            trim($op['ticker'])
        );

        $tipoAtivo =
            $op['tipo_ativo'] ?? '';

        $chave =
            $ticker . '|' . $tipoAtivo;

        if (!isset($posicoes[$chave])) {
            $posicoes[$chave] = [
                'ticker' => $ticker,
                'tipo_ativo' => $tipoAtivo,
                'quantidade_total' => 0,
                'total_investido' => 0.0,
                'valor_medio_ponderado' => 0.0,
                'cotacao_atual' => null,
                'valor_atual' => null,
                'resultado' => null
            ];
        }

        $p = &$posicoes[$chave];

        $quantidade =
            abs((int)$op['quantidade']);

        $preco =
            (float)$op['valor_unitario'];

        $tipoOperacao =
            $op['tipo_operacao'] ?? '';

        if (
            $quantidade === 0 ||
            $preco <= 0 ||
            !is_finite($preco) ||
            !in_array(
                $tipoOperacao,
                ['compra', 'venda'],
                true
            ) ||
            !in_array(
                $tipoAtivo,
                ['acao', 'fii', 'etf', 'bdr'],
                true
            ) ||
            (
                $tipoOperacao === 'compra' &&
                (int)$op['quantidade'] < 0
            )
        ) {
            throw new DomainException(
                'Operação inválida no histórico de ' .
                $ticker .
                '.'
            );
        }

        if ($tipoOperacao === 'compra') {
            $p['total_investido'] +=
                $quantidade * $preco;

            $p['quantidade_total'] +=
                $quantidade;

            $p['valor_medio_ponderado'] =
                $p['total_investido'] /
                $p['quantidade_total'];
        } else {
            if (
                $quantidade >
                $p['quantidade_total']
            ) {
                throw new DomainException(
                    'Venda de ' .
                    $quantidade .
                    ' unidades de ' .
                    $ticker .
                    ' excede a posição disponível.'
                );
            }

            $p['quantidade_total'] -=
                $quantidade;

            $p['total_investido'] =
                $p['quantidade_total'] *
                $p['valor_medio_ponderado'];

            if (
                $p['quantidade_total'] === 0
            ) {
                $p['total_investido'] = 0.0;
                $p['valor_medio_ponderado'] =
                    0.0;
            }
        }

        unset($p);
    }

    ksort($posicoes);

    return array_filter(
        $posicoes,
        function ($posicao) {
            return
                $posicao['quantidade_total'] >
                0;
        }
    );
}

function relInvestConsolidarInternacional(
    array $operacoes
) {
    usort(
        $operacoes,
        function ($a, $b) {
            return strcmp(
                $a['data'],
                $b['data']
            ) ?: (
                (int)$a['id'] <=>
                (int)$b['id']
            );
        }
    );

    $posicoes = [];

    foreach ($operacoes as $op) {
        $ticker = strtoupper(
            trim($op['ticker'])
        );

        $tipoAtivo =
            $op['tipo_ativo'] ?? '';

        $chave =
            $ticker . '|' . $tipoAtivo;

        if (!isset($posicoes[$chave])) {
            $posicoes[$chave] = [
                'ticker' => $ticker,
                'tipo_ativo' => $tipoAtivo,
                'quantidade_total' => '0',
                'total_investido_usd' => '0',
                'valor_medio_ponderado' => '0',
                'cotacao_atual' => null,
                'valor_atual' => null,
                'resultado' => null
            ];
        }

        $p = &$posicoes[$chave];

        $quantidade = ltrim(
            (string)$op['quantidade'],
            '-'
        );

        $preco =
            (string)$op['valor_unitario'];

        $tipoOperacao =
            $op['tipo_operacao'] ?? '';

        if (
            !in_array(
                $tipoAtivo,
                ['stock', 'etf', 'reit', 'adr'],
                true
            ) ||
            !in_array(
                $tipoOperacao,
                ['compra', 'venda'],
                true
            ) ||
            bccomp(
                $quantidade,
                '0',
                8
            ) <= 0 ||
            bccomp(
                $preco,
                '0',
                4
            ) <= 0 ||
            (
                $tipoOperacao === 'compra' &&
                bccomp(
                    (string)$op['quantidade'],
                    '0',
                    8
                ) < 0
            )
        ) {
            throw new DomainException(
                'Operação inválida no histórico de ' .
                $ticker .
                '.'
            );
        }

        if ($tipoOperacao === 'compra') {
            $p['total_investido_usd'] =
                bcadd(
                    $p['total_investido_usd'],
                    bcmul(
                        $quantidade,
                        $preco,
                        24
                    ),
                    24
                );

            $p['quantidade_total'] =
                bcadd(
                    $p['quantidade_total'],
                    $quantidade,
                    8
                );

            $p['valor_medio_ponderado'] =
                bcdiv(
                    $p['total_investido_usd'],
                    $p['quantidade_total'],
                    24
                );
        } else {
            if (
                bccomp(
                    $quantidade,
                    $p['quantidade_total'],
                    8
                ) > 0
            ) {
                throw new DomainException(
                    'Venda de ' .
                    relInvestQuantidadeInternacional(
                        $quantidade
                    ) .
                    ' unidades de ' .
                    $ticker .
                    ' excede a posição disponível.'
                );
            }

            $p['quantidade_total'] =
                bcsub(
                    $p['quantidade_total'],
                    $quantidade,
                    8
                );

            $p['total_investido_usd'] =
                bcmul(
                    $p['quantidade_total'],
                    $p['valor_medio_ponderado'],
                    24
                );

            if (
                bccomp(
                    $p['quantidade_total'],
                    '0',
                    8
                ) === 0
            ) {
                $p['total_investido_usd'] =
                    '0';

                $p['valor_medio_ponderado'] =
                    '0';
            }
        }

        unset($p);
    }

    ksort($posicoes);

    return array_filter(
        $posicoes,
        function ($posicao) {
            return bccomp(
                $posicao['quantidade_total'],
                '0',
                8
            ) > 0;
        }
    );
}

$erroPosicaoNacional = '';
$erroPosicaoInternacional = '';
$erroCotacaoNacional = '';
$erroCotacaoInternacional = '';
$erroMovimentacao = '';

$historicoNacional = [];
$historicoInternacional = [];

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            ticker,
            tipo_ativo,
            quantidade,
            valor_unitario,
            data,
            tipo_operacao
        FROM investimentos_nacionais
        WHERE usuario_id = ?
        ORDER BY data, id
    ");

    $stmt->bind_param(
        'i',
        $usuario_id
    );

    $stmt->execute();

    $historicoNacional =
        $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (Throwable $e) {
    $erroPosicaoNacional =
        'Não foi possível carregar o histórico nacional.';

    error_log(
        'MyCashFlow relatório investimentos nacional: ' .
        mcfMensagemErro($e)
    );
}

try {
    $stmt = $conn->prepare("
        SELECT
            id,
            ticker,
            tipo_ativo,
            quantidade,
            valor_unitario,
            data,
            tipo_operacao
        FROM investimentos_internacionais
        WHERE usuario_id = ?
        ORDER BY data, id
    ");

    $stmt->bind_param(
        'i',
        $usuario_id
    );

    $stmt->execute();

    $historicoInternacional =
        $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (Throwable $e) {
    $erroPosicaoInternacional =
        'Não foi possível carregar o histórico internacional.';

    error_log(
        'MyCashFlow relatório investimentos internacional: ' .
        mcfMensagemErro($e)
    );
}

$posicoesNacionais = [];
$posicoesInternacionais = [];

if ($erroPosicaoNacional === '') {
    try {
        $posicoesNacionais =
            relInvestConsolidarNacional(
                $historicoNacional
            );
    } catch (Throwable $e) {
        $erroPosicaoNacional =
            $e instanceof DomainException
                ? mcfMensagemErro($e)
                : 'Não foi possível consolidar a carteira nacional.';

        error_log(
            'MyCashFlow relatório consolidação nacional: ' .
            mcfMensagemErro($e)
        );
    }
}

if ($erroPosicaoInternacional === '') {
    try {
        $posicoesInternacionais =
            relInvestConsolidarInternacional(
                $historicoInternacional
            );
    } catch (Throwable $e) {
        $erroPosicaoInternacional =
            $e instanceof DomainException
                ? mcfMensagemErro($e)
                : 'Não foi possível consolidar a carteira internacional.';

        error_log(
            'MyCashFlow relatório consolidação internacional: ' .
            mcfMensagemErro($e)
        );
    }
}

$cotacoesNacionaisCompletas = true;

if (
    $erroPosicaoNacional === '' &&
    !empty($posicoesNacionais)
) {
    try {
        $tickers = array_values(
            array_unique(
                array_column(
                    $posicoesNacionais,
                    'ticker'
                )
            )
        );

        $cotacoes =
            mercadoApi()->stocks(
                $tickers,
                'BRL'
            );

        foreach (
            $posicoesNacionais as &$posicao
        ) {
            $ticker =
                $posicao['ticker'];

            $cotacao =
                $cotacoes[$ticker]['price'] ??
                null;

            if ($cotacao === null) {
                $cotacoesNacionaisCompletas =
                    false;

                continue;
            }

            $cotacao =
                (float)$cotacao;

            $posicao['cotacao_atual'] =
                $cotacao;

            $posicao['valor_atual'] =
                $cotacao *
                $posicao['quantidade_total'];

            $posicao['resultado'] =
                $posicao['valor_atual'] -
                $posicao['total_investido'];
        }

        unset($posicao);

    } catch (Throwable $e) {
        $cotacoesNacionaisCompletas =
            false;

        $erroCotacaoNacional =
            'Não foi possível consultar todas as cotações atuais do mercado nacional.';

        error_log(
            'MyCashFlow relatório cotações nacionais: ' .
            mcfMensagemErro($e)
        );
    }
}

$cotacoesInternacionaisCompletas = true;

if (
    $erroPosicaoInternacional === '' &&
    !empty($posicoesInternacionais)
) {
    try {
        $tickers = array_values(
            array_unique(
                array_column(
                    $posicoesInternacionais,
                    'ticker'
                )
            )
        );

        $cotacoes =
            mercadoApi()->stocks(
                $tickers,
                'USD'
            );

        foreach (
            $posicoesInternacionais as &$posicao
        ) {
            $ticker =
                $posicao['ticker'];

            $cotacao =
                $cotacoes[$ticker]['price'] ??
                null;

            if ($cotacao === null) {
                $cotacoesInternacionaisCompletas =
                    false;

                continue;
            }

            $cotacao =
                (string)$cotacao;

            $posicao['cotacao_atual'] =
                $cotacao;

            $posicao['valor_atual'] =
                bcmul(
                    $cotacao,
                    $posicao['quantidade_total'],
                    24
                );

            $posicao['resultado'] =
                bcsub(
                    $posicao['valor_atual'],
                    $posicao['total_investido_usd'],
                    24
                );
        }

        unset($posicao);

    } catch (Throwable $e) {
        $cotacoesInternacionaisCompletas =
            false;

        $erroCotacaoInternacional =
            'Não foi possível consultar todas as cotações atuais do mercado internacional.';

        error_log(
            'MyCashFlow relatório cotações internacionais: ' .
            mcfMensagemErro($e)
        );
    }
}

$custoAtualNacional = 0.0;
$valorAtualNacional = 0.0;

foreach (
    $posicoesNacionais as $posicao
) {
    $custoAtualNacional +=
        (float)$posicao['total_investido'];

    if (
        $posicao['valor_atual'] !== null
    ) {
        $valorAtualNacional +=
            (float)$posicao['valor_atual'];
    }
}

$resultadoAtualNacional =
    $cotacoesNacionaisCompletas &&
    $erroPosicaoNacional === ''
        ? $valorAtualNacional -
            $custoAtualNacional
        : null;

$percentualAtualNacional =
    $resultadoAtualNacional !== null &&
    $custoAtualNacional > 0
        ? (
            $resultadoAtualNacional /
            $custoAtualNacional
        ) * 100
        : (
            $resultadoAtualNacional !== null
                ? 0
                : null
        );

$custoAtualInternacional = '0';
$valorAtualInternacional = '0';

foreach (
    $posicoesInternacionais as $posicao
) {
    $custoAtualInternacional =
        bcadd(
            $custoAtualInternacional,
            $posicao['total_investido_usd'],
            24
        );

    if (
        $posicao['valor_atual'] !== null
    ) {
        $valorAtualInternacional =
            bcadd(
                $valorAtualInternacional,
                $posicao['valor_atual'],
                24
            );
    }
}

$resultadoAtualInternacional =
    $cotacoesInternacionaisCompletas &&
    $erroPosicaoInternacional === ''
        ? bcsub(
            $valorAtualInternacional,
            $custoAtualInternacional,
            24
        )
        : null;

$percentualAtualInternacional =
    $resultadoAtualInternacional !== null &&
    bccomp(
        $custoAtualInternacional,
        '0',
        24
    ) > 0
        ? (
            (float)$resultadoAtualInternacional /
            (float)$custoAtualInternacional
        ) * 100
        : (
            $resultadoAtualInternacional !== null
                ? 0
                : null
        );

$dadosNacionais = [];
$dadosInternacionais = [];

for ($mes = 1; $mes <= 12; $mes++) {
    $dadosNacionais[$mes] = [
        'compras' => 0.0,
        'vendas' => 0.0,
        'operacoes' => 0,
        'ativos' => []
    ];

    $dadosInternacionais[$mes] = [
        'compras' => '0',
        'vendas' => '0',
        'operacoes' => 0,
        'ativos' => []
    ];
}

$ativosAnoNacional = [];
$ativosAnoInternacional = [];

$totalComprasNacional = 0.0;
$totalVendasNacional = 0.0;
$totalOperacoesNacional = 0;

$totalComprasInternacional = '0';
$totalVendasInternacional = '0';
$totalOperacoesInternacional = 0;

try {
    $stmt = $conn->prepare("
        SELECT
            ticker,
            tipo_ativo,
            quantidade,
            valor_unitario,
            data,
            tipo_operacao
        FROM investimentos_nacionais
        WHERE usuario_id = ?
          AND YEAR(data) = ?
        ORDER BY data, id
    ");

    $stmt->bind_param(
        'ii',
        $usuario_id,
        $ano
    );

    $stmt->execute();

    $movimentacoes =
        $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    foreach ($movimentacoes as $op) {
        $mes =
            (int)date(
                'n',
                strtotime($op['data'])
            );

        $valorOperacao =
            abs((int)$op['quantidade']) *
            (float)$op['valor_unitario'];

        $chaveAtivo =
            strtoupper(
                trim($op['ticker'])
            ) .
            '|' .
            $op['tipo_ativo'];

        $dadosNacionais[$mes]['operacoes']++;

        $dadosNacionais[$mes]['ativos'][
            $chaveAtivo
        ] = true;

        $ativosAnoNacional[
            $chaveAtivo
        ] = true;

        $totalOperacoesNacional++;

        if (
            $op['tipo_operacao'] ===
            'compra'
        ) {
            $dadosNacionais[$mes]['compras'] +=
                $valorOperacao;

            $totalComprasNacional +=
                $valorOperacao;
        } elseif (
            $op['tipo_operacao'] ===
            'venda'
        ) {
            $dadosNacionais[$mes]['vendas'] +=
                $valorOperacao;

            $totalVendasNacional +=
                $valorOperacao;
        }
    }

    $stmt = $conn->prepare("
        SELECT
            ticker,
            tipo_ativo,
            quantidade,
            valor_unitario,
            data,
            tipo_operacao
        FROM investimentos_internacionais
        WHERE usuario_id = ?
          AND YEAR(data) = ?
        ORDER BY data, id
    ");

    $stmt->bind_param(
        'ii',
        $usuario_id,
        $ano
    );

    $stmt->execute();

    $movimentacoes =
        $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    foreach ($movimentacoes as $op) {
        $mes =
            (int)date(
                'n',
                strtotime($op['data'])
            );

        $quantidade =
            ltrim(
                (string)$op['quantidade'],
                '-'
            );

        $valorOperacao =
            bcmul(
                $quantidade,
                (string)$op['valor_unitario'],
                24
            );

        $chaveAtivo =
            strtoupper(
                trim($op['ticker'])
            ) .
            '|' .
            $op['tipo_ativo'];

        $dadosInternacionais[$mes]['operacoes']++;

        $dadosInternacionais[$mes]['ativos'][
            $chaveAtivo
        ] = true;

        $ativosAnoInternacional[
            $chaveAtivo
        ] = true;

        $totalOperacoesInternacional++;

        if (
            $op['tipo_operacao'] ===
            'compra'
        ) {
            $dadosInternacionais[$mes]['compras'] =
                bcadd(
                    $dadosInternacionais[$mes]['compras'],
                    $valorOperacao,
                    24
                );

            $totalComprasInternacional =
                bcadd(
                    $totalComprasInternacional,
                    $valorOperacao,
                    24
                );
        } elseif (
            $op['tipo_operacao'] ===
            'venda'
        ) {
            $dadosInternacionais[$mes]['vendas'] =
                bcadd(
                    $dadosInternacionais[$mes]['vendas'],
                    $valorOperacao,
                    24
                );

            $totalVendasInternacional =
                bcadd(
                    $totalVendasInternacional,
                    $valorOperacao,
                    24
                );
        }
    }

} catch (Throwable $e) {
    $erroMovimentacao =
        'Não foi possível carregar a movimentação do período selecionado.';

    error_log(
        'MyCashFlow relatório movimentação investimentos: ' .
        mcfMensagemErro($e)
    );
}

$anosDisponiveis = [];

try {
    $stmt = $conn->prepare("
        SELECT DISTINCT ano
        FROM (
            SELECT
                YEAR(data) AS ano
            FROM investimentos_nacionais
            WHERE usuario_id = ?

            UNION

            SELECT
                YEAR(data) AS ano
            FROM investimentos_internacionais
            WHERE usuario_id = ?
        ) AS anos
        WHERE ano IS NOT NULL
        ORDER BY ano DESC
    ");

    $stmt->bind_param(
        'ii',
        $usuario_id,
        $usuario_id
    );

    $stmt->execute();

    $resultAnos =
        $stmt->get_result();

    while (
        $row =
            $resultAnos->fetch_assoc()
    ) {
        $anosDisponiveis[] =
            (int)$row['ano'];
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log(
        'MyCashFlow relatório anos investimentos: ' .
        mcfMensagemErro($e)
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
    $anosDisponiveis[] =
        $anoAtual;
}

rsort($anosDisponiveis);

$labelsGrafico = [];
$comprasNacionalGrafico = [];
$vendasNacionalGrafico = [];
$comprasInternacionalGrafico = [];
$vendasInternacionalGrafico = [];

foreach ($meses as $numero => $nome) {
    $labelsGrafico[] = $nome;

    $comprasNacionalGrafico[] =
        $dadosNacionais[$numero]['compras'];

    $vendasNacionalGrafico[] =
        $dadosNacionais[$numero]['vendas'];

    $comprasInternacionalGrafico[] =
        (float)$dadosInternacionais[$numero]['compras'];

    $vendasInternacionalGrafico[] =
        (float)$dadosInternacionais[$numero]['vendas'];
}

$totalAtivosAnoNacional =
    count($ativosAnoNacional);

$totalAtivosAnoInternacional =
    count($ativosAnoInternacional);

$totalAtivosCarteiraNacional =
    count($posicoesNacionais);

$totalAtivosCarteiraInternacional =
    count($posicoesInternacionais);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Relatório de Investimentos</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-investimentos.css?v=2"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="investimentos-rel-layout">

    <div class="investimentos-rel-cabecalho">

        <div>

            <h1>
                Relatório de Investimentos
            </h1>

            <p>
                Posição atual da carteira e movimentação de
                <?= (int)$ano ?>
            </p>

        </div>

        <div class="investimentos-rel-acoes">

            <form
                method="GET"
                class="investimentos-rel-filtro"
            >

                <label for="ano">
                    Ano
                </label>

                <select
                    name="ano"
                    id="ano"
                    onchange="this.form.submit()"
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

            </form>

            <button
                type="button"
                class="investimentos-rel-imprimir"
                onclick="window.print()"
            >
                Imprimir
            </button>

        </div>

    </div>

    <section class="investimentos-rel-secao">

        <div class="investimentos-rel-titulo">

            <div>

                <h2>Posição atual</h2>

                <span>
                    Situação da carteira hoje,
                    considerando todo o histórico
                    de operações.
                </span>

            </div>

        </div>

        <div class="investimentos-rel-mercado">

            <h3>
                Investimentos Nacionais
                <small>BRL</small>
            </h3>

            <?php if (
                $erroPosicaoNacional !== ''
            ) { ?>

                <p class="investimentos-rel-aviso">
                    <?= relInvestEscape(
                        $erroPosicaoNacional
                    ) ?>
                </p>

            <?php } ?>

            <?php if (
                $erroCotacaoNacional !== ''
            ) { ?>

                <p class="investimentos-rel-aviso">
                    <?= relInvestEscape(
                        $erroCotacaoNacional
                    ) ?>
                </p>

            <?php } ?>

            <div class="investimentos-rel-resumo">

                <div class="investimentos-rel-card">

                    <span>Custo atual</span>

                    <strong>
                        <?= relInvestBRL(
                            $custoAtualNacional
                        ) ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Valor atual</span>

                    <strong>
                        <?= $cotacoesNacionaisCompletas &&
                            $erroPosicaoNacional === ''
                                ? relInvestBRL(
                                    $valorAtualNacional
                                )
                                : 'Indisponível'
                        ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>
                        Resultado não realizado
                    </span>

                    <strong
                        class="<?= relInvestClasseResultado(
                            $resultadoAtualNacional
                        ) ?>"
                    >
                        <?= $resultadoAtualNacional !== null
                            ? relInvestBRL(
                                $resultadoAtualNacional
                            )
                            : 'Indisponível'
                        ?>
                    </strong>

                    <small>
                        <?= relInvestPercentual(
                            $percentualAtualNacional
                        ) ?>
                    </small>

                </div>

                <div class="investimentos-rel-card">

                    <span>
                        Ativos em carteira
                    </span>

                    <strong>
                        <?= (int)$totalAtivosCarteiraNacional ?>
                    </strong>

                </div>

            </div>

            <div class="investimentos-rel-tabela-wrapper">

                <table class="investimentos-rel-tabela">

                    <thead>

                        <tr>
                            <th>Ativo</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Preço médio</th>
                            <th>Custo</th>
                            <th>Cotação atual</th>
                            <th>Valor atual</th>
                            <th>Resultado</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (
                        empty($posicoesNacionais)
                    ) { ?>

                        <tr>

                            <td colspan="8">
                                Nenhuma posição nacional
                                em carteira.
                            </td>

                        </tr>

                    <?php } else { ?>

                        <?php foreach (
                            $posicoesNacionais as $posicao
                        ) { ?>

                            <?php
                            $percentual =
                                $posicao['resultado'] !== null &&
                                $posicao['total_investido'] > 0
                                    ? (
                                        $posicao['resultado'] /
                                        $posicao['total_investido']
                                    ) * 100
                                    : null;
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= relInvestEscape(
                                            $posicao['ticker']
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <span
                                        class="av-tipo-ativo av-tipo-<?= relInvestEscape(
                                            $posicao['tipo_ativo']
                                        ) ?>"
                                    >
                                        <?= relInvestEscape(
                                            relInvestTipoNacional(
                                                $posicao['tipo_ativo']
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= number_format(
                                        (int)$posicao['quantidade_total'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <?= relInvestBRL(
                                        $posicao['valor_medio_ponderado']
                                    ) ?>
                                </td>

                                <td>
                                    <?= relInvestBRL(
                                        $posicao['total_investido']
                                    ) ?>
                                </td>

                                <td>
                                    <?= $posicao['cotacao_atual'] !== null
                                        ? relInvestBRL(
                                            $posicao['cotacao_atual']
                                        )
                                        : 'Indisponível'
                                    ?>
                                </td>

                                <td>
                                    <?= $posicao['valor_atual'] !== null
                                        ? relInvestBRL(
                                            $posicao['valor_atual']
                                        )
                                        : 'Indisponível'
                                    ?>
                                </td>

                                <td
                                    class="<?= relInvestClasseResultado(
                                        $posicao['resultado']
                                    ) ?>"
                                >
                                    <?=
                                        $posicao['resultado'] !== null
                                            ? relInvestBRL(
                                                $posicao['resultado']
                                            ) .
                                                ' (' .
                                                relInvestPercentual(
                                                    $percentual
                                                ) .
                                                ')'
                                            : 'Indisponível'
                                    ?>
                                </td>

                            </tr>

                        <?php } ?>

                    <?php } ?>

                    </tbody>

                    <tfoot>

                        <tr>

                            <td colspan="4">
                                <strong>Total</strong>
                            </td>

                            <td>
                                <strong>
                                    <?= relInvestBRL(
                                        $custoAtualNacional
                                    ) ?>
                                </strong>
                            </td>

                            <td>—</td>

                            <td>
                                <strong>
                                    <?= $cotacoesNacionaisCompletas &&
                                        $erroPosicaoNacional === ''
                                            ? relInvestBRL(
                                                $valorAtualNacional
                                            )
                                            : 'Indisponível'
                                    ?>
                                </strong>
                            </td>

                            <td
                                class="<?= relInvestClasseResultado(
                                    $resultadoAtualNacional
                                ) ?>"
                            >
                                <strong>
                                    <?= $resultadoAtualNacional !== null
                                        ? relInvestBRL(
                                            $resultadoAtualNacional
                                        ) .
                                            ' (' .
                                            relInvestPercentual(
                                                $percentualAtualNacional
                                            ) .
                                            ')'
                                        : 'Indisponível'
                                    ?>
                                </strong>
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

        <div class="investimentos-rel-mercado">

            <h3>
                Investimentos Internacionais
                <small>USD</small>
            </h3>

            <?php if (
                $erroPosicaoInternacional !== ''
            ) { ?>

                <p class="investimentos-rel-aviso">
                    <?= relInvestEscape(
                        $erroPosicaoInternacional
                    ) ?>
                </p>

            <?php } ?>

            <?php if (
                $erroCotacaoInternacional !== ''
            ) { ?>

                <p class="investimentos-rel-aviso">
                    <?= relInvestEscape(
                        $erroCotacaoInternacional
                    ) ?>
                </p>

            <?php } ?>

            <div class="investimentos-rel-resumo">

                <div class="investimentos-rel-card">

                    <span>Custo atual</span>

                    <strong>
                        <?= relInvestUSD(
                            $custoAtualInternacional
                        ) ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Valor atual</span>

                    <strong>
                        <?= $cotacoesInternacionaisCompletas &&
                            $erroPosicaoInternacional === ''
                                ? relInvestUSD(
                                    $valorAtualInternacional
                                )
                                : 'Indisponível'
                        ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>
                        Resultado não realizado
                    </span>

                    <strong
                        class="<?= relInvestClasseResultado(
                            $resultadoAtualInternacional
                        ) ?>"
                    >
                        <?= $resultadoAtualInternacional !== null
                            ? relInvestUSD(
                                $resultadoAtualInternacional
                            )
                            : 'Indisponível'
                        ?>
                    </strong>

                    <small>
                        <?= relInvestPercentual(
                            $percentualAtualInternacional
                        ) ?>
                    </small>

                </div>

                <div class="investimentos-rel-card">

                    <span>
                        Ativos em carteira
                    </span>

                    <strong>
                        <?= (int)$totalAtivosCarteiraInternacional ?>
                    </strong>

                </div>

            </div>

            <div class="investimentos-rel-tabela-wrapper">

                <table class="investimentos-rel-tabela">

                    <thead>

                        <tr>
                            <th>Ativo</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Preço médio</th>
                            <th>Custo</th>
                            <th>Cotação atual</th>
                            <th>Valor atual</th>
                            <th>Resultado</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (
                        empty($posicoesInternacionais)
                    ) { ?>

                        <tr>

                            <td colspan="8">
                                Nenhuma posição internacional
                                em carteira.
                            </td>

                        </tr>

                    <?php } else { ?>

                        <?php foreach (
                            $posicoesInternacionais as $posicao
                        ) { ?>

                            <?php
                            $percentual =
                                $posicao['resultado'] !== null &&
                                bccomp(
                                    $posicao['total_investido_usd'],
                                    '0',
                                    24
                                ) > 0
                                    ? (
                                        (float)$posicao['resultado'] /
                                        (float)$posicao['total_investido_usd']
                                    ) * 100
                                    : null;
                            ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= relInvestEscape(
                                            $posicao['ticker']
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <span
                                        class="av-tipo-ativo av-tipo-<?= relInvestEscape(
                                            $posicao['tipo_ativo']
                                        ) ?>"
                                    >
                                        <?= relInvestEscape(
                                            relInvestTipoInternacional(
                                                $posicao['tipo_ativo']
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= relInvestQuantidadeInternacional(
                                        $posicao['quantidade_total']
                                    ) ?>
                                </td>

                                <td>
                                    <?= relInvestUSD(
                                        $posicao['valor_medio_ponderado']
                                    ) ?>
                                </td>

                                <td>
                                    <?= relInvestUSD(
                                        $posicao['total_investido_usd']
                                    ) ?>
                                </td>

                                <td>
                                    <?= $posicao['cotacao_atual'] !== null
                                        ? relInvestUSD(
                                            $posicao['cotacao_atual']
                                        )
                                        : 'Indisponível'
                                    ?>
                                </td>

                                <td>
                                    <?= $posicao['valor_atual'] !== null
                                        ? relInvestUSD(
                                            $posicao['valor_atual']
                                        )
                                        : 'Indisponível'
                                    ?>
                                </td>

                                <td
                                    class="<?= relInvestClasseResultado(
                                        $posicao['resultado']
                                    ) ?>"
                                >
                                    <?=
                                        $posicao['resultado'] !== null
                                            ? relInvestUSD(
                                                $posicao['resultado']
                                            ) .
                                                ' (' .
                                                relInvestPercentual(
                                                    $percentual
                                                ) .
                                                ')'
                                            : 'Indisponível'
                                    ?>
                                </td>

                            </tr>

                        <?php } ?>

                    <?php } ?>

                    </tbody>

                    <tfoot>

                        <tr>

                            <td colspan="4">
                                <strong>Total</strong>
                            </td>

                            <td>
                                <strong>
                                    <?= relInvestUSD(
                                        $custoAtualInternacional
                                    ) ?>
                                </strong>
                            </td>

                            <td>—</td>

                            <td>
                                <strong>
                                    <?= $cotacoesInternacionaisCompletas &&
                                        $erroPosicaoInternacional === ''
                                            ? relInvestUSD(
                                                $valorAtualInternacional
                                            )
                                            : 'Indisponível'
                                    ?>
                                </strong>
                            </td>

                            <td
                                class="<?= relInvestClasseResultado(
                                    $resultadoAtualInternacional
                                ) ?>"
                            >
                                <strong>
                                    <?= $resultadoAtualInternacional !== null
                                        ? relInvestUSD(
                                            $resultadoAtualInternacional
                                        ) .
                                            ' (' .
                                            relInvestPercentual(
                                                $percentualAtualInternacional
                                            ) .
                                            ')'
                                        : 'Indisponível'
                                    ?>
                                </strong>
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </section>

    <section class="investimentos-rel-secao">

        <div class="investimentos-rel-titulo">

            <div>

                <h2>
                    Movimentação em
                    <?= (int)$ano ?>
                </h2>

                <span>
                    Compras e vendas realizadas entre
                    01/01/<?= (int)$ano ?>
                    e
                    31/12/<?= (int)$ano ?>.
                </span>

            </div>

        </div>

        <?php if (
            $erroMovimentacao !== ''
        ) { ?>

            <p class="investimentos-rel-aviso">
                <?= relInvestEscape(
                    $erroMovimentacao
                ) ?>
            </p>

        <?php } ?>

        <div class="investimentos-rel-mercado">

            <h3>
                Investimentos Nacionais
                <small>BRL</small>
            </h3>

            <div class="investimentos-rel-resumo">

                <div class="investimentos-rel-card">

                    <span>Compras</span>

                    <strong>
                        <?= relInvestBRL(
                            $totalComprasNacional
                        ) ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Vendas</span>

                    <strong>
                        <?= relInvestBRL(
                            $totalVendasNacional
                        ) ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Total de operações</span>

                    <strong>
                        <?= (int)$totalOperacoesNacional ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Ativos negociados</span>

                    <strong>
                        <?= (int)$totalAtivosAnoNacional ?>
                    </strong>

                </div>

            </div>

            <div class="investimentos-rel-grafico">

                <h4>
                    Compras x Vendas por mês
                </h4>

                <div class="investimentos-rel-grafico-wrapper">
                    <canvas
                        id="graficoInvestimentosNacionais"
                    ></canvas>
                </div>

            </div>

            <div class="investimentos-rel-tabela-wrapper">

                <table class="investimentos-rel-tabela">

                    <thead>

                        <tr>
                            <th>Mês</th>
                            <th>Compras</th>
                            <th>Vendas</th>
                            <th>Operações</th>
                            <th>Ativos negociados</th>
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

                                <?= relInvestEscape(
                                    $nome
                                ) ?>

                                <?php if (
                                    $ano === $anoAtual &&
                                    $numero === $mesAtual
                                ) { ?>

                                    <span class="indicador-mes-atual">
                                        Atual
                                    </span>

                                <?php } ?>

                            </td>

                            <td>
                                <?= relInvestBRL(
                                    $dadosNacionais[$numero]['compras']
                                ) ?>
                            </td>

                            <td>
                                <?= relInvestBRL(
                                    $dadosNacionais[$numero]['vendas']
                                ) ?>
                            </td>

                            <td>
                                <?= (int)$dadosNacionais[$numero]['operacoes'] ?>
                            </td>

                            <td>
                                <?= count(
                                    $dadosNacionais[$numero]['ativos']
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
                                    <?= relInvestBRL(
                                        $totalComprasNacional
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= relInvestBRL(
                                        $totalVendasNacional
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= (int)$totalOperacoesNacional ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= (int)$totalAtivosAnoNacional ?>
                                </strong>
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

        <div class="investimentos-rel-mercado">

            <h3>
                Investimentos Internacionais
                <small>USD</small>
            </h3>

            <div class="investimentos-rel-resumo">

                <div class="investimentos-rel-card">

                    <span>Compras</span>

                    <strong>
                        <?= relInvestUSD(
                            $totalComprasInternacional
                        ) ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Vendas</span>

                    <strong>
                        <?= relInvestUSD(
                            $totalVendasInternacional
                        ) ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Total de operações</span>

                    <strong>
                        <?= (int)$totalOperacoesInternacional ?>
                    </strong>

                </div>

                <div class="investimentos-rel-card">

                    <span>Ativos negociados</span>

                    <strong>
                        <?= (int)$totalAtivosAnoInternacional ?>
                    </strong>

                </div>

            </div>

            <div class="investimentos-rel-grafico">

                <h4>
                    Compras x Vendas por mês
                </h4>

                <div class="investimentos-rel-grafico-wrapper">
                    <canvas
                        id="graficoInvestimentosInternacionais"
                    ></canvas>
                </div>

            </div>

            <div class="investimentos-rel-tabela-wrapper">

                <table class="investimentos-rel-tabela">

                    <thead>

                        <tr>
                            <th>Mês</th>
                            <th>Compras</th>
                            <th>Vendas</th>
                            <th>Operações</th>
                            <th>Ativos negociados</th>
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

                                <?= relInvestEscape(
                                    $nome
                                ) ?>

                                <?php if (
                                    $ano === $anoAtual &&
                                    $numero === $mesAtual
                                ) { ?>

                                    <span class="indicador-mes-atual">
                                        Atual
                                    </span>

                                <?php } ?>

                            </td>

                            <td>
                                <?= relInvestUSD(
                                    $dadosInternacionais[$numero]['compras']
                                ) ?>
                            </td>

                            <td>
                                <?= relInvestUSD(
                                    $dadosInternacionais[$numero]['vendas']
                                ) ?>
                            </td>

                            <td>
                                <?= (int)$dadosInternacionais[$numero]['operacoes'] ?>
                            </td>

                            <td>
                                <?= count(
                                    $dadosInternacionais[$numero]['ativos']
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
                                    <?= relInvestUSD(
                                        $totalComprasInternacional
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= relInvestUSD(
                                        $totalVendasInternacional
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= (int)$totalOperacoesInternacional ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    <?= (int)$totalAtivosAnoInternacional ?>
                                </strong>
                            </td>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labelsInvestimentos =
    <?= json_encode(
        $labelsGrafico,
        JSON_UNESCAPED_UNICODE
    ) ?>;

const comprasNacionais =
    <?= json_encode(
        $comprasNacionalGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const vendasNacionais =
    <?= json_encode(
        $vendasNacionalGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const comprasInternacionais =
    <?= json_encode(
        $comprasInternacionalGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

const vendasInternacionais =
    <?= json_encode(
        $vendasInternacionalGrafico,
        JSON_NUMERIC_CHECK
    ) ?>;

function formatarBRL(valor) {
    return 'R$ ' +
        Number(valor).toLocaleString(
            'pt-BR',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
}

function formatarUSD(valor) {
    return 'US$ ' +
        Number(valor).toLocaleString(
            'pt-BR',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );
}

new Chart(
    document
        .getElementById(
            'graficoInvestimentosNacionais'
        )
        .getContext('2d'),
    {
        type: 'bar',

        data: {
            labels: labelsInvestimentos,

            datasets: [
                {
                    label: 'Compras',
                    data: comprasNacionais
                },
                {
                    label: 'Vendas',
                    data: vendasNacionais
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
                            return formatarBRL(valor);
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
                                contexto.dataset.label +
                                ': ' +
                                formatarBRL(
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

new Chart(
    document
        .getElementById(
            'graficoInvestimentosInternacionais'
        )
        .getContext('2d'),
    {
        type: 'bar',

        data: {
            labels: labelsInvestimentos,

            datasets: [
                {
                    label: 'Compras',
                    data: comprasInternacionais
                },
                {
                    label: 'Vendas',
                    data: vendasInternacionais
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
                            return formatarUSD(valor);
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
                                contexto.dataset.label +
                                ': ' +
                                formatarUSD(
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
</script>

</body>
</html>