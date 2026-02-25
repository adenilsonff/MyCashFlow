<?php
include __DIR__ . '/../config.php';
session_start();

// Inserir nova ação
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticker = $_POST['ticker'];
    $quantidade = $_POST['quantidade'];
    $valor_unitario = $_POST['valor_unitario'];
    $data = $_POST['data'];
    $valor_mercado = $_POST['valor_mercado'];

    $sql = "INSERT INTO acoes (ticker, quantidade, valor_unitario, data, valor_mercado) 
            VALUES ('$ticker', '$quantidade', '$valor_unitario', '$data', '$valor_mercado')";
    $conn->query($sql);
}

// Listar ações
$sql = "SELECT * FROM acoes";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ações</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/menu.php'; ?>

    <h2>Cadastro de Ações</h2>
    <form method="POST">
        <input type="text" name="ticker" placeholder="Ticker (ex: PETR4)" required>
        <input type="number" name="quantidade" placeholder="Quantidade" required>
        <input type="number" step="0.01" name="valor_unitario" placeholder="Valor unitário" required>
        <input type="date" name="data" required>
        <input type="number" step="0.01" name="valor_mercado" placeholder="Valor de mercado (opcional)">
        <button type="submit">Adicionar</button>
    </form>

    <h2>Lista de Ações</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Ticker</th>
            <th>Quantidade</th>
            <th>Valor Unitário</th>
            <th>Data</th>
            <th>Valor Mercado</th>
            <th>Total</th>
        </tr>
        <?php while($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['ticker'] ?></td>
            <td><?= $row['quantidade'] ?></td>
            <td>R$ <?= number_format($row['valor_unitario'], 2, ',', '.') ?></td>
            <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
            <td>R$ <?= number_format($row['valor_mercado'], 2, ',', '.') ?></td>
            <td>R$ <?= number_format($row['total'], 2, ',', '.') ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>