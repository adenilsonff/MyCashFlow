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

function voltarPagina($mes, $ano) {
    header("Location: div_datacom.php?mes={$mes}&ano={$ano}");
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
    $valorFormatado = number_format((float)$valor, 8, ',', '');
    $valorFormatado = rtrim($valorFormatado, '0');
    $valorFormatado = rtrim($valorFormatado, ',');

    return $valorFormatado;
}

function formatarData($data) {
    if (empty($data)) {
        return 'A definir';
    }

    return date("d/m/Y", strtotime($data));
}

if (isset($_POST['nova_div'])) {
    $ticker = strtoupper(trim($_POST['ticker'] ?? ''));
    $datacom = trim($_POST['datacom'] ?? '');
    $datapagInformada = trim($_POST['datapag'] ?? '');
    $datapag = $datapagInformada !== '' ? $datapagInformada : null;
    $valor = normalizarValor($_POST['valor'] ?? 0);
    $tipo = strtoupper(trim($_POST['tipo'] ?? ''));

    if (
        $ticker !== '' &&
        $datacom !== '' &&
        $valor > 0 &&
        in_array($tipo, ['DIV', 'JCP'], true)
    ) {
        $stmt = $conn->prepare("
            INSERT INTO div_datacom
            (ticker, datacom, datapag, valor, tipo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssds",
            $ticker,
            $datacom,
            $datapag,
            $valor,
            $tipo
        );
        $stmt->execute();
        $stmt->close();
    }

    voltarPagina($mes, $ano);
}

if (isset($_POST['ajustar_valor'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $novoValor = normalizarValor($_POST['novo_valor'] ?? 0);

    if ($id > 0 && $novoValor > 0) {
        $stmt = $conn->prepare("
            UPDATE div_datacom
            SET valor = ?
            WHERE id = ?
        ");
        $stmt->bind_param("di", $novoValor, $id);
        $stmt->execute();
        $stmt->close();
    }

    voltarPagina($mes, $ano);
}

if (isset($_POST['deletar_div'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        $stmt = $conn->prepare("
            DELETE FROM div_datacom
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    voltarPagina($mes, $ano);
}

$stmt = $conn->prepare("
    SELECT
        id,
        ticker,
        datacom,
        datapag,
        valor,
        tipo
    FROM div_datacom
    WHERE MONTH(datacom) = ?
      AND YEAR(datacom) = ?
    ORDER BY datacom ASC, ticker ASC, id ASC
");
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$divs = [];

while ($row = $result->fetch_assoc()) {
    $divs[] = $row;
}

$stmt->close();

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
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Div DataCOM</title>
    <link rel="stylesheet" href="../assets/css/style-divis.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <div class="card-cadastro-renda">
                <h2>Cadastro da DataCOM</h2>

                <form class="form-rendas" method="POST">
                    <input type="hidden" name="nova_div" value="1">

                    <input
                        type="text"
                        name="ticker"
                        placeholder="Ex: PETR4, VBBR3..."
                        maxlength="10"
                        required
                    >

                    <label for="datacom">Data COM</label>

                    <input
                        type="date"
                        id="datacom"
                        name="datacom"
                        required
                    >

                    <label for="datapag">Data de Pagamento</label>

                    <input
                        type="date"
                        id="datapag"
                        name="datapag"
                    >

                    <label for="valor">Valor por ação</label>

                    <input
                        type="number"
                        step="0.00000001"
                        min="0.00000001"
                        id="valor"
                        name="valor"
                        placeholder="Ex: 0.41736422"
                        required
                    >

                    <select name="tipo" required>
                        <option value="DIV">DIV</option>
                        <option value="JCP">JCP</option>
                    </select>

                    <button type="submit">Registrar</button>
                </form>
            </div>

            <div class="card-cadastro-renda">
                <h2>Filtrar por Mês/Ano</h2>

                <form method="GET" class="form-rendas">
                    <select name="mes">
                        <?php foreach ($meses as $numero => $nome) { ?>
                            <option
                                value="<?= $numero ?>"
                                <?= $numero === $mes ? 'selected' : '' ?>
                            >
                                <?= $nome ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="ano">
                        <?php for ($y = (int)date("Y") - 5; $y <= (int)date("Y") + 5; $y++) { ?>
                            <option
                                value="<?= $y ?>"
                                <?= $y === $ano ? 'selected' : '' ?>
                            >
                                <?= $y ?>
                            </option>
                        <?php } ?>
                    </select>

                    <button type="submit">Filtrar</button>
                </form>
            </div>

            <div class="card-cadastro-renda">
                <h2>Ajustar Valor do Dividendo</h2>

                <form method="POST" class="form-rendas">
                    <select name="id" required>
                        <option value="">Selecione o Registro</option>

                        <?php foreach ($divs as $d) { ?>
                            <option value="<?= (int)$d['id'] ?>">
                                <?= htmlspecialchars($d['ticker']) ?>
                                -
                                <?= formatarData($d['datacom']) ?>
                                (
                                <?= htmlspecialchars($d['tipo']) ?>
                                -
                                R$ <?= formatarValorProvento($d['valor']) ?>
                                )
                            </option>
                        <?php } ?>
                    </select>

                    <input
                        type="number"
                        step="0.00000001"
                        min="0.00000001"
                        name="novo_valor"
                        placeholder="Ex: 0.41736422"
                        required
                    >

                    <button type="submit" name="ajustar_valor">
                        Atualizar
                    </button>
                </form>
            </div>

            <div class="card-cadastro-renda">
                <h2>Deletar DataCOM</h2>

                <form
                    method="POST"
                    class="form-rendas"
                    onsubmit="return confirm('Tem certeza que deseja excluir este registro?');"
                >
                    <select name="id" required>
                        <option value="">Selecione</option>

                        <?php foreach ($divs as $d) { ?>
                            <option value="<?= (int)$d['id'] ?>">
                                <?= htmlspecialchars($d['ticker']) ?>
                                -
                                <?= formatarData($d['datacom']) ?>
                                (
                                <?= htmlspecialchars($d['tipo']) ?>
                                -
                                R$ <?= formatarValorProvento($d['valor']) ?>
                                )
                            </option>
                        <?php } ?>
                    </select>

                    <button type="submit" name="deletar_div">
                        Excluir
                    </button>
                </form>
            </div>
        </div>

        <div class="card-lista-renda">
            <h2>
                Lista DataCOM -
                <?= $meses[$mes] ?>/<?= $ano ?>
            </h2>

            <table class="tabela-rendas">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Data COM</th>
                        <th>Data Pagamento</th>
                        <th>Valor por Ação</th>
                        <th>Tipo</th>
                        <th>Tempo Restante</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($divs)) { ?>
                        <?php foreach ($divs as $d) { ?>
                            <?php
                            $dataCom = new DateTime($d['datacom']);
                            $dataCom->setTime(23, 59, 59);

                            $hoje = new DateTime();
                            $hoje->setTime(0, 0, 0);

                            $segundosRestantes =
                                $dataCom->getTimestamp() -
                                $hoje->getTimestamp();

                            if ($segundosRestantes <= 0) {
                                $tempoRestante = "------";
                            } else {
                                $diasRestantes =
                                    (int)ceil($segundosRestantes / 86400);

                                $tempoRestante =
                                    $diasRestantes === 1
                                    ? "1 dia"
                                    : "{$diasRestantes} dias";
                            }
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars($d['ticker']) ?>
                                </td>

                                <td>
                                    <?= formatarData($d['datacom']) ?>
                                </td>

                                <td>
                                    <?= formatarData($d['datapag']) ?>
                                </td>

                                <td>
                                    R$ <?= formatarValorProvento($d['valor']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['tipo']) ?>
                                </td>

                                <td>
                                    <?= $tempoRestante ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6">
                                Nenhum dividendo cadastrado para este período.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>