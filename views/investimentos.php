<?php
require_once __DIR__.'/../config.php';
require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../includes/mercado_api.php';

$usuario_id = mcfDonoId();

function resumoEscape($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function resumoCusto(array $operacoes, $nacional) {
    usort(
        $operacoes,
        function ($a, $b) {
            return strcmp($a['data'], $b['data']) ?:
                ($a['id'] <=> $b['id']);
        }
    );

    $posicoes = [];

    foreach ($operacoes as $op) {
        $ticker = strtoupper(
            trim($op['ticker'])
        );

        if ($nacional) {
            $ticker = preg_replace(
                '/\.SA$/',
                '',
                $ticker
            );
        }

        $tipoAtivo =
            $op['tipo_ativo'] ?? '';

        $chave =
            $ticker . '|' . $tipoAtivo;

        if (!isset($posicoes[$chave])) {
            $posicoes[$chave] = [
                'quantidade' => '0',
                'custo' => '0',
                'medio' => '0'
            ];
        }

        $p = &$posicoes[$chave];

        $quantidade =
            ltrim(
                (string)$op['quantidade'],
                '-'
            );

        $preco =
            (string)$op['valor_unitario'];

        $tipoOperacao =
            $op['tipo_operacao'] ?? '';

        $tiposAtivoValidos =
            $nacional
                ? ['acao', 'fii', 'etf', 'bdr']
                : ['stock', 'etf', 'reit', 'adr'];

        if (
            !in_array(
                $tipoAtivo,
                $tiposAtivoValidos,
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
                8
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
                $ticker . '.'
            );
        }

        if ($tipoOperacao === 'compra') {
            $p['custo'] =
                bcadd(
                    $p['custo'],
                    bcmul(
                        $quantidade,
                        $preco,
                        24
                    ),
                    24
                );

            $p['quantidade'] =
                bcadd(
                    $p['quantidade'],
                    $quantidade,
                    8
                );

            $p['medio'] =
                bcdiv(
                    $p['custo'],
                    $p['quantidade'],
                    24
                );

        } else {
            if (
                bccomp(
                    $quantidade,
                    $p['quantidade'],
                    8
                ) > 0
            ) {
                throw new DomainException(
                    'Venda acima do saldo no histórico de ' .
                    $ticker .
                    ' em ' .
                    date(
                        'd/m/Y',
                        strtotime($op['data'])
                    ) .
                    '.'
                );
            }

            $p['quantidade'] =
                bcsub(
                    $p['quantidade'],
                    $quantidade,
                    8
                );

            $p['custo'] =
                bcmul(
                    $p['quantidade'],
                    $p['medio'],
                    24
                );

            if (
                bccomp(
                    $p['quantidade'],
                    '0',
                    8
                ) === 0
            ) {
                $p['custo'] = '0';
                $p['medio'] = '0';
            }
        }

        unset($p);
    }

    $total = '0';

    foreach ($posicoes as $p) {
        $total =
            bcadd(
                $total,
                $p['custo'],
                24
            );
    }

    return $total;
}

function resumoDolar(&$diagnostico) {
    $cambio =
        mercadoApi()->fx();

    $q =
        $cambio['USD'];

    $diagnostico =
        mercadoLegenda($q);

    if ($q['price'] === null) {
        return null;
    }

    return [
        'valor' => (float)$q['price'],
        'timestamp' => $q['market_time'],
        'fonte' => mercadoLegenda($q)
    ];
}

function resumoConverter(
    $nacional,
    $internacional,
    $dolar
) {
    if (
        $nacional === null ||
        $internacional === null
    ) {
        return null;
    }

    if (
        bccomp(
            $internacional,
            '0',
            24
        ) === 0
    ) {
        return $nacional;
    }

    if ($dolar === null) {
        return null;
    }

    return bcadd(
        $nacional,
        bcmul(
            $internacional,
            (string)$dolar,
            24
        ),
        24
    );
}

$resumo_erros = [];

$total_nacional = null;
$total_internacional = null;

$dolar = null;
$diagnostico_cambio = '';
$total_geral = null;

if (!extension_loaded('bcmath')) {
    $resumo_erros[] =
        'Ative a extensão BCMath do PHP para calcular o resumo com precisão.';

} else {
    try {
        $stmt =
            $conn->prepare("
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

        $operacoesNacionais =
            $stmt
                ->get_result()
                ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $total_nacional =
            resumoCusto(
                $operacoesNacionais,
                true
            );

    } catch (Throwable $e) {
        error_log(
            'MyCashFlow resumo nacional: ' .
            mcfMensagemErro($e)
        );

        $resumo_erros[] =
            'Carteira nacional: ' .
            (
                $e instanceof DomainException
                    ? mcfMensagemErro($e)
                    : 'não foi possível carregar o custo das posições.'
            );
    }

    try {
        $stmt =
            $conn->prepare("
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

        $operacoesInternacionais =
            $stmt
                ->get_result()
                ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $total_internacional =
            resumoCusto(
                $operacoesInternacionais,
                false
            );

    } catch (Throwable $e) {
        error_log(
            'MyCashFlow resumo internacional: ' .
            mcfMensagemErro($e)
        );

        $resumo_erros[] =
            'Carteira internacional: ' .
            (
                $e instanceof DomainException
                    ? mcfMensagemErro($e)
                    : 'não foi possível carregar o custo das posições.'
            );
    }

    if (
        $total_internacional !== null &&
        bccomp(
            $total_internacional,
            '0',
            24
        ) > 0
    ) {
        try {
            $dolar =
                resumoDolar(
                    $diagnostico_cambio
                );

        } catch (Throwable $e) {
            error_log(
                'MyCashFlow resumo câmbio: ' .
                mcfMensagemErro($e)
            );

            $diagnostico_cambio =
                'Não foi possível consultar o câmbio.';
        }
    }

    $total_geral =
        resumoConverter(
            $total_nacional,
            $total_internacional,
            $dolar['valor'] ?? null
        );
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Investimentos</title>

    <link
        rel="stylesheet"
        href="../assets/css/style-acoes-menu.css?v=7"
    >
</head>

<body class="acoes-menu-page">
    <?php include('../includes/header.php'); ?>
    <?php include('../includes/menu.php'); ?>

    <main class="acoes-page">
        <p><a href="investimentos_operacoes.php">Corrigir operações registradas</a> · <a data-mcf-own href="visao_conjunta.php?modulo=investimentos">Visão conjunta dos investimentos autorizados</a></p>
        <div class="acoes-top">
            <div>
                <h1>Investimentos</h1>

                <p>
                    Acompanhe suas posições e acesse suas carteiras de investimentos.
                </p>
            </div>
        </div>

        <?php foreach ($resumo_erros as $resumo_erro) { ?>
            <p
                class="acoes-aviso"
                role="alert"
            >
                <?= resumoEscape($resumo_erro) ?>
            </p>
        <?php } ?>

        <section
            class="acoes-card"
            aria-labelledby="acoes-resumo-titulo"
        >
            <div class="acoes-summary">
                <div>
                    <h2 id="acoes-resumo-titulo">
                        Resumo da carteira
                    </h2>

                    <p>
                        Custo dos investimentos que permanecem em carteira.
                    </p>
                </div>

                <div class="acoes-total">
                    <span>
                        Consolidado em reais · estimativa
                    </span>

                    <strong>
                        <?= $total_geral !== null
                            ? 'R$ ' .
                                number_format(
                                    $total_geral,
                                    2,
                                    ',',
                                    '.'
                                )
                            : 'Indisponível' ?>
                    </strong>
                </div>
            </div>

            <div
                class="acoes-scroll"
                tabindex="0"
                role="region"
                aria-label="Custos por carteira"
            >
                <table class="acoes-table">
                    <thead>
                        <tr>
                            <th scope="col">
                                Carteira
                            </th>

                            <th
                                scope="col"
                                class="acoes-number"
                            >
                                Custo das posições atuais
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <th scope="row">
                                Investimentos Nacionais

                                <span class="acoes-moeda">
                                    BRL
                                </span>
                            </th>

                            <td class="acoes-number">
                                <?= $total_nacional !== null
                                    ? 'R$ ' .
                                        number_format(
                                            $total_nacional,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    : 'Indisponível' ?>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                Investimentos Internacionais

                                <span class="acoes-moeda">
                                    USD
                                </span>
                            </th>

                            <td class="acoes-number">
                                <?= $total_internacional !== null
                                    ? 'US$ ' .
                                        number_format(
                                            $total_internacional,
                                            2,
                                            ',',
                                            '.'
                                        )
                                    : 'Indisponível' ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="acoes-notas">
                <?php if ($dolar !== null) { ?>
                    <p>
                        <strong>Câmbio utilizado:</strong>

                        US$ 1 = R$
                        <?= number_format(
                            $dolar['valor'],
                            4,
                            ',',
                            '.'
                        ) ?>.

                        <?= resumoEscape(
                            $dolar['fonte']
                        ) ?>

                        <?php if (
                            $dolar['timestamp'] !== null
                        ) { ?>
                            ·
                            <?= (
                                new DateTimeImmutable(
                                    '@' .
                                    $dolar['timestamp']
                                )
                            )
                                ->setTimezone(
                                    new DateTimeZone(
                                        'America/Sao_Paulo'
                                    )
                                )
                                ->format(
                                    'd/m/Y H:i'
                                ) ?>
                            (Brasília)
                        <?php } ?>.
                    </p>

                    <p>
                        O consolidado converte o custo em dólares pelo câmbio informado.
                        Não representa o valor de mercado nem o custo histórico em reais.
                    </p>

                <?php } elseif (
                    $total_internacional !== null &&
                    bccomp(
                        $total_internacional,
                        '0',
                        24
                    ) > 0
                ) { ?>
                    <p role="status">
                        Câmbio indisponível.
                        Consulte os custos separados em reais e dólares.
                    </p>

                    <?php if (
                        $diagnostico_cambio !== ''
                    ) { ?>
                        <details>
                            <summary>
                                Detalhes da consulta de câmbio
                            </summary>

                            <p>
                                <?= resumoEscape(
                                    $diagnostico_cambio
                                ) ?>
                            </p>
                        </details>
                    <?php } ?>

                <?php } else { ?>
                    <p>
                        As compras aumentam o custo da posição;
                        as vendas retiram o custo pelo preço médio.
                    </p>
                <?php } ?>
            </div>
        </section>

        <section
            class="acoes-acessos"
            aria-label="Acessar carteiras"
        >
            <a
                class="acoes-acesso"
                href="investimentos_nacionais.php"
            >
                <div>
                    <span class="acoes-etiqueta">
                        MERCADO NACIONAL
                    </span>

                    <h2>
                        Investimentos Nacionais
                    </h2>

                    <p>
                        Ações, FIIs, ETFs e BDRs negociados no mercado nacional.
                    </p>
                </div>

                <span class="acoes-abrir">
                    Acessar carteira
                    <span aria-hidden="true">
                        →
                    </span>
                </span>
            </a>

            <a
                class="acoes-acesso"
                href="investimentos_internacionais.php"
            >
                <div>
                    <span class="acoes-etiqueta">
                        MERCADO INTERNACIONAL
                    </span>

                    <h2>
                        Investimentos Internacionais
                    </h2>

                    <p>
                        Stocks, ETFs, REITs e ADRs com posições apresentadas em dólares.
                    </p>
                </div>

                <span class="acoes-abrir">
                    Acessar carteira
                    <span aria-hidden="true">
                        →
                    </span>
                </span>
            </a>
        </section>
    </main>

    <?php include('../includes/footer.php'); ?>
</body>
</html>
