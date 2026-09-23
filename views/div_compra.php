<?php
require_once __DIR__.'/../config.php';

require_once __DIR__ . '/../config.php';



if (session_status() === PHP_SESSION_NONE) {

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

}



if (!isset($_SESSION['usuario_id'])) {

    header("Location: login/login.php");

    exit;

}



require_once __DIR__ . '/../includes/mercado_api.php';



$usuario_id =

    mcfDonoId();



$mes =

    isset($_POST['mes'])

        ? (int)$_POST['mes']

        : (int)date("n");



$ano =

    isset($_POST['ano'])

        ? (int)$_POST['ano']

        : (int)date("Y");



$capital =

    isset($_POST['capital'])

        ? (float)$_POST['capital']

        : 0;



$tipoAtivoFiltro =

    isset($_POST['tipo_ativo'])

        ? strtolower(

            trim(

                $_POST['tipo_ativo']

            )

        )

        : '';



if (

    $mes < 1 ||

    $mes > 12

) {

    $mes =

        (int)date("n");

}



if (

    $ano < 2000 ||

    $ano > 2100

) {

    $ano =

        (int)date("Y");

}



if (

    $tipoAtivoFiltro !== '' &&

    !in_array(

        $tipoAtivoFiltro,

        ['acao', 'fii', 'etf', 'bdr'],

        true

    )

) {

    $tipoAtivoFiltro = '';

}



$meses = [

    1 => "Janeiro",

    2 => "Fevereiro",

    3 => "Março",

    4 => "Abril",

    5 => "Maio",

    6 => "Junho",

    7 => "Julho",

    8 => "Agosto",

    9 => "Setembro",

    10 => "Outubro",

    11 => "Novembro",

    12 => "Dezembro"

];



function formatarValorProvento($valor) {

    $valorFormatado =

        number_format(

            (float)$valor,

            8,

            ',',

            ''

        );



    $valorFormatado =

        rtrim(

            $valorFormatado,

            '0'

        );



    $valorFormatado =

        rtrim(

            $valorFormatado,

            ','

        );



    return $valorFormatado;

}



function formatarMoedaSimulador($valor) {

    return number_format(

        (float)$valor,

        2,

        ',',

        '.'

    );

}



function formatarIndicadorSimulador($valor, $percentual = false) {
    if (!is_numeric($valor) || !is_finite((float)$valor)
        || (!$percentual && $valor < 0)) {
        return '—';
    }

    $numero = (float)$valor;
    if ($percentual) {
        $numero = round($numero, 2);
        return ($numero > 0 ? '+' : '')
            . number_format($numero, 2, ',', '.') . '%';
    }

    return number_format($numero, 0, ',', '.');
}

function formatarData($data) {

    if (empty($data)) {

        return 'A definir';

    }



    return date(

        "d/m/Y",

        strtotime($data)

    );

}



function nomeTipoAtivo($tipo) {

    $tipos = [

        'acao' => 'Ação',

        'fii' => 'FII',

        'etf' => 'ETF',

        'bdr' => 'BDR'

    ];



    return

        $tipos[$tipo] ??

        $tipo;

}



function nomeTipoProvento($tipo) {

    $tipos = [

        'DIV' => 'Dividendo',

        'JCP' => 'JCP',

        'REND' => 'Rendimento'

    ];



    return

        $tipos[$tipo] ??

        $tipo;

}



function obterDadosSalvosAtivo(

    $conn,

    $usuario_id,

    $ticker,

    $tipoAtivo

) {

    $stmt = $conn->prepare("

        SELECT

            valor_mercado,

            logo



        FROM investimentos_nacionais



        WHERE usuario_id = ?

          AND UPPER(ticker) = UPPER(?)

          AND tipo_ativo = ?



        ORDER BY id DESC



        LIMIT 1

    ");



    $stmt->bind_param(

        "iss",

        $usuario_id,

        $ticker,

        $tipoAtivo

    );



    $stmt->execute();



    $result =

        $stmt->get_result();



    $dados = [

        'valor_mercado' => 0,

        'logo' => null

    ];



    if (

        $row =

            $result->fetch_assoc()

    ) {

        $dados['valor_mercado'] =

            $row['valor_mercado'] !==

            null

                ? (float)$row[

                    'valor_mercado'

                ]

                : 0;



        $dados['logo'] =

            $row['logo'] ??

            null;

    }



    $stmt->close();



    return $dados;

}



function obterCotacaoAtual($ticker) {

    $ticker =

        strtoupper(

            trim($ticker)

        );



    if ($ticker === '') {

        return null;

    }



    try {

        $cotacoes =

            mercadoApi()->stocks(

                [$ticker],

                'BRL'

            );



        if (

            !isset(

                $cotacoes[$ticker]

            )

        ) {

            return null;

        }



        $dados =

            $cotacoes[$ticker];



        if (

            !isset(

                $dados['price']

            ) ||

            $dados['price'] === null ||

            !is_numeric(

                $dados['price']

            ) ||

            (float)$dados['price'] <= 0

        ) {

            return null;

        }



        return [

            'valor' =>

                (float)$dados['price'],



            'logo' =>

                $dados['logo'] ??

                null,



            'change_percent' => $dados['change_percent'] ?? null,

            'volume' => $dados['volume'] ?? null,

            'stale' =>

                !empty(

                    $dados['stale']

                )

        ];



    } catch (Throwable $e) {

        error_log(

            'MyCashFlow simulador de proventos: falha ao consultar ' .

            $ticker .

            ': ' .

            mcfMensagemErro($e)

        );



        return null;

    }

}



$resultados = [];

$erroSimulacao = '';



if (

    isset(

        $_POST['simular_todas']

    ) &&

    $capital > 0

) {

    try {

        $hoje =

            date("Y-m-d");



        $sql = "

            SELECT

                ticker,

                tipo_ativo,

                MIN(datacom) AS primeira_datacom



            FROM div_datacom



            WHERE usuario_id = ?

              AND MONTH(datacom) = ?

              AND YEAR(datacom) = ?

              AND datacom >= ?

        ";



        if (

            $tipoAtivoFiltro !== ''

        ) {

            $sql .= "

                AND tipo_ativo = ?

            ";

        }



        $sql .= "

            GROUP BY

                ticker,

                tipo_ativo



            ORDER BY

                ticker ASC,

                tipo_ativo ASC

        ";



        $stmt =

            $conn->prepare($sql);



        if (

            $tipoAtivoFiltro !== ''

        ) {

            $stmt->bind_param(

                "iiiss",

                $usuario_id,

                $mes,

                $ano,

                $hoje,

                $tipoAtivoFiltro

            );

        } else {

            $stmt->bind_param(

                "iiis",

                $usuario_id,

                $mes,

                $ano,

                $hoje

            );

        }



        $stmt->execute();



        $result =

            $stmt->get_result();



        while (

            $ativo =

                $result->fetch_assoc()

        ) {

            $ticker =

                strtoupper(

                    trim(

                        $ativo['ticker']

                    )

                );



            $tipoAtivo =

                strtolower(

                    trim(

                        $ativo[

                            'tipo_ativo'

                        ]

                    )

                );



            if (

                $ticker === '' ||

                !in_array(

                    $tipoAtivo,

                    [

                        'acao',

                        'fii',

                        'etf',

                        'bdr'

                    ],

                    true

                )

            ) {

                continue;

            }



            $dadosSalvos =

                obterDadosSalvosAtivo(

                    $conn,

                    $usuario_id,

                    $ticker,

                    $tipoAtivo

                );



            $cotacaoAtual =

                obterCotacaoAtual(

                    $ticker

                );



            if (

                $cotacaoAtual !== null &&

                $cotacaoAtual['valor'] > 0

            ) {

                $valorMercado =

                    $cotacaoAtual[

                        'valor'

                    ];



                $logo =

                    !empty(

                        $cotacaoAtual[

                            'logo'

                        ]

                    )

                        ? $cotacaoAtual[

                            'logo'

                        ]

                        : $dadosSalvos[

                            'logo'

                        ];



                $fonteCotacao =

                    !empty(

                        $cotacaoAtual[

                            'stale'

                        ]

                    )

                        ? 'Cotação salva pela API'

                        : 'Cotação atual';



            } else {

                $valorMercado =

                    $dadosSalvos[

                        'valor_mercado'

                    ];



                $logo =

                    $dadosSalvos[

                        'logo'

                    ];



                $fonteCotacao =

                    'Última cotação salva';

            }



            if (

                $valorMercado <= 0

            ) {

                continue;

            }



            $quantidade =

                (int)floor(

                    $capital /

                    $valorMercado

                );



            if (

                $quantidade <= 0

            ) {

                continue;

            }



            $capitalInvestido =

                $quantidade *

                $valorMercado;



            $saldo =

                $capital -

                $capitalInvestido;



            $stmtProvento =

                $conn->prepare("

                    SELECT

                        datacom,

                        datapag,

                        valor,

                        tipo



                    FROM div_datacom



                    WHERE usuario_id = ?

                      AND UPPER(ticker) = UPPER(?)

                      AND tipo_ativo = ?

                      AND MONTH(datacom) = ?

                      AND YEAR(datacom) = ?

                      AND datacom >= ?



                    ORDER BY

                        datacom ASC,

                        id ASC

                ");



            $stmtProvento->bind_param(

                "issiis",

                $usuario_id,

                $ticker,

                $tipoAtivo,

                $mes,

                $ano,

                $hoje

            );



            $stmtProvento->execute();



            $resultProvento =

                $stmtProvento

                    ->get_result();



            $proventoTotal = 0;



            $detalhesProvento = [];



            while (

                $provento =

                    $resultProvento

                        ->fetch_assoc()

            ) {

                $valorProvento =

                    (float)$provento[

                        'valor'

                    ];



                if (

                    $valorProvento <= 0

                ) {

                    continue;

                }



                $rendimento =

                    $quantidade *

                    $valorProvento;



                $proventoTotal +=

                    $rendimento;



                $detalhesProvento[] = [

                    'tipo' =>

                        $provento[

                            'tipo'

                        ],



                    'valor' =>

                        $valorProvento,



                    'datacom' =>

                        $provento[

                            'datacom'

                        ],



                    'datapag' =>

                        $provento[

                            'datapag'

                        ],



                    'rendimento' =>

                        $rendimento

                ];

            }



            $stmtProvento->close();



            if (

                $proventoTotal <= 0

            ) {

                continue;

            }



            $retornoPercentual =

                $capitalInvestido > 0

                    ? (

                        $proventoTotal /

                        $capitalInvestido

                    ) * 100

                    : 0;



            $resultados[] = [

                'ticker' =>

                    $ticker,



                'tipo_ativo' =>

                    $tipoAtivo,



                'logo' =>

                    $logo,



                'valor_mercado' =>

                    $valorMercado,



                'fonte_cotacao' =>

                    $fonteCotacao,



                'change_percent' => $cotacaoAtual['change_percent'] ?? null,

                'volume' => $cotacaoAtual['volume'] ?? null,

                'capital_investido' =>

                    $capitalInvestido,



                'saldo' =>

                    $saldo,



                'quantidade' =>

                    $quantidade,



                'provento_total' =>

                    $proventoTotal,



                'retorno_percentual' =>

                    $retornoPercentual,



                'detalhes' =>

                    $detalhesProvento

            ];

        }



        $stmt->close();



        usort(

            $resultados,

            function ($a, $b) {

                return

                    $b[

                        'provento_total'

                    ]

                    <=>

                    $a[

                        'provento_total'

                    ];

            }

        );



    } catch (Throwable $e) {

        error_log(

            'MyCashFlow simulador de proventos: ' .

            mcfMensagemErro($e)

        );



        $erroSimulacao =

            'Não foi possível realizar a simulação.';

    }

}



$simulacaoRealizada =

    isset(

        $_POST['simular_todas']

    ) &&

    $capital > 0;



$tipoFiltroTexto =

    $tipoAtivoFiltro !== ''

        ? nomeTipoAtivo(

            $tipoAtivoFiltro

        )

        : 'Todos os tipos';



$melhorResultado =

    !empty($resultados)

        ? $resultados[0]

        : null;

?>



<!DOCTYPE html>

<html lang="pt-br">

<head>

    <meta charset="UTF-8">



    <title>

        Simulador de Proventos

    </title>



    <link

        rel="stylesheet"

        href="../assets/css/style-divis.css?v=5"

    >

</head>



<body>

    <?php include("../includes/header.php"); ?>

    <?php include("../includes/menu.php"); ?>



    <main class="simulador-layout">

        <div class="simulador-cabecalho">

            <div>

                <h1>

                    Simulador de Proventos

                </h1>



                <p>

                    Compare quanto cada oportunidade pode gerar em proventos antes da Data COM.

                </p>

            </div>



            <div class="simulador-acoes">

                <a

                    href="dividendos.php"

                    class="btn-datacom btn-secundario"

                >

                    Voltar

                </a>

            </div>

        </div>



        <div class="simulador-painel">

            <div class="simulador-painel-titulo">

                <div>

                    <h2>

                        Simular investimento

                    </h2>



                    <p>

                        Informe o capital disponível e o período da Data COM.

                    </p>

                </div>

            </div>



            <form

                method="POST"

                class="simulador-form"

            >

                <div class="simulador-campo simulador-capital">

                    <label for="capital">

                        Capital disponível

                    </label>



                    <div class="simulador-input-moeda">

                        <span>R$</span>



                        <input

                            type="number"

                            step="0.01"

                            min="0.01"

                            name="capital"

                            id="capital"

                            value="<?= $capital > 0

                                ? htmlspecialchars(

                                    (string)$capital

                                )

                                : '' ?>"

                            placeholder="0,00"

                            required

                        >

                    </div>

                </div>



                <div class="simulador-campo">

                    <label for="mes">

                        Mês da Data COM

                    </label>



                    <select

                        name="mes"

                        id="mes"

                        required

                    >

                        <?php foreach (

                            $meses

                            as $numero => $nome

                        ) { ?>



                            <option

                                value="<?= $numero ?>"

                                <?= $numero === $mes

                                    ? 'selected'

                                    : '' ?>

                            >

                                <?= htmlspecialchars(

                                    $nome

                                ) ?>

                            </option>



                        <?php } ?>

                    </select>

                </div>



                <div class="simulador-campo">

                    <label for="ano">

                        Ano

                    </label>



                    <select

                        name="ano"

                        id="ano"

                        required

                    >

                        <?php

                        for (

                            $y =

                                (int)date("Y");

                            $y <=

                                (int)date("Y") + 5;

                            $y++

                        ) {

                        ?>



                            <option

                                value="<?= $y ?>"

                                <?= $y === $ano

                                    ? 'selected'

                                    : '' ?>

                            >

                                <?= $y ?>

                            </option>



                        <?php } ?>

                    </select>

                </div>



                <div class="simulador-campo">

                    <label for="tipo_ativo">

                        Tipo de Ativo

                    </label>



                    <select

                        name="tipo_ativo"

                        id="tipo_ativo"

                    >

                        <option

                            value=""

                            <?= $tipoAtivoFiltro === ''

                                ? 'selected'

                                : '' ?>

                        >

                            Todos os tipos

                        </option>



                        <option

                            value="acao"

                            <?= $tipoAtivoFiltro === 'acao'

                                ? 'selected'

                                : '' ?>

                        >

                            Ação

                        </option>



                        <option

                            value="fii"

                            <?= $tipoAtivoFiltro === 'fii'

                                ? 'selected'

                                : '' ?>

                        >

                            FII

                        </option>



                        <option

                            value="etf"

                            <?= $tipoAtivoFiltro === 'etf'

                                ? 'selected'

                                : '' ?>

                        >

                            ETF

                        </option>



                        <option

                            value="bdr"

                            <?= $tipoAtivoFiltro === 'bdr'

                                ? 'selected'

                                : '' ?>

                        >

                            BDR

                        </option>

                    </select>

                </div>



                <div class="simulador-campo simulador-campo-botao">

                    <button

                        type="submit"

                        name="simular_todas"

                        class="btn-datacom"

                    >

                        Simular

                    </button>

                </div>

            <?= mcfCsrfField() ?></form>

        </div>



        <?php if (

            $erroSimulacao !== ''

        ) { ?>



            <div class="simulador-mensagem erro">

                <?= htmlspecialchars(

                    $erroSimulacao

                ) ?>

            </div>



        <?php } ?>



        <?php if (

            $simulacaoRealizada &&

            $erroSimulacao === ''

        ) { ?>



            <div class="simulador-resumo">

                <div class="datacom-card-resumo">

                    <span>

                        Capital disponível

                    </span>



                    <strong>

                        R$

                        <?= formatarMoedaSimulador(

                            $capital

                        ) ?>

                    </strong>

                </div>



                <div class="datacom-card-resumo">

                    <span>

                        Período da Data COM

                    </span>



                    <strong>

                        <?= htmlspecialchars(

                            $meses[$mes]

                        ) ?>/<?= $ano ?>

                    </strong>

                </div>



                <div class="datacom-card-resumo">

                    <span>

                        Tipo de ativo

                    </span>



                    <strong>

                        <?= htmlspecialchars(

                            $tipoFiltroTexto

                        ) ?>

                    </strong>

                </div>



                <div class="datacom-card-resumo">

                    <span>

                        Oportunidades encontradas

                    </span>



                    <strong>

                        <?= count(

                            $resultados

                        ) ?>

                    </strong>

                </div>

            </div>



            <?php if (

                $melhorResultado !== null

            ) { ?>



                <div class="simulador-destaque">

                    <div>

                        <span>

                            Maior provento estimado

                        </span>



                        <strong>

                            <?= htmlspecialchars(

                                $melhorResultado[

                                    'ticker'

                                ]

                            ) ?>



                            ·



                            <span

                                class="tipo-ativo-<?= htmlspecialchars(

                                    $melhorResultado[

                                        'tipo_ativo'

                                    ]

                                ) ?>"

                            >

                                <?= htmlspecialchars(

                                    nomeTipoAtivo(

                                        $melhorResultado[

                                            'tipo_ativo'

                                        ]

                                    )

                                ) ?>

                            </span>

                        </strong>

                    </div>



                    <div>

                        <span>

                            Proventos estimados

                        </span>



                        <strong>

                            R$

                            <?= formatarMoedaSimulador(

                                $melhorResultado[

                                    'provento_total'

                                ]

                            ) ?>

                        </strong>

                    </div>



                    <div>

                        <span>

                            DY estimado

                        </span>



                        <strong>

                            <?= number_format(

                                $melhorResultado[

                                    'retorno_percentual'

                                ],

                                2,

                                ',',

                                '.'

                            ) ?>%

                        </strong>

                    </div>

                </div>



            <?php } ?>



            <div class="simulador-aviso">
                <strong>Importante sobre a simulação</strong>

                <p>
                    Esta simulação tem caráter informativo e utiliza os proventos
                    cadastrados para o período, a cotação disponível e o capital
                    informado. O DY estimado representa a relação entre os proventos
                    considerados na simulação e o valor efetivamente investido.
                </p>

                <p>
                    Um maior valor de proventos ou DY estimado não significa
                    necessariamente que o ativo seja o melhor investimento.
                    A decisão de investimento deve considerar outros fatores,
                    como riscos, situação financeira do ativo ou emissor,
                    liquidez, histórico de resultados e sustentabilidade
                    dos proventos.
                </p>
            </div>

            <div class="simulador-lista">

                <div class="datacom-lista-cabecalho">

                    <div>

                        <h2>

                            Comparativo de Indicadores

                        </h2>



                        <p>

                            <?= htmlspecialchars(

                                $meses[$mes]

                            ) ?>/<?= $ano ?>



                            ·



                            <?= htmlspecialchars(

                                $tipoFiltroTexto

                            ) ?>

                        </p>

                    </div>

                </div>



                <?php if (

                    !empty($resultados)

                ) { ?>



                    <p class="simulador-fonte">
                        Variação e volume acompanham a cotação consultada e podem refletir dados salvos.
                        Volume indica a quantidade negociada, não um valor em reais.
                        “—” indica dado indisponível.
                    </p>

                    <div class="simulador-tabela-wrapper">

                        <table class="simulador-tabela">

                            <thead>

                                <tr>

                                    <th>Logo</th>

                                    <th>Ativo</th>

                                    <th>Tipo</th>

                                    <th>Cotação</th>

                                    <th>Variação (%)</th>

                                    <th>Volume</th>

                                    <th>Investido</th>

                                    <th>Saldo</th>

                                    <th>Qtd.</th>

                                    <th>Proventos</th>

                                    <th>DY estimado</th>

                                </tr>

                            </thead>



                            <tbody>

                                <?php foreach (

                                    $resultados

                                    as $indice => $ativo

                                ) { ?>



                                    <tr

                                        class="<?= $indice === 0

                                            ? 'simulador-melhor-linha'

                                            : '' ?>"

                                    >

                                        <td>

                                            <?php if (

                                                !empty(

                                                    $ativo['logo']

                                                )

                                            ) { ?>



                                                <img

                                                    src="<?= htmlspecialchars(

                                                        $ativo['logo']

                                                    ) ?>"

                                                    alt="<?= htmlspecialchars(

                                                        $ativo['ticker']

                                                    ) ?>"

                                                    class="simulador-logo"

                                                >



                                            <?php } else { ?>



                                                <span class="proventos-sem-logo">

                                                    -

                                                </span>



                                            <?php } ?>

                                        </td>



                                        <td class="simulador-ativo">

                                            <?= htmlspecialchars(

                                                $ativo['ticker']

                                            ) ?>



                                            <?php if (

                                                $indice === 0

                                            ) { ?>



                                                <span class="simulador-melhor">

                                                    Maior provento

                                                </span>



                                            <?php } ?>

                                        </td>



                                        <td>

                                            <span

                                                class="datacom-tipo tipo-ativo-<?= htmlspecialchars(

                                                    $ativo[

                                                        'tipo_ativo'

                                                    ]

                                                ) ?>"

                                            >

                                                <?= htmlspecialchars(

                                                    nomeTipoAtivo(

                                                        $ativo[

                                                            'tipo_ativo'

                                                        ]

                                                    )

                                                ) ?>

                                            </span>

                                        </td>



                                        <td>

                                            <strong>

                                                R$

                                                <?= formatarMoedaSimulador(

                                                    $ativo[

                                                        'valor_mercado'

                                                    ]

                                                ) ?>

                                            </strong>



                                            <small class="simulador-fonte">

                                                <?= htmlspecialchars(

                                                    $ativo[

                                                        'fonte_cotacao'

                                                    ]

                                                ) ?>

                                            </small>

                                        </td>



                                        <td>
                                            <?= formatarIndicadorSimulador($ativo['change_percent'] ?? null, true) ?>
                                        </td>

                                        <td>
                                            <?= formatarIndicadorSimulador($ativo['volume'] ?? null) ?>
                                        </td>


                                        <td>

                                            R$

                                            <?= formatarMoedaSimulador(

                                                $ativo[

                                                    'capital_investido'

                                                ]

                                            ) ?>

                                        </td>



                                        <td>

                                            R$

                                            <?= formatarMoedaSimulador(

                                                $ativo[

                                                    'saldo'

                                                ]

                                            ) ?>

                                        </td>



                                        <td>

                                            <?= (int)$ativo[

                                                'quantidade'

                                            ] ?>

                                        </td>



                                        <td class="simulador-provento">

                                            R$

                                            <?= formatarMoedaSimulador(

                                                $ativo[

                                                    'provento_total'

                                                ]

                                            ) ?>

                                        </td>



                                        <td class="simulador-retorno">

                                            <?= number_format(

                                                $ativo[

                                                    'retorno_percentual'

                                                ],

                                                2,

                                                ',',

                                                '.'

                                            ) ?>%

                                        </td>

                                    </tr>



                                    <?php foreach (

                                        $ativo[

                                            'detalhes'

                                        ]

                                        as $p

                                    ) { ?>



                                        <tr class="simulador-detalhe">

                                            <td colspan="2">

                                                <strong>

                                                    <?= htmlspecialchars(

                                                        nomeTipoProvento(

                                                            $p[

                                                                'tipo'

                                                            ]

                                                        )

                                                    ) ?>

                                                </strong>

                                            </td>



                                            <td colspan="2">

                                                R$

                                                <?= formatarValorProvento(

                                                    $p[

                                                        'valor'

                                                    ]

                                                ) ?>

                                                por unidade

                                            </td>



                                            <td colspan="3">

                                                Data COM:



                                                <strong>

                                                    <?= formatarData(

                                                        $p[

                                                            'datacom'

                                                        ]

                                                    ) ?>

                                                </strong>

                                            </td>



                                            <td colspan="2">

                                                Pagamento:



                                                <strong>

                                                    <?= formatarData(

                                                        $p[

                                                            'datapag'

                                                        ]

                                                    ) ?>

                                                </strong>

                                            </td>



                                            <td colspan="2">

                                                Estimado:



                                                <strong>

                                                    R$

                                                    <?= formatarMoedaSimulador(

                                                        $p[

                                                            'rendimento'

                                                        ]

                                                    ) ?>

                                                </strong>

                                            </td>

                                        </tr>



                                    <?php } ?>



                                <?php } ?>

                            </tbody>

                        </table>

                    </div>



                <?php } else { ?>



                    <div class="simulador-vazio">

                        Nenhuma oportunidade de provento com Data COM válida foi encontrada para este período e tipo de ativo.

                    </div>



                <?php } ?>

            </div>



        <?php } ?>

    </main>



    <?php include("../includes/footer.php"); ?>

</body>

</html>