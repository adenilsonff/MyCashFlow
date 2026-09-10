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

function acoesEscape($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
    static $cache = [];
    $ticker = acoesTicker($ticker);
    if (array_key_exists($ticker, $cache)) {
        return $cache[$ticker];
    }
    // Mantém o token já utilizado no módulo; permite configuração externa.
    $token = defined('BRAPI_TOKEN') ? BRAPI_TOKEN : (getenv('BRAPI_TOKEN') ?: 'pV9h8EEsN5xs9ESi2Uk89n');
    $url = 'https://brapi.dev/api/quote/' . rawurlencode($ticker);
    $headers = "Accept: application/json\r\nAuthorization: Bearer " . $token . "\r\n";
    $response = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 8, CURLOPT_HTTPHEADER => explode("\r\n", trim($headers))
        ]);
        $response = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            $response = false;
        }
        curl_close($ch);
    } else {
        $context = stream_context_create(['http' => [
            'timeout' => 8, 'header' => $headers, 'follow_location' => 0
        ]]);
        $response = @file_get_contents($url, false, $context);
    }
    $dados = $response !== false ? json_decode($response, true) : null;
    $acao = $dados['results'][0] ?? [];
    $preco = $acao['regularMarketPrice'] ?? null;
    $logo = $acao['logourl'] ?? null;
    if (acoesTicker($acao['symbol'] ?? '') !== $ticker
        || !is_numeric($preco) || !is_finite((float) $preco) || $preco <= 0) {
        return $cache[$ticker] = [null, null];
    }
    if (!is_string($logo) || !filter_var($logo, FILTER_VALIDATE_URL)
        || strtolower(parse_url($logo, PHP_URL_SCHEME) ?? '') !== 'https') {
        $logo = null;
    }
    return $cache[$ticker] = [(float) $preco, $logo];
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
    foreach ($posicoes as &$p) {
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

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações Nacionais</title>
    <link rel="stylesheet" href="../assets/css/style-acoes.css?v=5">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="acoes-layout">
        <div class="acoes-container">
            <!-- Card de Operação -->
            <div class="card-cadastro">
                <h2>Operação de Ações</h2>
                <?php if ($mensagem !== '') { ?><p role="status"><?= acoesEscape($mensagem) ?></p><?php } ?>
                <?php if ($erro !== '') { ?><p role="alert"><?= acoesEscape($erro) ?></p><?php } ?>
                <form class="form-acoes" method="POST">
                    <input type="hidden" name="csrf" value="<?= acoesEscape($_SESSION['acoes_csrf']) ?>">
                    <!-- Campos comuns -->
                    <input type="text" name="ticker" value="<?= acoesEscape($acoes_form['ticker']) ?>" placeholder="Ticker (ex: PETR4)" required>
                    <input type="number" name="quantidade" value="<?= acoesEscape($acoes_form['quantidade']) ?>" min="1" step="1" max="2147483647" placeholder="Quantidade" required>
                    <input type="number" step="0.01" min="0.01" name="valor_unitario" value="<?= acoesEscape($acoes_form['valor_unitario']) ?>" placeholder="Valor unitário" required>
                    <input type="date" name="data" value="<?= acoesEscape($acoes_form['data']) ?>" required>

                    <!-- Botões Compra/Venda abaixo do campo Data -->
                    <div class="acao-buttons">
                        <button type="button" class="btn-acao compra<?= $acoes_form['acao'] === 'compra' ? ' active' : '' ?>" onclick="selecionarAcao('compra')">Compra</button>
                        <button type="button" class="btn-acao venda<?= $acoes_form['acao'] === 'venda' ? ' active' : '' ?>" onclick="selecionarAcao('venda')">Venda</button>
                    </div>

                    <!-- Campo oculto para enviar ao PHP -->
                    <input type="hidden" name="acao" id="acao" value="<?= acoesEscape($acoes_form['acao']) ?>">

                    <button type="submit">Registrar</button>
                </form>

            </div>

            <!-- Card da Lista Consolidada -->
            <div class="card-lista">
                <h2>Lista de Ações Consolidadas</h2>
                <?php if ($erro_carteira !== '') { ?><p role="alert"><?= acoesEscape($erro_carteira) ?></p><?php } ?>
                <?php if (!$cotacoes_completas && $erro_carteira === '') { ?><p>Cotação indisponível para um ou mais ativos. O resultado total não pôde ser calculado.</p><?php } ?>
                <table class="tabela-acoes">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Ticker</th>
                            <th>Quantidade Total</th>
                            <th>Valor Médio</th>
                            <th>Total Investido</th>
                            <th>Valor Mercado Atual</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_resultado = 0;
                        foreach ($posicoes as $row) { 
                            if ($row['resultado'] !== null) { $total_resultado += $row['resultado']; }
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['logo'])) { ?>
                                    <img src="<?= acoesEscape($row['logo']) ?>" alt="<?= acoesEscape($row['ticker']) ?>" style="height:30px;">
                                <?php } else { ?>
                                    -
                                <?php } ?>
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
                            </td>
                            <td>
                                <?php if ($row['resultado'] === null) { ?>
                                    Indisponível
                                <?php } elseif ($row['resultado'] >= 0) { ?>
                                    <span style="color:green;">+ R$ <?= number_format($row['resultado'], 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span style="color:red;">- R$ <?= number_format(abs($row['resultado']), 2, ',', '.') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <td colspan="6" style="text-align:right;"><strong>Total:</strong></td>
                            <td>
                                <?php if (!$cotacoes_completas) { ?>
                                    Indisponível
                                <?php } elseif ($total_resultado >= 0) { ?>
                                    <span style="color:green;">+ R$ <?= number_format($total_resultado, 2, ',', '.') ?></span>
                                <?php } else { ?>
                                    <span style="color:red;">- R$ <?= number_format(abs($total_resultado), 2, ',', '.') ?></span>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Card de Pesquisa de Ordens -->
            <div class="card-lista" style="margin-top:30px;">
                <h2>Pesquisar Ordens Executadas</h2>
                <form method="GET" class="form-acoes">
                    <input type="text" name="busca" value="<?= acoesEscape($busca) ?>" placeholder="Digite o ticker ou data (DD/MM/AAAA ou AAAA-MM-DD)">
                    <button type="submit">Pesquisar</button>
                </form>

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
                                    <td><?= $ordem['tipo'] === 'compra' ? '<span style="color:green;">Compra</span>' : '<span style="color:red;">Venda</span>' ?></td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </main>

    <?php include("../includes/footer.php"); ?>
    <script>
    function selecionarAcao(tipo) {
        // Atualiza o hidden input
        document.getElementById('acao').value = tipo;

        // Remove active de todos os botões
        document.querySelectorAll('.btn-acao').forEach(btn => btn.classList.remove('active'));

        // Adiciona active no botão clicado
        document.querySelector('.btn-acao.' + tipo).classList.add('active');
    }
    </script>

</body>
</html>
