<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Inserir novo registro Div Compra
if (isset($_POST['nova_compra'])) {
    $acao = strtoupper($_POST['acao']); // garante maiúscula
    $valor = floatval($_POST['valor']); // valor disponível para investir
    $tipo = $_POST['tipo']; // DIV ou JCP

    // Aqui futuramente você vai puxar da API o preço da ação
    // Exemplo fictício:
    $precoAcao = 25.00; // valor retornado da API
    $qtdAcoes = floor($valor / $precoAcao);

    // Ganho esperado (exemplo: dividendo de R$ 2 por ação)
    $dividendoPorAcao = 2.00; // valor que virá da API ou tabela
    $ganho = $qtdAcoes * $dividendoPorAcao;

    $stmt = $conn->prepare("INSERT INTO div_compra (acao, valor, tipo, preco_acao, qtd_acoes, ganho) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsdid", $acao, $valor, $tipo, $precoAcao, $qtdAcoes, $ganho);
    $stmt->execute();
    $stmt->close();
}

// Buscar registros
$sql = "SELECT id, acao, valor, tipo, preco_acao, qtd_acoes, ganho 
        FROM div_compra 
        ORDER BY id DESC";
$result = $conn->query($sql);

$compras = [];
while ($row = $result->fetch_assoc()) {
    $compras[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Div Compra</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <!-- Cadastro -->
            <div class="card-cadastro-renda">
                <h2>Simulação de Compra</h2>
                <form class="form-rendas" method="POST">
                    <input type="hidden" name="nova_compra" value="1">

                    <!-- Nome da ação -->
                    <input type="text" name="acao" placeholder="Ex: PETR4, VALE3..." required>

                    <!-- Valor disponível -->
                    <input type="number" step="0.01" name="valor" placeholder="R$ 0,00" required>

                    <!-- Tipo: DIV ou JCP -->
                    <select name="tipo" required>
                        <option value="DIV">DIV</option>
                        <option value="JCP">JCP</option>
                    </select>

                    <button type="submit">Calcular</button>
                </form>
            </div>
        </div>

        <!-- Lista -->
        <div class="card-lista-renda">
            <h2>Histórico de Simulações</h2>
            <table class="tabela-rendas">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Valor Investido</th>
                        <th>Tipo</th>
                        <th>Preço da Ação</th>
                        <th>Qtd. Ações</th>
                        <th>Ganho Estimado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $c) { ?>
                    <tr>
                        <td><?= htmlspecialchars($c['acao']) ?></td>
                        <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                        <td><?= $c['tipo'] ?></td>
                        <td>R$ <?= number_format($c['preco_acao'], 2, ',', '.') ?></td>
                        <td><?= $c['qtd_acoes'] ?></td>
                        <td>R$ <?= number_format($c['ganho'], 2, ',', '.') ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>