<?php
include __DIR__ . '/../config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../includes/mercado_api.php';

function acoesEscape($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


function carteiraPercentual($resultado, $custo) {
    if ($resultado === null || (float)$custo <= 0) return '—';
    $valor = (float)$resultado / (float)$custo * 100;
    return ($valor > 0 ? '+' : '') . number_format($valor, 2, ',', '.') . '%';
}

function carteiraLogos($ticker, $atual, array $historico) {
    $normalizar = function ($v) {
        $v = strtoupper(trim((string) $v));
        return preg_replace('/\.SA$/', '', $v);
    };
    $ticker = $normalizar($ticker);
    $urls = [];
    // Uma imagem local pode complementar ativos sem cobertura da API.
    if (preg_match('/^[A-Z0-9.-]{1,20}$/D', $ticker)) {
        foreach (['png', 'webp', 'jpg', 'svg'] as $ext) {
            $relativo = '../assets/img/acoes/nacionais/' . $ticker . '.' . $ext;
            if (is_file(__DIR__ . '/' . $relativo)) {
                $urls[] = $relativo;
                break;
            }
        }
    }
    $adicionar = function ($url) use (&$urls) {
        if (is_string($url) && strlen($url) <= 500
            && filter_var($url, FILTER_VALIDATE_URL)
            && strtolower(parse_url($url, PHP_URL_SCHEME) ?? '') === 'https'
            && !in_array($url, $urls, true)) {
            $urls[] = $url;
        }
    };
    $adicionar($atual);
    foreach (array_reverse($historico) as $op) {
        if ($normalizar($op['ticker'] ?? '') === $ticker) {
            $adicionar($op['logo'] ?? null);
        }
        if (count($urls) >= 5) break;
    }
    return array_slice($urls, 0, 5);
}

function acoesTicker($ticker) {
    return preg_replace('/\.SA$/', '', strtoupper(trim($ticker)));
}

// Reconstitui o custo em ordem cronológica; no mesmo dia, prevalece o id.
// tipo determina a direção, aceitando também vendas antigas com sinal negativo.
function acoesConsolidar(array $operacoes) {
    usort($operacoes, function ($a, $b) {
        return strcmp($a['data'], $b['data']) ?: ($a['id'] <=> $b['id']);
    });
    $posicoes = [];
    foreach ($operacoes as $op) {
        $ticker = acoesTicker($op['ticker']);
        if (!isset($posicoes[$ticker])) {
            $posicoes[$ticker] = [
                'ticker' => $ticker, 'quantidade_total' => 0,
                'total_investido' => 0.0, 'valor_medio_ponderado' => 0.0,
                'logo' => null
            ];
        }
        $p = &$posicoes[$ticker];
        $q = abs((int) $op['quantidade']);
        $preco = (float) $op['valor_unitario'];
        if ($q === 0 || $preco <= 0 || !is_finite($preco)
            || !in_array($op['tipo'], ['compra', 'venda'], true)
            || ($op['tipo'] === 'compra' && $op['quantidade'] < 0)) {
            throw new DomainException("Operação inválida no histórico de " . $ticker . ".");
        }
        if ($op['tipo'] === 'compra') {
            $p['total_investido'] += $q * $preco;
            $p['quantidade_total'] += $q;
            $p['valor_medio_ponderado'] = $p['total_investido'] / $p['quantidade_total'];
        } else {
            if ($q > $p['quantidade_total']) {
                throw new DomainException("Venda de " . $q . " ações de " . $ticker . " em " . date('d/m/Y', strtotime($op['data']))
                    . " excede a posição disponível (" . $p['quantidade_total'] . " ações).");
            }
            $p['quantidade_total'] -= $q;
            $p['total_investido'] = $p['quantidade_total'] * $p['valor_medio_ponderado'];
            if ($p['quantidade_total'] === 0) {
                $p['total_investido'] = 0.0;
                $p['valor_medio_ponderado'] = 0.0;
            }
        }
        if (!empty($op['logo']) && filter_var($op['logo'], FILTER_VALIDATE_URL)
            && strtolower(parse_url($op['logo'], PHP_URL_SCHEME) ?? '') === 'https') {
            $p['logo'] = $op['logo'];
        }
        unset($p);
    }
    ksort($posicoes);
    return array_filter($posicoes, function ($p) { return $p['quantidade_total'] > 0; });
}

function getDadosAcao($ticker) {
    $ticker = strtoupper(trim($ticker));
    $q = mercadoApi()->stocks([$ticker], 'BRL')[$ticker];
    return [$q['price'] !== null ? (float)$q['price'] : null, $q['logo']];
}

$_SESSION['acoes_csrf'] = $_SESSION['acoes_csrf'] ?? bin2hex(random_bytes(32));
$mensagem = $_SESSION['acoes_mensagem'] ?? '';
unset($_SESSION['acoes_mensagem']);
$erro = '';
$erro_carteira = '';
$busca = isset($_GET['busca']) && is_string($_GET['busca']) ? trim($_GET['busca']) : '';
$ordens = [];
$acoes_form = ['ticker' => '', 'quantidade' => '', 'valor_unitario' => '', 'data' => '', 'acao' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($acoes_form as $campo => $valor) {
        if (isset($_POST[$campo]) && is_string($_POST[$campo])) {
            $acoes_form[$campo] = $_POST[$campo];
        }
    }
}
$colunas_acoes = [];
foreach ($conn->query('SHOW COLUMNS FROM acoes_nacionais')->fetch_all(MYSQLI_ASSOC) as $coluna) {
    $colunas_acoes[$coluna['Field']] = $coluna;
}
$acoes_tem_tipo = isset($colunas_acoes['tipo']);
$acoes_tipo_sql = $acoes_tem_tipo ? 'tipo' : "CASE WHEN quantidade < 0 THEN 'venda' ELSE 'compra' END AS tipo";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bloqueado = false;
    try {
        if (!is_string($_POST['csrf'] ?? null)
            || !hash_equals($_SESSION['acoes_csrf'], $_POST['csrf'])) {
            throw new DomainException('Formulário expirado. Atualize a página e tente novamente.');
        }
        foreach (['ticker', 'quantidade', 'valor_unitario', 'data', 'acao'] as $campo) {
            if (!isset($_POST[$campo]) || !is_string($_POST[$campo])) {
                throw new DomainException('Preencha todos os campos da operação.');
            }
        }
        $ticker = acoesTicker($_POST['ticker']);
        $qTexto = trim($_POST['quantidade']);
        $vTexto = trim($_POST['valor_unitario']);
        $data = $_POST['data'];
        $acao = $_POST['acao'];
        if (!preg_match('/^[A-Z0-9]{1,10}$/D', $ticker)
            || !preg_match('/^[1-9][0-9]{0,9}$/D', $qTexto)
            || (float) $qTexto > 2147483647
            || !preg_match('/^[0-9]{1,8}(\.[0-9]{1,2})?$/D', $vTexto)
            || (float) $vTexto <= 0
            || !in_array($acao, ['compra', 'venda'], true)) {
            throw new DomainException('Informe ticker, quantidade inteira positiva, valor positivo com até duas casas decimais e Compra ou Venda.');
        }
        $dt = DateTime::createFromFormat('!Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data || $data < '1000-01-01' || $data > date('Y-m-d')) {
            throw new DomainException('Informe uma data válida, até hoje.');
        }
        $quantidade = (int) $qTexto;
        $valor_unitario = (float) $vTexto;
        list($valor_mercado, $logo) = getDadosAcao($ticker);

        // Serializa os registros feitos por esta página, inclusive quando ainda
        // não há nenhuma operação do ticker. Não exige mudar tabela ou engine.
        $lock = $conn->query("SELECT GET_LOCK('mycashflow_acoes_nacionais_registro', 10) AS adquirido")->fetch_assoc();
        $bloqueado = (int) $lock['adquirido'] === 1;
        if (!$bloqueado) {
            throw new DomainException('Há outro registro em andamento. Tente novamente.');
        }
        $historico = $conn->query("SELECT id, ticker, quantidade, valor_unitario, data, $acoes_tipo_sql, logo FROM acoes_nacionais ORDER BY data, id")->fetch_all(MYSQLI_ASSOC);
        $historico = array_values(array_filter($historico, function ($op) use ($ticker) {
            return acoesTicker($op['ticker']) === $ticker;
        }));
        $historico[] = [
            'id' => PHP_INT_MAX, 'ticker' => $ticker, 'quantidade' => $quantidade,
            'valor_unitario' => $valor_unitario, 'data' => $data, 'tipo' => $acao, 'logo' => $logo
        ];
        // Valida toda a sequência, inclusive vendas posteriores à data inserida.
        acoesConsolidar($historico);
        if ($acao === 'venda') {
            $quantidade = -$quantidade;
        }
        // Na tabela antiga, zero representa ausência de cotação histórica.
        // Esse campo nunca é utilizado como cotação atual na carteira.
        if ($valor_mercado === null && $colunas_acoes['valor_mercado']['Null'] === 'NO') {
            $valor_mercado = 0.0;
        }
        if ($logo !== null && strlen($logo) > 255) {
            $logo = null;
        }
        if ($acoes_tem_tipo) {
            $stmt = $conn->prepare("INSERT INTO acoes_nacionais
                (ticker, quantidade, valor_unitario, data, valor_mercado, logo, tipo)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sidsdss', $ticker, $quantidade, $valor_unitario, $data, $valor_mercado, $logo, $acao);
        } else {
            $stmt = $conn->prepare("INSERT INTO acoes_nacionais
                (ticker, quantidade, valor_unitario, data, valor_mercado, logo)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sidsds', $ticker, $quantidade, $valor_unitario, $data, $valor_mercado, $logo);
        }
        $stmt->execute();
        $stmt->close();
        $_SESSION['acoes_mensagem'] = 'Operação registrada com sucesso.';
        $_SESSION['acoes_csrf'] = bin2hex(random_bytes(32));
    } catch (DomainException $e) {
        $erro = $e->getMessage();
    } catch (Throwable $e) {
        error_log('MyCashFlow: falha ao registrar ação nacional: ' . $e->getMessage());
        $erro = 'Não foi possível registrar a operação. Verifique a conexão com o banco.';
    } finally {
        if ($bloqueado) {
            $conn->query("SELECT RELEASE_LOCK('mycashflow_acoes_nacionais_registro')");
        }
    }
    if ($erro === '') {
        header('Location: acoes_nacionais.php' . ($busca !== '' ? '?' . http_build_query(['busca' => $busca]) : ''));
        exit;
    }
}

$posicoes = [];
$cotacoes_completas = true;
try {
    $operacoes = $conn->query("SELECT id, ticker, quantidade, valor_unitario, data, $acoes_tipo_sql, logo FROM acoes_nacionais ORDER BY data, id")->fetch_all(MYSQLI_ASSOC);
    $posicoes = acoesConsolidar($operacoes);
    $av_cotacoes = mercadoApi()->stocks(array_column($posicoes, 'ticker'), 'BRL');
    foreach ($posicoes as &$p) {
        $p['api'] = $av_cotacoes[$p['ticker']];
        list($cotacao, $logo_atual) = getDadosAcao($p['ticker']);
        $p['valor_mercado_atual'] = $cotacao;
        $p['logo'] = $logo_atual ?? $p['logo'];
        $p['resultado'] = $cotacao !== null
            ? $cotacao * $p['quantidade_total'] - $p['total_investido'] : null;
        if ($cotacao === null) {
            $cotacoes_completas = false;
        }
    }
    unset($p);
} catch (Throwable $e) {
    $posicoes = [];
    $cotacoes_completas = false;
    $erro_carteira = $e instanceof DomainException
        ? $e->getMessage() . ' Confira o histórico antes de consolidar a carteira.'
        : 'Não foi possível carregar a carteira.';
}
if ($busca !== '') {
    try {
        $termo = $busca;
        if (preg_match('~^\d{2}/\d{2}/\d{4}$~D', $termo)) {
            $partes = explode('/', $termo);
            $termo = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        $termo = '%' . $termo . '%';
        $stmt = $conn->prepare("SELECT ticker, quantidade, valor_unitario, data, valor_mercado, $acoes_tipo_sql
            FROM acoes_nacionais WHERE ticker LIKE ? OR data LIKE ? ORDER BY data DESC, id DESC");
        $stmt->bind_param('ss', $termo, $termo);
        $stmt->execute();
        $ordens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Throwable $e) {
        $erro = 'Não foi possível consultar as ordens.';
    }
}
?>


<?php
// Apenas totais de apresentação; as regras de cadastro e consolidação são preservadas.
$av_custo = 0;
foreach ($posicoes as $av_posicao) {
    $av_custo = $av_custo + $av_posicao['total_investido'];
}
$av_ok = $erro_carteira === '';
$av_mercado = 0;
foreach ($posicoes as $av_posicao) {
    if ($av_posicao['valor_mercado_atual'] !== null) {
        $av_mercado = $av_mercado + $av_posicao['valor_mercado_atual'] * $av_posicao['quantidade_total'];
    }
}
$av_valores_ok = $av_ok && $cotacoes_completas;
$av_ganho = $av_valores_ok ? $av_mercado - $av_custo : null;
$av_modal = $_SERVER['REQUEST_METHOD'] === 'POST' && $erro !== ''
    ? 'modal-operacao' : ($busca !== '' ? 'modal-historico' : '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ações Nacionais</title>
    <link rel="stylesheet" href="../assets/css/style-acoes.css?v=20260913">
</head>
<body class="av-body" data-modal-inicial="<?= acoesEscape($av_modal) ?>">
    <?php include('../includes/header.php'); ?>
    <?php include('../includes/menu.php'); ?>
    <main class="av-page">
        <a class="av-back" href="acoes.php">← Resumo de Ações</a>
        <div class="av-top">
            <div><h1>Ações Nacionais</h1><p>Consulte suas posições na B3 e registre compras e vendas.</p></div>
            <div class="av-actions">
                <button type="button" class="av-primary" data-open="modal-operacao">Nova operação</button>
                <button type="button" data-open="modal-historico">Pesquisar ordens</button>
            </div>
        </div>
        <?php if ($mensagem !== '') { ?><p class="av-success" role="status"><?= acoesEscape($mensagem) ?></p><?php } ?>
        <?php if ($erro !== '') { ?><p class="av-alert" role="alert"><?= acoesEscape($erro) ?></p><?php } ?>
        <?php if ($erro_carteira !== '') { ?><p class="av-alert" role="alert"><?= acoesEscape($erro_carteira) ?></p><?php } ?>
        
        <section class="av-card" aria-labelledby="carteira-titulo">
            <div class="av-summary">
                <div><h2 id="carteira-titulo">Posição consolidada</h2><small><?= $av_ok ? count($posicoes) . ' ativo(s) em carteira' : 'Consulta indisponível' ?> · BRL</small></div>
                <div class="av-metrics"><div class="av-summary-value"><span>Custo das posições atuais</span><strong><?= $av_ok ? 'R$ ' . number_format($av_custo, 2, ',', '.') : 'Indisponível' ?></strong></div>
<div class="av-summary-value"><span>Valor atual da carteira</span><strong><?= $av_valores_ok ? 'R$ ' . number_format($av_mercado, 2, ',', '.') : 'Indisponível' ?></strong><small>Pelas cotações consultadas</small></div>
<div class="av-summary-value"><span>Resultado não realizado</span><strong class="<?= $av_ganho !== null && (float)$av_ganho < 0 ? 'av-negative' : 'av-positive' ?>"><?= $av_ganho !== null ? ((float)$av_ganho < 0 ? '- ' : '+ ') . 'R$ ' . number_format(abs((float)$av_ganho), 2, ',', '.') : 'Indisponível' ?></strong><small><?= carteiraPercentual($av_ganho, $av_custo) ?></small></div>
</div>
            </div>
            <?php if (!$cotacoes_completas && $av_ok) { ?><p class="av-notice">Uma ou mais cotações estão indisponíveis. O resultado total não pôde ser calculado.</p><?php } ?>
            <div class="av-scroll" tabindex="0" role="region" aria-label="Tabela de posições">
                <table class="tabela-acoes">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Ticker</th>
                            <th>Quantidade</th>
                            <th>Preço médio</th>
                            <th>Custo (R$)</th>
                            <th>Cotação</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
<?php if (!$posicoes && $erro_carteira === '') { ?><tr><td colspan="7" class="av-empty">Nenhuma posição em carteira. Use “Nova operação” para registrar uma compra.</td></tr><?php } ?>
                        <?php 
                        $total_resultado = 0;
                        foreach ($posicoes as $row) { 
                            if ($row['resultado'] !== null) { $total_resultado += $row['resultado']; }
                        ?>
                        <tr>
                            <td>
                                    <?php $av_logos = carteiraLogos($row['ticker'], $row['logo'] ?? null, $operacoes ?? []); ?>
                                    <span class="av-logo" data-logos="<?= acoesEscape(json_encode($av_logos, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)) ?>" title="<?= acoesEscape($row['ticker']) ?>">
                                        <span class="av-logo-fallback" aria-hidden="true"><?= acoesEscape(substr($row['ticker'], 0, 4)) ?></span>
                                        <img class="av-logo-img" alt="Logo de <?= acoesEscape($row['ticker']) ?>" width="30" height="30" hidden referrerpolicy="no-referrer">
                                    </span>
                                </td>
                            <td><?= acoesEscape($row['ticker']) ?></td>
                            <td><?= $row['quantidade_total'] ?></td>
                            <td>R$ <?= number_format($row['valor_medio_ponderado'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($row['total_investido'], 2, ',', '.') ?></td>
                            <td>
                                <?php if ($row['valor_mercado_atual'] !== null) { ?>
                                    R$ <?= number_format($row['valor_mercado_atual'], 2, ',', '.') ?>
                                <?php } else { ?>
                                    -
                                <?php } ?>
<details class="av-quote-info"><summary><?= $row['api']['stale'] ? 'Preço salvo · atualização pendente' : 'Fonte e horário' ?></summary><?= acoesEscape(mercadoLegenda($row['api'])) ?></details>
                            </td>
                            <td>
                                <?php if ($row['resultado'] === null) { ?>
                                    Indisponível
                                <?php } elseif ($row['resultado'] >= 0) { ?>
                                    <span class="av-positive">+ R$ <?= number_format($row['resultado'], 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span class="av-negative">- R$ <?= number_format(abs($row['resultado']), 2, ',', '.') ?></span>
                                <?php } ?><small class="av-return-percent"><?= carteiraPercentual($row['resultado'], $row['total_investido']) ?></small>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td colspan="6" style="text-align:right;"><strong>Total:</strong></td>
                            <td>
                                <?php if (!$cotacoes_completas) { ?>
                                    Indisponível
                                <?php } elseif ($total_resultado >= 0) { ?>
                                    <span class="av-positive">+ R$ <?= number_format($total_resultado, 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span class="av-negative">- R$ <?= number_format(abs($total_resultado), 2, ',', '.') ?></span>
                                <?php } ?><small class="av-return-percent"><?= carteiraPercentual($cotacoes_completas ? $total_resultado : null, $av_custo) ?></small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php if (array_filter($posicoes, function($p) { return !empty($p['api']['stale']); })) { ?><p class="av-notice">O resultado utiliza uma ou mais cotações salvas. Atualização pendente; consulte o horário de cada preço.</p><?php } ?>
<p class="av-note">O resultado corresponde às posições atuais. O lucro ou prejuízo das vendas não está incluído.</p>
        </section>
    </main>
    <dialog id="modal-operacao" class="av-dialog" aria-labelledby="operacao-titulo">
        <div class="av-dialog-top"><div><h2 id="operacao-titulo">Registrar operação</h2><p>Ações Nacionais · BRL</p></div><button type="button" data-close aria-label="Fechar operação">×</button></div>
        <div class="av-dialog-content">
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $erro !== '') { ?><p class="av-alert" role="alert"><?= acoesEscape($erro) ?></p><?php } ?>
            <form class="form-acoes" method="POST">
                    <input type="hidden" name="csrf" value="<?= acoesEscape($_SESSION['acoes_csrf']) ?>">
                    <!-- Campos comuns -->
                    <label for="op-ticker">Ticker</label>
                    <input type="text" name="ticker" id="op-ticker" value="<?= acoesEscape($acoes_form['ticker']) ?>" placeholder="Ticker (ex: PETR4)" required>
                    <label for="op-quantidade">Quantidade de ações</label>
                    <input type="number" name="quantidade" id="op-quantidade" value="<?= acoesEscape($acoes_form['quantidade']) ?>" min="1" step="1" max="2147483647" placeholder="Quantidade" required>
                    <label for="op-preco">Preço unitário de execução (R$)</label>
                    <input type="number" step="0.01" min="0.01" name="valor_unitario" id="op-preco" value="<?= acoesEscape($acoes_form['valor_unitario']) ?>" placeholder="Valor unitário" required>
                    <label for="op-data">Data da operação</label>
                    <input type="date" name="data" id="op-data" value="<?= acoesEscape($acoes_form['data']) ?>" required>

                    <!-- Botões Compra/Venda abaixo do campo Data -->
                    <div class="acao-buttons">
                        <button type="button" aria-pressed="<?= $acoes_form['acao'] === 'compra' ? 'true' : 'false' ?>" class="btn-acao compra<?= $acoes_form['acao'] === 'compra' ? ' active' : '' ?>" onclick="selecionarAcao('compra')">Compra</button>
                        <button type="button" aria-pressed="<?= $acoes_form['acao'] === 'venda' ? 'true' : 'false' ?>" class="btn-acao venda<?= $acoes_form['acao'] === 'venda' ? ' active' : '' ?>" onclick="selecionarAcao('venda')">Venda</button>
                    </div>

                    <!-- Campo oculto para enviar ao PHP -->
                    <input type="hidden" name="acao" id="acao" value="<?= acoesEscape($acoes_form['acao']) ?>">

                    <div class="av-form-actions"><button type="button" data-close>Cancelar</button><button class="av-primary" type="submit">Registrar</button>
                </div>
                    </form>
        </div>
    </dialog>
    <dialog id="modal-historico" class="av-dialog av-dialog-wide" aria-labelledby="historico-titulo">
        <div class="av-dialog-top"><div><h2 id="historico-titulo">Pesquisar ordens</h2><p>Consulte compras e vendas por ticker ou data.</p></div><button type="button" data-close aria-label="Fechar pesquisa">×</button></div>
        <div class="av-dialog-content">
            <form method="GET" class="av-search">
                <label for="busca">Ticker ou data (DD/MM/AAAA ou AAAA-MM-DD)</label>
                <div><input type="text" name="busca" id="busca" value="<?= acoesEscape($busca) ?>" placeholder="PETR4 ou 09/09/2026"><button type="submit" class="av-primary">Pesquisar</button><a class="av-button" href="acoes_nacionais.php">Limpar</a></div>
            </form>
            <div class="av-scroll" tabindex="0" role="region" aria-label="Resultados da pesquisa">
                <?php if ($busca !== '') { ?>
                    <?php if (!$ordens) { ?>
                        <p>Nenhuma ordem encontrada para <strong><?= acoesEscape($busca) ?></strong>.</p>
                    <?php } else { ?>
                        <table class="tabela-acoes">
                            <thead><tr><th>Ticker</th><th>Quantidade</th><th>Valor Unitário</th><th>Data</th><th>Valor Mercado</th><th>Tipo</th></tr></thead>
                            <tbody>
                                <?php foreach ($ordens as $ordem) { ?>
                                <tr>
                                    <td><?= acoesEscape($ordem['ticker']) ?></td>
                                    <td><?= (int) $ordem['quantidade'] ?></td>
                                    <td>R$ <?= number_format($ordem['valor_unitario'], 2, ',', '.') ?></td>
                                    <td><?= date('d/m/Y', strtotime($ordem['data'])) ?></td>
                                    <td><?= $ordem['valor_mercado'] !== null && $ordem['valor_mercado'] > 0 ? 'R$ ' . number_format($ordem['valor_mercado'], 2, ',', '.') : '-' ?></td>
                                    <td><?= $ordem['tipo'] === 'compra' ? '<span class="av-positive">Compra</span>' : '<span class="av-negative">Venda</span>' ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>
                <?php } ?>
            </div>
            <?php if ($busca === '') { ?><p class="av-note">Informe um ticker ou uma data para consultar o histórico.</p><?php } ?>
        </div>
    </dialog>
    <script>

    function selecionarAcao(tipo) {
        // Atualiza o hidden input
        document.getElementById('acao').value = tipo;

        // Remove active de todos os botões
        document.querySelectorAll('.btn-acao').forEach(btn => btn.classList.remove('active'));

        // Adiciona active no botão clicado
        document.querySelector('.btn-acao.' + tipo).classList.add('active');
    }
    

    // Modais nativos: Escape fecha e o foco retorna ao botão de origem.
    let ultimoAcionador = null;
    function abrirModal(id, acionador) {
        const modal = document.getElementById(id);
        if (!modal || modal.open) return;
        ultimoAcionador = acionador || null;
        modal.showModal();
    }
    document.querySelectorAll('[data-open]').forEach(botao => {
        botao.addEventListener('click', () => abrirModal(botao.dataset.open, botao));
    });
    document.querySelectorAll('[data-close]').forEach(botao => {
        botao.addEventListener('click', () => botao.closest('dialog').close());
    });
    document.querySelectorAll('dialog').forEach(modal => {
        modal.addEventListener('close', () => {
            if (ultimoAcionador) ultimoAcionador.focus();
        });
    });
    document.querySelectorAll('.btn-acao').forEach(botao => {
        botao.addEventListener('click', () => {
            document.querySelectorAll('.btn-acao').forEach(b => b.setAttribute('aria-pressed', b.classList.contains('active') ? 'true' : 'false'));
        });
    });
    document.querySelectorAll('.av-logo').forEach(container => {
        const img = container.querySelector('img');
        const fallback = container.querySelector('.av-logo-fallback');
        let urls;
        try { urls = JSON.parse(container.dataset.logos); } catch (_) { urls = []; }
        if (!Array.isArray(urls)) urls = [];
        let proxima = 0;
        img.addEventListener('load', () => {
            img.hidden = false;
            fallback.hidden = true;
        });
        img.addEventListener('error', tentarProxima);
        function tentarProxima() {
            img.hidden = true;
            fallback.hidden = false;
            if (proxima < urls.length) img.src = urls[proxima++];
        }
        // Eventos são registrados antes do src, inclusive para imagens em cache.
        tentarProxima();
    });
    const modalInicial = document.body.dataset.modalInicial;
    if (modalInicial) abrirModal(modalInicial, document.querySelector('[data-open="' + modalInicial + '"]'));

    </script>
    <?php include('../includes/footer.php'); ?>
</body>
</html>
