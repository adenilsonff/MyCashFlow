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

$usuario_id =
    mcfDonoId();

$mes =
    isset($_GET['mes'])
        ? (int)$_GET['mes']
        : (int)date("n");

$ano =
    isset($_GET['ano'])
        ? (int)$_GET['ano']
        : (int)date("Y");

$tipoAtivoFiltro =
    isset($_GET['tipo_ativo'])
        ? strtolower(
            trim(
                $_GET['tipo_ativo']
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

function formatarMoeda($valor) {
    return number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
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

$sql = "
    SELECT
        d.id,
        d.ticker,
        d.tipo_ativo,
        d.datacom,
        d.datapag,
        d.valor,
        d.tipo,

        (
            SELECT a.logo
            FROM investimentos_nacionais a
            WHERE a.usuario_id = d.usuario_id
              AND UPPER(a.ticker) = UPPER(d.ticker)
              AND a.tipo_ativo = d.tipo_ativo
              AND a.logo IS NOT NULL
              AND a.logo <> ''
            ORDER BY a.id DESC
            LIMIT 1
        ) AS logo,

        (
            SELECT COALESCE(
                SUM(
                    CASE
                        WHEN a.tipo_operacao = 'compra'
                            THEN ABS(a.quantidade)

                        WHEN a.tipo_operacao = 'venda'
                            THEN -ABS(a.quantidade)

                        ELSE 0
                    END
                ),
                0
            )

            FROM investimentos_nacionais a

            WHERE a.usuario_id = d.usuario_id
              AND UPPER(a.ticker) = UPPER(d.ticker)
              AND a.tipo_ativo = d.tipo_ativo
              AND a.data <= d.datacom
        ) AS quantidade_elegivel

    FROM div_datacom d

    WHERE d.usuario_id = ?
      AND d.datapag IS NOT NULL
      AND MONTH(d.datapag) = ?
      AND YEAR(d.datapag) = ?
";

if (
    $tipoAtivoFiltro !== ''
) {
    $sql .= "
        AND d.tipo_ativo = ?
    ";
}

$sql .= "
    ORDER BY
        d.datapag ASC,
        d.ticker ASC,
        d.id ASC
";

$stmt =
    $conn->prepare($sql);

if (
    $tipoAtivoFiltro !== ''
) {
    $stmt->bind_param(
        "iiis",
        $usuario_id,
        $mes,
        $ano,
        $tipoAtivoFiltro
    );
} else {
    $stmt->bind_param(
        "iii",
        $usuario_id,
        $mes,
        $ano
    );
}

$stmt->execute();

$result =
    $stmt->get_result();

$proventos = [];
$totalGeral = 0;

while (
    $row =
        $result->fetch_assoc()
) {
    $quantidadeElegivel =
        (int)$row[
            'quantidade_elegivel'
        ];

    if (
        $quantidadeElegivel <= 0
    ) {
        continue;
    }

    $totalRecebido =
        $quantidadeElegivel *
        (float)$row['valor'];

    $row[
        'quantidade_elegivel'
    ] =
        $quantidadeElegivel;

    $row[
        'total_recebido'
    ] =
        $totalRecebido;

    $proventos[] = $row;

    $totalGeral +=
        $totalRecebido;
}

$stmt->close();

$totalRegistros =
    count($proventos);

$tipoFiltroTexto =
    $tipoAtivoFiltro !== ''
        ? nomeTipoAtivo(
            $tipoAtivoFiltro
        )
        : 'Todos os tipos';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">

    <title>
        Proventos a Receber
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style-divis.css?v=5"
    >
</head>

<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="proventos-valor-layout">
        <div class="proventos-valor-cabecalho">
            <div>
                <h1>
                    Proventos a Receber
                </h1>

                <p>
                    Consulte os proventos calculados com base na sua posição na Data COM.
                </p>
            </div>

            <div class="proventos-valor-acoes">
                <button
                    type="button"
                    class="btn-datacom"
                    onclick="abrirModalValor('modalFiltroValor')"
                >
                    Filtrar
                </button>

                <a
                    href="dividendos.php"
                    class="btn-datacom btn-secundario"
                >
                    Voltar
                </a>
            </div>
        </div>

        <div class="proventos-valor-resumo">
            <div class="datacom-card-resumo">
                <span>
                    Período de pagamento
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
                    Proventos encontrados
                </span>

                <strong>
                    <?= $totalRegistros ?>
                </strong>
            </div>

            <div class="datacom-card-resumo">
                <span>
                    Total de proventos
                </span>

                <strong>
                    R$
                    <?= formatarMoeda(
                        $totalGeral
                    ) ?>
                </strong>
            </div>
        </div>

        <div class="proventos-valor-lista">
            <div class="datacom-lista-cabecalho">
                <div>
                    <h2>
                        Demonstrativo de Proventos
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

            <div class="proventos-valor-tabela-wrapper">
                <table class="proventos-valor-tabela">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Ativo</th>
                            <th>Tipo</th>
                            <th>Data COM</th>
                            <th>Quantidade</th>
                            <th>Provento</th>
                            <th>Valor por Unidade</th>
                            <th>Total</th>
                            <th>Pagamento</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (
                            !empty($proventos)
                        ) { ?>

                            <?php foreach (
                                $proventos
                                as $p
                            ) { ?>

                                <tr>
                                    <td>
                                        <?php if (
                                            !empty(
                                                $p['logo']
                                            )
                                        ) { ?>

                                            <img
                                                src="<?= htmlspecialchars(
                                                    $p['logo']
                                                ) ?>"
                                                alt="<?= htmlspecialchars(
                                                    $p['ticker']
                                                ) ?>"
                                                class="proventos-logo"
                                            >

                                        <?php } else { ?>

                                            <span class="proventos-sem-logo">
                                                -
                                            </span>

                                        <?php } ?>
                                    </td>

                                    <td class="proventos-ativo">
                                        <?= htmlspecialchars(
                                            $p['ticker']
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="datacom-tipo tipo-ativo-<?= htmlspecialchars(
                                                $p['tipo_ativo']
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                nomeTipoAtivo(
                                                    $p[
                                                        'tipo_ativo'
                                                    ]
                                                )
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= formatarData(
                                            $p['datacom']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int)$p[
                                            'quantidade_elegivel'
                                        ] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            nomeTipoProvento(
                                                $p['tipo']
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        R$
                                        <?= formatarValorProvento(
                                            $p['valor']
                                        ) ?>
                                    </td>

                                    <td class="proventos-total">
                                        R$
                                        <?= formatarMoeda(
                                            $p[
                                                'total_recebido'
                                            ]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= formatarData(
                                            $p['datapag']
                                        ) ?>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td
                                    colspan="9"
                                    class="datacom-vazio"
                                >
                                    Nenhum provento encontrado para este período e tipo de ativo.
                                </td>
                            </tr>

                        <?php } ?>
                    </tbody>

                    <?php if (
                        !empty($proventos)
                    ) { ?>

                        <tfoot>
                            <tr>
                                <td colspan="7">
                                    Total Geral de Proventos
                                </td>

                                <td class="proventos-total-geral">
                                    R$
                                    <?= formatarMoeda(
                                        $totalGeral
                                    ) ?>
                                </td>

                                <td></td>
                            </tr>
                        </tfoot>

                    <?php } ?>
                </table>
            </div>
        </div>
    </main>

    <div
        class="modal-datacom"
        id="modalFiltroValor"
    >
        <div class="modal-datacom-conteudo modal-menor">
            <div class="modal-datacom-cabecalho">
                <h2>
                    Filtrar proventos
                </h2>

                <button
                    type="button"
                    class="modal-fechar"
                    onclick="fecharModalValor('modalFiltroValor')"
                >
                    &times;
                </button>
            </div>

            <form
                method="GET"
                class="form-datacom"
            >
                <label for="valor_mes">
                    Mês de Pagamento
                </label>

                <select
                    name="mes"
                    id="valor_mes"
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

                <label for="valor_ano">
                    Ano
                </label>

                <select
                    name="ano"
                    id="valor_ano"
                    required
                >
                    <?php
                    for (
                        $y =
                            (int)date("Y") - 5;
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

                <label for="valor_tipo_ativo">
                    Tipo de Ativo
                </label>

                <select
                    name="tipo_ativo"
                    id="valor_tipo_ativo"
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

                <button
                    type="submit"
                    class="btn-datacom"
                >
                    Filtrar
                </button>
            </form>
        </div>
    </div>

    <?php include("../includes/footer.php"); ?>

    <script>
        function abrirModalValor(id) {
            document
                .getElementById(id)
                .classList
                .add('ativo');

            document
                .body
                .classList
                .add('modal-aberto');
        }

        function fecharModalValor(id) {
            document
                .getElementById(id)
                .classList
                .remove('ativo');

            document
                .body
                .classList
                .remove('modal-aberto');
        }

        document
            .querySelectorAll(
                '.modal-datacom'
            )
            .forEach(
                function(modal) {
                    modal.addEventListener(
                        'click',
                        function(event) {
                            if (
                                event.target ===
                                modal
                            ) {
                                fecharModalValor(
                                    modal.id
                                );
                            }
                        }
                    );
                }
            );

        document.addEventListener(
            'keydown',
            function(event) {
                if (
                    event.key ===
                    'Escape'
                ) {
                    document
                        .querySelectorAll(
                            '.modal-datacom.ativo'
                        )
                        .forEach(
                            function(modal) {
                                fecharModalValor(
                                    modal.id
                                );
                            }
                        );
                }
            }
        );
    </script>
</body>
</html>