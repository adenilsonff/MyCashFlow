<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Filtro de mês/ano
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

// Deletar cartão
if (isset($_POST['deletar_cartao'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM cartoes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: cartoes.php?mes=$mes&ano=$ano");
    exit;
}

// Atualizar status de pagamento
if (isset($_POST['atualizar_paga'])) {
    $id = $_POST['id'];
    $paga = $_POST['paga'] == 1 ? 1 : 0;
    $stmt = $conn->prepare("UPDATE cartoes SET paga = ? WHERE id = ?");
    $stmt->bind_param("ii", $paga, $id);
    $stmt->execute();
    $stmt->close();
}

// Ajustar valor
if (isset($_POST['ajustar_valor'])) {
    $id = $_POST['id'];
    $novo_valor = floatval($_POST['novo_valor']);
    $stmt = $conn->prepare("UPDATE cartoes SET valor = ? WHERE id = ?");
    $stmt->bind_param("di", $novo_valor, $id);
    $stmt->execute();
    $stmt->close();
}

// Inserir novo cartão
if (isset($_POST['novo_cartao'])) {
    $nome = $_POST['nomeCartao'];
    $categoria = $_POST['categoria']; // pessoal/conjunta/unica
    $data = new DateTime($_POST['data']);
    $valor = floatval($_POST['valor']);
    $parcelas = (int)$_POST['parcelas'];

    // Valor de cada parcela
    $valorParcela = $valor / $parcelas;

    // Se tiver parcelas, insere várias faturas
    for ($i = 0; $i < $parcelas; $i++) {
        $dataParcela = clone $data;
        $dataParcela->modify("+$i month");
        $dataFormatada = $dataParcela->format("Y-m-d");

        // Número da parcela
        $parcela = $i + 1;

        $stmt = $conn->prepare("INSERT INTO cartoes (nome, categoria, data, paga, valor, parcela) VALUES (?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("sssdi", $nome, $categoria, $dataFormatada, $valorParcela, $parcela);
        $stmt->execute();
        $stmt->close();
    }
}

// Buscar registros
$sql = "SELECT id, nome, categoria, data, paga, valor, parcela 
        FROM cartoes 
        WHERE MONTH(data) = ? AND YEAR(data) = ? 
        ORDER BY data ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$cartoes = [];
while ($row = $result->fetch_assoc()) {
    $cartoes[] = $row;
    $total += $row['valor'];
}

function diasRestantes($data) {
    $hoje = new DateTime();
    $venc = new DateTime($data);
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
    <title>Cartões</title>
    <link rel="stylesheet" href="../assets/css/style-cartao.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="cartoes-container">
        <div class="cartoes-grid">
            <!-- Coluna esquerda -->
            <div class="col-esquerda">
                <!-- Cadastro -->
                <div class="card-cadastro">
                    <h2>Cadastro de Conta</h2>
                    <form class="form-acoes" method="POST">
                        <input type="hidden" name="novo_cartao" value="1">
                        <input type="text" name="nomeCartao" placeholder="Conta" required>
                        <select name="categoria" required>
                            <option value="pessoal">Pessoal</option>
                            <option value="conjunta">Conjunta</option>
                            <option value="unica">Outra</option>
                        </select>
                        <input type="date" name="data" required>
                        <input type="number" step="0.01" name="valor" placeholder="R$ 0,00" required>
                        <input type="number" name="parcelas" min="1" max="24" placeholder="Parcelas" required>
                        <button type="submit">Registrar</button>
                    </form>
                </div>

                <!-- Deletar Cartão -->
                <div class="card-cadastro">
                    <h2>Deletar Conta</h2>
                    <form method="POST" class="form-acoes" onsubmit="return confirm('Tem certeza que deseja excluir este cartão?');">
                        <select name="id" required>
                            <option value="">Selecione a Conta</option>
                            <?php foreach ($cartoes as $c) { ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['nome']) ?> - <?= date("d/m/Y", strtotime($c['data'])) ?> (R$ <?= number_format($c['valor'], 2, ',', '.') ?>)
                                </option>
                            <?php } ?>
                        </select>
                        <button type="submit" name="deletar_cartao">Excluir</button>
                    </form>
                </div>
            </div>

            <!-- Coluna do meio -->
            <div class="col-meio">
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
                        <button type="submit">Filtrar</button>
                    </form>
                </div>

                <!-- Ajuste de Valor -->
                <div class="card-cadastro">
                    <h2>Ajustar Valor da Fatura</h2>
                    <form method="POST" class="form-acoes">
                        <select name="id" required>
                            <option value="">Selecione a Conta</option>
                            <?php foreach ($cartoes as $c) { ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['nome']) ?> - <?= date("d/m/Y", strtotime($c['data'])) ?> (R$ <?= number_format($c['valor'], 2, ',', '.') ?>)
                                </option>
                            <?php } ?>
                        </select>
                        <input type="number" step="0.01" name="novo_valor" placeholder="Novo valor" required>
                        <button type="submit" name="ajustar_valor">Atualizar</button>
                    </form>
                </div>
            </div>

            <!-- Coluna da direita -->
            <div class="col-direita">
                <!-- Lista de Cartões -->
                <div class="card-lista">
                    <h2>Lista de Cartões - <?= $meses[$mes-1] ?>/<?= $ano ?></h2>
                    <table class="tabela-acoes">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Parcelas</th>
                                <th>Paga</th>
                                <th>Porcentagem</th>
                                <th>Dias Restantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartoes as $c) { ?>
                            <tr class="<?= $c['paga'] ? 'linha-paga' : 'linha-nao-paga' ?>">
                                <td><?= htmlspecialchars($c['nome']) ?></td>
                                <td><?= ucfirst($c['categoria']) ?></td>
                                <td><?= date("d/m/Y", strtotime($c['data'])) ?></td>
                                <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                                <td><?= $c['parcela'] ?></td>
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
                                <td><?= diasRestantes($c['data']) ?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>