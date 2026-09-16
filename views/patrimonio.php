<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$usuario_id = filter_var($_SESSION['usuario_id'] ?? null, FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]);
if ($usuario_id === false) {
    header('Location: login/login.php');
    exit;
}
require_once __DIR__ . '/../config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../includes/mercado_api.php';
require_once __DIR__ . '/../includes/patrimonio_calculo.php';

function patrimonioEscape($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function patrimonioMoeda($valor, $moeda = 'R$') {
    return $valor === null ? 'Indisponível' : $moeda . ' ' . number_format((float)$valor, 2, ',', '.');
}

$contas = [];
$disponibilidades = null;
$investimentos = null;
$total = null;
$erros = [];
$carteiras = [
    ['titulo' => 'Investimentos Nacionais', 'url' => 'investimentos_nacionais.php', 'nacional' => true],
    ['titulo' => 'Investimentos Internacionais', 'url' => 'investimentos_internacionais.php', 'nacional' => false]
];
foreach ($carteiras as &$carteira) {
    $carteira += ['posicoes' => [], 'total' => null, 'cambio' => null, 'erro' => ''];
}
unset($carteira);
if (!extension_loaded('bcmath')) {
    $erros[] = 'Ative a extensão BCMath do PHP, já utilizada pela carteira internacional, para calcular o patrimônio.';
} else {
    try {
        $contas = saldosListarContas($conn, $usuario_id);
        $disponibilidades = patrimonioDisponibilidades($contas);
    } catch (Throwable $e) {
        $erros[] = 'Não foi possível carregar os saldos das contas financeiras.';
    }
    foreach ($carteiras as &$carteira) {
        try {
            $carteira = array_replace($carteira,
                patrimonioCarteira($conn, $usuario_id, $carteira['nacional'], mercadoApi()));
        } catch (Throwable $e) {
            $carteira['erro'] = $e instanceof DomainException
                ? $e->getMessage() . ' Confira o histórico da carteira.'
                : 'Não foi possível carregar esta carteira.';
            $erros[] = $carteira['titulo'] . ': ' . $carteira['erro'];
        }
    }
    unset($carteira);
    $investimentos = patrimonioSomar($carteiras[0]['total'], $carteiras[1]['total']);
    $total = patrimonioSomar($disponibilidades, $investimentos);
}
$tipos = ['corrente' => 'Corrente', 'poupanca' => 'Poupança', 'carteira' => 'Carteira',
    'corretora' => 'Corretora', 'outros' => 'Outros', 'acao' => 'Ação', 'fii' => 'FII',
    'etf' => 'ETF', 'bdr' => 'BDR', 'stock' => 'Stock', 'reit' => 'REIT', 'adr' => 'ADR'];
$cssPagina = '/MyCashFlow/assets/css/style-patrimonio.css';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu.php';
?>
<main class="patrimonio-layout">
    <h1>Patrimônio</h1>
    <p class="patrimonio-intro">Seu patrimônio financeiro: disponibilidades e posições atuais de investimentos a valor de mercado, em reais.</p>
    <div class="patrimonio-resumo">
        <section class="patrimonio-destaque"><h2>Patrimônio Financeiro Total</h2><strong><?= patrimonioMoeda($total) ?></strong></section>
        <section><h2>Disponibilidades</h2><strong><?= patrimonioMoeda($disponibilidades) ?></strong></section>
        <section><h2>Investimentos</h2><strong><?= patrimonioMoeda($investimentos) ?></strong></section>
    </div>
    <?php if ($total === null): ?>
        <div class="patrimonio-aviso" role="status">O total está indisponível porque há dados, cotações ou câmbio pendentes. Os componentes disponíveis continuam abaixo; valores ausentes não são considerados zero.</div>
    <?php endif; ?>
    <?php foreach ($erros as $erro): ?>
        <p class="patrimonio-aviso"><?= patrimonioEscape($erro) ?></p>
    <?php endforeach; ?>

    <section class="patrimonio-secao" aria-labelledby="contas-titulo">
        <div class="patrimonio-secao-topo"><h2 id="contas-titulo">Contas financeiras</h2><a href="saldos.php">Ver Contas e Saldos</a></div>
        <p>O total considera contas ativas, seguindo Contas e Saldos. Contas inativas aparecem para conferência e ficam fora da soma.</p>
        <div class="patrimonio-tabela" tabindex="0" role="region" aria-label="Saldos por conta">
            <table><caption>Disponibilidades: <?= patrimonioMoeda($disponibilidades) ?></caption>
                <thead><tr><th scope="col">Conta</th><th scope="col">Instituição</th><th scope="col">Tipo</th><th scope="col">Situação</th><th scope="col" class="numero">Saldo atual</th></tr></thead>
                <tbody>
                <?php foreach ($contas as $conta): ?>
                    <tr><th scope="row"><?= patrimonioEscape($conta['nome']) ?></th><td><?= patrimonioEscape($conta['instituicao']) ?></td><td><?= patrimonioEscape($tipos[$conta['tipo']] ?? $conta['tipo']) ?></td><td><?= (int)$conta['ativa'] === 1 ? 'Ativa' : 'Inativa · fora do total' ?></td><td class="numero"><?= patrimonioMoeda($conta['saldo_atual']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$contas): ?><tr><td colspan="5"><?= $disponibilidades === null ? 'Saldos indisponíveis.' : 'Nenhuma conta financeira cadastrada.' ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php foreach ($carteiras as $indice => $carteira): ?>
    <section class="patrimonio-secao" aria-labelledby="carteira-<?= $indice ?>">
        <div class="patrimonio-secao-topo"><h2 id="carteira-<?= $indice ?>"><?= $carteira['titulo'] ?></h2><a href="<?= $carteira['url'] ?>">Ver carteira</a></div>
        <?php if (!$carteira['nacional']): ?>
            <p>Posições e cotações em USD, convertidas uma única vez para reais.</p>
            <?php if ($carteira['cambio']): ?>
                <p><strong>USD/BRL: <?= $carteira['cambio']['price'] === null ? 'Indisponível' : 'R$ ' . number_format((float)$carteira['cambio']['price'], 4, ',', '.') ?></strong><small><?= patrimonioEscape(mercadoLegenda($carteira['cambio'])) ?></small></p>
            <?php elseif (!$carteira['posicoes'] && $carteira['total'] !== null): ?>
                <p>Sem posição internacional; não é necessário câmbio.</p>
            <?php endif; ?>
        <?php endif; ?>
        <div class="patrimonio-tabela" tabindex="0" role="region" aria-label="<?= $carteira['titulo'] ?>">
            <table><caption>Total em reais: <?= patrimonioMoeda($carteira['total']) ?></caption>
                <thead><tr><th scope="col">Ativo</th><th scope="col">Tipo</th><th scope="col" class="numero">Quantidade</th><th scope="col" class="numero">Cotação (<?= $carteira['nacional'] ? 'BRL' : 'USD' ?>)</th><?php if (!$carteira['nacional']): ?><th scope="col" class="numero">Valor de mercado (USD)</th><?php endif; ?><th scope="col" class="numero">Valor de mercado (BRL)</th></tr></thead>
                <tbody>
                <?php foreach ($carteira['posicoes'] as $p): ?>
                    <tr><th scope="row"><?= patrimonioEscape($p['ticker']) ?><small><?= patrimonioEscape(mercadoLegenda($p['api'])) ?></small></th><td><?= patrimonioEscape($tipos[$p['tipo_ativo']] ?? $p['tipo_ativo']) ?></td><td class="numero"><?= patrimonioEscape(internacionalQuantidade($p['quantidade_total'])) ?></td><td class="numero"><?= patrimonioMoeda($p['api']['price'] ?? null, $carteira['nacional'] ? 'R$' : 'US$') ?></td><?php if (!$carteira['nacional']): ?><td class="numero"><?= patrimonioMoeda($p['valor_origem'], 'US$') ?></td><?php endif; ?><td class="numero"><?= patrimonioMoeda($p['valor_brl']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$carteira['posicoes']): ?><tr><td colspan="<?= $carteira['nacional'] ? 5 : 6 ?>"><?= $carteira['total'] === null ? 'Carteira indisponível.' : 'Nenhuma posição em aberto.' ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endforeach; ?>
    <p class="patrimonio-nota">Patrimônio Financeiro = Disponibilidades + Investimentos Nacionais + Investimentos Internacionais em reais. Transferências entre contas consideradas apenas redistribuem o saldo. O dinheiro disponível em corretoras é separado das posições investidas. Valores exibidos são arredondados para centavos.</p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
