<?php
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/relatorios/style-relatorios.css?v=2">
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="relatorios-layout">

    <div class="cabecalho-relatorios">
        <div>
            <h1>Relatórios</h1>
            <p>
                Consulte e analise suas informações financeiras de forma consolidada.
            </p>
        </div>
    </div>

    <section class="relatorios-grid">

        <a href="financeiro.php" class="card-relatorio">

            <div class="card-relatorio-conteudo">
                <span class="card-relatorio-tipo">
                    Financeiro
                </span>

                <h2>
                    Visão Financeira
                </h2>

                <p>
                    Acompanhe receitas, despesas, saldos e a evolução financeira ao longo do ano.
                </p>
            </div>

            <div class="card-relatorio-rodape">
                <span>
                    Abrir relatório
                </span>

                <span class="card-relatorio-seta">
                    &rarr;
                </span>
            </div>

        </a>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>

</body>
</html>