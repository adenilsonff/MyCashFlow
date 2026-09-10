<?php
include __DIR__ . '/../config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function internacionalEscape($v) {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function internacionalQuantidade($v) {
    $v = (string) $v;
    if (strpos($v, '.') !== false) {
        $v = rtrim(rtrim($v, '0'), '.');
    }
    return str_replace('.', ',', $v);
}

function internacionalDecimal($v, $inteiros, $casas) {
    if (!is_string($v) || !preg_match('/^[0-9]{1,' . $inteiros . '}(?:\.[0-9]{1,' . $casas . '})?$/D', trim($v))) {
        throw new DomainException('Informe um número positivo, sem separador de milhares e dentro da precisão permitida.');
    }
    $v = trim($v);
    if (bccomp($v, '0', $casas) <= 0) {
        throw new DomainException('Quantidade, preço e valor da operação devem ser maiores que zero.');
    }
    return bcadd($v, '0', $casas);
}

// O servidor recalcula o campo derivado; não confia no resultado do navegador.
function internacionalCalcular($form) {
    $preco = internacionalDecimal($form['valor_unitario'], 14, 4);
    if ($form['modo'] === 'valor') {
        $limite = internacionalDecimal($form['valor_operacao'], 16, 8);
        // BCMath trunca a quantidade em oito casas: nunca excede o orçamento.
        $quantidade = bcdiv($limite, $preco, 8);
        if (bccomp($quantidade, '0', 8) <= 0) {
            throw new DomainException('O valor informado não compra a fração mínima de 0,00000001 ação.');
        }
    } elseif ($form['modo'] === 'quantidade') {
        $quantidade = internacionalDecimal($form['quantidade'], 14, 8);
    } else {
        throw new DomainException('Preencha a quantidade ou o valor da operação.');
    }
    if (bccomp($quantidade, '99999999999999.99999999', 8) > 0) {
        throw new DomainException('Quantidade acima do limite suportado.');
    }
    $totalExato = bcmul($quantidade, $preco, 12);
    if (bccomp($totalExato, '9999999999999999.99999999', 12) > 0) {
        throw new DomainException('Valor da operação acima do limite suportado.');
    }
    // Custo da posição usa quantidade × preço, sem depender deste arredondamento.
    $total = bcadd($totalExato, '0.000000005', 8);
    if (bccomp($total, '0', 8) <= 0) {
        throw new DomainException('Valor da operação inferior à precisão de registro.');
    }
    return [$quantidade, $preco, $total];
}

function internacionalConsolidar(array $operacoes) {
    usort($operacoes, function ($a, $b) {
        return strcmp($a['data'], $b['data']) ?: ($a['id'] <=> $b['id']);
    });
    $posicoes = [];
    foreach ($operacoes as $op) {
        $ticker = strtoupper(trim($op['ticker']));
        if (!isset($posicoes[$ticker])) {
            $posicoes[$ticker] = [
                'ticker' => $ticker, 'quantidade_total' => '0',
                'total_investido_usd' => '0', 'valor_medio_ponderado' => '0', 'logo' => null
            ];
        }
        $p = &$posicoes[$ticker];
        $q = ltrim((string) $op['quantidade'], '-');
        $preco = (string) $op['valor_unitario'];
        if (!in_array($op['tipo'], ['compra', 'venda'], true)
            || bccomp($q, '0', 8) <= 0 || bccomp($preco, '0', 4) <= 0
            || ($op['tipo'] === 'compra' && bccomp($op['quantidade'], '0', 8) < 0)) {
            throw new DomainException('Operação inválida no histórico de ' . $ticker . '.');
        }
        if ($op['tipo'] === 'compra') {
            $p['total_investido_usd'] = bcadd($p['total_investido_usd'], bcmul($q, $preco, 24), 24);
            $p['quantidade_total'] = bcadd($p['quantidade_total'], $q, 8);
            $p['valor_medio_ponderado'] = bcdiv($p['total_investido_usd'], $p['quantidade_total'], 24);
        } else {
            if (bccomp($q, $p['quantidade_total'], 8) > 0) {
                throw new DomainException('Venda de ' . internacionalQuantidade($q) . ' ações de ' . $ticker
                    . ' em ' . date('d/m/Y', strtotime($op['data']))
                    . ' excede a posição disponível (' . internacionalQuantidade($p['quantidade_total']) . ' ações).');
            }
            $p['quantidade_total'] = bcsub($p['quantidade_total'], $q, 8);
            $p['total_investido_usd'] = bcmul($p['quantidade_total'], $p['valor_medio_ponderado'], 24);
            if (bccomp($p['quantidade_total'], '0', 8) === 0) {
                $p['total_investido_usd'] = '0';
                $p['valor_medio_ponderado'] = '0';
            }
        }
        if (!empty($op['logo']) && filter_var($op['logo'], FILTER_VALIDATE_URL)
            && strtolower(parse_url($op['logo'], PHP_URL_SCHEME) ?? '') === 'https') {
            $p['logo'] = $op['logo'];
        }
        unset($p);
    }
    ksort($posicoes);
    return array_filter($posicoes, function ($p) { return bccomp($p['quantidade_total'], '0', 8) > 0; });
}

function internacionalJson($url, array $headers = []) {
    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers)
            ]);
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status !== 200 || $response === false) {
                return [];
            }
        } else {
            $context = stream_context_create(['http' => [
                'timeout' => 8, 'follow_location' => 0,
                'header' => implode("\r\n", array_merge(['Accept: application/json'], $headers))
            ]]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return [];
            }
        }
        $dados = json_decode($response, true);
        return is_array($dados) ? $dados : [];
    } catch (Throwable $e) {
        return [];
    }
}

function internacionalCotacao($ticker) {
    static $cache = [];
    if (isset($cache[$ticker])) {
        return $cache[$ticker];
    }
    // Preserva a integração original. Só aceita a ação solicitada em USD.
    // BRAPI concentra sua cobertura em ativos da B3; ticker sem cobertura
    // fica indisponível, sem substituir uma ação americana pelo seu BDR.
    $token = defined('BRAPI_TOKEN') ? BRAPI_TOKEN : (getenv('BRAPI_TOKEN') ?: 'pV9h8EEsN5xs9ESi2Uk89n');
    $dados = internacionalJson('https://brapi.dev/api/quote/' . rawurlencode($ticker),
        ['Authorization: Bearer ' . $token]);
    $acao = $dados['results'][0] ?? [];
    $preco = $acao['regularMarketPrice'] ?? null;
    if (($acao['symbol'] ?? '') !== $ticker || ($acao['currency'] ?? '') !== 'USD'
        || !is_numeric($preco) || !is_finite((float) $preco) || $preco <= 0) {
        return $cache[$ticker] = [null, null];
    }
    $logo = $acao['logourl'] ?? null;
    if (!is_string($logo) || strlen($logo) > 500 || !filter_var($logo, FILTER_VALIDATE_URL)
        || strtolower(parse_url($logo, PHP_URL_SCHEME) ?? '') !== 'https') {
        $logo = null;
    }
    return $cache[$ticker] = [number_format((float) $preco, 4, '.', ''), $logo];
}

$erro = '';
$erro_carteira = '';
$mensagem = $_SESSION['internacional_mensagem'] ?? '';
unset($_SESSION['internacional_mensagem']);
$_SESSION['internacional_csrf'] = $_SESSION['internacional_csrf'] ?? bin2hex(random_bytes(32));
$busca = isset($_GET['busca']) && is_string($_GET['busca']) ? trim($_GET['busca']) : '';
$form = ['ticker' => '', 'quantidade' => '', 'valor_operacao' => '', 'valor_unitario' => '', 'data' => '', 'acao' => '', 'modo' => 'quantidade'];
foreach ($form as $campo => $valor) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$campo]) && is_string($_POST[$campo])) {
        $form[$campo] = trim($_POST[$campo]);
    }
}
$estrutura_ok = false;
try {
    if (!extension_loaded('bcmath')) {
        throw new DomainException('Ative a extensão BCMath do PHP para registrar frações com precisão.');
    }
    $colunas = [];
    foreach ($conn->query('SHOW COLUMNS FROM acoes_internacionais')->fetch_all(MYSQLI_ASSOC) as $c) {
        $colunas[$c['Field']] = strtolower($c['Type']);
    }
    if (($colunas['quantidade'] ?? '') !== 'decimal(22,8)' || ($colunas['valor_investido'] ?? '') !== 'decimal(24,8)') {
        throw new DomainException('Aplique o SQL de atualização de Ações Internacionais antes de registrar operações com oito casas decimais.');
    }
    $estrutura_ok = true;
} catch (Throwable $e) {
    $erro = $e instanceof DomainException ? $e->getMessage() : 'Não foi possível conferir a estrutura da tabela.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $estrutura_ok) {
    $bloqueado = false;
    try {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['internacional_csrf'], $_POST['csrf'])) {
            throw new DomainException('Formulário expirado. Atualize a página e tente novamente.');
        }
        $ticker = strtoupper($form['ticker']);
        $acao = $form['acao'];
        if (!preg_match('/^[A-Z][A-Z0-9.-]{0,19}$/D', $ticker) || !in_array($acao, ['compra', 'venda'], true)) {
            throw new DomainException('Informe o ticker e selecione Compra ou Venda.');
        }
        $data = $form['data'];
        $dt = DateTime::createFromFormat('!Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data || $data < '1000-01-01' || $data > date('Y-m-d')) {
            throw new DomainException('Informe uma data válida, até hoje.');
        }
        list($quantidade, $valor_unitario, $valor_investido) = internacionalCalcular($form);
        list($valor_mercado, $logo) = internacionalCotacao($ticker);
        $lock = $conn->query("SELECT GET_LOCK('mycashflow_acoes_internacionais_registro', 10) AS adquirido")->fetch_assoc();
        $bloqueado = (int) $lock['adquirido'] === 1;
        if (!$bloqueado) {
            throw new DomainException('Há outro registro em andamento. Tente novamente.');
        }
        $ops = $conn->query('SELECT id,ticker,quantidade,valor_unitario,data,logo,tipo FROM acoes_internacionais ORDER BY data,id')->fetch_all(MYSQLI_ASSOC);
        $ops = array_values(array_filter($ops, function ($op) use ($ticker) {
            return strtoupper(trim($op['ticker'])) === $ticker;
        }));
        $ops[] = ['id' => PHP_INT_MAX, 'ticker' => $ticker, 'quantidade' => $quantidade,
            'valor_unitario' => $valor_unitario, 'data' => $data, 'logo' => $logo, 'tipo' => $acao];
        internacionalConsolidar($ops);
        if ($acao === 'venda') {
            $quantidade = '-' . $quantidade;
            $valor_investido = '-' . $valor_investido;
        }
        $stmt = $conn->prepare('INSERT INTO acoes_internacionais
            (ticker,quantidade,valor_unitario,valor_investido,data,valor_mercado,logo,tipo)
            VALUES (?,?,?,?,?,?,?,?)');
        // DECIMAL é enviado como texto para evitar perda de precisão por float.
        $stmt->bind_param('ssssssss', $ticker, $quantidade, $valor_unitario, $valor_investido, $data, $valor_mercado, $logo, $acao);
        $stmt->execute();
        $stmt->close();
        $_SESSION['internacional_mensagem'] = 'Operação registrada com sucesso.';
        $_SESSION['internacional_csrf'] = bin2hex(random_bytes(32));
    } catch (DomainException $e) {
        $erro = $e->getMessage();
    } catch (Throwable $e) {
        error_log('MyCashFlow internacional: ' . $e->getMessage());
        $erro = 'Não foi possível registrar a operação. Consulte o registro de erros do servidor.';
    } finally {
        if ($bloqueado) {
            try {
                $conn->query("SELECT RELEASE_LOCK('mycashflow_acoes_internacionais_registro')");
            } catch (Throwable $e) {
                error_log('MyCashFlow: falha ao liberar bloqueio internacional.');
            }
        }
    }
    if ($erro === '') {
        header('Location: acoes_internacionais.php' . ($busca !== '' ? '?' . http_build_query(['busca' => $busca]) : ''));
        exit;
    }
}

$posicoes = [];
$cotacoes_completas = $estrutura_ok;
if ($estrutura_ok) {
    try {
        $ops = $conn->query('SELECT id,ticker,quantidade,valor_unitario,data,logo,tipo FROM acoes_internacionais ORDER BY data,id')->fetch_all(MYSQLI_ASSOC);
        $posicoes = internacionalConsolidar($ops);
        foreach ($posicoes as &$p) {
            list($cotacao, $logo) = internacionalCotacao($p['ticker']);
            $p['valor_mercado_atual'] = $cotacao;
            $p['logo'] = $logo ?? $p['logo'];
            $p['resultado'] = $cotacao !== null
                ? bcsub(bcmul($cotacao, $p['quantidade_total'], 24), $p['total_investido_usd'], 24) : null;
            if ($cotacao === null) {
                $cotacoes_completas = false;
            }
        }
        unset($p);
    } catch (Throwable $e) {
        $posicoes = [];
        $cotacoes_completas = false;
        $erro_carteira = $e instanceof DomainException ? $e->getMessage() : 'Não foi possível carregar a carteira.';
    }
}
$ordens = [];
if ($busca !== '') {
    try {
        $termo = $busca;
        if (preg_match('~^\d{2}/\d{2}/\d{4}$~D', $termo)) {
            $partes = explode('/', $termo);
            $termo = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
        }
        $termo = '%' . $termo . '%';
        $stmt = $conn->prepare('SELECT ticker,quantidade,valor_unitario,data,valor_mercado,tipo
            FROM acoes_internacionais WHERE ticker LIKE ? OR data LIKE ? ORDER BY data DESC,id DESC');
        $stmt->bind_param('ss', $termo, $termo);
        $stmt->execute();
        $ordens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } catch (Throwable $e) {
        $erro = 'Não foi possível consultar o histórico.';
    }
}
$cambio = internacionalJson('https://economia.awesomeapi.com.br/json/last/USD-BRL,EUR-BRL');
$cotacao_usd = $cambio['USDBRL']['bid'] ?? null;
$cotacao_eur = $cambio['EURBRL']['bid'] ?? null;
$cotacao_usd = is_numeric($cotacao_usd) && $cotacao_usd > 0 ? $cotacao_usd : null;
$cotacao_eur = is_numeric($cotacao_eur) && $cotacao_eur > 0 ? $cotacao_eur : null;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Ações Internacionais</title>
    <link rel="stylesheet" href="../assets/css/style-acoes-internacionais.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="acoes-layout">
        <div class="acoes-container internacionais">
            
            <!-- Coluna esquerda: Operações + Cotação -->
            <div class="col-esquerda">
                <!-- Card de Operação -->
                <div class="card-cadastro">
                    <h2>Operação de Ações Internacionais</h2>
                    <?php if ($mensagem !== '') { ?><p role="status"><?= internacionalEscape($mensagem) ?></p><?php } ?>
                    <?php if ($erro !== '') { ?><p role="alert"><?= internacionalEscape($erro) ?></p><?php } ?>
                    <form class="form-acoes" method="POST" id="form-internacional">
                        <input type="hidden" name="csrf" value="<?= internacionalEscape($_SESSION['internacional_csrf']) ?>">
                        <input type="hidden" name="modo" id="modo" value="<?= internacionalEscape($form['modo']) ?>">
                        <input type="text" name="ticker" maxlength="20" placeholder="Ticker (ex: AAPL, MSFT)" value="<?= internacionalEscape($form['ticker']) ?>" required>
                        <label for="valor_unitario">Preço unitário de execução (US$)</label>
                        <input type="number" step="0.0001" min="0.0001" name="valor_unitario" id="valor_unitario" value="<?= internacionalEscape($form['valor_unitario']) ?>" required>
                        <label for="quantidade">Quantidade de ações</label>
                        <input type="number" step="0.00000001" min="0.00000001" name="quantidade" id="quantidade" value="<?= internacionalEscape($form['quantidade']) ?>">
                        <label for="valor_operacao">Valor da operação (US$)</label>
                        <input type="number" step="0.00000001" min="0.00000001" name="valor_operacao" id="valor_operacao" value="<?= internacionalEscape($form['valor_operacao']) ?>">
                        <p id="previa-operacao" aria-live="polite">Preencha o preço e a quantidade ou o valor da operação.</p>
                        <input type="date" name="data" value="<?= internacionalEscape($form['data']) ?>" required>
                        <div class="acao-buttons">
                            <button type="button" class="btn-acao compra<?= $form['acao'] === 'compra' ? ' active' : '' ?>" onclick="selecionarAcao('compra')">Compra</button>
                            <button type="button" class="btn-acao venda<?= $form['acao'] === 'venda' ? ' active' : '' ?>" onclick="selecionarAcao('venda')">Venda</button>
                        </div>
                        <input type="hidden" name="acao" id="acao" value="<?= internacionalEscape($form['acao']) ?>">
                        <button type="submit" <?= !$estrutura_ok ? 'disabled' : '' ?>>Registrar</button>
                    </form>
                </div>

                <!-- Card de Cotação -->
                <div class="card-cotacao">
                    <h2>Cotação do Dia</h2>
                    <div class="cotacoes">
                        <p class="usd">Dólar (USD): 
                            <?php if ($cotacao_usd !== null) { ?>
                                R$ <?= number_format($cotacao_usd, 2, ',', '.') ?>
                            <?php } else { ?> - <?php } ?>
                        </p>
                        <p class="eur">Euro (EUR): 
                            <?php if ($cotacao_eur !== null) { ?>
                                R$ <?= number_format($cotacao_eur, 2, ',', '.') ?>
                            <?php } else { ?> - <?php } ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Coluna do meio: Lista Consolidada -->
            <div class="col-meio">
                <div class="card-lista">
                    <h2>Lista de Ações Internacionais Consolidadas</h2>
                    <p>Carteira em dólares (USD). Resultado não realizado da posição atual.</p>
                    <?php if ($erro_carteira !== '') { ?><p role="alert"><?= internacionalEscape($erro_carteira) ?></p><?php } ?>
                    <?php if (!$cotacoes_completas && $estrutura_ok && $erro_carteira === '') { ?><p>Não foi obtida cotação atual em USD para um ou mais ativos. O resultado total fica indisponível.</p><?php } ?>
                    <table class="tabela-acoes">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Ticker</th>
                                <th>Quantidade Total</th>
                                <th>Valor Médio</th>
                                <th>Total Investido (US$)</th>
                                <th>Valor Mercado Atual</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$posicoes && $estrutura_ok && $erro_carteira === '') { ?>
                            <tr><td colspan="7">Nenhuma posição em carteira.</td></tr>
                            <?php } ?>
                            <?php 
                            $total_resultado = 0;
                            foreach ($posicoes as $row) { 
                                if ($row['resultado'] !== null) { $total_resultado = bcadd((string) $total_resultado, $row['resultado'], 24); }
                            ?>
                            <tr>
                                <td><?php if (!empty($row['logo'])) { ?><img src="<?= internacionalEscape($row['logo']) ?>" style="height:30px;"><?php } else { ?>-<?php } ?></td>
                                <td><?= internacionalEscape($row['ticker']) ?></td>
                                <td><?= internacionalQuantidade($row['quantidade_total']) ?></td>
                                <td>US$ <?= number_format($row['valor_medio_ponderado'], 2, ',', '.') ?></td>
                                <td>US$ <?= number_format($row['total_investido_usd'], 2, ',', '.') ?></td>
                                <td><?= $row['valor_mercado_atual'] !== null ? 'US$ ' . number_format($row['valor_mercado_atual'], 2, ',', '.') : 'Indisponível' ?></td>
                                <td><?php if ($row['resultado'] === null) { ?>Indisponível<?php } elseif (bccomp($row['resultado'], '0', 24) >= 0) { ?><span style="color:green;">+ US$ <?= number_format($row['resultado'], 2, ',', '.') ?></span><?php } else { ?><span style="color:red;">- US$ <?= number_format(abs($row['resultado']), 2, ',', '.') ?></span><?php } ?></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td colspan="6" style="text-align:right;"><strong>Total:</strong></td>
                                <td><?php if (!$cotacoes_completas) { ?>Indisponível<?php } elseif (bccomp((string) $total_resultado, '0', 24) >= 0) { ?><span style="color:green;">+ US$ <?= number_format($total_resultado, 2, ',', '.') ?></span><?php } else { ?><span style="color:red;">- US$ <?= number_format(abs($total_resultado), 2, ',', '.') ?></span><?php } ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Coluna da direita: Pesquisa de Ordens -->
            <div class="col-direita">
                <div class="card-lista">
                    <h2>Pesquisar Ordens Executadas</h2>
                    <form method="GET" class="form-acoes">
                        <input type="text" name="busca" value="<?= internacionalEscape($busca) ?>" placeholder="Digite o ticker ou data (DD/MM/AAAA ou AAAA-MM-DD)">
                        <button type="submit">Pesquisar</button>
                    </form>

                    <?php if ($busca !== '') { ?>
                        <?php if (!$ordens) { ?>
                            <p>Nenhuma ordem encontrada para <strong><?= internacionalEscape($busca) ?></strong>.</p>
                        <?php } else { ?>
                            <table class="tabela-acoes">
                                <thead><tr><th>Ticker</th><th>Quantidade</th><th>Valor Unitário (US$)</th><th>Data</th><th>Valor Mercado</th><th>Tipo</th></tr></thead>
                                <tbody>
                                    <?php foreach ($ordens as $ordem) { ?>
                                    <tr>
                                        <td><?= internacionalEscape($ordem['ticker']) ?></td>
                                        <td><?= internacionalQuantidade($ordem['quantidade']) ?></td>
                                        <td>US$ <?= number_format($ordem['valor_unitario'], 4, ',', '.') ?></td>
                                        <td><?= date('d/m/Y', strtotime($ordem['data'])) ?></td>
                                        <td><?= $ordem['valor_mercado'] !== null ? 'US$ ' . number_format($ordem['valor_mercado'], 2, ',', '.') : '-' ?></td>
                                        <td><?= $ordem['tipo'] === 'compra' ? '<span style="color:green;">Compra</span>' : '<span style="color:red;">Venda</span>' ?></td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

        </div>
    </main>



    <?php include("../includes/footer.php"); ?>
    <script>
    // Inteiros escalados (BigInt) evitam arredondamento binário no formulário.
    function decimalInteiro(texto, casas, inteiros) {
        if (!new RegExp('^[0-9]{1,' + inteiros + '}(?:\\.[0-9]{1,' + casas + '})?$').test(texto)) {
            throw new Error('Confira a precisão dos campos: quantidade e valor até 8 casas; preço até 4.');
        }
        const partes = texto.split('.');
        const n = BigInt(partes[0]) * 10n ** BigInt(casas)
            + BigInt((partes[1] || '').padEnd(casas, '0'));
        if (n <= 0n) throw new Error('Informe valores maiores que zero.');
        return n;
    }
    function decimalTexto(n, casas) {
        const s = n.toString().padStart(casas + 1, '0');
        return (s.slice(0, -casas) + '.' + s.slice(-casas)).replace(/0+$/, '').replace(/\.$/, '');
    }
    function calcularFracao(modo, origem, precoTexto) {
        const p = decimalInteiro(precoTexto, 4, 14);
        const q = modo === 'valor'
            ? decimalInteiro(origem, 8, 16) * 10000n / p
            : decimalInteiro(origem, 8, 14);
        if (q <= 0n) throw new Error('Valor inferior à fração mínima de 0,00000001 ação.');
        if (q > 9999999999999999999999n) throw new Error('Quantidade acima do limite suportado.');
        const produto = q * p; // escala 12
        if (produto > 9999999999999999999999990000n) throw new Error('Valor da operação acima do limite suportado.');
        const total = (produto + 5000n) / 10000n; // escala 8, arredondamento decimal
        if (total <= 0n) throw new Error('Valor da operação inferior à precisão de registro.');
        return { quantidade: decimalTexto(q, 8), valor: decimalTexto(total, 8) };
    }
    function selecionarAcao(tipo) {
        document.getElementById('acao').value = tipo;
        document.querySelectorAll('.btn-acao').forEach(btn => btn.classList.remove('active'));
        document.querySelector('.btn-acao.' + tipo).classList.add('active');
    }
    const campoQuantidade = document.getElementById('quantidade');
    const campoValor = document.getElementById('valor_operacao');
    const campoPreco = document.getElementById('valor_unitario');
    const campoModo = document.getElementById('modo');
    const previa = document.getElementById('previa-operacao');
    function atualizarConversao() {
        const porValor = campoModo.value === 'valor';
        const origem = porValor ? campoValor : campoQuantidade;
        const destino = porValor ? campoQuantidade : campoValor;
        campoQuantidade.setCustomValidity('');
        campoValor.setCustomValidity('');
        if (!origem.value || !campoPreco.value) {
            destino.value = '';
            previa.textContent = 'Preencha o preço e a quantidade ou o valor da operação.';
            return false;
        }
        try {
            const calculo = calcularFracao(campoModo.value, origem.value, campoPreco.value);
            destino.value = porValor ? calculo.quantidade : calculo.valor;
            previa.textContent = 'Operação: ' + calculo.quantidade.replace('.', ',')
                + ' ação(ões), total calculado de US$ ' + calculo.valor.replace('.', ',')
                + (porValor ? '. A quantidade é limitada a oito casas, sem exceder o valor informado.' : '.');
            return true;
        } catch (e) {
            destino.value = '';
            previa.textContent = e.message;
            origem.setCustomValidity(e.message);
            return false;
        }
    }
    campoQuantidade.addEventListener('input', () => { campoModo.value = 'quantidade'; atualizarConversao(); });
    campoValor.addEventListener('input', () => { campoModo.value = 'valor'; atualizarConversao(); });
    campoPreco.addEventListener('input', atualizarConversao);
    document.getElementById('form-internacional').addEventListener('submit', (event) => {
        if (!atualizarConversao()) {
            event.preventDefault();
            document.getElementById('form-internacional').reportValidity();
        } else if (!['compra', 'venda'].includes(document.getElementById('acao').value)) {
            event.preventDefault();
            previa.textContent = 'Selecione Compra ou Venda antes de registrar.';
        }
    });
    atualizarConversao();
    </script>
</body>
</html>
