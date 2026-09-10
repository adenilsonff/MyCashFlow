<?php
include __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");
$categoriaFiltro = isset($_GET['categoria']) ? $_GET['categoria'] : '';

if ($mes < 1 || $mes > 12) {
    $mes = (int)date("n");
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date("Y");
}

if (!in_array($categoriaFiltro, ['', 'pessoal', 'conjunta'], true)) {
    $categoriaFiltro = '';
}

function voltarContas($mes, $ano, $categoria = '')
{
    $url = "contas.php?mes=" . (int)$mes . "&ano=" . (int)$ano;

    if ($categoria !== '') {
        $url .= "&categoria=" . urlencode($categoria);
    }

    header("Location: $url");
    exit;
}

function diasRestantes($dataVencimento, $paga)
{
    if ((int)$paga === 1) {
        return "Pago";
    }

    $hoje = new DateTime();
    $hoje->setTime(0, 0, 0);

    $vencimento = new DateTime($dataVencimento);
    $vencimento->setTime(0, 0, 0);

    if ($vencimento < $hoje) {
        return "Vencida";
    }

    if ($vencimento == $hoje) {
        return "Hoje";
    }

    $dias = $hoje->diff($vencimento)->days;

    return $dias === 1 ? "1 dia" : $dias . " dias";
}

function normalizarValor($valor)
{
    $valor = trim((string)$valor);
    $valor = str_replace(['R$', ' '], '', $valor);

    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    return (float)$valor;
}

function gerarGrupoRecorrencia()
{
    try {
        return bin2hex(random_bytes(16));
    } catch (Exception $e) {
        return uniqid('rec_', true);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nova_conta'])) {
        $nome = trim($_POST['nomeConta'] ?? '');
        $tipo = $_POST['tipoConta'] ?? '';
        $categoria = $_POST['categoria'] ?? '';
        $vencimentoInformado = $_POST['vencimento'] ?? '';
        $valor = normalizarValor($_POST['valor'] ?? 0);

        if (
            $nome !== '' &&
            in_array($tipo, ['unica', 'mensal'], true) &&
            in_array($categoria, ['pessoal', 'conjunta'], true) &&
            $vencimentoInformado !== '' &&
            $valor >= 0
        ) {
            $vencimento = new DateTime($vencimentoInformado);

            if ($tipo === 'mensal') {
                $grupoRecorrencia = gerarGrupoRecorrencia();

                $diaOriginal = (int)$vencimento->format('d');
                $mesOriginal = (int)$vencimento->format('m');
                $anoOriginal = (int)$vencimento->format('Y');

                for ($i = 0; $i < 12; $i++) {
                    $primeiroDia = new DateTime(
                        sprintf('%04d-%02d-01', $anoOriginal, $mesOriginal)
                    );

                    if ($i > 0) {
                        $primeiroDia->modify("+$i month");
                    }

                    $ultimoDia = (int)$primeiroDia->format('t');
                    $dia = min($diaOriginal, $ultimoDia);

                    $dataFormatada = sprintf(
                        '%04d-%02d-%02d',
                        (int)$primeiroDia->format('Y'),
                        (int)$primeiroDia->format('m'),
                        $dia
                    );

                    $stmt = $conn->prepare("
                        INSERT INTO contas
                        (nome, tipo, categoria, vencimento, paga, valor, grupo_recorrencia)
                        VALUES (?, ?, ?, ?, 0, ?, ?)
                    ");

                    $stmt->bind_param(
                        "ssssds",
                        $nome,
                        $tipo,
                        $categoria,
                        $dataFormatada,
                        $valor,
                        $grupoRecorrencia
                    );

                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $dataFormatada = $vencimento->format('Y-m-d');

                $stmt = $conn->prepare("
                    INSERT INTO contas
                    (nome, tipo, categoria, vencimento, paga, valor, grupo_recorrencia)
                    VALUES (?, ?, ?, ?, 0, ?, NULL)
                ");

                $stmt->bind_param(
                    "ssssd",
                    $nome,
                    $tipo,
                    $categoria,
                    $dataFormatada,
                    $valor
                );

                $stmt->execute();
                $stmt->close();
            }
        }

        voltarContas($mes, $ano, $categoriaFiltro);
    }

    if (isset($_POST['atualizar_paga'])) {
        $id = (int)($_POST['id'] ?? 0);
        $paga = isset($_POST['paga']) && (int)$_POST['paga'] === 1 ? 1 : 0;

        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE contas
                SET paga = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ii", $paga, $id);
            $stmt->execute();
            $stmt->close();
        }

        voltarContas($mes, $ano, $categoriaFiltro);
    }

    if (isset($_POST['ajustar_valor'])) {
        $id = (int)($_POST['id'] ?? 0);
        $novoValor = normalizarValor($_POST['novo_valor'] ?? 0);

        if ($id > 0 && $novoValor >= 0) {
            $stmt = $conn->prepare("
                UPDATE contas
                SET valor = ?
                WHERE id = ?
            ");

            $stmt->bind_param("di", $novoValor, $id);
            $stmt->execute();
            $stmt->close();
        }

        voltarContas($mes, $ano, $categoriaFiltro);
    }

    if (isset($_POST['deletar_conta'])) {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("
                SELECT tipo, vencimento, grupo_recorrencia
                FROM contas
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultadoConta = $stmt->get_result();
            $contaExcluir = $resultadoConta->fetch_assoc();
            $stmt->close();

            if ($contaExcluir) {
                if (
                    $contaExcluir['tipo'] === 'mensal' &&
                    !empty($contaExcluir['grupo_recorrencia'])
                ) {
                    $grupoRecorrencia = $contaExcluir['grupo_recorrencia'];
                    $vencimento = $contaExcluir['vencimento'];

                    $stmt = $conn->prepare("
                        DELETE FROM contas
                        WHERE grupo_recorrencia = ?
                        AND vencimento >= ?
                    ");

                    $stmt->bind_param(
                        "ss",
                        $grupoRecorrencia,
                        $vencimento
                    );

                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("
                        DELETE FROM contas
                        WHERE id = ?
                    ");

                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        voltarContas($mes, $ano, $categoriaFiltro);
    }
}

$sql = "
    SELECT
        id,
        nome,
        tipo,
        categoria,
        vencimento,
        paga,
        valor,
        grupo_recorrencia
    FROM contas
    WHERE MONTH(vencimento) = ?
    AND YEAR(vencimento) = ?
";

if ($categoriaFiltro !== '') {
    $sql .= " AND categoria = ?";
}

$sql .= " ORDER BY vencimento ASC, nome ASC";

$stmt = $conn->prepare($sql);

if ($categoriaFiltro !== '') {
    $stmt->bind_param("iis", $mes, $ano, $categoriaFiltro);
} else {
    $stmt->bind_param("ii", $mes, $ano);
}

$stmt->execute();
$result = $stmt->get_result();

$contas = [];
$total = 0;
$totalPago = 0;
$totalAberto = 0;

while ($row = $result->fetch_assoc()) {
    $contas[] = $row;

    $valorConta = (float)$row['valor'];

    $total += $valorConta;

    if ((int)$row['paga'] === 1) {
        $totalPago += $valorConta;
    } else {
        $totalAberto += $valorConta;
    }
}

$stmt->close();

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

$rotuloCategoria = 'Todas';

if ($categoriaFiltro === 'pessoal') {
    $rotuloCategoria = 'Pessoal';
} elseif ($categoriaFiltro === 'conjunta') {
    $rotuloCategoria = 'Conjunta';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despesas</title>
    <link rel="stylesheet" href="../assets/css/style-contas.css?v=2">
</head>
<body>

<?php include("../includes/header.php"); ?>
<?php include("../includes/menu.php"); ?>

<main class="contas-container">
    <div class="cabecalho-contas">
        <div>
            <h1>Despesas</h1>
            <p>
                <?= htmlspecialchars($meses[$mes]) ?> de <?= $ano ?>
                · <?= htmlspecialchars($rotuloCategoria) ?>
            </p>
        </div>

        <div class="acoes-contas">
            <button
                type="button"
                class="btn-padrao"
                onclick="abrirModal('modal-nova-conta')"
            >
                Nova despesa
            </button>

            <button
                type="button"
                class="btn-padrao btn-secundario"
                onclick="abrirModal('modal-pesquisa')"
            >
                Pesquisar
            </button>
        </div>
    </div>

    <div class="resumo-contas">
        <div class="card-resumo">
            <span>Total</span>
            <strong>
                R$ <?= number_format($total, 2, ',', '.') ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Pago</span>
            <strong>
                R$ <?= number_format($totalPago, 2, ',', '.') ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Em aberto</span>
            <strong>
                R$ <?= number_format($totalAberto, 2, ',', '.') ?>
            </strong>
        </div>
    </div>

    <div class="card-lista">
        <div class="titulo-lista">
            <div>
                <h2>Lista de Contas</h2>
                <span>
                    <?= htmlspecialchars($meses[$mes]) ?>/<?= $ano ?>
                </span>
            </div>

            <span class="quantidade-contas">
                <?= count($contas) ?>
                <?= count($contas) === 1 ? 'conta' : 'contas' ?>
            </span>
        </div>

        <div class="tabela-responsiva">
            <table class="tabela-acoes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Paga</th>
                        <th>Porcentagem</th>
                        <th>Dias Restantes</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($contas)) { ?>
                    <tr>
                        <td colspan="9" class="sem-registros">
                            Nenhuma conta encontrada para este período.
                        </td>
                    </tr>
                <?php } else { ?>
                    <?php foreach ($contas as $c) {
                        $dias = diasRestantes(
                            $c['vencimento'],
                            $c['paga']
                        );

                        if ((int)$c['paga'] === 1) {
                            $classeLinha = 'linha-paga';
                        } elseif ($dias === 'Vencida') {
                            $classeLinha = 'linha-vencida';
                        } else {
                            $classeLinha = 'linha-nao-paga';
                        }

                        $porcentagem = $total > 0
                            ? ((float)$c['valor'] / $total) * 100
                            : 0;

                        $mensal = $c['tipo'] === 'mensal';

                        $mensagemExclusao = $mensal
                            ? 'Tem certeza que deseja excluir esta conta mensal e todos os lançamentos futuros desta recorrência? Os meses anteriores serão mantidos.'
                            : 'Tem certeza que deseja excluir esta conta?';
                    ?>
                        <tr class="<?= $classeLinha ?>">
                            <td class="nome-conta">
                                <?= htmlspecialchars($c['nome']) ?>
                            </td>

                            <td>
                                <?= $mensal ? 'Mensal' : 'Única' ?>
                            </td>

                            <td>
                                <?= $c['categoria'] === 'conjunta'
                                    ? 'Conjunta'
                                    : 'Pessoal'
                                ?>
                            </td>

                            <td>
                                <?= date(
                                    "d/m/Y",
                                    strtotime($c['vencimento'])
                                ) ?>
                            </td>

                            <td>
                                R$ <?= number_format(
                                    $c['valor'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <form method="POST" class="form-status">
                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$c['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="atualizar_paga"
                                        value="1"
                                    >

                                    <select
                                        name="paga"
                                        onchange="this.form.submit()"
                                    >
                                        <option
                                            value="0"
                                            <?= !(int)$c['paga']
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            Não
                                        </option>

                                        <option
                                            value="1"
                                            <?= (int)$c['paga']
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            Sim
                                        </option>
                                    </select>
                                </form>
                            </td>

                            <td>
                                <?= number_format(
                                    $porcentagem,
                                    2,
                                    ',',
                                    '.'
                                ) ?>%
                            </td>

                            <td>
                                <span class="<?= $dias === 'Vencida'
                                    ? 'status-vencida'
                                    : ''
                                ?>">
                                    <?= htmlspecialchars($dias) ?>
                                </span>
                            </td>

                            <td>
                                <div class="acoes-linha">
                                    <button
                                        type="button"
                                        class="btn-acao"
                                        onclick="abrirAjuste(
                                            <?= (int)$c['id'] ?>,
                                            <?= htmlspecialchars(
                                                json_encode(
                                                    $c['nome'],
                                                    JSON_UNESCAPED_UNICODE
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            '<?= number_format(
                                                (float)$c['valor'],
                                                2,
                                                '.',
                                                ''
                                            ) ?>'
                                        )"
                                    >
                                        Ajustar
                                    </button>

                                    <form
                                        method="POST"
                                        class="form-excluir"
                                        onsubmit="return confirm(<?= htmlspecialchars(
                                            json_encode(
                                                $mensagemExclusao,
                                                JSON_UNESCAPED_UNICODE
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>);"
                                    >
                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$c['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="deletar_conta"
                                            class="btn-acao btn-excluir"
                                        >
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>

                    <tr class="linha-total">
                        <td colspan="4">
                            <strong>Total</strong>
                        </td>

                        <td>
                            <strong>
                                R$ <?= number_format(
                                    $total,
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </strong>
                        </td>

                        <td colspan="4"></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal" id="modal-nova-conta">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h2>Nova despesa</h2>

            <button
                type="button"
                class="fechar-modal"
                onclick="fecharModal('modal-nova-conta')"
            >
                &times;
            </button>
        </div>

        <form method="POST" class="form-modal">
            <input
                type="hidden"
                name="nova_conta"
                value="1"
            >

            <label>
                Nome da conta
                <input
                    type="text"
                    name="nomeConta"
                    required
                >
            </label>

            <label>
                Tipo
                <select
                    name="tipoConta"
                    id="tipo-conta"
                    required
                >
                    <option value="unica">
                        Única
                    </option>

                    <option value="mensal">
                        Mensal
                    </option>
                </select>
            </label>

            <div
                class="aviso-mensal"
                id="aviso-mensal"
            >
                Serão criados 12 lançamentos mensais a partir do vencimento informado.
            </div>

            <label>
                Categoria
                <select name="categoria" required>
                    <option value="pessoal">
                        Pessoal
                    </option>

                    <option value="conjunta">
                        Conjunta
                    </option>
                </select>
            </label>

            <label>
                Vencimento
                <input
                    type="date"
                    name="vencimento"
                    required
                >
            </label>

            <label>
                Valor
                <input
                    type="number"
                    name="valor"
                    step="0.01"
                    min="0"
                    placeholder="0,00"
                    required
                >
            </label>

            <button
                type="submit"
                class="btn-padrao"
            >
                Registrar
            </button>
        </form>
    </div>
</div>

<div class="modal" id="modal-pesquisa">
    <div class="modal-conteudo modal-pequeno">
        <div class="modal-cabecalho">
            <h2>Pesquisar despesas</h2>

            <button
                type="button"
                class="fechar-modal"
                onclick="fecharModal('modal-pesquisa')"
            >
                &times;
            </button>
        </div>

        <form method="GET" class="form-modal">
            <label>
                Mês
                <select name="mes">
                    <?php foreach ($meses as $numero => $nomeMes) { ?>
                        <option
                            value="<?= $numero ?>"
                            <?= $numero === $mes
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= htmlspecialchars($nomeMes) ?>
                        </option>
                    <?php } ?>
                </select>
            </label>

            <label>
                Ano
                <select name="ano">
                    <?php
                    for (
                        $y = 2020;
                        $y <= (int)date("Y") + 5;
                        $y++
                    ) {
                    ?>
                        <option
                            value="<?= $y ?>"
                            <?= $y === $ano
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= $y ?>
                        </option>
                    <?php } ?>
                </select>
            </label>

            <label>
                Categoria
                <select name="categoria">
                    <option
                        value=""
                        <?= $categoriaFiltro === ''
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Todas
                    </option>

                    <option
                        value="pessoal"
                        <?= $categoriaFiltro === 'pessoal'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Pessoal
                    </option>

                    <option
                        value="conjunta"
                        <?= $categoriaFiltro === 'conjunta'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Conjunta
                    </option>
                </select>
            </label>

            <button
                type="submit"
                class="btn-padrao"
            >
                Pesquisar
            </button>
        </form>
    </div>
</div>

<div class="modal" id="modal-ajustar">
    <div class="modal-conteudo modal-pequeno">
        <div class="modal-cabecalho">
            <h2>Ajustar valor</h2>

            <button
                type="button"
                class="fechar-modal"
                onclick="fecharModal('modal-ajustar')"
            >
                &times;
            </button>
        </div>

        <form method="POST" class="form-modal">
            <input
                type="hidden"
                name="ajustar_valor"
                value="1"
            >

            <input
                type="hidden"
                name="id"
                id="ajuste-id"
            >

            <div
                class="conta-selecionada"
                id="ajuste-nome"
            ></div>

            <label>
                Novo valor
                <input
                    type="number"
                    name="novo_valor"
                    id="ajuste-valor"
                    step="0.01"
                    min="0"
                    required
                >
            </label>

            <button
                type="submit"
                class="btn-padrao"
            >
                Atualizar
            </button>
        </form>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

<script>
function abrirModal(id) {
    document.getElementById(id).classList.add('ativo');
}

function fecharModal(id) {
    document.getElementById(id).classList.remove('ativo');
}

function abrirAjuste(id, nome, valor) {
    document.getElementById('ajuste-id').value = id;
    document.getElementById('ajuste-nome').textContent = nome;
    document.getElementById('ajuste-valor').value = valor;

    abrirModal('modal-ajustar');
}

document.querySelectorAll('.modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('ativo');
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document
            .querySelectorAll('.modal.ativo')
            .forEach(function(modal) {
                modal.classList.remove('ativo');
            });
    }
});

const tipoConta = document.getElementById('tipo-conta');
const avisoMensal = document.getElementById('aviso-mensal');

function atualizarAvisoMensal() {
    if (tipoConta.value === 'mensal') {
        avisoMensal.classList.add('visivel');
    } else {
        avisoMensal.classList.remove('visivel');
    }
}

tipoConta.addEventListener(
    'change',
    atualizarAvisoMensal
);

atualizarAvisoMensal();
</script>

</body>
</html>