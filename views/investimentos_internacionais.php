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
require_once __DIR__ . '/../includes/carteiras_posicoes.php';

$usuario_id = mcfDonoId();

function internacionalEscape($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function carteiraPercentual($resultado, $custo) {
    if ($resultado === null || (float)$custo <= 0) {
        return '—';
    }

    $valor = (float)$resultado / (float)$custo * 100;

    return ($valor > 0 ? '+' : '') .
        number_format($valor, 2, ',', '.') . '%';
}

function carteiraLogos($ticker, $atual, array $historico) {
    $normalizar = function ($v) {
        return strtoupper(trim((string)$v));
    };

    $ticker = $normalizar($ticker);
    $urls = [];

    if (preg_match('/^[A-Z0-9.-]{1,20}$/D', $ticker)) {
        foreach (['png', 'webp', 'jpg', 'svg'] as $ext) {
            $relativo =
                '../assets/img/acoes/internacionais/' .
                $ticker . '.' . $ext;

            if (is_file(__DIR__ . '/' . $relativo)) {
                $urls[] = $relativo;
                break;
            }
        }
    }

    $adicionar = function ($url) use (&$urls) {
        if (
            is_string($url) &&
            strlen($url) <= 500 &&
            filter_var($url, FILTER_VALIDATE_URL) &&
            strtolower(parse_url($url, PHP_URL_SCHEME) ?? '') === 'https' &&
            !in_array($url, $urls, true)
        ) {
            $urls[] = $url;
        }
    };

    $adicionar($atual);

    foreach (array_reverse($historico) as $op) {
        if ($normalizar($op['ticker'] ?? '') === $ticker) {
            $adicionar($op['logo'] ?? null);
        }

        if (count($urls) >= 5) {
            break;
        }
    }

    return array_slice($urls, 0, 5);
}



function internacionalDecimal($v, $inteiros, $casas) {
    if (
        !is_string($v) ||
        !preg_match(
            '/^[0-9]{1,' . $inteiros .
            '}(?:\.[0-9]{1,' . $casas . '})?$/D',
            trim($v)
        )
    ) {
        throw new DomainException(
            'Informe um número positivo, sem separador de milhares e dentro da precisão permitida.'
        );
    }

    $v = trim($v);

    if (bccomp($v, '0', $casas) <= 0) {
        throw new DomainException(
            'Quantidade, preço e valor da operação devem ser maiores que zero.'
        );
    }

    return bcadd($v, '0', $casas);
}

function internacionalCalcular($form) {
    $preco =
        internacionalDecimal(
            $form['valor_unitario'],
            14,
            4
        );

    if ($form['modo'] === 'valor') {
        $limite =
            internacionalDecimal(
                $form['valor_operacao'],
                16,
                8
            );

        $quantidade =
            bcdiv($limite, $preco, 8);

        if (bccomp($quantidade, '0', 8) <= 0) {
            throw new DomainException(
                'O valor informado não compra a fração mínima de 0,00000001 unidade.'
            );
        }

    } elseif ($form['modo'] === 'quantidade') {
        $quantidade =
            internacionalDecimal(
                $form['quantidade'],
                14,
                8
            );

    } else {
        throw new DomainException(
            'Preencha a quantidade ou o valor da operação.'
        );
    }

    if (
        bccomp(
            $quantidade,
            '99999999999999.99999999',
            8
        ) > 0
    ) {
        throw new DomainException(
            'Quantidade acima do limite suportado.'
        );
    }

    $totalExato =
        bcmul($quantidade, $preco, 12);

    if (
        bccomp(
            $totalExato,
            '9999999999999999.99999999',
            12
        ) > 0
    ) {
        throw new DomainException(
            'Valor da operação acima do limite suportado.'
        );
    }

    $total =
        bcadd(
            $totalExato,
            '0.000000005',
            8
        );

    if (bccomp($total, '0', 8) <= 0) {
        throw new DomainException(
            'Valor da operação inferior à precisão de registro.'
        );
    }

    return [
        $quantidade,
        $preco,
        $total
    ];
}

function internacionalNomeTipoAtivo($tipo) {
    $tipos = [
        'stock' => 'Stock',
        'etf' => 'ETF',
        'reit' => 'REIT',
        'adr' => 'ADR'
    ];

    return $tipos[$tipo] ?? $tipo;
}



$_SESSION['internacional_csrf'] =
    $_SESSION['internacional_csrf'] ??
    bin2hex(random_bytes(32));

$erro = '';
$erro_carteira = '';

$mensagem =
    $_SESSION['internacional_mensagem'] ?? '';

unset($_SESSION['internacional_mensagem']);

$busca =
    isset($_GET['busca']) &&
    is_string($_GET['busca'])
        ? trim($_GET['busca'])
        : '';

$form = [
    'tipo_ativo' => 'stock',
    'ticker' => '',
    'quantidade' => '',
    'valor_operacao' => '',
    'valor_unitario' => '',
    'data' => '',
    'acao' => '',
    'modo' => 'quantidade'
];

foreach ($form as $campo => $valor) {
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST' &&
        isset($_POST[$campo]) &&
        is_string($_POST[$campo])
    ) {
        $form[$campo] =
            trim($_POST[$campo]);
    }
}

$estrutura_ok = false;

try {
    if (!extension_loaded('bcmath')) {
        throw new DomainException(
            'Ative a extensão BCMath do PHP para registrar frações com precisão.'
        );
    }

    $colunas = [];

    foreach (
        $conn
            ->query(
                'SHOW COLUMNS FROM investimentos_internacionais'
            )
            ->fetch_all(MYSQLI_ASSOC)
        as $c
    ) {
        $colunas[$c['Field']] =
            strtolower($c['Type']);
    }

    if (
        ($colunas['quantidade'] ?? '') !==
            'decimal(22,8)' ||
        ($colunas['valor_investido'] ?? '') !==
            'decimal(24,8)' ||
        !isset($colunas['usuario_id']) ||
        !isset($colunas['tipo_ativo']) ||
        !isset($colunas['tipo_operacao'])
    ) {
        throw new DomainException(
            'A estrutura de Investimentos Internacionais não corresponde à versão esperada.'
        );
    }

    $estrutura_ok = true;

} catch (Throwable $e) {
    $erro =
        $e instanceof DomainException
            ? mcfMensagemErro($e)
            : 'Não foi possível conferir a estrutura da tabela.';
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $estrutura_ok
) {
    $bloqueado = false;

    try {
        if (
            !is_string($_POST['csrf'] ?? null) ||
            !hash_equals(
                $_SESSION['internacional_csrf'],
                $_POST['csrf']
            )
        ) {
            throw new DomainException(
                'Formulário expirado. Atualize a página e tente novamente.'
            );
        }

        $tipoAtivo =
            strtolower($form['tipo_ativo']);

        $ticker =
            strtoupper($form['ticker']);

        $acao =
            $form['acao'];

        if (
            !in_array(
                $tipoAtivo,
                ['stock', 'etf', 'reit', 'adr'],
                true
            )
        ) {
            throw new DomainException(
                'Selecione um tipo de ativo válido.'
            );
        }

        if (
            !preg_match(
                '/^[A-Z][A-Z0-9.-]{0,19}$/D',
                $ticker
            ) ||
            !in_array(
                $acao,
                ['compra', 'venda'],
                true
            )
        ) {
            throw new DomainException(
                'Informe o ticker e selecione Compra ou Venda.'
            );
        }

        $data =
            $form['data'];

        $dt =
            DateTime::createFromFormat(
                '!Y-m-d',
                $data
            );

        if (
            !$dt ||
            $dt->format('Y-m-d') !== $data ||
            $data < '1000-01-01' ||
            $data > date('Y-m-d')
        ) {
            throw new DomainException(
                'Informe uma data válida, até hoje.'
            );
        }

        [
            $quantidade,
            $valor_unitario,
            $valor_investido
        ] = internacionalCalcular($form);

        $dadosCotacao =
            mercadoApi()->stocks(
                [$ticker],
                'USD'
            );

        $cotacao =
            $dadosCotacao[$ticker] ?? [
                'price' => null,
                'logo' => null
            ];

        $valor_mercado =
            $cotacao['price'] !== null
                ? (string)$cotacao['price']
                : null;

        $logo =
            $cotacao['logo'] ?? null;

        if (
            $logo !== null &&
            strlen($logo) > 500
        ) {
            $logo = null;
        }

        $lock =
            $conn
                ->query(
                    "SELECT GET_LOCK(
                        'mycashflow_investimentos_internacionais_registro',
                        10
                    ) AS adquirido"
                )
                ->fetch_assoc();

        $bloqueado =
            (int)$lock['adquirido'] === 1;

        if (!$bloqueado) {
            throw new DomainException(
                'Há outro registro em andamento. Tente novamente.'
            );
        }

        $stmt =
            $conn->prepare("
                SELECT
                    id,
                    ticker,
                    tipo_ativo,
                    quantidade,
                    valor_unitario,
                    data,
                    logo,
                    tipo_operacao
                FROM investimentos_internacionais
                WHERE usuario_id = ?
                  AND UPPER(ticker) = UPPER(?)
                  AND tipo_ativo = ?
                ORDER BY data, id
            ");

        $stmt->bind_param(
            'iss',
            $usuario_id,
            $ticker,
            $tipoAtivo
        );

        $stmt->execute();

        $opsTicker =
            $stmt
                ->get_result()
                ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $opsTicker[] = [
            'id' => PHP_INT_MAX,
            'ticker' => $ticker,
            'tipo_ativo' => $tipoAtivo,
            'quantidade' => $quantidade,
            'valor_unitario' => $valor_unitario,
            'data' => $data,
            'logo' => $logo,
            'tipo_operacao' => $acao
        ];

        internacionalConsolidar($opsTicker);

        if ($acao === 'venda') {
            $quantidade =
                '-' . $quantidade;

            $valor_investido =
                '-' . $valor_investido;
        }

        $stmt =
            $conn->prepare("
                INSERT INTO investimentos_internacionais
                (
                    usuario_id,
                    ticker,
                    tipo_ativo,
                    quantidade,
                    valor_unitario,
                    valor_investido,
                    data,
                    valor_mercado,
                    logo,
                    tipo_operacao
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

        $stmt->bind_param(
            'isssssssss',
            $usuario_id,
            $ticker,
            $tipoAtivo,
            $quantidade,
            $valor_unitario,
            $valor_investido,
            $data,
            $valor_mercado,
            $logo,
            $acao
        );

        $stmt->execute();
        $stmt->close();

        $_SESSION['internacional_mensagem'] =
            'Operação registrada com sucesso.';

        $_SESSION['internacional_csrf'] =
            bin2hex(random_bytes(32));

    } catch (DomainException $e) {
        $erro =
            mcfMensagemErro($e);

    } catch (Throwable $e) {
        error_log(
            'MyCashFlow internacional: ' .
            mcfMensagemErro($e)
        );

        $erro =
            'Não foi possível registrar a operação. Consulte o registro de erros do servidor.';

    } finally {
        if ($bloqueado) {
            try {
                $conn->query(
                    "SELECT RELEASE_LOCK(
                        'mycashflow_investimentos_internacionais_registro'
                    )"
                );

            } catch (Throwable $e) {
                error_log(
                    'MyCashFlow: falha ao liberar bloqueio internacional.'
                );
            }
        }
    }

    if ($erro === '') {
        header(
            'Location: investimentos_internacionais.php' .
            (
                $busca !== ''
                    ? '?' .
                        http_build_query(
                            ['busca' => $busca]
                        )
                    : ''
            )
        );
        exit;
    }
}

$posicoes = [];
$ops = [];
$cotacoes_completas = $estrutura_ok;

if ($estrutura_ok) {
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
                    logo,
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

        $ops =
            $stmt
                ->get_result()
                ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $posicoes =
            internacionalConsolidar($ops);

        $tickers =
            array_values(
                array_unique(
                    array_column(
                        $posicoes,
                        'ticker'
                    )
                )
            );

        $av_cotacoes =
            !empty($tickers)
                ? mercadoApi()->stocks(
                    $tickers,
                    'USD'
                )
                : [];

        foreach ($posicoes as &$p) {
            $p['api'] =
                $av_cotacoes[$p['ticker']] ??
                [
                    'price' => null,
                    'logo' => null,
                    'stale' => true
                ];

            $cotacao =
                $p['api']['price'] !== null
                    ? (string)$p['api']['price']
                    : null;

            $logo =
                $p['api']['logo'] ?? null;

            $p['valor_mercado_atual'] =
                $cotacao;

            $p['logo'] =
                $logo ?? $p['logo'];

            $p['resultado'] =
                $cotacao !== null
                    ? bcsub(
                        bcmul(
                            $cotacao,
                            $p['quantidade_total'],
                            24
                        ),
                        $p['total_investido_usd'],
                        24
                    )
                    : null;

            if ($cotacao === null) {
                $cotacoes_completas = false;
            }
        }

        unset($p);

    } catch (Throwable $e) {
        $posicoes = [];
        $cotacoes_completas = false;

        $erro_carteira =
            $e instanceof DomainException
                ? mcfMensagemErro($e)
                : 'Não foi possível carregar a carteira.';
    }
}

$ordens = [];

if ($busca !== '') {
    try {
        $termo = $busca;

        if (
            preg_match(
                '~^\d{2}/\d{2}/\d{4}$~D',
                $termo
            )
        ) {
            $partes =
                explode('/', $termo);

            $termo =
                $partes[2] . '-' .
                $partes[1] . '-' .
                $partes[0];
        }

        $termo =
            '%' . $termo . '%';

        $stmt =
            $conn->prepare("
                SELECT
                    ticker,
                    tipo_ativo,
                    quantidade,
                    valor_unitario,
                    data,
                    valor_mercado,
                    tipo_operacao
                FROM investimentos_internacionais
                WHERE usuario_id = ?
                  AND (
                        ticker LIKE ?
                        OR data LIKE ?
                      )
                ORDER BY data DESC, id DESC
            ");

        $stmt->bind_param(
            'iss',
            $usuario_id,
            $termo,
            $termo
        );

        $stmt->execute();

        $ordens =
            $stmt
                ->get_result()
                ->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

    } catch (Throwable $e) {
        $erro =
            'Não foi possível consultar o histórico.';
    }
}

$cambio =
    mercadoApi()->fx();

$cotacao_usd =
    $cambio['USD']['price'];

$cotacao_eur =
    $cambio['EUR']['price'];

$av_custo = '0';

foreach ($posicoes as $av_posicao) {
    $av_custo =
        bcadd(
            $av_custo,
            $av_posicao['total_investido_usd'],
            24
        );
}

$av_ok =
    $estrutura_ok &&
    $erro_carteira === '';

$av_mercado = '0';

foreach ($posicoes as $av_posicao) {
    if (
        $av_posicao['valor_mercado_atual'] !== null
    ) {
        $av_mercado =
            bcadd(
                $av_mercado,
                bcmul(
                    $av_posicao['valor_mercado_atual'],
                    $av_posicao['quantidade_total'],
                    24
                ),
                24
            );
    }
}

$av_valores_ok =
    $av_ok &&
    $cotacoes_completas;

$av_ganho =
    $av_valores_ok
        ? bcsub(
            $av_mercado,
            $av_custo,
            24
        )
        : null;

$av_modal =
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    $erro !== ''
        ? 'modal-operacao'
        : (
            $busca !== ''
                ? 'modal-historico'
                : ''
        );
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>Investimentos Internacionais</title>

    <link
        rel="stylesheet"
        href="../assets/css/style-acoes-internacionais.css?v=20260913"
    >
</head>

<body
    class="av-body"
    data-modal-inicial="<?= internacionalEscape($av_modal) ?>"
>
    <?php include('../includes/header.php'); ?>
    <?php include('../includes/menu.php'); ?>

    <main class="av-page">
        <a
            class="av-back"
            href="investimentos.php"
        >
            ← Resumo de Investimentos
        </a>

        <div class="av-top">
            <div>
                <h1>Investimentos Internacionais</h1>

                <p>
                    Gerencie suas posições em dólares e operações fracionadas.
                </p>
            </div>

            <div class="av-actions">
                <button
                    type="button"
                    class="av-primary"
                    data-open="modal-operacao"
                >
                    Nova operação
                </button>

                <button
                    type="button"
                    data-open="modal-historico"
                >
                    Pesquisar ordens
                </button>
            </div>
        </div>

        <?php if ($mensagem !== '') { ?>
            <p
                class="av-success"
                role="status"
            >
                <?= internacionalEscape($mensagem) ?>
            </p>
        <?php } ?>

        <?php if ($erro !== '') { ?>
            <p
                class="av-alert"
                role="alert"
            >
                <?= internacionalEscape($erro) ?>
            </p>
        <?php } ?>

        <?php if ($erro_carteira !== '') { ?>
            <p
                class="av-alert"
                role="alert"
            >
                <?= internacionalEscape($erro_carteira) ?>
            </p>
        <?php } ?>

        <div
            class="av-cambio"
            aria-label="Cotação do dólar e euro"
        >
            <strong>Câmbio de referência</strong>

            <span>
                USD/BRL
                <b>
                    <?= $cotacao_usd !== null
                        ? 'R$ ' .
                            number_format(
                                $cotacao_usd,
                                4,
                                ',',
                                '.'
                            )
                        : 'Indisponível' ?>
                </b>
            </span>

            <span>
                EUR/BRL
                <b>
                    <?= $cotacao_eur !== null
                        ? 'R$ ' .
                            number_format(
                                $cotacao_eur,
                                4,
                                ',',
                                '.'
                            )
                        : 'Indisponível' ?>
                </b>
            </span>

            <small>
                Carteira apresentada em USD.
            </small>

            <details class="av-quote-info">
                <summary>
                    Fonte e horário do câmbio<?= $cambio['USD']['stale'] || $cambio['EUR']['stale']
                        ? ' · atualização pendente'
                        : '' ?>
                </summary>

                <p>
                    <?= internacionalEscape(
                        mercadoLegenda(
                            $cambio['USD']
                        )
                    ) ?>
                </p>

                <p>
                    EUR:
                    <?= internacionalEscape(
                        mercadoLegenda(
                            $cambio['EUR']
                        )
                    ) ?>
                </p>
            </details>
        </div>

        <section
            class="av-card"
            aria-labelledby="carteira-titulo"
        >
            <div class="av-summary">
                <div>
                    <h2 id="carteira-titulo">
                        Posição consolidada
                    </h2>

                    <small>
                        <?= $av_ok
                            ? count($posicoes) .
                                ' ativo(s) em carteira'
                            : 'Consulta indisponível' ?>
                        · USD
                    </small>
                </div>

                <div class="av-metrics">
                    <div class="av-summary-value">
                        <span>
                            Custo das posições atuais
                        </span>

                        <strong>
                            <?= $av_ok
                                ? 'US$ ' .
                                    number_format(
                                        $av_custo,
                                        2,
                                        ',',
                                        '.'
                                    )
                                : 'Indisponível' ?>
                        </strong>
                    </div>

                    <div class="av-summary-value">
                        <span>
                            Valor atual da carteira
                        </span>

                        <strong>
                            <?= $av_valores_ok
                                ? 'US$ ' .
                                    number_format(
                                        $av_mercado,
                                        2,
                                        ',',
                                        '.'
                                    )
                                : 'Indisponível' ?>
                        </strong>

                        <small>
                            Pelas cotações consultadas
                        </small>
                    </div>

                    <div class="av-summary-value">
                        <span>
                            Resultado não realizado
                        </span>

                        <strong
                            class="<?= $av_ganho !== null &&
                                (float)$av_ganho < 0
                                    ? 'av-negative'
                                    : 'av-positive' ?>"
                        >
                            <?= $av_ganho !== null
                                ? (
                                    (float)$av_ganho < 0
                                        ? '- '
                                        : '+ '
                                ) .
                                    'US$ ' .
                                    number_format(
                                        abs(
                                            (float)$av_ganho
                                        ),
                                        2,
                                        ',',
                                        '.'
                                    )
                                : 'Indisponível' ?>
                        </strong>

                        <small>
                            <?= carteiraPercentual(
                                $av_ganho,
                                $av_custo
                            ) ?>
                        </small>
                    </div>
                </div>
            </div>

            <?php if (
                !$cotacoes_completas &&
                $av_ok
            ) { ?>
                <p class="av-notice">
                    Uma ou mais cotações estão indisponíveis.
                    O resultado total não pôde ser calculado.
                </p>
            <?php } ?>

            <div
                class="av-scroll"
                tabindex="0"
                role="region"
                aria-label="Tabela de posições"
            >
                <table class="tabela-acoes">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Ticker</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Preço médio</th>
                            <th>Custo (US$)</th>
                            <th>Cotação</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            !$posicoes &&
                            $estrutura_ok &&
                            $erro_carteira === ''
                        ) { ?>
                            <tr>
                                <td colspan="8">
                                    Nenhuma posição em carteira.
                                </td>
                            </tr>
                        <?php } ?>

                        <?php
                        $total_resultado = '0';

                        foreach ($posicoes as $row) {
                            if (
                                $row['resultado'] !== null
                            ) {
                                $total_resultado =
                                    bcadd(
                                        $total_resultado,
                                        $row['resultado'],
                                        24
                                    );
                            }
                        ?>
                            <tr>
                                <td>
                                    <?php
                                    $av_logos =
                                        carteiraLogos(
                                            $row['ticker'],
                                            $row['logo'] ?? null,
                                            $ops
                                        );
                                    ?>

                                    <span
                                        class="av-logo"
                                        data-logos="<?= internacionalEscape(
                                            json_encode(
                                                $av_logos,
                                                JSON_UNESCAPED_SLASHES |
                                                JSON_INVALID_UTF8_SUBSTITUTE
                                            )
                                        ) ?>"
                                        title="<?= internacionalEscape(
                                            $row['ticker']
                                        ) ?>"
                                    >
                                        <span
                                            class="av-logo-fallback"
                                            aria-hidden="true"
                                        >
                                            <?= internacionalEscape(
                                                substr(
                                                    $row['ticker'],
                                                    0,
                                                    4
                                                )
                                            ) ?>
                                        </span>

                                        <img
                                            class="av-logo-img"
                                            alt="Logo de <?= internacionalEscape(
                                                $row['ticker']
                                            ) ?>"
                                            width="30"
                                            height="30"
                                            hidden
                                            referrerpolicy="no-referrer"
                                        >
                                    </span>
                                </td>

                                <td>
                                    <?= internacionalEscape(
                                        $row['ticker']
                                    ) ?>
                                </td>

                                <td>
                                    <span class="av-tipo-ativo av-tipo-<?= internacionalEscape($row['tipo_ativo']) ?>">
                                        <?= internacionalEscape(
                                            internacionalNomeTipoAtivo(
                                                $row['tipo_ativo']
                                            )
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= internacionalQuantidade(
                                        $row['quantidade_total']
                                    ) ?>
                                </td>

                                <td>
                                    US$
                                    <?= number_format(
                                        $row['valor_medio_ponderado'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    US$
                                    <?= number_format(
                                        $row['total_investido_usd'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $row['valor_mercado_atual'] !== null
                                        ? 'US$ ' .
                                            number_format(
                                                $row['valor_mercado_atual'],
                                                2,
                                                ',',
                                                '.'
                                            )
                                        : 'Indisponível' ?>

                                    <details class="av-quote-info">
                                        <summary>
                                            <?= !empty(
                                                $row['api']['stale']
                                            )
                                                ? 'Preço salvo · atualização pendente'
                                                : 'Fonte e horário' ?>
                                        </summary>

                                        <?= internacionalEscape(
                                            mercadoLegenda(
                                                $row['api']
                                            )
                                        ) ?>
                                    </details>
                                </td>

                                <td>
                                    <?php if (
                                        $row['resultado'] === null
                                    ) { ?>
                                        Indisponível

                                    <?php } elseif (
                                        bccomp(
                                            $row['resultado'],
                                            '0',
                                            24
                                        ) >= 0
                                    ) { ?>
                                        <span class="av-positive">
                                            + US$
                                            <?= number_format(
                                                $row['resultado'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>

                                    <?php } else { ?>
                                        <span class="av-negative">
                                            - US$
                                            <?= number_format(
                                                abs(
                                                    (float)$row['resultado']
                                                ),
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>
                                    <?php } ?>

                                    <small
                                        class="av-return-percent"
                                    >
                                        <?= carteiraPercentual(
                                            $row['resultado'],
                                            $row['total_investido_usd']
                                        ) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php if ($posicoes) { ?>
                            <tr>
                                <td
                                    colspan="7"
                                    style="text-align:right;"
                                >
                                    <strong>Total:</strong>
                                </td>

                                <td>
                                    <?php if (
                                        !$cotacoes_completas
                                    ) { ?>
                                        Indisponível

                                    <?php } elseif (
                                        bccomp(
                                            $total_resultado,
                                            '0',
                                            24
                                        ) >= 0
                                    ) { ?>
                                        <span class="av-positive">
                                            + US$
                                            <?= number_format(
                                                $total_resultado,
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>

                                    <?php } else { ?>
                                        <span class="av-negative">
                                            - US$
                                            <?= number_format(
                                                abs(
                                                    (float)$total_resultado
                                                ),
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                        </span>
                                    <?php } ?>

                                    <small
                                        class="av-return-percent"
                                    >
                                        <?= carteiraPercentual(
                                            $cotacoes_completas
                                                ? $total_resultado
                                                : null,
                                            $av_custo
                                        ) ?>
                                    </small>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php if (
                array_filter(
                    $posicoes,
                    function ($p) {
                        return !empty(
                            $p['api']['stale']
                        );
                    }
                )
            ) { ?>
                <p class="av-notice">
                    O resultado utiliza uma ou mais cotações salvas.
                    Atualização pendente; consulte o horário de cada preço.
                </p>
            <?php } ?>

            <p class="av-note">
                O resultado corresponde às posições atuais.
                O lucro ou prejuízo das vendas não está incluído.
            </p>
        </section>
    </main>

    <dialog
        id="modal-operacao"
        class="av-dialog"
        aria-labelledby="operacao-titulo"
    >
        <div class="av-dialog-top">
            <div>
                <h2 id="operacao-titulo">
                    Registrar operação
                </h2>

                <p>
                    Investimentos Internacionais · USD
                </p>
            </div>

            <button
                type="button"
                data-close
                aria-label="Fechar operação"
            >
                ×
            </button>
        </div>

        <div class="av-dialog-content">
            <?php if (
                $_SERVER['REQUEST_METHOD'] === 'POST' &&
                $erro !== ''
            ) { ?>
                <p
                    class="av-alert"
                    role="alert"
                >
                    <?= internacionalEscape($erro) ?>
                </p>
            <?php } ?>

            <form
                class="form-acoes"
                method="POST"
                id="form-internacional"
            >
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= internacionalEscape(
                        $_SESSION['internacional_csrf']
                    ) ?>"
                >

                <input
                    type="hidden"
                    name="modo"
                    id="modo"
                    value="<?= internacionalEscape(
                        $form['modo']
                    ) ?>"
                >

                <label for="tipo_ativo">
                    Tipo de ativo
                </label>

                <select
                    name="tipo_ativo"
                    id="tipo_ativo"
                    required
                >
                    <option
                        value="stock"
                        <?= $form['tipo_ativo'] === 'stock'
                            ? 'selected'
                            : '' ?>
                    >
                        Stock
                    </option>

                    <option
                        value="etf"
                        <?= $form['tipo_ativo'] === 'etf'
                            ? 'selected'
                            : '' ?>
                    >
                        ETF
                    </option>

                    <option
                        value="reit"
                        <?= $form['tipo_ativo'] === 'reit'
                            ? 'selected'
                            : '' ?>
                    >
                        REIT
                    </option>

                    <option
                        value="adr"
                        <?= $form['tipo_ativo'] === 'adr'
                            ? 'selected'
                            : '' ?>
                    >
                        ADR
                    </option>
                </select>

                <label for="op-ticker">
                    Ticker
                </label>

                <input
                    type="text"
                    id="op-ticker"
                    name="ticker"
                    maxlength="20"
                    placeholder="Ex: AAPL, VOO, O"
                    value="<?= internacionalEscape(
                        $form['ticker']
                    ) ?>"
                    required
                >

                <label for="valor_unitario">
                    Preço unitário de execução (US$)
                </label>

                <input
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    name="valor_unitario"
                    id="valor_unitario"
                    value="<?= internacionalEscape(
                        $form['valor_unitario']
                    ) ?>"
                    required
                >

                <label for="quantidade">
                    Quantidade de unidades
                </label>

                <input
                    type="number"
                    step="0.00000001"
                    min="0.00000001"
                    name="quantidade"
                    id="quantidade"
                    value="<?= internacionalEscape(
                        $form['quantidade']
                    ) ?>"
                >

                <label for="valor_operacao">
                    Valor da operação (US$)
                </label>

                <input
                    type="number"
                    step="0.00000001"
                    min="0.00000001"
                    name="valor_operacao"
                    id="valor_operacao"
                    value="<?= internacionalEscape(
                        $form['valor_operacao']
                    ) ?>"
                >

                <p
                    id="previa-operacao"
                    aria-live="polite"
                >
                    Preencha o preço e a quantidade ou o valor da operação.
                </p>

                <label for="op-data">
                    Data da operação
                </label>

                <input
                    type="date"
                    id="op-data"
                    name="data"
                    value="<?= internacionalEscape(
                        $form['data']
                    ) ?>"
                    required
                >

                <div class="acao-buttons">
                    <button
                        type="button"
                        aria-pressed="<?= $form['acao'] === 'compra'
                            ? 'true'
                            : 'false' ?>"
                        class="btn-acao compra<?= $form['acao'] === 'compra'
                            ? ' active'
                            : '' ?>"
                        onclick="selecionarAcao('compra')"
                    >
                        Compra
                    </button>

                    <button
                        type="button"
                        aria-pressed="<?= $form['acao'] === 'venda'
                            ? 'true'
                            : 'false' ?>"
                        class="btn-acao venda<?= $form['acao'] === 'venda'
                            ? ' active'
                            : '' ?>"
                        onclick="selecionarAcao('venda')"
                    >
                        Venda
                    </button>
                </div>

                <input
                    type="hidden"
                    name="acao"
                    id="acao"
                    value="<?= internacionalEscape(
                        $form['acao']
                    ) ?>"
                >

                <div class="av-form-actions">
                    <button
                        type="button"
                        data-close
                    >
                        Cancelar
                    </button>

                    <button
                        class="av-primary"
                        type="submit"
                        <?= !$estrutura_ok
                            ? 'disabled'
                            : '' ?>
                    >
                        Registrar
                    </button>
                </div>
            <?= mcfCsrfField() ?></form>
        </div>
    </dialog>

    <dialog
        id="modal-historico"
        class="av-dialog av-dialog-wide"
        aria-labelledby="historico-titulo"
    >
        <div class="av-dialog-top">
            <div>
                <h2 id="historico-titulo">
                    Pesquisar ordens
                </h2>

                <p>
                    Consulte compras e vendas por ticker ou data.
                </p>
            </div>

            <button
                type="button"
                data-close
                aria-label="Fechar pesquisa"
            >
                ×
            </button>
        </div>

        <div class="av-dialog-content">
            <form
                method="GET"
                class="av-search"
            >
                <label for="busca">
                    Ticker ou data
                    (DD/MM/AAAA ou AAAA-MM-DD)
                </label>

                <div>
                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        value="<?= internacionalEscape(
                            $busca
                        ) ?>"
                        placeholder="AAPL ou 09/09/2026"
                    >

                    <button
                        type="submit"
                        class="av-primary"
                    >
                        Pesquisar
                    </button>

                    <a
                        class="av-button"
                        href="investimentos_internacionais.php"
                    >
                        Limpar
                    </a>
                </div>
            </form>

            <div
                class="av-scroll"
                tabindex="0"
                role="region"
                aria-label="Resultados da pesquisa"
            >
                <?php if ($busca !== '') { ?>
                    <?php if (!$ordens) { ?>
                        <p>
                            Nenhuma ordem encontrada para
                            <strong>
                                <?= internacionalEscape(
                                    $busca
                                ) ?>
                            </strong>.
                        </p>

                    <?php } else { ?>
                        <table class="tabela-acoes">
                            <thead>
                                <tr>
                                    <th>Ticker</th>
                                    <th>Ativo</th>
                                    <th>Quantidade</th>
                                    <th>
                                        Valor Unitário (US$)
                                    </th>
                                    <th>Data</th>
                                    <th>Valor Mercado</th>
                                    <th>Operação</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach (
                                    $ordens as $ordem
                                ) { ?>
                                    <tr>
                                        <td>
                                            <?= internacionalEscape(
                                                $ordem['ticker']
                                            ) ?>
                                        </td>

                                        <td>
                                            <span class="av-tipo-ativo av-tipo-<?= internacionalEscape($ordem['tipo_ativo']) ?>">
                                                <?= internacionalEscape(
                                                    internacionalNomeTipoAtivo(
                                                        $ordem['tipo_ativo']
                                                    )
                                                ) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= internacionalQuantidade(
                                                ltrim(
                                                    (string)$ordem['quantidade'],
                                                    '-'
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            US$
                                            <?= number_format(
                                                $ordem['valor_unitario'],
                                                4,
                                                ',',
                                                '.'
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $ordem['data']
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= $ordem['valor_mercado'] !== null
                                                ? 'US$ ' .
                                                    number_format(
                                                        $ordem['valor_mercado'],
                                                        2,
                                                        ',',
                                                        '.'
                                                    )
                                                : '-' ?>
                                        </td>

                                        <td>
                                            <?= $ordem['tipo_operacao'] === 'compra'
                                                ? '<span class="av-positive">Compra</span>'
                                                : '<span class="av-negative">Venda</span>' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>
                <?php } ?>
            </div>

            <?php if ($busca === '') { ?>
                <p class="av-note">
                    Informe um ticker ou uma data para consultar o histórico.
                </p>
            <?php } ?>
        </div>
    </dialog>

    <script>
        function decimalInteiro(texto, casas, inteiros) {
            if (
                !new RegExp(
                    '^[0-9]{1,' +
                    inteiros +
                    '}(?:\\.[0-9]{1,' +
                    casas +
                    '})?$'
                ).test(texto)
            ) {
                throw new Error(
                    'Confira a precisão dos campos: quantidade e valor até 8 casas; preço até 4.'
                );
            }

            const partes = texto.split('.');

            const n =
                BigInt(partes[0]) *
                    10n ** BigInt(casas) +
                BigInt(
                    (partes[1] || '')
                        .padEnd(casas, '0')
                );

            if (n <= 0n) {
                throw new Error(
                    'Informe valores maiores que zero.'
                );
            }

            return n;
        }

        function decimalTexto(n, casas) {
            const s =
                n
                    .toString()
                    .padStart(
                        casas + 1,
                        '0'
                    );

            return (
                s.slice(0, -casas) +
                '.' +
                s.slice(-casas)
            )
                .replace(/0+$/, '')
                .replace(/\.$/, '');
        }

        function calcularFracao(
            modo,
            origem,
            precoTexto
        ) {
            const p =
                decimalInteiro(
                    precoTexto,
                    4,
                    14
                );

            const q =
                modo === 'valor'
                    ? decimalInteiro(
                        origem,
                        8,
                        16
                    ) *
                        10000n /
                        p
                    : decimalInteiro(
                        origem,
                        8,
                        14
                    );

            if (q <= 0n) {
                throw new Error(
                    'Valor inferior à fração mínima de 0,00000001 unidade.'
                );
            }

            if (
                q >
                9999999999999999999999n
            ) {
                throw new Error(
                    'Quantidade acima do limite suportado.'
                );
            }

            const produto =
                q * p;

            if (
                produto >
                9999999999999999999999990000n
            ) {
                throw new Error(
                    'Valor da operação acima do limite suportado.'
                );
            }

            const total =
                (produto + 5000n) /
                10000n;

            if (total <= 0n) {
                throw new Error(
                    'Valor da operação inferior à precisão de registro.'
                );
            }

            return {
                quantidade:
                    decimalTexto(
                        q,
                        8
                    ),
                valor:
                    decimalTexto(
                        total,
                        8
                    )
            };
        }

        function selecionarAcao(tipo) {
            document
                .getElementById('acao')
                .value = tipo;

            document
                .querySelectorAll('.btn-acao')
                .forEach(
                    btn =>
                        btn.classList.remove(
                            'active'
                        )
                );

            document
                .querySelector(
                    '.btn-acao.' + tipo
                )
                .classList.add('active');
        }

        const campoQuantidade =
            document.getElementById(
                'quantidade'
            );

        const campoValor =
            document.getElementById(
                'valor_operacao'
            );

        const campoPreco =
            document.getElementById(
                'valor_unitario'
            );

        const campoModo =
            document.getElementById(
                'modo'
            );

        const previa =
            document.getElementById(
                'previa-operacao'
            );

        function atualizarConversao() {
            const porValor =
                campoModo.value === 'valor';

            const origem =
                porValor
                    ? campoValor
                    : campoQuantidade;

            const destino =
                porValor
                    ? campoQuantidade
                    : campoValor;

            campoQuantidade.setCustomValidity('');
            campoValor.setCustomValidity('');

            if (
                !origem.value ||
                !campoPreco.value
            ) {
                destino.value = '';

                previa.textContent =
                    'Preencha o preço e a quantidade ou o valor da operação.';

                return false;
            }

            try {
                const calculo =
                    calcularFracao(
                        campoModo.value,
                        origem.value,
                        campoPreco.value
                    );

                destino.value =
                    porValor
                        ? calculo.quantidade
                        : calculo.valor;

                previa.textContent =
                    'Operação: ' +
                    calculo.quantidade.replace(
                        '.',
                        ','
                    ) +
                    ' unidade(s), total calculado de US$ ' +
                    calculo.valor.replace(
                        '.',
                        ','
                    ) +
                    (
                        porValor
                            ? '. A quantidade é limitada a oito casas, sem exceder o valor informado.'
                            : '.'
                    );

                return true;

            } catch (e) {
                destino.value = '';

                previa.textContent =
                    e.message;

                origem.setCustomValidity(
                    e.message
                );

                return false;
            }
        }

        campoQuantidade.addEventListener(
            'input',
            () => {
                campoModo.value =
                    'quantidade';

                atualizarConversao();
            }
        );

        campoValor.addEventListener(
            'input',
            () => {
                campoModo.value =
                    'valor';

                atualizarConversao();
            }
        );

        campoPreco.addEventListener(
            'input',
            atualizarConversao
        );

        document
            .getElementById(
                'form-internacional'
            )
            .addEventListener(
                'submit',
                event => {
                    if (
                        !atualizarConversao()
                    ) {
                        event.preventDefault();

                        document
                            .getElementById(
                                'form-internacional'
                            )
                            .reportValidity();

                    } else if (
                        ![
                            'compra',
                            'venda'
                        ].includes(
                            document
                                .getElementById(
                                    'acao'
                                )
                                .value
                        )
                    ) {
                        event.preventDefault();

                        previa.textContent =
                            'Selecione Compra ou Venda antes de registrar.';
                    }
                }
            );

        atualizarConversao();

        let ultimoAcionador = null;

        function abrirModal(
            id,
            acionador
        ) {
            const modal =
                document.getElementById(id);

            if (
                !modal ||
                modal.open
            ) {
                return;
            }

            ultimoAcionador =
                acionador || null;

            modal.showModal();
        }

        document
            .querySelectorAll(
                '[data-open]'
            )
            .forEach(
                botao => {
                    botao.addEventListener(
                        'click',
                        () =>
                            abrirModal(
                                botao.dataset.open,
                                botao
                            )
                    );
                }
            );

        document
            .querySelectorAll(
                '[data-close]'
            )
            .forEach(
                botao => {
                    botao.addEventListener(
                        'click',
                        () =>
                            botao
                                .closest(
                                    'dialog'
                                )
                                .close()
                    );
                }
            );

        document
            .querySelectorAll('dialog')
            .forEach(
                modal => {
                    modal.addEventListener(
                        'close',
                        () => {
                            if (
                                ultimoAcionador
                            ) {
                                ultimoAcionador.focus();
                            }
                        }
                    );
                }
            );

        document
            .querySelectorAll(
                '.btn-acao'
            )
            .forEach(
                botao => {
                    botao.addEventListener(
                        'click',
                        () => {
                            document
                                .querySelectorAll(
                                    '.btn-acao'
                                )
                                .forEach(
                                    b => {
                                        b.setAttribute(
                                            'aria-pressed',
                                            b.classList.contains(
                                                'active'
                                            )
                                                ? 'true'
                                                : 'false'
                                        );
                                    }
                                );
                        }
                    );
                }
            );

        document
            .querySelectorAll(
                '.av-logo'
            )
            .forEach(
                container => {
                    const img =
                        container.querySelector(
                            'img'
                        );

                    const fallback =
                        container.querySelector(
                            '.av-logo-fallback'
                        );

                    let urls;

                    try {
                        urls =
                            JSON.parse(
                                container.dataset.logos
                            );
                    } catch (_) {
                        urls = [];
                    }

                    if (
                        !Array.isArray(urls)
                    ) {
                        urls = [];
                    }

                    let proxima = 0;

                    img.addEventListener(
                        'load',
                        () => {
                            img.hidden = false;
                            fallback.hidden = true;
                        }
                    );

                    img.addEventListener(
                        'error',
                        tentarProxima
                    );

                    function tentarProxima() {
                        img.hidden = true;
                        fallback.hidden = false;

                        if (
                            proxima <
                            urls.length
                        ) {
                            img.src =
                                urls[
                                    proxima++
                                ];
                        }
                    }

                    tentarProxima();
                }
            );

        const modalInicial =
            document.body.dataset
                .modalInicial;

        if (modalInicial) {
            abrirModal(
                modalInicial,
                document.querySelector(
                    '[data-open="' +
                    modalInicial +
                    '"]'
                )
            );
        }
    </script>

    <?php include('../includes/footer.php'); ?>
</body>
</html>