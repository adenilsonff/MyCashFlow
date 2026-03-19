<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

// Inserir novo dividendo
if (isset($_POST['novo_dividendo'])) {
    $acao_id = (int)$_POST['acao_id'];
    $valor_unitario = floatval($_POST['valor_unitario']);
    $data = new DateTime($_POST['data']);
    $dataFormatada = $data->format("Y-m-d");

    // Buscar quantidade da ação
    $quantidade = 0;
    $stmt = $conn->prepare("SELECT quantidade FROM acoes_nacionais WHERE id = ?");
    $stmt->bind_param("i", $acao_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $quantidade = (int)$row['quantidade'];
    }
    $stmt->close();

    if ($quantidade === 0) {
        $stmt = $conn->prepare("SELECT quantidade FROM acoes_internacionais WHERE id = ?");
        $stmt->bind_param("i", $acao_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $quantidade = (int)$row['quantidade'];
        }
        $stmt->close();
    }

    $total = $quantidade * $valor_unitario;

    $stmt = $conn->prepare("INSERT INTO dividendos_valor (acao_id, valor_unitario, data, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isdi", $acao_id, $valor_unitario, $dataFormatada, $total);
    $stmt->execute();
    $stmt->close();
}

// Ajustar valor unitário do dividendo
if (isset($_POST['ajustar_valor'])) {
    $id = (int)$_POST['id'];
    $novo_valor = floatval($_POST['novo_valor']);

    // Buscar quantidade da ação vinculada ao dividendo
    $stmt = $conn->prepare("SELECT dv.acao_id, 
                                   COALESCE(an.quantidade, ai.quantidade) AS quantidade
                            FROM dividendos_valor dv
                            LEFT JOIN acoes_nacionais an ON dv.acao_id = an.id
                            LEFT JOIN acoes_internacionais ai ON dv.acao_id = ai.id
                            WHERE dv.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quantidade = 0;
    if ($row = $result->fetch_assoc()) {
        $quantidade = (int)$row['quantidade'];
    }
    $stmt->close();

    $novo_total = $quantidade * $novo_valor;

    $stmt = $conn->prepare("UPDATE dividendos_valor SET valor_unitario = ?, total = ? WHERE id = ?");
    $stmt->bind_param("dii", $novo_valor, $novo_total, $id);
    $stmt->execute();
    $stmt->close();
}

// Deletar dividendo
if (isset($_POST['deletar_dividendo'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM dividendos_valor WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

$mostrar_todos = isset($_GET['mostrar_todos']);

if ($mostrar_todos) {
    $sql = "SELECT dv.id, dv.acao_id, dv.valor_unitario, dv.data, dv.total,
                   COALESCE(an.ticker, ai.ticker) AS ticker
            FROM dividendos_valor dv
            LEFT JOIN acoes_nacionais an ON dv.acao_id = an.id
            LEFT JOIN acoes_internacionais ai ON dv.acao_id = ai.id
            ORDER BY dv.data ASC";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT dv.id, dv.acao_id, dv.valor_unitario, dv.data, dv.total,
                   COALESCE(an.ticker, ai.ticker) AS ticker
            FROM dividendos_valor dv
            LEFT JOIN acoes_nacionais an ON dv.acao_id = an.id
            LEFT JOIN acoes_internacionais ai ON dv.acao_id = ai.id
            WHERE MONTH(dv.data) = ? AND YEAR(dv.data) = ?
            ORDER BY dv.data ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $mes, $ano);
}
$stmt->execute();
$result = $stmt->get_result();

$dividendos = [];
$total_geral = 0;
while ($row = $result->fetch_assoc()) {
    $dividendos[] = $row;
    $total_geral += $row['total'];
}

$meses = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dividendos</title>
    <link rel="stylesheet" href="../assets/css/style-dividendos.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <!-- Cadastro -->
            <div class="card-cadastro-renda">
                <h2>Cadastro de Dividendos</h2>
                <form class="form-rendas" method="POST">
                    <input type="hidden" name="novo_dividendo" value="1">

                    <select name="acao_id" required>
                        <option value="">Selecione a Ação</option>
                        <?php
                        $acoes = $conn->query("SELECT id, ticker FROM acoes_nacionais 
                                               UNION 
                                               SELECT id, ticker FROM acoes_internacionais");
                        while ($a = $acoes->fetch_assoc()) {
                            echo "<option value='{$a['id']}'>" . htmlspecialchars($a['ticker']) . "</option>";
                        }
                        ?>
                    </select>

                    <input type="number" step="0.01" name="valor_unitario" placeholder="Valor por ação" required>
                    <input type="date" name="data" required>

                    <button type="submit">Registrar</button>
                </form>
            </div>

            <!-- Filtro -->
            <div class="card-cadastro-renda">
                <h2>Filtrar por Mês/Ano</h2>
                <form method="GET" class="form-rendas">
                    <select name="mes">
                        <?php for($i=1;$i<=12;$i++) {
                            $sel = ($i == $mes) ? "selected" : "";
                            echo "<option value='$i' $sel>{$meses[$i-1]}</option>";
                        } ?>
                    </select>
                    <select name="ano">
                        <?php for($y = date("Y"); $y <= date("Y")+5; $y++) {
                            $sel = ($y == $ano) ? "selected" : "";
                            echo "<option value='$y' $sel>$y</option>";
                        } ?>
                    </select>
                    <button type="submit">Filtrar</button>
                    <!-- Aqui entra o botão extra -->
                    <button type="submit" name="mostrar_todos" value="1">Mostrar Todos</button>
                </form>
            </div>
            <!-- Ajuste de Valor -->
            <div class="card-cadastro-renda">
                <h2>Ajustar Valor de Dividendo</h2>
                <form method="POST" class="form-rendas">
                    <select name="id" required>
                        <option value="">Selecione o Dividendo</option>
                        <?php foreach ($dividendos as $d) { ?>
                            <option value="<?= $d['id'] ?>">
                                <?= htmlspecialchars($d['ticker']) ?> - <?= date("d/m/Y", strtotime($d['data'])) ?> 
                                (R$ <?= number_format($d['valor_unitario'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <input type="number" step="0.01" name="novo_valor" placeholder="Novo valor unitário" required>
                    <button type="submit" name="ajustar_valor">Atualizar</button>
                </form>
            </div>

            <!-- Deletar Dividendo -->
            <div class="card-cadastro-renda">
                <h2>Deletar Dividendo</h2>
                <form method="POST" class="form-rendas" onsubmit="return confirm('Tem certeza que deseja excluir este dividendo?');">
                    <select name="id" required>
                        <option value="">Selecione o Dividendo</option>
                        <?php foreach ($dividendos as $d) { ?>
                            <option value="<?= $d['id'] ?>">
                                <?= htmlspecialchars($d['ticker']) ?> - <?= date("d/m/Y", strtotime($d['data'])) ?> 
                                (R$ <?= number_format($d['valor_unitario'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" name="deletar_dividendo">Excluir</button>
                </form>
            </div>
        </div>

        <!-- Lista de Dividendos -->
        <div class="card-lista-renda">
            <h2>Lista de Dividendos - <?= $meses[$mes-1] ?>/<?= $ano ?></h2>
            <table class="tabela-rendas">
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Valor Unitário</th>
                        <th>Data</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dividendos as $d) { ?>
                    <tr>
                        <td><?= htmlspecialchars($d['ticker']) ?></td>
                        <td>R$ <?= number_format($d['valor_unitario'], 2, ',', '.') ?></td>
                        <td><?= (new DateTime($d['data']))->format("d/m/Y") ?></td>
                        <td>R$ <?= number_format($d['total'], 2, ',', '.') ?></td>
                    </tr>
                    <?php } ?>
                    <tr style="font-weight:bold; background:#f4f4f4;">
                        <td colspan="3">Total</td>
                        <td>R$ <?= number_format($total_geral, 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>