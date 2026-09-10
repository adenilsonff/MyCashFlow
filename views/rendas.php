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

if ($mes < 1 || $mes > 12) {
    $mes = (int)date("n");
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date("Y");
}

function voltarRendas($mes, $ano)
{
    header("Location: rendas.php?mes=" . (int)$mes . "&ano=" . (int)$ano);
    exit;
}

function criarDataRecorrente(DateTime $dataInicial, $mesesAdicionar)
{
    $diaOriginal = (int)$dataInicial->format("d");

    $data = new DateTime(
        $dataInicial->format("Y-m-01")
    );

    if ($mesesAdicionar > 0) {
        $data->modify("+{$mesesAdicionar} month");
    }

    $ultimoDiaMes = (int)$data->format("t");
    $dia = min($diaOriginal, $ultimoDiaMes);

    $data->setDate(
        (int)$data->format("Y"),
        (int)$data->format("m"),
        $dia
    );

    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nova_renda'])) {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $dataInformada = $_POST['data'] ?? '';
        $valor = isset($_POST['valor']) ? (float)$_POST['valor'] : 0;
        $tipo = ($_POST['tipoRenda'] ?? '') === 'mensal' ? 'mensal' : 'unica';

        if ($nome !== '' && $descricao !== '' && $dataInformada !== '' && $valor >= 0) {
            $dataInicial = DateTime::createFromFormat('Y-m-d', $dataInformada);

            if ($dataInicial && $dataInicial->format('Y-m-d') === $dataInformada) {
                if ($tipo === 'mensal') {
                    $grupoRecorrencia = bin2hex(random_bytes(16));

                    $stmt = $conn->prepare("
                        INSERT INTO rendas
                        (nome, descricao, data, valor, tipo, grupo_recorrencia, recebido, porcentagem)
                        VALUES (?, ?, ?, ?, 'mensal', ?, 0, 0)
                    ");

                    for ($i = 0; $i < 12; $i++) {
                        $dataRecebimento = criarDataRecorrente($dataInicial, $i);
                        $dataFormatada = $dataRecebimento->format("Y-m-d");

                        $stmt->bind_param(
                            "sssds",
                            $nome,
                            $descricao,
                            $dataFormatada,
                            $valor,
                            $grupoRecorrencia
                        );

                        $stmt->execute();
                    }

                    $stmt->close();
                } else {
                    $dataFormatada = $dataInicial->format("Y-m-d");

                    $stmt = $conn->prepare("
                        INSERT INTO rendas
                        (nome, descricao, data, valor, tipo, grupo_recorrencia, recebido, porcentagem)
                        VALUES (?, ?, ?, ?, 'unica', NULL, 0, 0)
                    ");

                    $stmt->bind_param(
                        "sssd",
                        $nome,
                        $descricao,
                        $dataFormatada,
                        $valor
                    );

                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        voltarRendas($mes, $ano);
    }

    if (isset($_POST['atualizar_recebido'])) {
        $id = (int)($_POST['id'] ?? 0);
        $recebido = isset($_POST['recebido']) && (int)$_POST['recebido'] === 1 ? 1 : 0;

        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE rendas
                SET recebido = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ii", $recebido, $id);
            $stmt->execute();
            $stmt->close();
        }

        voltarRendas($mes, $ano);
    }

    if (isset($_POST['ajustar_valor'])) {
        $id = (int)($_POST['id'] ?? 0);
        $novoValor = isset($_POST['novo_valor']) ? (float)$_POST['novo_valor'] : 0;

        if ($id > 0 && $novoValor >= 0) {
            $stmt = $conn->prepare("
                UPDATE rendas
                SET valor = ?
                WHERE id = ?
            ");

            $stmt->bind_param("di", $novoValor, $id);
            $stmt->execute();
            $stmt->close();
        }

        voltarRendas($mes, $ano);
    }

    if (isset($_POST['deletar_renda'])) {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("
                SELECT tipo, grupo_recorrencia, data
                FROM rendas
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $renda = $resultado->fetch_assoc();
            $stmt->close();

            if ($renda) {
                if (
                    $renda['tipo'] === 'mensal' &&
                    !empty($renda['grupo_recorrencia'])
                ) {
                    $stmt = $conn->prepare("
                        DELETE FROM rendas
                        WHERE grupo_recorrencia = ?
                        AND data >= ?
                    ");

                    $stmt->bind_param(
                        "ss",
                        $renda['grupo_recorrencia'],
                        $renda['data']
                    );

                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("
                        DELETE FROM rendas
                        WHERE id = ?
                    ");

                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        voltarRendas($mes, $ano);
    }
}

$sql = "
    SELECT
        id,
        nome,
        descricao,
        data,
        valor,
        tipo,
        grupo_recorrencia,
        recebido,
        porcentagem
    FROM rendas
    WHERE MONTH(data) = ?
    AND YEAR(data) = ?
    ORDER BY data ASC, id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$rendas = [];
$total = 0;
$totalRecebido = 0;

while ($row = $result->fetch_assoc()) {
    $rendas[] = $row;
    $total += (float)$row['valor'];

    if ((int)$row['recebido'] === 1) {
        $totalRecebido += (float)$row['valor'];
    }
}

$stmt->close();

$totalAReceber = $total - $totalRecebido;

$meses = [
    "Janeiro",
    "Fevereiro",
    "Março",
    "Abril",
    "Maio",
    "Junho",
    "Julho",
    "Agosto",
    "Setembro",
    "Outubro",
    "Novembro",
    "Dezembro"
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receitas</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=2">
</head>
<body>

<?php include("../includes/header.php"); ?>
<?php include("../includes/menu.php"); ?>

<main class="rendas-layout">

    <div class="cabecalho-rendas">
        <div>
            <h1>Receitas</h1>
            <p><?= htmlspecialchars($meses[$mes - 1]) ?>/<?= $ano ?></p>
        </div>

        <div class="acoes-rendas">
            <button type="button" class="btn-padrao" data-modal="modalNovaRenda">
                Nova Receita
            </button>

            <button type="button" class="btn-padrao" data-modal="modalPesquisa">
                Pesquisar
            </button>
        </div>
    </div>

    <div class="resumo-rendas">

        <div class="card-resumo">
            <span>Total previsto</span>
            <strong>
                R$ <?= number_format($total, 2, ',', '.') ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Total recebido</span>
            <strong>
                R$ <?= number_format($totalRecebido, 2, ',', '.') ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>A receber</span>
            <strong>
                R$ <?= number_format($totalAReceber, 2, ',', '.') ?>
            </strong>
        </div>

    </div>

    <div class="card-lista-renda">

        <div class="titulo-tabela">
            <h2>
                Lista de Receitas - <?= htmlspecialchars($meses[$mes - 1]) ?>/<?= $ano ?>
            </h2>
        </div>

        <div class="tabela-wrapper">

            <table class="tabela-rendas">

                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Recebido</th>
                        <th>%</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($rendas)) { ?>

                    <tr>
                        <td colspan="7" class="sem-registros">
                            Nenhuma receita encontrada neste período.
                        </td>
                    </tr>

                <?php } else { ?>

                    <?php foreach ($rendas as $r) { ?>

                        <tr class="<?= $r['recebido'] ? 'linha-recebida' : 'linha-nao-recebida' ?>">

                            <td>
                                <?= htmlspecialchars($r['nome']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['descricao']) ?>
                            </td>

                            <td>
                                <?= date("d/m/Y", strtotime($r['data'])) ?>
                            </td>

                            <td>
                                R$ <?= number_format($r['valor'], 2, ',', '.') ?>
                            </td>

                            <td>

                                <form method="POST" class="form-status">

                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                    <input type="hidden" name="mes" value="<?= $mes ?>">
                                    <input type="hidden" name="ano" value="<?= $ano ?>">

                                    <input
                                        type="hidden"
                                        name="atualizar_recebido"
                                        value="1"
                                    >

                                    <select
                                        name="recebido"
                                        onchange="this.form.submit()"
                                    >
                                        <option
                                            value="0"
                                            <?= !$r['recebido'] ? 'selected' : '' ?>
                                        >
                                            Não
                                        </option>

                                        <option
                                            value="1"
                                            <?= $r['recebido'] ? 'selected' : '' ?>
                                        >
                                            Sim
                                        </option>
                                    </select>

                                </form>

                            </td>

                            <td>
                                <?= $total > 0
                                    ? number_format(
                                        ((float)$r['valor'] / $total) * 100,
                                        2,
                                        ',',
                                        '.'
                                    )
                                    : '0,00'
                                ?>%
                            </td>

                            <td>

                                <div class="acoes-linha">

                                    <button
                                        type="button"
                                        class="btn-acao btn-ajustar"
                                        data-id="<?= (int)$r['id'] ?>"
                                        data-nome="<?= htmlspecialchars($r['nome'], ENT_QUOTES) ?>"
                                        data-valor="<?= number_format($r['valor'], 2, '.', '') ?>"
                                    >
                                        Ajustar
                                    </button>

                                    <form
                                        method="POST"
                                        class="form-excluir"
                                        onsubmit="return confirm(
                                            '<?= $r['tipo'] === 'mensal'
                                                ? 'Esta receita é mensal. O mês selecionado e todos os meses seguintes desta recorrência serão excluídos. Deseja continuar?'
                                                : 'Tem certeza que deseja excluir esta receita?'
                                            ?>'
                                        );"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$r['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="deletar_renda"
                                            value="1"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-acao btn-excluir"
                                        >
                                            Excluir
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="3">
                            Total
                        </td>

                        <td>
                            R$ <?= number_format($total, 2, ',', '.') ?>
                        </td>

                        <td colspan="3"></td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </div>

</main>

<div id="modalNovaRenda" class="modal-rendas">

    <div class="modal-conteudo">

        <button
            type="button"
            class="modal-fechar"
            data-fechar
        >
            ×
        </button>

        <h2>Nova Receita</h2>

        <form method="POST" class="form-rendas">

            <input
                type="hidden"
                name="nova_renda"
                value="1"
            >

            <input
                type="text"
                name="nome"
                placeholder="Nome da receita"
                required
            >

            <input
                type="text"
                name="descricao"
                placeholder="Descrição"
                required
            >

            <select name="tipoRenda" required>
                <option value="unica">
                    Única
                </option>

                <option value="mensal">
                    Mensal
                </option>
            </select>

            <input
                type="date"
                name="data"
                required
            >

            <input
                type="number"
                step="0.01"
                min="0"
                name="valor"
                placeholder="Valor"
                required
            >

            <button type="submit" class="btn-padrao">
                Registrar
            </button>

        </form>

    </div>

</div>

<div id="modalPesquisa" class="modal-rendas">

    <div class="modal-conteudo">

        <button
            type="button"
            class="modal-fechar"
            data-fechar
        >
            ×
        </button>

        <h2>Pesquisar Receitas</h2>

        <form method="GET" class="form-rendas">

            <select name="mes">

                <?php for ($i = 1; $i <= 12; $i++) { ?>

                    <option
                        value="<?= $i ?>"
                        <?= $i === $mes ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($meses[$i - 1]) ?>
                    </option>

                <?php } ?>

            </select>

            <select name="ano">

                <?php
                $anoInicial = min((int)date("Y") - 5, $ano);
                $anoFinal = max((int)date("Y") + 5, $ano);

                for ($y = $anoInicial; $y <= $anoFinal; $y++) {
                ?>

                    <option
                        value="<?= $y ?>"
                        <?= $y === $ano ? 'selected' : '' ?>
                    >
                        <?= $y ?>
                    </option>

                <?php } ?>

            </select>

            <button type="submit" class="btn-padrao">
                Pesquisar
            </button>

        </form>

    </div>

</div>

<div id="modalAjustar" class="modal-rendas">

    <div class="modal-conteudo">

        <button
            type="button"
            class="modal-fechar"
            data-fechar
        >
            ×
        </button>

        <h2>Ajustar Receita</h2>

        <form method="POST" class="form-rendas">

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

            <div id="ajuste-nome" class="nome-renda-ajuste"></div>

            <input
                type="number"
                step="0.01"
                min="0"
                name="novo_valor"
                id="ajuste-valor"
                required
            >

            <button type="submit" class="btn-padrao">
                Atualizar
            </button>

        </form>

    </div>

</div>

<?php include("../includes/footer.php"); ?>

<script>
document.querySelectorAll('[data-modal]').forEach(function(botao) {
    botao.addEventListener('click', function() {
        const modal = document.getElementById(this.dataset.modal);

        if (modal) {
            modal.classList.add('ativo');
        }
    });
});

document.querySelectorAll('[data-fechar]').forEach(function(botao) {
    botao.addEventListener('click', function() {
        this.closest('.modal-rendas').classList.remove('ativo');
    });
});

document.querySelectorAll('.modal-rendas').forEach(function(modal) {
    modal.addEventListener('click', function(evento) {
        if (evento.target === modal) {
            modal.classList.remove('ativo');
        }
    });
});

document.querySelectorAll('.btn-ajustar').forEach(function(botao) {
    botao.addEventListener('click', function() {
        document.getElementById('ajuste-id').value = this.dataset.id;
        document.getElementById('ajuste-valor').value = this.dataset.valor;
        document.getElementById('ajuste-nome').textContent = this.dataset.nome;

        document.getElementById('modalAjustar').classList.add('ativo');
    });
});

document.addEventListener('keydown', function(evento) {
    if (evento.key === 'Escape') {
        document.querySelectorAll('.modal-rendas.ativo').forEach(function(modal) {
            modal.classList.remove('ativo');
        });
    }
});
</script>

</body>
</html>