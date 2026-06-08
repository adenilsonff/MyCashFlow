<?php
include __DIR__ . '/../../config.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// --- FILTROS ---
$where = [];
if (!empty($_GET['data_inicio'])) {
    $where[] = "data >= '" . $conn->real_escape_string($_GET['data_inicio']) . "'";
}
if (!empty($_GET['data_fim'])) {
    $where[] = "data <= '" . $conn->real_escape_string($_GET['data_fim']) . "'";
}
if (!empty($_GET['ano'])) {
    $where[] = "YEAR(data) = " . intval($_GET['ano']);
}
if (!empty($_GET['mes'])) {
    $where[] = "MONTH(data) = " . intval($_GET['mes']);
}
if (!empty($_GET['ticker'])) {
    $where[] = "ticker = '" . $conn->real_escape_string($_GET['ticker']) . "'";
}
$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// --- LISTA DE TICKERS ---
$sqlTickers = "SELECT DISTINCT ticker FROM acoes_nacionais ORDER BY ticker ASC";
$resTickers = $conn->query($sqlTickers);

// --- RESUMO ---
$sqlResumo = "SELECT 
                ticker,
                SUM(quantidade) AS qtd_total,
                SUM(quantidade * valor_unitario) AS investido,
                MAX(valor_mercado) AS mercado_atual,
                (MAX(valor_mercado) * SUM(quantidade)) - SUM(quantidade * valor_unitario) AS resultado
              FROM acoes_nacionais
              $whereSql
              GROUP BY ticker
              HAVING qtd_total > 0";
$resResumo = $conn->query($sqlResumo);

$dados = [];
$totalInvestido = 0;
$totalResultado = 0;

while($row = $resResumo->fetch_assoc()) {
    $dados[] = [
        'ticker' => $row['ticker'],
        'investido' => $row['investido'],
        'mercado' => $row['mercado_atual'] * $row['qtd_total'],
        'resultado' => $row['resultado']
    ];
    $totalInvestido += $row['investido'];
    $totalResultado += $row['resultado'];
}
$jsonDados = json_encode($dados);

// --- DETALHES ---
$sqlDetalhes = "SELECT ticker, data, quantidade, valor_unitario, valor_mercado
                FROM acoes_nacionais
                $whereSql
                ORDER BY data ASC";
$resDetalhes = $conn->query($sqlDetalhes);

$relatorio = [
    'titulo' => 'Relatório de Ações Nacionais',
    'total_investido' => $totalInvestido,
    'total_resultado' => $totalResultado,
    'dados' => $dados,
    'detalhes' => $resDetalhes
];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $relatorio['titulo'] ?></title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/relatorios/style-rel-acoes.css">
</head>
<body>
    <?php include("../../includes/header.php"); ?>
    <?php include("../../includes/menu.php"); ?>

    <main class="acoes-layout">
        <h1><?= $relatorio['titulo'] ?></h1>
        <div class="filtro-acoes">
            <button class="btn-filtro" onclick="mostrarRelatorio('consolidado')">Consolidado</button>
            <button class="btn-filtro" onclick="mostrarRelatorio('nacional')">Nacional</button>
            <button class="btn-filtro" onclick="mostrarRelatorio('internacional')">Internacional</button>
        </div>

        <button onclick="window.print()" class="btn-imprimir">🖨️ Imprimir</button>

        <!-- FILTROS -->
        <form method="GET" class="filtros">
            <label>Data inicial: <input type="date" name="data_inicio" value="<?= $_GET['data_inicio'] ?? '' ?>"></label>
            <label>Data final: <input type="date" name="data_fim" value="<?= $_GET['data_fim'] ?? '' ?>"></label>
            <label>Ano:
                <select name="ano">
                    <option value="">Todos</option>
                    <?php 
                    $anos = $conn->query("SELECT DISTINCT YEAR(data) AS ano FROM acoes_nacionais ORDER BY ano DESC");
                    while($a = $anos->fetch_assoc()) { ?>
                        <option value="<?= $a['ano'] ?>" <?= (($_GET['ano'] ?? '')==$a['ano'])?'selected':'' ?>><?= $a['ano'] ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>Mês:
                <select name="mes">
                    <option value="">Todos</option>
                    <?php 
                    $meses = ['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
                    foreach($meses as $num=>$nome) { ?>
                        <option value="<?= $num ?>" <?= (($_GET['mes'] ?? '')==$num)?'selected':'' ?>><?= $nome ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>Ação:
                <select name="ticker">
                    <option value="">Todas</option>
                    <?php while($t = $resTickers->fetch_assoc()) { ?>
                        <option value="<?= $t['ticker'] ?>" <?= (($_GET['ticker'] ?? '')==$t['ticker'])?'selected':'' ?>><?= $t['ticker'] ?></option>
                    <?php } ?>
                </select>
            </label>
            <button type="submit">Filtrar</button>
        </form>

        <!-- KPIs -->
        <section class="kpis">
            <div class="kpi-card">
                <h3>Total Investido</h3>
                <p>R$ <?= number_format($relatorio['total_investido'], 2, ',', '.') ?></p>
            </div>
            <div class="kpi-card">
                <h3>Resultado Consolidado</h3>
                <p>
                    <?php if ($relatorio['total_resultado'] >= 0) { ?>
                        <span class="positivo">+ R$ <?= number_format($relatorio['total_resultado'], 2, ',', '.') ?></span>
                    <?php } else { ?>
                        <span class="negativo">- R$ <?= number_format(abs($relatorio['total_resultado']), 2, ',', '.') ?></span>
                    <?php } ?>
                </p>
            </div>
        </section>

        <!-- Layout duas colunas -->
        <div class="layout-duas-colunas">
            <!-- Coluna esquerda: gráfico -->
            <div class="col-esquerda">
                <label for="tipoGrafico">Tipo de gráfico:</label>
                <select id="tipoGrafico">
                    <option value="bar">Barra</option>
                    <option value="line">Linha</option>
                    <option value="pie">Pizza</option>
                    <option value="doughnut">Rosquinha</option>
                </select>
                <canvas id="graficoAcoes"></canvas>
            </div>

            <!-- Coluna direita: cards -->
            <div class="col-direita">
                <div class="cards-container">
                    <?php foreach($relatorio['dados'] as $acao) { ?>
                        <div class="card-acao">
                            <h3><?= $acao['ticker'] ?></h3>
                            <p>Investido: R$ <?= number_format($acao['investido'], 2, ',', '.') ?></p>
                            <p>Mercado: R$ <?= number_format($acao['mercado'], 2, ',', '.') ?></p>
                            <p>
                                <?php if ($acao['resultado'] >= 0) { ?>
                                    <span class="positivo">+ R$ <?= number_format($acao['resultado'], 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span class="negativo">- R$ <?= number_format(abs($acao['resultado']), 2, ',', '.') ?></span>
                                <?php } ?>
                            </p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Tabela detalhada -->
        <section class="tabela-detalhes">
            <h2>Histórico de Operações</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ticker</th>
                        <th>Data</th>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th>Valor Mercado</th>
                        <th>Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $relatorio['detalhes']->fetch_assoc()) {
                        $investido = $row['quantidade'] * $row['valor_unitario'];
                        $mercado   = $row['quantidade'] * $row['valor_mercado'];
                        $resultado = $mercado - $investido;
                    ?>
                        <tr>
                            <td><?= $row['ticker'] ?></td>
                            <td><?= date('d/m/Y', strtotime($row['data'])) ?></td>
                            <td><?= $row['quantidade'] ?></td>
                            <td>R$ <?= number_format($row['valor_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($row['valor_mercado'], 2, ',', '.') ?></td>
                            <td><?= number_format($resultado, 2, ',', '.') ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Passando dados para JS externo -->
    <script>
        window.relatorioDados = <?= $jsonDados ?>;
    </script>
    <!-- Arquivo JS separado -->
    <script src="/MyCashFlow/assets/js/relatorios/rel-acoes.js"></script>
</body>
</html>
