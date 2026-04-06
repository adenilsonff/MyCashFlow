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
    <title>Dividendos - Demonstrativo</title>
    <link rel="stylesheet" href="../assets/css/style-divis.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">

            <!-- Card de Filtro de Mês -->
            <div class="card-cadastro-renda">
                <h2>Filtrar Dividendos por Mês</h2>
                <form method="POST" class="form-rendas">
                    <label for="mes">Mês:</label>
                    <select name="mes" id="mes" required>
                        <?php
                        $meses = [
                            1 => "Janeiro",
                            2 => "Fevereiro",
                            3 => "Março",
                            4 => "Abril",
                            5 => "Maio",
                            6 => "Junho",
                            7 => "Julho",
                            8 => "Agosto",
                            9 => "Setembro",
                            10 => "Outubro",
                            11 => "Novembro",
                            12 => "Dezembro"
                        ];

                        $mesAtual = date("n"); // mês atual (1-12)

                        foreach ($meses as $num => $nome) {
                            $selected = ($num == $mesAtual) ? "selected" : "";
                            echo "<option value='$num' $selected>$nome</option>";
                        }
                        ?>
                    </select>

                    <label for="ano">Ano:</label>
                    <input type="number" name="ano" id="ano" value="<?= date("Y") ?>" required>

                    <button type="submit" name="filtrar">Filtrar</button>
                </form>
            </div>

            <!-- Card de Lista de Dividendos -->
            <?php
            if (isset($_POST['filtrar'])) {
                $mesSelecionado = $_POST['mes'];
                $anoSelecionado = $_POST['ano'];
            } else {
                $mesSelecionado = date("n");
                $anoSelecionado = date("Y");
            }

            // Buscar apenas ações que o usuário possui e que têm dividendos no mês selecionado
            $sql = "SELECT a.id, a.ticker, a.quantidade, a.logo, d.valor, d.tipo, d.datapag
                    FROM acoes_nacionais a
                    INNER JOIN div_datacom d ON d.id_acao = a.id
                    WHERE a.quantidade > 0
                      AND MONTH(d.datapag) = ? 
                      AND YEAR(d.datapag) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $mesSelecionado, $anoSelecionado);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $totalGeral = 0;
                ?>
                <div class="card-lista-renda">
                    <h2>Dividendos Recebidos - <?= $meses[$mesSelecionado] ?>/<?= $anoSelecionado ?></h2>
                    <table class="tabela-rendas">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Ticker</th>
                                <th>Quantidade</th>
                                <th>Tipo</th>
                                <th>Valor por Ação</th>
                                <th>Total Recebido</th>
                                <th>Data Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            $totalRecebido = $row['quantidade'] * $row['valor'];
                            $totalGeral += $totalRecebido;
                            ?>
                            <tr>
                                <td><img src="<?= $row['logo'] ?>" alt="<?= $row['ticker'] ?>" style="height:40px;"></td>
                                <td><?= htmlspecialchars($row['ticker']) ?></td>
                                <td><?= $row['quantidade'] ?></td>
                                <td><?= htmlspecialchars($row['tipo']) ?></td>
                                <td>R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($totalRecebido, 2, ',', '.') ?></td>
                                <td><?= date("d/m/Y", strtotime($row['datapag'])) ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <h3>Total Geral de Dividendos: R$ <?= number_format($totalGeral, 2, ',', '.') ?></h3>
                </div>
                <?php
            } else {
                echo "<p>Nenhum dividendo encontrado para este período.</p>";
            }
            ?>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>