<?php
include __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

$tipoAtivoFiltro = isset($_GET['tipo_ativo'])
    ? strtolower(trim($_GET['tipo_ativo']))
    : '';

if ($mes < 1 || $mes > 12) {
    $mes = (int)date("n");
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date("Y");
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

function voltarPagina($mes, $ano, $tipoAtivoFiltro = '') {
    $parametros = [
        'mes' => $mes,
        'ano' => $ano
    ];

    if ($tipoAtivoFiltro !== '') {
        $parametros['tipo_ativo'] = $tipoAtivoFiltro;
    }

    header(
        "Location: div_datacom.php?" .
        http_build_query($parametros)
    );
    exit;
}

function normalizarValor($valor) {
    $valor = trim((string)$valor);
    $valor = str_replace(['R$', ' '], '', $valor);

    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    return (float)$valor;
}

function formatarValorProvento($valor) {
    $valorFormatado = number_format(
        (float)$valor,
        8,
        ',',
        ''
    );

    $valorFormatado = rtrim(
        $valorFormatado,
        '0'
    );

    $valorFormatado = rtrim(
        $valorFormatado,
        ','
    );

    return $valorFormatado;
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

    return $tipos[$tipo] ?? $tipo;
}

function nomeTipoProvento($tipo) {
    $tipos = [
        'DIV' => 'Dividendo',
        'JCP' => 'JCP',
        'REND' => 'Rendimento'
    ];

    return $tipos[$tipo] ?? $tipo;
}

function tipoProventoValido(
    $tipoAtivo,
    $tipoProvento
) {
    $permitidos = [
        'acao' => ['DIV', 'JCP'],
        'fii' => ['REND'],
        'etf' => ['DIV', 'REND'],
        'bdr' => ['DIV']
    ];

    return
        isset($permitidos[$tipoAtivo]) &&
        in_array(
            $tipoProvento,
            $permitidos[$tipoAtivo],
            true
        );
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

if (isset($_POST['nova_div'])) {
    $ticker = strtoupper(
        trim($_POST['ticker'] ?? '')
    );

    $tipoAtivo = strtolower(
        trim($_POST['tipo_ativo'] ?? '')
    );

    $datacom = trim(
        $_POST['datacom'] ?? ''
    );

    $datapagInformada = trim(
        $_POST['datapag'] ?? ''
    );

    $datapag =
        $datapagInformada !== ''
            ? $datapagInformada
            : null;

    $valor = normalizarValor(
        $_POST['valor'] ?? 0
    );

    $tipo = strtoupper(
        trim($_POST['tipo'] ?? '')
    );

    if (
        $ticker !== '' &&
        in_array(
            $tipoAtivo,
            ['acao', 'fii', 'etf', 'bdr'],
            true
        ) &&
        $datacom !== '' &&
        $valor > 0 &&
        tipoProventoValido(
            $tipoAtivo,
            $tipo
        )
    ) {
        $stmt = $conn->prepare("
            INSERT INTO div_datacom
            (
                usuario_id,
                ticker,
                tipo_ativo,
                datacom,
                datapag,
                valor,
                tipo
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issssds",
            $usuario_id,
            $ticker,
            $tipoAtivo,
            $datacom,
            $datapag,
            $valor,
            $tipo
        );

        $stmt->execute();
        $stmt->close();
    }

    voltarPagina(
        $mes,
        $ano,
        $tipoAtivoFiltro
    );
}

if (isset($_POST['ajustar_valor'])) {
    $id = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;

    $novoValor = normalizarValor(
        $_POST['novo_valor'] ?? 0
    );

    if (
        $id > 0 &&
        $novoValor > 0
    ) {
        $stmt = $conn->prepare("
            UPDATE div_datacom
            SET valor = ?
            WHERE id = ?
              AND usuario_id = ?
        ");

        $stmt->bind_param(
            "dii",
            $novoValor,
            $id,
            $usuario_id
        );

        $stmt->execute();
        $stmt->close();
    }

    voltarPagina(
        $mes,
        $ano,
        $tipoAtivoFiltro
    );
}

if (isset($_POST['deletar_div'])) {
    $id = isset($_POST['id'])
        ? (int)$_POST['id']
        : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("
            DELETE FROM div_datacom
            WHERE id = ?
              AND usuario_id = ?
        ");

        $stmt->bind_param(
            "ii",
            $id,
            $usuario_id
        );

        $stmt->execute();
        $stmt->close();
    }

    voltarPagina(
        $mes,
        $ano,
        $tipoAtivoFiltro
    );
}

$sql = "
    SELECT
        id,
        ticker,
        tipo_ativo,
        datacom,
        datapag,
        valor,
        tipo
    FROM div_datacom
    WHERE usuario_id = ?
      AND MONTH(datacom) = ?
      AND YEAR(datacom) = ?
";

if ($tipoAtivoFiltro !== '') {
    $sql .= "
        AND tipo_ativo = ?
    ";
}

$sql .= "
    ORDER BY
        datacom ASC,
        ticker ASC,
        id ASC
";

$stmt = $conn->prepare($sql);

if ($tipoAtivoFiltro !== '') {
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

$divs = [];

while ($row = $result->fetch_assoc()) {
    $divs[] = $row;
}

$stmt->close();

$totalRegistros = count($divs);

$tipoFiltroTexto =
    $tipoAtivoFiltro !== ''
        ? nomeTipoAtivo($tipoAtivoFiltro)
        : 'Todos os tipos';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">

    <title>
        Proventos - Data COM
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style-divis.css?v=5"
    >
</head>

<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="datacom-layout">
        <div class="datacom-cabecalho">
            <div>
                <h1>Data COM</h1>

                <p>
                    Cadastre e acompanhe os eventos que dão direito aos proventos.
                </p>
            </div>

            <div class="datacom-acoes">
                <button
                    type="button"
                    class="btn-datacom"
                    onclick="abrirModal('modalCadastro')"
                >
                    + Cadastrar
                </button>

                <button
                    type="button"
                    class="btn-datacom btn-secundario"
                    onclick="abrirModal('modalFiltro')"
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

        <div class="datacom-resumo">
            <div class="datacom-card-resumo">
                <span>Período</span>

                <strong>
                    <?= htmlspecialchars(
                        $meses[$mes]
                    ) ?>/<?= $ano ?>
                </strong>
            </div>

            <div class="datacom-card-resumo">
                <span>
                    Proventos cadastrados
                </span>

                <strong>
                    <?= $totalRegistros ?>
                </strong>

                <small>
                    <?= htmlspecialchars(
                        $tipoFiltroTexto
                    ) ?>
                </small>
            </div>
        </div>

        <div class="datacom-lista">
            <div class="datacom-lista-cabecalho">
                <div>
                    <h2>
                        Proventos cadastrados
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

            <div class="datacom-tabela-wrapper">
                <table class="datacom-tabela">
                    <thead>
                        <tr>
                            <th>Ativo</th>
                            <th>Tipo</th>
                            <th>Data COM</th>
                            <th>Pagamento</th>
                            <th>Valor por Unidade</th>
                            <th>Provento</th>
                            <th>Tempo Restante</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($divs)) { ?>

                            <?php foreach ($divs as $d) { ?>

                                <?php
                                $dataCom =
                                    new DateTime(
                                        $d['datacom']
                                    );

                                $dataCom->setTime(
                                    23,
                                    59,
                                    59
                                );

                                $hoje =
                                    new DateTime();

                                $hoje->setTime(
                                    0,
                                    0,
                                    0
                                );

                                $segundosRestantes =
                                    $dataCom->getTimestamp() -
                                    $hoje->getTimestamp();

                                if (
                                    $segundosRestantes <= 0
                                ) {
                                    $tempoRestante =
                                        "------";
                                } else {
                                    $diasRestantes =
                                        (int)ceil(
                                            $segundosRestantes /
                                            86400
                                        );

                                    $tempoRestante =
                                        $diasRestantes === 1
                                            ? "1 dia"
                                            : "{$diasRestantes} dias";
                                }
                                ?>

                                <tr>
                                    <td class="datacom-ativo">
                                        <?= htmlspecialchars(
                                            $d['ticker']
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="datacom-tipo tipo-ativo-<?= htmlspecialchars(
                                                $d['tipo_ativo']
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                nomeTipoAtivo(
                                                    $d['tipo_ativo']
                                                )
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= formatarData(
                                            $d['datacom']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= formatarData(
                                            $d['datapag']
                                        ) ?>
                                    </td>

                                    <td>
                                        R$
                                        <?= formatarValorProvento(
                                            $d['valor']
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            nomeTipoProvento(
                                                $d['tipo']
                                            )
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $tempoRestante
                                        ) ?>
                                    </td>

                                    <td>
                                        <div class="datacom-acoes-linha">
                                            <button
                                                type="button"
                                                class="btn-linha editar"
                                                onclick="abrirAjuste(
                                                    <?= (int)$d['id'] ?>,
                                                    '<?= htmlspecialchars(
                                                        $d['ticker'],
                                                        ENT_QUOTES
                                                    ) ?>',
                                                    '<?= htmlspecialchars(
                                                        nomeTipoAtivo(
                                                            $d['tipo_ativo']
                                                        ),
                                                        ENT_QUOTES
                                                    ) ?>',
                                                    '<?= htmlspecialchars(
                                                        formatarValorProvento(
                                                            $d['valor']
                                                        ),
                                                        ENT_QUOTES
                                                    ) ?>'
                                                )"
                                            >
                                                Editar
                                            </button>

                                            <form
                                                method="POST"
                                                class="form-excluir"
                                                onsubmit="return confirm('Tem certeza que deseja excluir este registro?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$d['id'] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    name="deletar_div"
                                                    class="btn-linha excluir"
                                                >
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td
                                    colspan="8"
                                    class="datacom-vazio"
                                >
                                    Nenhum provento cadastrado para este período e tipo de ativo.
                                </td>
                            </tr>

                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div
        class="modal-datacom"
        id="modalCadastro"
    >
        <div class="modal-datacom-conteudo">
            <div class="modal-datacom-cabecalho">
                <h2>
                    Cadastrar Data COM
                </h2>

                <button
                    type="button"
                    class="modal-fechar"
                    onclick="fecharModal('modalCadastro')"
                >
                    &times;
                </button>
            </div>

            <form
                method="POST"
                class="form-datacom"
            >
                <input
                    type="hidden"
                    name="nova_div"
                    value="1"
                >

                <label for="tipo_ativo">
                    Tipo de Ativo
                </label>

                <select
                    name="tipo_ativo"
                    id="tipo_ativo"
                    required
                >
                    <option value="acao">
                        Ação
                    </option>

                    <option value="fii">
                        FII
                    </option>

                    <option value="etf">
                        ETF
                    </option>

                    <option value="bdr">
                        BDR
                    </option>
                </select>

                <label for="ticker">
                    Ativo
                </label>

                <input
                    type="text"
                    name="ticker"
                    id="ticker"
                    placeholder="Ex: PETR4, HGLG11, BOVA11..."
                    maxlength="10"
                    required
                >

                <label for="datacom">
                    Data COM
                </label>

                <input
                    type="date"
                    name="datacom"
                    id="datacom"
                    required
                >

                <label for="datapag">
                    Data de Pagamento
                </label>

                <input
                    type="date"
                    name="datapag"
                    id="datapag"
                >

                <label for="valor">
                    Valor por Unidade
                </label>

                <input
                    type="number"
                    step="0.00000001"
                    min="0.00000001"
                    name="valor"
                    id="valor"
                    placeholder="Ex: 0.41736422"
                    required
                >

                <label for="tipo">
                    Tipo de Provento
                </label>

                <select
                    name="tipo"
                    id="tipo"
                    required
                ></select>

                <button
                    type="submit"
                    class="btn-datacom"
                >
                    Registrar
                </button>
            </form>
        </div>
    </div>

    <div
        class="modal-datacom"
        id="modalFiltro"
    >
        <div class="modal-datacom-conteudo modal-menor">
            <div class="modal-datacom-cabecalho">
                <h2>
                    Filtrar proventos
                </h2>

                <button
                    type="button"
                    class="modal-fechar"
                    onclick="fecharModal('modalFiltro')"
                >
                    &times;
                </button>
            </div>

            <form
                method="GET"
                class="form-datacom"
            >
                <label for="filtro_mes">
                    Mês da Data COM
                </label>

                <select
                    name="mes"
                    id="filtro_mes"
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

                <label for="filtro_ano">
                    Ano
                </label>

                <select
                    name="ano"
                    id="filtro_ano"
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

                <label for="filtro_tipo_ativo">
                    Tipo de Ativo
                </label>

                <select
                    name="tipo_ativo"
                    id="filtro_tipo_ativo"
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

    <div
        class="modal-datacom"
        id="modalAjuste"
    >
        <div class="modal-datacom-conteudo modal-menor">
            <div class="modal-datacom-cabecalho">
                <h2>
                    Ajustar valor
                </h2>

                <button
                    type="button"
                    class="modal-fechar"
                    onclick="fecharModal('modalAjuste')"
                >
                    &times;
                </button>
            </div>

            <form
                method="POST"
                class="form-datacom"
            >
                <input
                    type="hidden"
                    name="id"
                    id="ajuste_id"
                >

                <label for="ajuste_ativo">
                    Ativo
                </label>

                <input
                    type="text"
                    id="ajuste_ativo"
                    readonly
                >

                <label for="novo_valor">
                    Novo Valor por Unidade
                </label>

                <input
                    type="number"
                    step="0.00000001"
                    min="0.00000001"
                    name="novo_valor"
                    id="novo_valor"
                    required
                >

                <button
                    type="submit"
                    name="ajustar_valor"
                    class="btn-datacom"
                >
                    Atualizar
                </button>
            </form>
        </div>
    </div>

    <?php include("../includes/footer.php"); ?>

    <script>
        const tipoAtivo =
            document.getElementById(
                'tipo_ativo'
            );

        const tipoProvento =
            document.getElementById(
                'tipo'
            );

        const tiposPermitidos = {
            acao: [
                {
                    valor: 'DIV',
                    nome: 'Dividendo'
                },
                {
                    valor: 'JCP',
                    nome: 'JCP'
                }
            ],

            fii: [
                {
                    valor: 'REND',
                    nome: 'Rendimento'
                }
            ],

            etf: [
                {
                    valor: 'DIV',
                    nome: 'Dividendo'
                },
                {
                    valor: 'REND',
                    nome: 'Rendimento'
                }
            ],

            bdr: [
                {
                    valor: 'DIV',
                    nome: 'Dividendo'
                }
            ]
        };

        function atualizarTiposProvento() {
            const tipoSelecionado =
                tipoAtivo.value;

            const opcoes =
                tiposPermitidos[
                    tipoSelecionado
                ] || [];

            tipoProvento.innerHTML = '';

            opcoes.forEach(
                function(opcao) {
                    const option =
                        document.createElement(
                            'option'
                        );

                    option.value =
                        opcao.valor;

                    option.textContent =
                        opcao.nome;

                    tipoProvento.appendChild(
                        option
                    );
                }
            );
        }

        function abrirModal(id) {
            document
                .getElementById(id)
                .classList
                .add('ativo');

            document
                .body
                .classList
                .add('modal-aberto');
        }

        function fecharModal(id) {
            document
                .getElementById(id)
                .classList
                .remove('ativo');

            document
                .body
                .classList
                .remove('modal-aberto');
        }

        function abrirAjuste(
            id,
            ticker,
            tipo,
            valor
        ) {
            document.getElementById(
                'ajuste_id'
            ).value = id;

            document.getElementById(
                'ajuste_ativo'
            ).value =
                ticker + ' - ' + tipo;

            document.getElementById(
                'novo_valor'
            ).value =
                valor.replace(',', '.');

            abrirModal('modalAjuste');
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
                                fecharModal(
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
                                fecharModal(
                                    modal.id
                                );
                            }
                        );
                }
            }
        );

        atualizarTiposProvento();
    </script>
</body>
</html>