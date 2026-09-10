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

function formatarValorProvento($valor) {
    $valorFormatado = number_format((float)$valor, 8, ',', '');
    $valorFormatado = rtrim($valorFormatado, '0');
    $valorFormatado = rtrim($valorFormatado, ',');

    return $valorFormatado;
}

function formatarMoeda($valor) {
    return number_format((float)$valor, 2, ',', '.');
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

$stmt = $conn->prepare("
    SELECT
        d.id,
        d.ticker,
        d.datacom,
        d.datapag,
        d.valor,
        d.tipo,
        (
            SELECT a.logo
            FROM acoes_nacionais a
            WHERE UPPER(a.ticker) = UPPER(d.ticker)
              AND a.logo IS NOT NULL
              AND a.logo <> ''
            ORDER BY a.id DESC
            LIMIT 1
        ) AS logo,
        (
            SELECT COALESCE(
                SUM(
                    CASE
                        WHEN a.tipo = 'compra' THEN a.quantidade
                        WHEN a.tipo = 'venda' THEN -a.quantidade
                        ELSE 0
                    END
                ),
                0
            )
            FROM acoes_nacionais a
            WHERE UPPER(a.ticker) = UPPER(d.ticker)
              AND a.data <= d.datacom
        ) AS quantidade_elegivel
    FROM div_datacom d
    WHERE d.datapag IS NOT NULL
      AND MONTH(d.datapag) = ?
      AND YEAR(d.datapag) = ?
    ORDER BY d.datapag ASC, d.ticker ASC, d.id ASC
");
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$dividendos = [];
$totalGeral = 0;

while ($row = $result->fetch_assoc()) {
    $quantidadeElegivel = (int)$row['quantidade_elegivel'];

    if ($quantidadeElegivel <= 0) {
        continue;
    }

    $totalRecebido = $quantidadeElegivel * (float)$row['valor'];

    $row['quantidade_elegivel'] = $quantidadeElegivel;
    $row['total_recebido'] = $totalRecebido;

    $dividendos[] = $row;
    $totalGeral += $totalRecebido;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dividendos - Demonstrativo</title>
    <link rel="stylesheet" href="../assets/css/style-divis.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <div class="card-cadastro-renda">
                <h2>Filtrar Dividendos por Mês</h2>

                <form method="GET" class="form-rendas">
                    <label for="mes">Mês:</label>

                    <select name="mes" id="mes" required>
                        <?php foreach ($meses as $numero => $nome) { ?>
                            <option
                                value="<?= $numero ?>"
                                <?= $numero === $mes ? 'selected' : '' ?>
                            >
                                <?= $nome ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label for="ano">Ano:</label>

                    <select name="ano" id="ano" required>
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
        </div>

        <div class="card-lista-renda">
            <h2>
                Dividendos -
                <?= $meses[$mes] ?>/<?= $ano ?>
            </h2>

            <table class="tabela-rendas">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Ticker</th>
                        <th>Data COM</th>
                        <th>Quantidade</th>
                        <th>Tipo</th>
                        <th>Valor por Ação</th>
                        <th>Total</th>
                        <th>Pagamento</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($dividendos)) { ?>
                        <?php foreach ($dividendos as $d) { ?>
                            <tr>
                                <td>
                                    <?php if (!empty($d['logo'])) { ?>
                                        <img
                                            src="<?= htmlspecialchars($d['logo']) ?>"
                                            alt="<?= htmlspecialchars($d['ticker']) ?>"
                                            style="height:40px;"
                                        >
                                    <?php } else { ?>
                                        -
                                    <?php } ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['ticker']) ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($d['datacom'])) ?>
                                </td>

                                <td>
                                    <?= (int)$d['quantidade_elegivel'] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($d['tipo']) ?>
                                </td>

                                <td>
                                    R$ <?= formatarValorProvento($d['valor']) ?>
                                </td>

                                <td>
                                    R$ <?= formatarMoeda($d['total_recebido']) ?>
                                </td>

                                <td>
                                    <?= date("d/m/Y", strtotime($d['datapag'])) ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8">
                                Nenhum dividendo encontrado para este período.
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <h3>
                Total Geral de Dividendos:
                R$ <?= formatarMoeda($totalGeral) ?>
            </h3>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>