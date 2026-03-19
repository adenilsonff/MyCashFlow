<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Filtro de mês/ano (definido logo no início para evitar erro de tipo)
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

// Deletar conta
if (isset($_POST['deletar_conta'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM contas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Redireciona para recarregar a lista atualizada
    header("Location: contas.php?mes=$mes&ano=$ano");
    exit;
}

// Atualizar status de pagamento
if (isset($_POST['atualizar_paga'])) {
    $id = $_POST['id'];
    $paga = $_POST['paga'] == 1 ? 1 : 0;
    $stmt = $conn->prepare("UPDATE contas SET paga = ? WHERE id = ?");
    $stmt->bind_param("ii", $paga, $id);
    $stmt->execute();
    $stmt->close();
}

// Ajustar valor da conta
if (isset($_POST['ajustar_valor'])) {
    $id = $_POST['id'];
    $novo_valor = floatval($_POST['novo_valor']);
    $stmt = $conn->prepare("UPDATE contas SET valor = ? WHERE id = ?");
    $stmt->bind_param("di", $novo_valor, $id);
    $stmt->execute();
    $stmt->close();
}

// Inserir nova conta
if (isset($_POST['nova_conta'])) {
    $nome = $_POST['nomeConta'];
    $tipo = $_POST['tipoConta'];          // mensal/anual
    $categoria = $_POST['categoria'];     // pessoal/conjunta
    $vencimento = new DateTime($_POST['vencimento']);
    $valor = floatval($_POST['valor']);

    if ($tipo === "anual") {
        for ($i = 0; $i < 12; $i++) {
            $dataVenc = clone $vencimento;
            $dataVenc->modify("+$i month");
            $dataFormatada = $dataVenc->format("Y-m-d");

            $stmt = $conn->prepare("INSERT INTO contas (nome, tipo, categoria, vencimento, paga, valor) VALUES (?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("ssssd", $nome, $tipo, $categoria, $dataFormatada, $valor);
            $stmt->execute();

            if ($stmt->error) {
                echo "Erro no INSERT: " . $stmt->error;
            }

            $stmt->close();
        }
    } else {
        $dataFormatada = $vencimento->format("Y-m-d");

        $stmt = $conn->prepare("INSERT INTO contas (nome, tipo, categoria, vencimento, paga, valor) VALUES (?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("ssssd", $nome, $tipo, $categoria, $dataFormatada, $valor);
        $stmt->execute();

        if ($stmt->error) {
            echo "Erro no INSERT: " . $stmt->error;
        }

        $stmt->close();
    }
}

// Filtro de mês/ano + categoria
$mes = $_GET['mes'] ?? date("n");
$ano = $_GET['ano'] ?? date("Y");
$categoriaFiltro = $_GET['categoria'] ?? ''; // nova opção do filtro

$sql = "SELECT id, nome, tipo, categoria, vencimento, paga, valor 
        FROM contas 
        WHERE MONTH(vencimento) = ? AND YEAR(vencimento) = ?";

if (!empty($categoriaFiltro)) {
    $sql .= " AND categoria = ?";
}

$sql .= " ORDER BY vencimento ASC";

if (!empty($categoriaFiltro)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $mes, $ano, $categoriaFiltro);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $mes, $ano);
}

$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$contas = [];
while ($row = $result->fetch_assoc()) {
    $contas[] = $row;
    $total += $row['valor'];
}

function diasRestantes($dataVencimento) {
    $hoje = new DateTime();
    $venc = new DateTime($dataVencimento);
    if ($venc < $hoje) {
        return "";
    }
    return $hoje->diff($venc)->days . " dias";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Contas</title>
    <link rel="stylesheet" href="../assets/css/style-contas.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="contas-container">
        <!-- Três cards lado a lado -->
        <div class="contas-row">
            <!-- Cadastro -->
            <div class="card-cadastro">
                <h2>Cadastro de Conta</h2>
                <form class="form-acoes" method="POST">
                    <input type="hidden" name="nova_conta" value="1">
                    <input type="text" name="nomeConta" placeholder="Nome da Conta" required>
                    <select name="tipoConta">
                        <option value="mensal">Mensal</option>
                        <option value="anual">Anual</option>
                    </select>
                    <select name="categoria" required>
                        <option value="pessoal">Pessoal</option>
                        <option value="conjunta">Conjunta</option>
                    </select>
                    <input type="date" name="vencimento" required>
                    <input type="number" step="0.01" name="valor" placeholder="R$ 0,00" required>
                    <button type="submit">Registrar</button>
                </form>
            </div>

            <!-- Filtro -->
            <div class="card-cadastro">
                <h2>Filtrar por Mês/Ano</h2>
                <form method="GET" class="form-acoes">
                    <select name="mes">
                        <?php 
                        $meses = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
                        for($i=1;$i<=12;$i++) {
                            $sel = ($i == $mes) ? "selected" : "";
                            echo "<option value='$i' $sel>{$meses[$i-1]}</option>";
                        }
                        ?>
                    </select>
                    <select name="ano">
                        <?php 
                        for($y = date("Y"); $y <= date("Y")+5; $y++) {
                            $sel = ($y == $ano) ? "selected" : "";
                            echo "<option value='$y' $sel>$y</option>";
                        }
                        ?>
                    </select>
                    <select name="categoria">
                        <option value="">Todas</option>
                        <option value="pessoal" <?= (isset($_GET['categoria']) && $_GET['categoria']=="pessoal") ? "selected" : "" ?>>Pessoal</option>
                        <option value="conjunta" <?= (isset($_GET['categoria']) && $_GET['categoria']=="conjunta") ? "selected" : "" ?>>Conjunta</option>
                    </select>
                    <button type="submit">Filtrar</button>
                </form>
            </div>

            <!-- Ajuste de Valor -->
            <div class="card-cadastro">
                <h2>Ajustar Valor de Conta</h2>
                <form method="POST" class="form-acoes">
                    <select name="id" required>
                        <option value="">Selecione a Conta</option>
                        <?php foreach ($contas as $c) { ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['nome']) ?> - <?= date("d/m/Y", strtotime($c['vencimento'])) ?> (R$ <?= number_format($c['valor'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <input type="number" step="0.01" name="novo_valor" placeholder="Novo valor" required>
                    <button type="submit" name="ajustar_valor">Atualizar</button>
                </form>
            </div>

            <!-- Deletar Conta -->
            <div class="card-cadastro">
                <h2>Deletar Conta</h2>
                <form method="POST" class="form-acoes" onsubmit="return confirm('Tem certeza que deseja excluir esta conta?');">
                    <select name="id" required>
                        <option value="">Selecione a Conta</option>
                        <?php foreach ($contas as $c) { ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['nome']) ?> - <?= date("d/m/Y", strtotime($c['vencimento'])) ?> (R$ <?= number_format($c['valor'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" name="deletar_conta">Excluir</button>
                </form>
            </div>
        </div>

        </div>

        <!-- Lista de Contas -->
        <div class="card-lista">
            <h2>Lista de Contas - <?= $meses[$mes-1] ?>/<?= $ano ?></h2>
            <table class="tabela-acoes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Paga</th>
                        <th>Porcentagem</th>
                        <th>Dias Restantes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contas as $c) { ?>
                    <tr class="<?= $c['paga'] ? 'linha-paga' : 'linha-nao-paga' ?>">
                        <td><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= $c['tipo'] ?></td>
                        <td><?= ucfirst($c['categoria']) ?></td>
                        <td><?= date("d/m/Y", strtotime($c['vencimento'])) ?></td>
                        <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <select name="paga" onchange="this.form.submit()">
                                    <option value="0" <?= !$c['paga'] ? 'selected' : '' ?>>Não</option>
                                    <option value="1" <?= $c['paga'] ? 'selected' : '' ?>>Sim</option>
                                </select>
                                <input type="hidden" name="atualizar_paga" value="1">
                            </form>
                        </td>
                        <td><?= $total > 0 ? number_format(($c['valor']/$total)*100, 2, ',', '.') : '0,00' ?>%</td>
                        <td><?= diasRestantes($c['vencimento']) ?></td>
                    </tr>
                    <?php } ?>
            </table>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</