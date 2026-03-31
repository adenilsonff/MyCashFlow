<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

// Inserir nova renda
if (isset($_POST['nova_renda'])) {
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $data = new DateTime($_POST['data']);
    $valor = floatval($_POST['valor']);
    $tipo = $_POST['tipoRenda']; // mensal ou anual

    if ($tipo === "anual") {
        for ($i = 0; $i < 12; $i++) {
            $dataReceb = clone $data;
            $dataReceb->modify("+$i month");
            $dataFormatada = $dataReceb->format("Y-m-d");

            $stmt = $conn->prepare("INSERT INTO rendas (nome, descricao, data, valor, recebido, porcentagem) VALUES (?, ?, ?, ?, 0, 0)");
            $stmt->bind_param("sssd", $nome, $descricao, $dataFormatada, $valor);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $dataFormatada = $data->format("Y-m-d");
        $stmt = $conn->prepare("INSERT INTO rendas (nome, descricao, data, valor, recebido, porcentagem) VALUES (?, ?, ?, ?, 0, 0)");
        $stmt->bind_param("sssd", $nome, $descricao, $dataFormatada, $valor);
        $stmt->execute();
        $stmt->close();
    }
}

// Atualizar status recebido
if (isset($_POST['atualizar_recebido'])) {
    $id = $_POST['id'];
    $recebido = $_POST['recebido'] == 1 ? 1 : 0;
    $stmt = $conn->prepare("UPDATE rendas SET recebido = ? WHERE id = ?");
    $stmt->bind_param("ii", $recebido, $id);
    $stmt->execute();
    $stmt->close();
}

// Ajustar valor
if (isset($_POST['ajustar_valor'])) {
    $id = $_POST['id'];
    $novo_valor = floatval($_POST['novo_valor']);
    $stmt = $conn->prepare("UPDATE rendas SET valor = ? WHERE id = ?");
    $stmt->bind_param("di", $novo_valor, $id);
    $stmt->execute();
    $stmt->close();
}

// Deletar renda
if (isset($_POST['deletar_renda'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM rendas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Buscar rendas do mês/ano
$sql = "SELECT id, nome, descricao, data, valor, recebido, porcentagem 
        FROM rendas 
        WHERE MONTH(data) = ? AND YEAR(data) = ?
        ORDER BY data ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$rendas = [];
while ($row = $result->fetch_assoc()) {
    $rendas[] = $row;
    $total += $row['valor'];
}

$meses = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Rendas</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <!-- Cadastro -->
            <div class="card-cadastro-renda">
                <h2>Cadastro de Renda</h2>
                <form class="form-rendas" method="POST">
                    <input type="hidden" name="nova_renda" value="1">

                    <!-- Nome da renda -->
                    <input type="text" name="nome" placeholder="Nome da Renda (ex: Salário, Freelance...)" required>

                    <!-- Descrição detalhada -->
                    <input type="text" name="descricao" placeholder="Descrição detalhada" required>

                    <!-- Tipo da renda: mensal ou anual -->
                    <select name="tipoRenda">
                        <option value="unica">Unica</option>
                        <option value="mensal">Mensal</option>
                    </select>

                    <!-- Data do recebimento -->
                    <input type="date" name="data" required>

                    <!-- Valor da renda -->
                    <input type="number" step="0.01" name="valor" placeholder="R$ 0,00" required>

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
                </form>
            </div>

            <!-- Ajuste de Valor -->
            <div class="card-cadastro-renda">
                <h2>Ajustar Valor de Renda</h2>
                <form method="POST" class="form-rendas">
                    <select name="id" required>
                        <option value="">Selecione a Renda</option>
                        <?php foreach ($rendas as $r) { ?>
                            <option value="<?= $r['id'] ?>">
                                <?= htmlspecialchars($r['descricao']) ?> - <?= date("d/m/Y", strtotime($r['data'])) ?> 
                                (R$ <?= number_format($r['valor'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <input type="number" step="0.01" name="novo_valor" placeholder="Novo valor" required>
                    <button type="submit" name="ajustar_valor">Atualizar</button>
                </form>
            </div>

            <!-- Deletar Renda -->
            <div class="card-cadastro-renda">
                <h2>Deletar Renda</h2>
                <form method="POST" class="form-rendas" onsubmit="return confirm('Tem certeza que deseja excluir esta renda?');">
                    <select name="id" required>
                        <option value="">Selecione a Renda</option>
                        <?php foreach ($rendas as $r) { ?>
                            <option value="<?= $r['id'] ?>">
                                <?= htmlspecialchars($r['descricao']) ?> - <?= date("d/m/Y", strtotime($r['data'])) ?> 
                                (R$ <?= number_format($r['valor'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" name="deletar_renda">Excluir</button>
                </form>
            </div>
        </div>

        <!-- Lista de Rendas -->
        <div class="card-lista-renda">
            <h2>Lista de Rendas - <?= $meses[$mes-1] ?>/<?= $ano ?></h2>
            <table class="tabela-rendas">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Recebido</th>
                        <th>Porcentagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rendas as $r) { ?>
                    <tr class="<?= $r['recebido'] ? 'linha-recebida' : 'linha-nao-recebida' ?>">
                        <td><?= htmlspecialchars($r['nome']) ?></td>
                        <td><?= htmlspecialchars($r['descricao']) ?></td>
                        <td><?= date("d/m/Y", strtotime($r['data'])) ?></td>
                        <td>R$ <?= number_format($r['valor'], 2, ',', '.') ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <select name="recebido" onchange="this.form.submit()">
                                    <option value="0" <?= !$r['recebido'] ? 'selected' : '' ?>>Não</option>
                                    <option value="1" <?= $r['recebido'] ? 'selected' : '' ?>>Sim</option>
                                </select>
                                <input type="hidden" name="atualizar_recebido" value="1">
                            </form>
                        </td>
                        <td><?= $total > 0 ? number_format(($r['valor']/$total)*100, 2, ',', '.') : '0,00' ?>%</td>
                    </tr>
                    <?php } ?>
                    <tr style="font-weight:bold; background:#f4f4f4;">
                        <td colspan="3">Total</td>
                        <td>R$ <?= number_format($total, 2, ',', '.') ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>

        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>