<?php
require_once __DIR__.'/../config.php';
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Proventos</title>
    <link rel="stylesheet" href="../assets/css/style-dividendos.css?v=2">
</head>

<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="proventos-layout">
        <div class="cabecalho-proventos">
            <div>
                <h1>Proventos</h1>
                <p>
                    Acompanhe seus proventos e simule oportunidades de investimento.
                </p>
            </div>
        </div>

        <div class="proventos-container">
            <a
                href="div_datacom.php"
                class="card-provento"
            >
                <div class="card-provento-conteudo">
                    <h2>Data COM</h2>
                    <p>
                        Cadastre e acompanhe as datas que dão direito aos proventos.
                    </p>
                </div>

                <span class="card-provento-acao">
                    Acessar
                </span>
            </a>

            <a
                href="div_valor.php"
                class="card-provento"
            >
                <div class="card-provento-conteudo">
                    <h2>Proventos a Receber</h2>
                    <p>
                        Consulte os proventos a que você tem direito com base na posição na Data COM.
                    </p>
                </div>

                <span class="card-provento-acao">
                    Acessar
                </span>
            </a>

            <a
                href="div_compra.php"
                class="card-provento"
            >
                <div class="card-provento-conteudo">
                    <h2>Simulador de Compra</h2>
                    <p>
                        Compare quanto uma compra pode gerar em proventos antes da Data COM.
                    </p>
                </div>

                <span class="card-provento-acao">
                    Simular
                </span>
            </a>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>