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
            $sql = "SELECT COALESCE(SUM(valor), 0) AS total_contas FROM contas";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $total_contas = $row['total_contas'] ?? 0;
            echo "<p>Total: R$ " . number_format($total_contas, 2, ',', '.') . "</p>";
            ?>
        </div>

        <!-- Card Rendas -->
        <div class="card">
            <h3>Receitas</h3>
            <?php
            $sql = "SELECT COALESCE(SUM(valor), 0) AS total_renda FROM rendas";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $total_renda = $row['total_renda'] ?? 0;
            echo "<p>Total em Renda: R$ " . number_format($total_renda, 2, ',', '.') . "</p>";
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