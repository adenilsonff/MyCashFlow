<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Função para buscar dados da ação via Brapi
function getDadosAcao($ticker) {
    $token = "pV9h8EEsN5xs9ESi2Uk89n"; 
    $ticker_api = strtoupper($ticker) . ".SA"; 
    $url = "https://brapi.dev/api/quote/$ticker_api?token=$token";

    $response = @file_get_contents($url);
    if ($response === false) {
        return [null, null];
    }

    $data = json_decode($response, true);

    if (isset($data['results'][0])) {
        $valor_mercado = $data['results'][0]['regularMarketPrice'] ?? null;
        $logo = $data['results'][0]['logourl'] ?? null;
        return [$valor_mercado, $logo];
    } else {
        return [null, null];
    }
}

// Inserir nova operação
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticker = strtoupper($_POST['ticker']);
    $quantidade = $_POST['quantidade'];
    $valor_unitario = $_POST['valor_unitario'];
    $data = $_POST['data'];
    $acao = $_POST['acao']; // compra ou venda

    list($valor_mercado, $logo) = getDadosAcao($ticker);

    // Se for venda, quantidade negativa
    if ($acao === "venda") {
        $quantidade = -$quantidade;
    }

    $stmt = $conn->prepare("INSERT INTO acoes (ticker, quantidade, valor_unitario, data, valor_mercado, logo) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sidsss", $ticker, $quantidade, $valor_unitario, $data, $valor_mercado, $logo);
    $stmt->execute();
    $stmt->close();
}

// Listar ações consolidadas
$sql = "SELECT 
            ticker,
            SUM(quantidade) AS quantidade_total,
            SUM(quantidade * valor_unitario) / NULLIF(SUM(quantidade),0) AS valor_medio_ponderado,
            SUM(quantidade * valor_unitario) AS total_investido,
            MAX(valor_mercado) AS valor_mercado_atual,
            MAX(logo) AS logo,
            (MAX(valor_mercado) * SUM(quantidade)) - SUM(quantidade * valor_unitario) AS resultado
        FROM acoes
        GROUP BY ticker";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações</title>
    <link rel="stylesheet" href="../assets/css/style-acoes.css?v=4">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="acoes-layout">
        <div class="acoes-container">
            <!-- Card de Operação -->
            <div class="card-cadastro">
                <h2>Operação de Ações</h2>
                <form class="form-acoes" method="POST">
                    <!-- Aba de seleção -->
                    <div class="acao-tabs">
                        <label>
                            <input type="radio" name="acao" value="compra" checked>
                            Compra
                        </label>
                        <label>
                            <input type="radio" name="acao" value="venda">
                            Venda
                        </label>
                    </div>

                    <!-- Campos comuns -->
                    <input type="text" name="ticker" placeholder="Ticker (ex: PETR4)" required>
                    <input type="number" name="quantidade" placeholder="Quantidade" required>
                    <input type="number" step="0.01" name="valor_unitario" placeholder="Valor unitário" required>
                    <input type="date" name="data" required>

                    <button type="submit">Registrar</button>
                </form>
            </div>

            <!-- Card da Lista -->
            <div class="card-lista">
                <h2>Lista de Ações Consolidadas</h2>
                <table class="tabela-acoes">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Ticker</th>
                            <th>Quantidade Total</th>
                            <th>Valor Médio Ponderado</th>
                            <th>Total Investido</th>
                            <th>Valor Mercado Atual</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['logo'])) { ?>
                                    <img src="<?= $row['logo'] ?>" alt="<?= $row['ticker'] ?>" style="height:30px;">
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                            <td><?= $row['ticker'] ?></td>
                            <td><?= $row['quantidade_total'] ?></td>
                            <td>R$ <?= number_format($row['valor_medio_ponderado'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($row['total_investido'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($row['valor_mercado_atual'] !== null) { ?>
                                    R$ <?= number_format($row['valor_mercado_atual'], 2, ',', '.') ?>
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($row['resultado'] >= 0) { ?>
                                    <span style="color:green;">+ R$ <?= number_format($row['resultado'], 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span style="color:red;">- R$ <?= number_format(abs($row['resultado']), 2, ',', '.') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>