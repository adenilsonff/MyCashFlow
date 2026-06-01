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
    $ticker_api = strtoupper($ticker);
    $url = "https://brapi.dev/api/quote/$ticker_api?token=$token";

    $response = @file_get_contents($url);
    if ($response === false) {
        return [null, null];
    }

    $data = json_decode($response, true);

    if (isset($data['results'][0])) {
        $valor_mercado = isset($data['results'][0]['regularMarketPrice']) ? $data['results'][0]['regularMarketPrice'] : null;
        $logo = isset($data['results'][0]['logourl']) ? $data['results'][0]['logourl'] : null;
        return [$valor_mercado, $logo];
    } else {
        return [null, null];
    }
}

// Inserir nova operação
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticker = strtoupper($_POST['ticker']);
    $data = $_POST['data'];
    $acao = $_POST['acao']; // compra ou venda
    $tipo_compra = $_POST['tipo_compra']; // quantidade ou valor

    list($valor_mercado, $logo) = getDadosAcao($ticker);

    if ($tipo_compra === "quantidade") {
        $quantidade = (float) $_POST['quantidade'];
        $valor_unitario = (float) $_POST['valor_unitario'];
        $valor_investido = $quantidade * $valor_unitario;
    } else {
        $valor_investido = (float) $_POST['valor_investido'];
        $quantidade = $valor_mercado > 0 ? $valor_investido / $valor_mercado : 0;
        $valor_unitario = $valor_mercado;
    }

    if ($acao === "venda") {
        $quantidade = -$quantidade;
        $valor_investido = -$valor_investido;
    }

    $stmt = $conn->prepare("INSERT INTO acoes_internacionais 
        (ticker, quantidade, valor_unitario, valor_investido, data, valor_mercado, logo) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddsdss", $ticker, $quantidade, $valor_unitario, $valor_investido, $data, $valor_mercado, $logo);
    $stmt->execute();
    $stmt->close();
}

// Listar ações consolidadas
$sql = "SELECT 
            ticker,
            SUM(quantidade) AS quantidade_total,
            SUM(valor_investido) AS total_investido_usd,
            SUM(quantidade * valor_unitario) / NULLIF(SUM(quantidade),0) AS valor_medio_ponderado,
            MAX(valor_mercado) AS valor_mercado_atual,
            MAX(logo) AS logo,
            (MAX(valor_mercado) * SUM(quantidade)) - SUM(valor_investido) AS resultado
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
    <link rel="stylesheet" href="../assets/css/style-acoes.css?v=7">
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
                        <th>Dólar (USD)</th>
                        <th>Euro (EUR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>R$ <?= number_format((float)$cotacao_dolar, 4, ',', '.') ?></td>
                        <td>R$ <?= number_format((float)$cotacao_euro, 4, ',', '.') ?></td>
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

                    <!-- Tipo de compra -->
                    <div class="acao-tabs">
                        <label>
                            <input type="radio" name="tipo_compra" value="quantidade" checked>
                            Por Quantidade
                        </label>
                        <label>
                            <input type="radio" name="tipo_compra" value="valor">
                            Por Valor Investido
                        </label>
                    </div>

                    <!-- Campos -->
                    <input type="text" name="ticker" placeholder="Ticker (ex: AAPL, MSFT)" required>
                    <input type="number" step="0.0001" name="quantidade" placeholder="Quantidade (ex: 0.5)">
                    <input type="number" step="0.01" name="valor_unitario" placeholder="Valor unitário (US$)">
                    <input type="number" step="0.01" name="valor_investido" placeholder="Valor investido (US$)">
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
                            <td><?= number_format($row['quantidade_total'], 4, ',', '.') ?></td>
                            <td>US$ <?= number_format($row['valor_medio_ponderado'], 2, ',', '.') ?></td>
                            <td>
                                US$ <?= number_format($row['total_investido_usd'], 2, ',', '.') ?><br>
                                R$ <?= number_format($row['total_investido_usd'] * (float)$cotacao_dolar, 2, ',', '.') ?>
                            </td>
                            <td>US$ <?= number_format($row['valor_mercado_atual'], 2, ',', '.') ?></td>
                            <td>
    <?php if (isset($row['resultado']) && $row['resultado'] !== null) { ?>
        <?php if ($row['resultado'] >= 0) { ?>
            <span style="color:green;">+ US$ <?= number_format($row['resultado'], 2, ',', '.') ?></span><br>
            <span style="color:green;">+ R$ <?= number_format($row['resultado'] * (float)$cotacao_dolar, 2, ',', '.') ?></span>
        <?php } else { ?>
            <span style="color:red;">- US$ <?= number_format(abs($row['resultado']), 2, ',', '.') ?></span><br>
            <span style="color:red;">- R$ <?= number_format(abs($row['resultado']) * (float)$cotacao_dolar, 2, ',', '.') ?></span>
        <?php } ?>
    <?php } else { ?>
        <span>-</span>
    <?php } ?>
</td>
</tr>
<?php } ?>

<!-- Linha final com o total consolidado -->
<tr>
    <td colspan="6" style="text-align:right;"><strong>Total:</strong></td>
    <td>
        <?php if (isset($total_resultado) && $total_resultado !== null) { ?>
            <?php if ($total_resultado >= 0) { ?>
                <span style="color:green;">+ US$ <?= number_format($total_resultado, 2, ',', '.') ?></span><br>
                <span style="color:green;">+ R$ <?= number_format($total_resultado * (float)$cotacao_dolar, 2, ',', '.') ?></span>
            <?php } else { ?>
                <span style="color:red;">- US$ <?= number_format(abs($total_resultado), 2, ',', '.') ?></span><br>
                <span style="color:red;">- R$ <?= number_format(abs($total_resultado) * (float)$cotacao_dolar, 2, ',', '.') ?></span>
            <?php } ?>
        <?php } else { ?>
            <span>-</span>
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
