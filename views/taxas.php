<?php
// Página de Cadastro de Taxas
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Taxa Corretora</title>
    <link rel="stylesheet" href="../assets/css/style-daytrade.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="card-cadastro-renda">
            <h2>Cadastrar Taxa da Corretora</h2>
            <form class="form-rendas" action="salvar_taxa.php" method="post">
                <label for="corretora">Nome da Corretora:</label>
                <input type="text" id="corretora" name="corretora" required>

                <label for="nome_taxa">Nome da Taxa:</label>
                <input type="text" id="nome_taxa" name="nome_taxa" required>

                <!-- Botão Salvar -->
                <button type="submit" class="btn-salvar">Salvar</button>
            </form>

            <!-- Botão Voltar separado -->
            <a href="daytrade.php" class="btn-voltar">Voltar</a>
        </div>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>
</body>
</html>
