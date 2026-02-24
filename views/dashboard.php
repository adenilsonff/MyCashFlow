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

    <!--	
    <div class="logout">
        <a href="logout.php" class="btn">Sair</a>
    </div>
    -->

    <div class="cards-container">
        <div class="card">
            <h3>Contas</h3>
            <?php
            $sql = "SELECT SUM(valor) AS total_contas FROM contas";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo "<p>Total: R$ " . number_format($row['total_contas'], 2, ',', '.') . "</p>";
            ?>
        </div>

        <div class="card">
            <h3>Receitas</h3>
            <?php
            $sql = "SELECT SUM(valor) AS total_receitas FROM receitas";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo "<p>Total: R$ " . number_format($row['total_receitas'], 2, ',', '.') . "</p>";
            ?>
        </div>

        <div class="card">
            <h3>Investimentos</h3>
            <?php
            $sql = "SELECT SUM(total) AS total_acoes FROM acao";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            echo "<p>Total em Ações: R$ " . number_format($row['total_acoes'], 2, ',', '.') . "</p>";
            ?>
        </div>
    </div>
</main>

<?php include("../includes/footer.php"); ?>