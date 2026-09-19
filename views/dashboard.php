<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
session_start();
}

if (!isset($_SESSION['usuario_id'])) {
header("Location: login/login.php");
exit;
}

include __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/mercado_api.php';

$usuario_id = (int)$_SESSION['usuario_id'];

$mesAtual = (int)date('n');
$anoAtual = (int)date('Y');

$nomesMeses = [
1 => 'Janeiro',
2 => 'Fevereiro',
3 => 'Março',
4 => 'Abril',
5 => 'Maio',
6 => 'Junho',
7 => 'Julho',
8 => 'Agosto',
9 => 'Setembro',
10 => 'Outubro',
11 => 'Novembro',
12 => 'Dezembro'
];

$nomesMesesCurtos = [
1 => 'Jan',
2 => 'Fev',
3 => 'Mar',
4 => 'Abr',
5 => 'Mai',
6 => 'Jun',
7 => 'Jul',
8 => 'Ago',
9 => 'Set',
10 => 'Out',
11 => 'Nov',
12 => 'Dez'
];

$nomeMesAtual = $nomesMeses[$mesAtual];

function dashboardResumoCusto(array $operacoes, $nacional) {
usort(
$operacoes,
function ($a, $b) {
return strcmp($a['data'], $b['data']) ?:
($a['id'] <=> $b['id']);
}
);

$posicoes = [];

foreach ($operacoes as $op) {
$ticker = strtoupper(trim($op['ticker']));

if ($nacional) {
$ticker = preg_replace('/\.SA$/', '', $ticker);
}

$tipoAtivo = $op['tipo_ativo'] ?? '';
$chave = $ticker . '|' . $tipoAtivo;

if (!isset($posicoes[$chave])) {
$posicoes[$chave] = [
'quantidade' => '0',
'custo' => '0',
'medio' => '0'
];
}

$p = &$posicoes[$chave];

$quantidade = ltrim(
(string)$op['quantidade'],
'-'
);

$preco = (string)$op['valor_unitario'];

$tipoOperacao = $op['tipo_operacao'] ?? '';

$tiposAtivoValidos = $nacional
? ['acao', 'fii', 'etf', 'bdr']
: ['stock', 'etf', 'reit', 'adr'];

if (
!in_array(
$tipoAtivo,
$tiposAtivoValidos,
true
) ||
!in_array(
$tipoOperacao,
['compra', 'venda'],
true
) ||
bccomp(
$quantidade,
'0',
8
) <= 0 ||
bccomp(
$preco,
'0',
8
) <= 0 ||
(
$tipoOperacao === 'compra' &&
bccomp(
(string)$op['quantidade'],
'0',
8
) < 0
)
) {
throw new DomainException(
'Operação inválida no histórico de ' .
$ticker . '.'
);
}

if ($tipoOperacao === 'compra') {
$p['custo'] = bcadd(
$p['custo'],
bcmul(
$quantidade,
$preco,
24
),
24
);

$p['quantidade'] = bcadd(
$p['quantidade'],
$quantidade,
8
);

$p['medio'] = bcdiv(
$p['custo'],
$p['quantidade'],
24
);

} else {
if (
bccomp(
$quantidade,
$p['quantidade'],
8
) > 0
) {
throw new DomainException(
'Venda acima do saldo no histórico de ' .
$ticker .
' em ' .
date(
'd/m/Y',
strtotime($op['data'])
) .
'.'
);
}

$p['quantidade'] = bcsub(
$p['quantidade'],
$quantidade,
8
);

$p['custo'] = bcmul(
$p['quantidade'],
$p['medio'],
24
);

if (
bccomp(
$p['quantidade'],
'0',
8
) === 0
) {
$p['custo'] = '0';
$p['medio'] = '0';
}
}

unset($p);
}

$total = '0';

foreach ($posicoes as $p) {
$total = bcadd(
$total,
$p['custo'],
24
);
}

return $total;
}

$sqlReceitas = "
SELECT
COALESCE(SUM(valor), 0) AS total,
COALESCE(SUM(CASE WHEN recebido = 1 THEN valor ELSE 0 END), 0) AS recebido,
COALESCE(SUM(CASE WHEN recebido = 0 THEN valor ELSE 0 END), 0) AS pendente
FROM rendas
WHERE MONTH(data) = ? AND YEAR(data) = ?
";

$stmt = $conn->prepare($sqlReceitas);
$stmt->bind_param("ii", $mesAtual, $anoAtual);
$stmt->execute();
$receitas = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalReceitas = (float)$receitas['total'];
$totalRecebido = (float)$receitas['recebido'];
$totalReceber = (float)$receitas['pendente'];

$sqlDespesas = "
SELECT
COALESCE(SUM(valor), 0) AS total,
COALESCE(SUM(CASE WHEN paga = 1 THEN valor ELSE 0 END), 0) AS pago,
COALESCE(SUM(CASE WHEN paga = 0 THEN valor ELSE 0 END), 0) AS pendente
FROM contas
WHERE MONTH(vencimento) = ? AND YEAR(vencimento) = ?
";

$stmt = $conn->prepare($sqlDespesas);
$stmt->bind_param("ii", $mesAtual, $anoAtual);
$stmt->execute();
$despesas = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalDespesas = (float)$despesas['total'];
$totalPago = (float)$despesas['pago'];
$totalPendente = (float)$despesas['pendente'];

$saldoPrevisto = $totalReceitas - $totalDespesas;
$saldoRealizado = $totalRecebido - $totalPago;

$sqlCartao = "
SELECT
COALESCE(SUM(c.valor), 0) AS total,
COALESCE(SUM(CASE WHEN cp.categoria = 'pessoal' THEN c.valor ELSE 0 END), 0) AS pessoal,
COALESCE(SUM(CASE WHEN cp.categoria = 'conjunta' THEN c.valor ELSE 0 END), 0) AS conjunta,
COALESCE(SUM(CASE WHEN cp.categoria = 'unica' THEN c.valor ELSE 0 END), 0) AS reembolsavel,
COALESCE(SUM(CASE WHEN c.paga = 1 THEN c.valor ELSE 0 END), 0) AS pago,
COALESCE(SUM(CASE WHEN c.paga = 0 THEN c.valor ELSE 0 END), 0) AS pendente
FROM cartoes c
INNER JOIN compras cp ON cp.id = c.compra_id
WHERE MONTH(c.data) = ? AND YEAR(c.data) = ?
";

$stmt = $conn->prepare($sqlCartao);
$stmt->bind_param("ii", $mesAtual, $anoAtual);
$stmt->execute();
$cartao = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalCartao = (float)$cartao['total'];
$totalCartaoPessoal = (float)$cartao['pessoal'];
$totalCartaoConjunta = (float)$cartao['conjunta'];
$totalCartaoReembolsavel = (float)$cartao['reembolsavel'];
$totalCartaoPago = (float)$cartao['pago'];
$totalCartaoPendente = (float)$cartao['pendente'];

$sqlDayTrade = "
SELECT
COALESCE(SUM(lucro_final), 0) AS resultado,
COALESCE(SUM(darf), 0) AS darf,
COUNT(id) AS operacoes
FROM operacoes
WHERE MONTH(data) = ? AND YEAR(data) = ?
";

$stmt = $conn->prepare($sqlDayTrade);
$stmt->bind_param("ii", $mesAtual, $anoAtual);
$stmt->execute();
$dayTrade = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalDayTrade = (float)$dayTrade['resultado'];
$totalDarf = (float)$dayTrade['darf'];
$totalOperacoes = (int)$dayTrade['operacoes'];

$totalNacional = null;
$totalInternacional = null;
$totalInvestimentos = null;
$cotacaoDolarInvestimentos = null;

if (extension_loaded('bcmath')) {
try {
$stmt = $conn->prepare("
SELECT
id,
ticker,
tipo_ativo,
quantidade,
valor_unitario,
data,
tipo_operacao
FROM investimentos_nacionais
WHERE usuario_id = ?
ORDER BY data, id
");

$stmt->bind_param(
'i',
$usuario_id
);

$stmt->execute();

$operacoesNacionais = $stmt
->get_result()
->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$totalNacional = dashboardResumoCusto(
$operacoesNacionais,
true
);

} catch (Throwable $e) {
error_log(
'MyCashFlow dashboard investimentos nacionais: ' .
$e->getMessage()
);

$totalNacional = null;
}

try {
$stmt = $conn->prepare("
SELECT
id,
ticker,
tipo_ativo,
quantidade,
valor_unitario,
data,
tipo_operacao
FROM investimentos_internacionais
WHERE usuario_id = ?
ORDER BY data, id
");

$stmt->bind_param(
'i',
$usuario_id
);

$stmt->execute();

$operacoesInternacionais = $stmt
->get_result()
->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$totalInternacional = dashboardResumoCusto(
$operacoesInternacionais,
false
);

} catch (Throwable $e) {
error_log(
'MyCashFlow dashboard investimentos internacionais: ' .
$e->getMessage()
);

$totalInternacional = null;
}

if (
$totalNacional !== null &&
$totalInternacional !== null
) {
if (
bccomp(
$totalInternacional,
'0',
24
) === 0
) {
$totalInvestimentos = $totalNacional;

} else {
try {
$cambio = mercadoApi()->fx();
$cotacaoDolarInvestimentos =
$cambio['USD']['price'] ?? null;

if ($cotacaoDolarInvestimentos !== null) {
$totalInvestimentos = bcadd(
$totalNacional,
bcmul(
$totalInternacional,
(string)$cotacaoDolarInvestimentos,
24
),
24
);
}

} catch (Throwable $e) {
error_log(
'MyCashFlow dashboard câmbio investimentos: ' .
$e->getMessage()
);

$cotacaoDolarInvestimentos = null;
}
}
}
}

$inicioGrafico = new DateTime('first day of this month');
$inicioGrafico->modify('-5 months');

$fimGrafico = new DateTime('first day of next month');

$dataInicioGrafico = $inicioGrafico->format('Y-m-d');
$dataFimGrafico = $fimGrafico->format('Y-m-d');

$periodosGrafico = [];
$labelsGrafico = [];
$receitasGrafico = [];
$despesasGrafico = [];

$dataGrafico = clone $inicioGrafico;

for ($i = 0; $i < 6; $i++) {
$chave = $dataGrafico->format('Y-m');
$mes = (int)$dataGrafico->format('n');
$ano = $dataGrafico->format('Y');

$periodosGrafico[] = $chave;
$labelsGrafico[] = $nomesMesesCurtos[$mes] . '/' . substr($ano, 2);
$receitasGrafico[$chave] = 0;
$despesasGrafico[$chave] = 0;

$dataGrafico->modify('+1 month');
}

$sqlReceitasGrafico = "
SELECT
DATE_FORMAT(data, '%Y-%m') AS periodo,
COALESCE(SUM(valor), 0) AS total
FROM rendas
WHERE data >= ? AND data < ?
GROUP BY DATE_FORMAT(data, '%Y-%m')
ORDER BY periodo
";

$stmt = $conn->prepare($sqlReceitasGrafico);
$stmt->bind_param("ss", $dataInicioGrafico, $dataFimGrafico);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
if (array_key_exists($row['periodo'], $receitasGrafico)) {
$receitasGrafico[$row['periodo']] = (float)$row['total'];
}
}

$stmt->close();

$sqlDespesasGrafico = "
SELECT
DATE_FORMAT(vencimento, '%Y-%m') AS periodo,
COALESCE(SUM(valor), 0) AS total
FROM contas
WHERE vencimento >= ? AND vencimento < ?
GROUP BY DATE_FORMAT(vencimento, '%Y-%m')
ORDER BY periodo
";

$stmt = $conn->prepare($sqlDespesasGrafico);
$stmt->bind_param("ss", $dataInicioGrafico, $dataFimGrafico);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
if (array_key_exists($row['periodo'], $despesasGrafico)) {
$despesasGrafico[$row['periodo']] = (float)$row['total'];
}
}

$stmt->close();

$valoresReceitasGrafico = [];
$valoresDespesasGrafico = [];

foreach ($periodosGrafico as $periodo) {
$valoresReceitasGrafico[] = $receitasGrafico[$periodo];
$valoresDespesasGrafico[] = $despesasGrafico[$periodo];
}

$dadosCartaoGrafico = [
$totalCartaoPessoal,
$totalCartaoConjunta,
$totalCartaoReembolsavel
];

$cssPagina = "/MyCashFlow/assets/css/style-dashboard.css";

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu.php';
?>

<main class="dashboard-layout">

<div class="dashboard-cabecalho">
<div>
<h2>Início</h2>
<p>Visão financeira de <?php echo $nomeMesAtual . '/' . $anoAtual; ?></p>
</div>

<div class="dashboard-usuario">
<span>Bem-vindo</span>
<strong><?php echo htmlspecialchars($_SESSION['usuario_email']); ?></strong>
</div>
</div>

<div class="cards-container">

<a href="/MyCashFlow/views/rendas.php" class="card-link">
<div class="card">
<div class="card-topo">
<h3>Receitas</h3>
<span class="card-status status-positivo">Mês atual</span>
</div>

<p class="valor-principal valor-positivo">
R$ <?php echo number_format($totalReceitas, 2, ',', '.'); ?>
</p>

<div class="card-detalhes">
<div>
<span>Recebido</span>
<strong>R$ <?php echo number_format($totalRecebido, 2, ',', '.'); ?></strong>
</div>
<div>
<span>A receber</span>
<strong>R$ <?php echo number_format($totalReceber, 2, ',', '.'); ?></strong>
</div>
</div>

<div class="card-rodape">Ver receitas</div>
</div>
</a>

<a href="/MyCashFlow/views/contas.php" class="card-link">
<div class="card">
<div class="card-topo">
<h3>Despesas</h3>
<span class="card-status status-negativo">Mês atual</span>
</div>

<p class="valor-principal valor-negativo">
R$ <?php echo number_format($totalDespesas, 2, ',', '.'); ?>
</p>

<div class="card-detalhes">
<div>
<span>Pago</span>
<strong>R$ <?php echo number_format($totalPago, 2, ',', '.'); ?></strong>
</div>
<div>
<span>Pendente</span>
<strong>R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></strong>
</div>
</div>

<div class="card-rodape">Ver despesas</div>
</div>
</a>

<div class="card card-saldo">
<div class="card-topo">
<h3>Saldo do Mês</h3>
<span class="card-status <?php echo $saldoPrevisto >= 0 ? 'status-positivo' : 'status-negativo'; ?>">
<?php echo $saldoPrevisto >= 0 ? 'Positivo' : 'Negativo'; ?>
</span>
</div>

<p class="valor-principal <?php echo $saldoPrevisto >= 0 ? 'valor-positivo' : 'valor-negativo'; ?>">
R$ <?php echo number_format($saldoPrevisto, 2, ',', '.'); ?>
</p>

<div class="card-detalhes">
<div>
<span>Previsto</span>
<strong class="<?php echo $saldoPrevisto >= 0 ? 'valor-positivo' : 'valor-negativo'; ?>">
R$ <?php echo number_format($saldoPrevisto, 2, ',', '.'); ?>
</strong>
</div>

<div>
<span>Realizado</span>
<strong class="<?php echo $saldoRealizado >= 0 ? 'valor-positivo' : 'valor-negativo'; ?>">
R$ <?php echo number_format($saldoRealizado, 2, ',', '.'); ?>
</strong>
</div>
</div>

<div class="card-rodape card-rodape-neutro">Receitas - Despesas</div>
</div>

<a href="/MyCashFlow/views/cartao.php" class="card-link">
<div class="card">
<div class="card-topo">
<h3>Cartão de Crédito</h3>
<span class="card-status status-neutro">Fatura</span>
</div>

<p class="valor-principal">
R$ <?php echo number_format($totalCartao, 2, ',', '.'); ?>
</p>

<div class="card-detalhes card-detalhes-lista">
<div>
<span>Particular</span>
<strong>R$ <?php echo number_format($totalCartaoPessoal, 2, ',', '.'); ?></strong>
</div>

<div>
<span>Conjunta</span>
<strong>R$ <?php echo number_format($totalCartaoConjunta, 2, ',', '.'); ?></strong>
</div>

<div>
<span>Reembolsável</span>
<strong>R$ <?php echo number_format($totalCartaoReembolsavel, 2, ',', '.'); ?></strong>
</div>

<div>
<span>Pago</span>
<strong>R$ <?php echo number_format($totalCartaoPago, 2, ',', '.'); ?></strong>
</div>

<div>
<span>Pendente</span>
<strong>R$ <?php echo number_format($totalCartaoPendente, 2, ',', '.'); ?></strong>
</div>
</div>

<div class="card-rodape">Ver fatura</div>
</div>
</a>

<a href="/MyCashFlow/views/daytrade.php" class="card-link">
<div class="card">
<div class="card-topo">
<h3>Day Trade</h3>
<span class="card-status <?php echo $totalDayTrade >= 0 ? 'status-positivo' : 'status-negativo'; ?>">
<?php echo $totalDayTrade >= 0 ? 'Lucro' : 'Prejuízo'; ?>
</span>
</div>

<p class="valor-principal <?php echo $totalDayTrade >= 0 ? 'valor-positivo' : 'valor-negativo'; ?>">
R$ <?php echo number_format($totalDayTrade, 2, ',', '.'); ?>
</p>

<div class="card-detalhes">
<div>
<span>DARF</span>
<strong>R$ <?php echo number_format($totalDarf, 2, ',', '.'); ?></strong>
</div>

<div>
<span>Operações</span>
<strong><?php echo $totalOperacoes; ?></strong>
</div>
</div>

<div class="card-rodape">Ver Day Trade</div>
</div>
</a>

<a href="/MyCashFlow/views/investimentos.php" class="card-link">
<div class="card">
<div class="card-topo">
<h3>Investimentos</h3>
<span class="card-status status-neutro">Carteira</span>
</div>

<p class="valor-principal">
<?php
echo $totalInvestimentos !== null
? 'R$ ' . number_format($totalInvestimentos, 2, ',', '.')
: 'Indisponível';
?>
</p>

<div class="card-detalhes">
<div>
<span>Nacional</span>
<strong>
<?php
echo $totalNacional !== null
? 'R$ ' . number_format($totalNacional, 2, ',', '.')
: 'Indisponível';
?>
</strong>
</div>

<div>
<span>Internacional</span>
<strong>
<?php
echo $totalInternacional !== null
? 'US$ ' . number_format($totalInternacional, 2, ',', '.')
: 'Indisponível';
?>
</strong>
</div>
</div>

<?php if ($cotacaoDolarInvestimentos !== null) { ?>
<div class="card-rodape">
Ver investimentos · US$ 1 = R$
<?php echo number_format($cotacaoDolarInvestimentos, 4, ',', '.'); ?>
</div>
<?php } else { ?>
<div class="card-rodape">Ver investimentos</div>
<?php } ?>
</div>
</a>

</div>

<section class="graficos-dashboard">

<div class="grafico-card">
<div class="grafico-cabecalho">
<div>
<h3>Receitas x Despesas</h3>
<p>Comparativo dos últimos 6 meses</p>
</div>
</div>

<div class="grafico-container grafico-linha">
<canvas id="graficoReceitasDespesas"></canvas>
</div>
</div>

<div class="grafico-card">
<div class="grafico-cabecalho">
<div>
<h3>Composição da Fatura</h3>
<p><?php echo $nomeMesAtual . '/' . $anoAtual; ?></p>
</div>
</div>

<div class="grafico-container grafico-rosca">
<canvas id="graficoCartao"></canvas>
</div>
</div>

</section>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const moeda = new Intl.NumberFormat('pt-BR', {
style: 'currency',
currency: 'BRL'
});

const contextoReceitasDespesas = document.getElementById('graficoReceitasDespesas');

new Chart(contextoReceitasDespesas, {
type: 'line',
data: {
labels: <?php echo json_encode($labelsGrafico, JSON_UNESCAPED_UNICODE); ?>,
datasets: [
{
label: 'Receitas',
data: <?php echo json_encode($valoresReceitasGrafico); ?>,
borderColor: '#27AE60',
backgroundColor: 'rgba(39, 174, 96, 0.12)',
borderWidth: 3,
tension: 0.3,
fill: false,
pointRadius: 4,
pointHoverRadius: 6
},
{
label: 'Despesas',
data: <?php echo json_encode($valoresDespesasGrafico); ?>,
borderColor: '#C0392B',
backgroundColor: 'rgba(192, 57, 43, 0.12)',
borderWidth: 3,
tension: 0.3,
fill: false,
pointRadius: 4,
pointHoverRadius: 6
}
]
},
options: {
responsive: true,
maintainAspectRatio: false,
interaction: {
mode: 'index',
intersect: false
},
plugins: {
legend: {
position: 'bottom'
},
tooltip: {
callbacks: {
label: function(context) {
return context.dataset.label + ': ' + moeda.format(context.raw);
}
}
}
},
scales: {
y: {
beginAtZero: true,
ticks: {
callback: function(value) {
return moeda.format(value);
}
}
}
}
}
});

const contextoCartao = document.getElementById('graficoCartao');

new Chart(contextoCartao, {
type: 'doughnut',
data: {
labels: [
'Particular',
'Conjunta',
'Reembolsável'
],
datasets: [{
data: <?php echo json_encode($dadosCartaoGrafico); ?>,
backgroundColor: [
'#3498DB',
'#F39C12',
'#9B59B6'
],
borderColor: '#FFFFFF',
borderWidth: 3,
hoverOffset: 6
}]
},
options: {
responsive: true,
maintainAspectRatio: false,
cutout: '65%',
plugins: {
legend: {
position: 'bottom'
},
tooltip: {
callbacks: {
label: function(context) {
return context.label + ': ' + moeda.format(context.raw);
}
}
}
}
}
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
