<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

include("../includes/header.php"); 
include("../includes/menu.php"); 
include("../config.php"); 
?>

<main class="dashboard-layout">
    <h2>Dashboard - MyCashFlow</h2>
    <p>Bem-vindo, <?php echo $_SESSION['usuario_email']; ?>!</p>

    <div class="cards-container">
        <!-- Card Contas -->
        <div class="card">
            <h3>Despesas</h3>
            <?php
            $mesAtual = date('m');
            $anoAtual = date('Y');

            // Total do mês atual (usando vencimento)
            $sqlMes = "SELECT COALESCE(SUM(valor), 0) AS total_mes 
                    FROM contas 
                    WHERE MONTH(vencimento) = $mesAtual AND YEAR(vencimento) = $anoAtual";
            $resultMes = $conn->query($sqlMes);
            $rowMes = $resultMes->fetch_assoc();
            $total_mes = $rowMes['total_mes'] ?? 0;

            // Total anual (usando vencimento)
            $sqlAno = "SELECT COALESCE(SUM(valor), 0) AS total_ano 
                    FROM contas 
                    WHERE YEAR(vencimento) = $anoAtual";
            $resultAno = $conn->query($sqlAno);
            $rowAno = $resultAno->fetch_assoc();
            $total_ano = $rowAno['total_ano'] ?? 0;

            echo "<p><strong>Mês Atual:</strong> R$ " . number_format($total_mes, 2, ',', '.') . "</p>";
            echo "<p><strong>Total Anual:</strong> R$ " . number_format($total_ano, 2, ',', '.') . "</p>";
            ?>
        </div>

        <!-- Card Rendas -->
        <div class="card">
            <h3>Receitas</h3>
            <?php
            $mesAtual = date('m');
            $anoAtual = date('Y');

            // Total do mês atual (usando coluna 'data')
            $sqlMes = "SELECT COALESCE(SUM(valor), 0) AS total_mes 
                    FROM rendas 
                    WHERE MONTH(data) = $mesAtual AND YEAR(data) = $anoAtual";
            $resultMes = $conn->query($sqlMes);
            $rowMes = $resultMes->fetch_assoc();
            $total_mes = $rowMes['total_mes'] ?? 0;

            // Total anual (usando coluna 'data')
            $sqlAno = "SELECT COALESCE(SUM(valor), 0) AS total_ano 
                    FROM rendas 
                    WHERE YEAR(data) = $anoAtual";
            $resultAno = $conn->query($sqlAno);
            $rowAno = $resultAno->fetch_assoc();
            $total_ano = $rowAno['total_ano'] ?? 0;

            echo "<p><strong>Mês Atual:</strong> R$ " . number_format($total_mes, 2, ',', '.') . "</p>";
            echo "<p><strong>Total Anual:</strong> R$ " . number_format($total_ano, 2, ',', '.') . "</p>";
            ?>
        </div>

        <!-- Card Investimentos -->
        <div class="card">
            <h3>Investimentos</h3>
            <?php
            $sql = "
                SELECT 
                    COALESCE((SELECT SUM(quantidade * valor_unitario) FROM acoes_nacionais),0) AS total_nacional,
                    COALESCE((SELECT SUM(quantidade * valor_unitario) FROM acoes_internacionais),0) AS total_internacional
            ";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $total_nacional = $row['total_nacional'] ?? 0;
            $total_internacional = $row['total_internacional'] ?? 0;
            $total_consolidado = $total_nacional + $total_internacional;

            echo "<p><strong>Nacional:</strong> R$ " . number_format($total_nacional, 2, ',', '.') . "</p>";
            echo "<p><strong>Internacional:</strong> R$ " . number_format($total_internacional, 2, ',', '.') . "</p>";
            echo "<p><strong>Total Consolidado:</strong> R$ " . number_format($total_consolidado, 2, ',', '.') . "</p>";
            ?>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>