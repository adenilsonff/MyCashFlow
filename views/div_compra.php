<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações Nacionais</title>
    <link rel="stylesheet" href="../assets/css/style-divis.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">

            <!-- Card de Entrada de Capital -->
            <div class="card-cadastro-renda">
                <h2>Simular Comparativo de Dividendos</h2>
                <form method="POST" class="form-rendas">
                    <label for="capital">Valor a Investir (R$):</label>
                    <input type="number" step="0.01" name="capital" id="capital" required>
                    <button type="submit" name="simular_todas">Simular</button>
                </form>
            </div>

            <!-- Card estilo Lista de Rendas -->
            <?php
            if (isset($_POST['simular_todas'])) {
                $capital = $_POST['capital'];

                // Buscar apenas ações que possuem dividendos cadastrados
                $sql = "SELECT DISTINCT a.id, a.ticker, a.valor_mercado, a.logo
                        FROM acoes_nacionais a
                        INNER JOIN div_datacom d ON d.id_acao = a.id";
                $result = $conn->query($sql);
                ?>
                <div class="card-lista-renda">
                    <h2>Comparativo de Dividendos - <?= date("m/Y") ?></h2>
                    <table class="tabela-rendas">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Ticker</th>
                                <th>Valor Mercado</th>
                                <th>Capital Investido</th>
                                <th>Qtd. Ações</th>
                                <th>Dividendos Estimados</th>
                                <th>Retorno (%)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($acao = $result->fetch_assoc()) {
                            $idAcao = $acao['id'];
                            $ticker = $acao['ticker'];
                            $valorMercado = $acao['valor_mercado'];
                            $logo = $acao['logo'];

                            // Evitar divisão por zero
                            if ($valorMercado <= 0) {
                                continue;
                            }

                            // Calcular quantidade possível de ações
                            $quantidade = floor($capital / $valorMercado);

                            // Buscar dividendos da ação
                            $sqlDiv = "SELECT valor, tipo FROM div_datacom WHERE id_acao = $idAcao";
                            $resultDiv = $conn->query($sqlDiv);

                            $dividendoTotal = 0;
                            $detalhesDiv = [];

                            while ($div = $resultDiv->fetch_assoc()) {
                                $rendimento = $quantidade * $div['valor'];
                                $dividendoTotal += $rendimento;
                                $detalhesDiv[] = [
                                    'tipo' => $div['tipo'],
                                    'valor' => $div['valor'],
                                    'rendimento' => $rendimento
                                ];
                            }

                            $retornoPercentual = $capital > 0 ? ($dividendoTotal / $capital) * 100 : 0;
                            ?>
                            <tr>
                                <td><img src="<?= $logo ?>" alt="<?= $ticker ?>" style="height:40px;"></td>
                                <td><?= htmlspecialchars($ticker) ?></td>
                                <td>R$ <?= number_format($valorMercado, 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($capital, 2, ',', '.') ?></td>
                                <td><?= $quantidade ?></td>
                                <td>R$ <?= number_format($dividendoTotal, 2, ',', '.') ?></td>
                                <td><?= number_format($retornoPercentual, 2, ',', '.') ?>%</td>
                            </tr>
                            <?php foreach ($detalhesDiv as $d) { ?>
                            <tr>
                                <td colspan="2"><?= $d['tipo'] ?></td>
                                <td colspan="2">R$ <?= number_format($d['valor'], 2, ',', '.') ?> por ação</td>
                                <!-- <td colspan="2">R$ <?= number_format($d['rendimento'], 2, ',', '.') ?></td> -->
                            </tr>
                            <?php } ?>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <?php
            }
            ?>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>