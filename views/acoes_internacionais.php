<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Cotação do dólar via AwesomeAPI
$url_dolar = "https://economia.awesomeapi.com.br/json/last/USD-BRL";
$response_dolar = @file_get_contents($url_dolar);
$data_dolar = json_decode($response_dolar, true);

$cotacao_dolar = null;
if (!empty($data_dolar["USDBRL"])) {
    $cotacao_dolar = $data_dolar["USDBRL"]["bid"];
}

// Cotação do euro via AwesomeAPI
$url_euro = "https://economia.awesomeapi.com.br/json/last/EUR-BRL";
$response_euro = @file_get_contents($url_euro);
$data_euro = json_decode($response_euro, true);

$cotacao_euro = null;
if (!empty($data_euro["EURBRL"])) {
    $cotacao_euro = $data_euro["EURBRL"]["bid"];
}

// Função para buscar dados da ação via Brapi
function getDadosAcao($ticker) {
    $token = "pV9h8EEsN5xs9ESi2Uk89n";  
    $ticker_api = strtoupper($ticker); // sem .SA
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
    $quantidade = (int) $_POST['quantidade'];
    $valor_unitario = (float) $_POST['valor_unitario'];
    $data = $_POST['data'];
    $acao = $_POST['acao']; // compra ou venda

    list($valor_mercado, $logo) = getDadosAcao($ticker);

    if ($acao === "venda") {
        $quantidade = -$quantidade;
    }

    $stmt = $conn->prepare("INSERT INTO acoes_internacionais 
        (ticker, quantidade, valor_unitario, data, valor_mercado, logo) 
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
        FROM acoes_internacionais
        GROUP BY ticker
        HAVING quantidade_total > 0";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações Internacionais</title>
    <link rel="stylesheet" href="../assets/css/style-acoes.css?v=5">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="acoes-layout">
        <div class="card-lista">
            <h2>Cotações de hoje</h2>
            <table class="tabela-acoes">
                <thead>
                    <tr>
                        <!-- <th>Data</th> --> <!-- comentado -->
                        <th>Dólar (USD)</th>
                        <th>Euro (EUR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <!-- <td><?= date("d/m/Y") ?></td> --> <!-- comentado -->
                        <td>R$ <?= number_format($cotacao_dolar, 4, ',', '.') ?></td>
                        <td>R$ <?= number_format($cotacao_euro, 4, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

       
        <div class="acoes-container">
            <!-- Card de Operação -->
            <div class="card-cadastro">
                <h2>Operação de Ações Internacionais</h2>
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
                    <input type="text" name="ticker" placeholder="Ticker (ex: AAPL, MSFT)" required>
                    <input type="number" name="quantidade" placeholder="Quantidade" required>
                    <input type="number" step="0.01" name="valor_unitario" placeholder="Valor unitário" required>
                    <input type="date" name="data" required>

                    <button type="submit">Registrar</button>
                </form>
            </div>

            <!-- Card da Lista -->
            <div class="card-lista">
                <h2>Lista de Ações Internacionais Consolidadas</h2>
                <table class="tabela-acoes">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Ticker</th>
                            <th>Quantidade Total</th>
                            <th>Valor Médio</th>
                            <th>Total Investido</th>
                            <th>Valor Mercado Atual</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_resultado = 0;
                        while($row = $result->fetch_assoc()) { 
                            $total_resultado += $row['resultado'];
                        ?>
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
                            <td>US$ <?= number_format($row['valor_medio_ponderado'], 2, ',', '.') ?></td>
                            <td>US$ <?= number_format($row['total_investido'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($row['valor_mercado_atual'] !== null) { ?>
                                    US$ <?= number_format($row['valor_mercado_atual'], 2, ',', '.') ?>
                                <?php } else { ?>
                                    -
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($row['resultado'] >= 0) { ?>
                                    <span style="color:green;">+ US$ <?= number_format($row['resultado'], 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span style="color:red;">- US$ <?= number_format(abs($row['resultado']), 2, ',', '.') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>

                        <!-- Linha final com o total consolidado -->
                        <tr>
                            <td colspan="6" style="text-align:right;"><strong>Total:</strong></td>
                            <td>
                                <?php if ($total_resultado >= 0) { ?>
                                    <span style="color:green;">+ US$ <?= number_format($total_resultado, 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span style="color:red;">- US$ <?= number_format(abs($total_resultado), 2, ',', '.') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>