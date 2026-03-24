<?php
include __DIR__ . '/../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

// Inserir novo registro Div DataCOM
if (isset($_POST['nova_div'])) {
    $acao = strtoupper($_POST['acao']); // garante maiúscula
    $datacom = new DateTime($_POST['datacom']);
    $datapag = new DateTime($_POST['datapag']);
    $valor = floatval($_POST['valor']);
    $tipo = $_POST['tipo']; // DIV ou JCP

    $dataComFormatada = $datacom->format("Y-m-d");
    $dataPagFormatada = $datapag->format("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO div_datacom (acao, datacom, datapag, valor, tipo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssds", $acao, $dataComFormatada, $dataPagFormatada, $valor, $tipo);
    $stmt->execute();
    $stmt->close();
}

// Ajustar valor
if (isset($_POST['ajustar_valor'])) {
    $id = $_POST['id'];
    $novo_valor = floatval($_POST['novo_valor']);
    $stmt = $conn->prepare("UPDATE div_datacom SET valor = ? WHERE id = ?");
    $stmt->bind_param("di", $novo_valor, $id);
    $stmt->execute();
    $stmt->close();
}

// Deletar registro
if (isset($_POST['deletar_div'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM div_datacom WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Buscar registros do mês/ano
$sql = "SELECT id, acao, datacom, datapag, valor, tipo 
        FROM div_datacom 
        WHERE MONTH(datacom) = ? AND YEAR(datacom) = ?
        ORDER BY datacom ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$divs = [];
while ($row = $result->fetch_assoc()) {
    $divs[] = $row;
    $total += $row['valor'];
}

$meses = ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Div DataCOM</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <!-- Cadastro -->
            <div class="card-cadastro-renda">
                <h2>Cadastro da dataCOM</h2>
                <form class="form-rendas" method="POST">
                    <input type="hidden" name="nova_div" value="1">

                    <!-- Nome da ação -->
                    <input type="text" name="acao" placeholder="Ex: PETR4, VALE3..." required>

                    <!-- Data COM -->
                    <label for="datacom">dataCOM</label>
                    <input type="date" id="datacom" name="datacom" required>

                    <!-- Data de Pagamento -->
                    <label for="datapag">PAGAMENTO</label>
                    <input type="date" id="datapag" name="datapag" required>

                    <!-- Valor -->
                    <input type="number" step="0.01" name="valor" placeholder="R$ 0,00" required>

                    <!-- Tipo: DIV ou JCP -->
                    <select name="tipo" required>
                        <option value="DIV">DIV</option>
                        <option value="JCP">JCP</option>
                    </select>

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
                <h2>Ajustar Valor do Dividendo</h2>
                <form method="POST" class="form-rendas">
                    <select name="id" required>
                        <option value="">Selecione o Registro</option>
                        <?php foreach ($divs as $d) { ?>
                            <option value="<?= $d['id'] ?>">
                                <?= htmlspecialchars($d['acao']) ?> - <?= date("d/m/Y", strtotime($d['datacom'])) ?> 
                                (<?= $d['tipo'] ?> - R$ <?= number_format($d['valor'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <input type="number" step="0.01" name="novo_valor" placeholder="Novo valor" required>
                    <button type="submit" name="ajustar_valor">Atualizar</button>
                </form>
            </div>

            <!-- Deletar Registro -->
            <div class="card-cadastro-renda">
                <h2>Deletar dataCOM</h2>
                <form method="POST" class="form-rendas" onsubmit="return confirm('Tem certeza que deseja excluir este registro?');">
                    <select name="id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($divs as $d) { ?>
                            <option value="<?= $d['id'] ?>">
                                <?= htmlspecialchars($d['acao']) ?> - <?= date("d/m/Y", strtotime($d['datacom'])) ?> 
                                (<?= $d['tipo'] ?> - R$ <?= number_format($d['valor'], 2, ',', '.') ?>)
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" name="deletar_div">Excluir</button>
                </form>
            </div>
        </div>

        <!-- Lista -->
        <div class="card-lista-renda">
            <h2>Lista Div DataCOM - <?= $meses[$mes-1] ?>/<?= $ano ?></h2>
            <table class="tabela-rendas">
                <thead>
                    <tr>
                        <th>Ação</th>
                        <th>Data COM</th>
                        <th>Data Pagamento</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Tempo Restante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($divs as $d) { 
                        // Normaliza ambas as datas para meia-noite
                        $dataCom = new DateTime($d['datacom']);
                        $dataCom->setTime(23,59,59); // fim do dia da data COM

                        $hoje = new DateTime();
                        $hoje->setTime(0,0,0); // início do dia de hoje

                        // diferença em segundos
                        $segundosRestantes = $dataCom->getTimestamp() - $hoje->getTimestamp();

                        if ($segundosRestantes <= 0) {
                            $tempoRestante = "------"; // já passou ou é hoje
                        } else {
                            // arredonda para cima para contar dias corridos
                            $diasRestantes = ceil($segundosRestantes / 86400);
                            $tempoRestante = $diasRestantes . " dias";
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($d['acao']) ?></td>
                        <td><?= date("d/m/Y", strtotime($d['datacom'])) ?></td>
                        <td><?= date("d/m/Y", strtotime($d['datapag'])) ?></td>
                        <td>R$ <?= number_format($d['valor'], 2, ',', '.') ?></td>
                        <td><?= $d['tipo'] ?></td>
                        <td><?= $tempoRestante ?></td>
                    </tr>
                    <?php } ?>
                    <tr style="font-weight:bold; background:#f4f4f4;">
                        <td colspan="3">Total</td>
                        <td colspan="3">R$ <?= number_format($total, 2, ',', '.') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        </main>

        <?php include("../includes/footer.php"); ?>
        </body>
        </html>