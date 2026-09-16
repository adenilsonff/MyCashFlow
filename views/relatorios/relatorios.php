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

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/relatorios/style-relatorios.css?v=2"
    >
</head>

<body>

<?php include("../../includes/header.php"); ?>
<?php include("../../includes/menu.php"); ?>

<main class="relatorios-layout">

    <div class="cabecalho-relatorios">

        <div>

            <h1>Relatórios</h1>

            <p>
                Consulte e analise suas informações financeiras
                de forma consolidada.
            </p>

        </div>

    </div>

    <section class="relatorios-grid">

        <a
            href="financeiro.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Financeiro
                </span>

                <h2>
                    Visão Financeira
                </h2>

                <p>
                    Acompanhe receitas, despesas, saldos e a evolução
                    financeira ao longo do ano.
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

        <a
            href="gastos.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Despesas
                </span>

                <h2>
                    Gastos
                </h2>

                <p>
                    Analise seus gastos ao longo do ano por categoria,
                    tipo, pagamento e evolução mensal.
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

        <a
            href="receitas.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Receitas
                </span>

                <h2>
                    Receitas
                </h2>

                <p>
                    Analise receitas previstas, valores recebidos,
                    valores a receber e a evolução mensal ao longo do ano.
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

        <a
            href="cartao.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Cartão de Crédito
                </span>

                <h2>
                    Cartão de Crédito
                </h2>

                <p>
                    Analise faturas, lançamentos, créditos,
                    valores pagos e a evolução mensal do cartão
                    ao longo do ano.
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

        <a
            href="investimentos.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Investimentos
                </span>

                <h2>
                    Investimentos
                </h2>

                <p>
                    Analise a posição atual da carteira, resultados,
                    compras, vendas e a movimentação dos investimentos
                    nacionais e internacionais.
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

        <a
            href="proventos.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Proventos
                </span>

                <h2>
                    Proventos
                </h2>

                <p>
                    Analise os direitos a proventos pela posição
                    histórica na Data COM, por ativo, tipo e
                    evolução ao longo do ano.
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

        <a
            href="daytrade.php"
            class="card-relatorio"
        >

            <div class="card-relatorio-conteudo">

                <span class="card-relatorio-tipo">
                    Day Trade
                </span>

                <h2>
                    Day Trade
                </h2>

                <p>
                    Analise volume negociado, resultados, taxas,
                    DARF e a evolução das operações de Day Trade
                    ao longo do ano.
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