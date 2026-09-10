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

function resumoEscape($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Reconstrói o custo remanescente, sem deduzir a receita das vendas do custo.
// Data e id definem a mesma sequência usada nas páginas operacionais.
function resumoCusto(array $operacoes, $nacional) {
    usort($operacoes, function ($a, $b) {
        return strcmp($a['data'], $b['data']) ?: ($a['id'] <=> $b['id']);
    });
    $posicoes = [];
    foreach ($operacoes as $op) {
        $ticker = strtoupper(trim($op['ticker']));
        if ($nacional) {
            $ticker = preg_replace('/\.SA$/', '', $ticker);
        }
        if (!isset($posicoes[$ticker])) {
            $posicoes[$ticker] = ['quantidade' => '0', 'custo' => '0', 'medio' => '0'];
        }
        $p = &$posicoes[$ticker];
        $quantidade = ltrim((string) $op['quantidade'], '-');
        $preco = (string) $op['valor_unitario'];
        if (!in_array($op['tipo'], ['compra', 'venda'], true)
            || bccomp($quantidade, '0', 8) <= 0 || bccomp($preco, '0', 8) <= 0
            || ($op['tipo'] === 'compra' && bccomp($op['quantidade'], '0', 8) < 0)) {
            throw new DomainException('Operação inválida no histórico de ' . $ticker . '.');
        }
        if ($op['tipo'] === 'compra') {
            $p['custo'] = bcadd($p['custo'], bcmul($quantidade, $preco, 24), 24);
            $p['quantidade'] = bcadd($p['quantidade'], $quantidade, 8);
            $p['medio'] = bcdiv($p['custo'], $p['quantidade'], 24);
        } else {
            if (bccomp($quantidade, $p['quantidade'], 8) > 0) {
                throw new DomainException('Venda acima do saldo no histórico de ' . $ticker
                    . ' em ' . date('d/m/Y', strtotime($op['data'])) . '.');
            }
            $p['quantidade'] = bcsub($p['quantidade'], $quantidade, 8);
            $p['custo'] = bcmul($p['quantidade'], $p['medio'], 24);
            if (bccomp($p['quantidade'], '0', 8) === 0) {
                $p['custo'] = '0';
                $p['medio'] = '0';
            }
        }
        unset($p);
    }
    $total = '0';
    foreach ($posicoes as $p) {
        $total = bcadd($total, $p['custo'], 24);
    }
    return $total;
}

function resumoBuscarCambio($url, array $headers, &$falha) {
    $falha = '';
    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
                CURLOPT_USERAGENT => 'MyCashFlow/1.0'
            ]);
            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $codigo = curl_errno($ch);
            curl_close($ch);
            if ($response === false) {
                $falha = in_array($codigo, [60, 77], true)
                    ? 'falha na verificação do certificado HTTPS'
                    : ($codigo === 28 ? 'tempo de conexão esgotado' : 'não foi possível conectar ao serviço');
                return [];
            }
            if ($status !== 200) {
                $falha = 'serviço respondeu HTTP ' . $status;
                return [];
            }
        } else {
            $context = stream_context_create(['http' => [
                'timeout' => 8, 'follow_location' => 0,
                'header' => implode("\r\n", array_merge(['Accept: application/json'], $headers))
            ]]);
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                $falha = 'não foi possível consultar o serviço por HTTPS';
                return [];
            }
        }
        $dados = json_decode($response, true);
        if (!is_array($dados)) {
            $falha = 'resposta inválida do serviço';
            return [];
        }
        return $dados;
    } catch (Throwable $e) {
        $falha = 'não foi possível consultar o serviço';
        return [];
    }
}

function resumoValidarCambio($valor, $timestamp, $fonte) {
    $valor = (string) $valor;
    if (!preg_match('/^[0-9]{1,8}(?:\.[0-9]{1,8})?$/D', $valor)
        || bccomp($valor, '0', 8) <= 0) {
        return null;
    }
    $timestamp = (string) $timestamp;
    return ['valor' => $valor,
        'timestamp' => ctype_digit($timestamp) && (int) $timestamp > 0 ? (int) $timestamp : null,
        'fonte' => $fonte];
}

function resumoDolar(&$diagnostico, $buscar = null) {
    $buscar = $buscar ?? 'resumoBuscarCambio';
    $diagnostico = '';
    $falha = '';
    $dados = $buscar('https://economia.awesomeapi.com.br/json/last/USD-BRL', [], $falha);
    $cotacao = $dados['USDBRL'] ?? [];
    if (($cotacao['code'] ?? '') === 'USD' && ($cotacao['codein'] ?? '') === 'BRL') {
        $validada = resumoValidarCambio($cotacao['bid'] ?? '', $cotacao['timestamp'] ?? '', 'AwesomeAPI');
        if ($validada !== null) {
            return $validada;
        }
    }
    $falhas = ['AwesomeAPI: ' . ($falha ?: 'resposta sem cotação válida de USD/BRL')];
    // Usa o mesmo token da integração BRAPI já existente no módulo.
    $token = defined('BRAPI_TOKEN') ? BRAPI_TOKEN : (getenv('BRAPI_TOKEN') ?: 'pV9h8EEsN5xs9ESi2Uk89n');
    $falha = '';
    $dados = $buscar('https://brapi.dev/api/v2/currency?currency=USD-BRL',
        ['Authorization: Bearer ' . $token], $falha);
    foreach (($dados['currency'] ?? []) as $cotacao) {
        if (($cotacao['fromCurrency'] ?? '') !== 'USD' || ($cotacao['toCurrency'] ?? '') !== 'BRL') {
            continue;
        }
        $validada = resumoValidarCambio($cotacao['bidPrice'] ?? '', $cotacao['updatedAtTimestamp'] ?? '', 'BRAPI — referência PTAX');
        if ($validada !== null) {
            return $validada;
        }
    }
    $falhas[] = 'BRAPI: ' . ($falha ?: 'resposta sem cotação válida de USD/BRL');
    $diagnostico = implode('. ', $falhas) . '.';
    error_log('MyCashFlow câmbio: ' . $diagnostico);
    return null;
}

function resumoConverter($nacional, $internacional, $dolar) {
    if ($nacional === null || $internacional === null) {
        return null;
    }
    if (bccomp($internacional, '0', 24) === 0) {
        return $nacional;
    }
    return $dolar === null ? null : bcadd($nacional, bcmul($internacional, $dolar, 24), 24);
}

$resumo_erros = [];
$total_nacional = null;
$total_internacional = null;
$dolar = null;
$diagnostico_cambio = '';
$total_geral = null;
if (!extension_loaded('bcmath')) {
    $resumo_erros[] = 'Ative a extensão BCMath do PHP para calcular o resumo com precisão.';
} else {
    foreach (['nacional' => 'acoes_nacionais', 'internacional' => 'acoes_internacionais'] as $categoria => $tabela) {
        try {
            // As tabelas são fixas, sem nomes recebidos do formulário.
            $operacoes = $conn->query("SELECT id,ticker,quantidade,valor_unitario,data,tipo FROM $tabela ORDER BY data,id")->fetch_all(MYSQLI_ASSOC);
            $total = resumoCusto($operacoes, $categoria === 'nacional');
            if ($categoria === 'nacional') {
                $total_nacional = $total;
            } else {
                $total_internacional = $total;
            }
        } catch (Throwable $e) {
            error_log('MyCashFlow resumo ' . $categoria . ': ' . $e->getMessage());
            $resumo_erros[] = 'Carteira ' . $categoria . ': ' . ($e instanceof DomainException
                ? $e->getMessage() : 'não foi possível carregar o custo das posições.');
        }
    }
    if ($total_internacional !== null && bccomp($total_internacional, '0', 24) > 0) {
        $dolar = resumoDolar($diagnostico_cambio);
    }
    $total_geral = resumoConverter($total_nacional, $total_internacional, $dolar['valor'] ?? null);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Ações</title>
    <link rel="stylesheet" href="../assets/css/style-acoes-menu.css?v=6">
</head>
<body>
    <?php include('../includes/header.php'); ?>
    <?php include('../includes/menu.php'); ?>

    <main class="acoes-layout">
        <h2>Resumo dos Investimentos em Ações</h2>
        <div class="card-lista">
            <?php foreach ($resumo_erros as $resumo_erro) { ?>
                <p role="alert"><?= resumoEscape($resumo_erro) ?></p>
            <?php } ?>
            <table class="tabela-acoes">
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Custo das posições atuais</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nacional</td>
                        <td><?= $total_nacional !== null ? 'R$ ' . number_format($total_nacional, 2, ',', '.') : 'Indisponível' ?></td>
                    </tr>
                    <tr>
                        <td>Internacional (USD)</td>
                        <td><?= $total_internacional !== null ? 'US$ ' . number_format($total_internacional, 2, ',', '.') : 'Indisponível' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Consolidado em reais — estimativa</strong></td>
                        <td><strong><?= $total_geral !== null ? 'R$ ' . number_format($total_geral, 2, ',', '.') : 'Indisponível' ?></strong></td>
                    </tr>
                </tbody>
            </table>
            <p>Os valores representam o custo das ações que permanecem em carteira, após as vendas.</p>
            <?php if ($dolar !== null) { ?>
                <p>Câmbio utilizado: US$ 1 = R$ <?= number_format($dolar['valor'], 4, ',', '.') ?> (<?= resumoEscape($dolar['fonte']) ?>).
                    <?php if ($dolar['timestamp'] !== null) { ?>
                        Cotação de <?= (new DateTimeImmutable('@' . $dolar['timestamp']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i') ?>, horário de Brasília.
                    <?php } ?>
                </p>
                <p>O consolidado converte o custo em dólares pelo câmbio informado. Não representa o valor de mercado nem o custo histórico de aquisição em reais.</p>
            <?php } elseif ($total_internacional !== null && bccomp($total_internacional, '0', 24) > 0) { ?>
                <p>Câmbio indisponível. Os custos em reais e dólares continuam apresentados separadamente.</p>
                <?php if ($diagnostico_cambio !== '') { ?><p role="status"><?= resumoEscape($diagnostico_cambio) ?></p><?php } ?>
            <?php } ?>
        </div>

        <div class="acoes-container" style="margin-top:30px;">
            <!-- Card Nacional -->
            <div class="card-cadastro" onclick="location.href='acoes_nacionais.php'" style="cursor:pointer;">
                <h2>Ações Nacionais</h2>
                <p>Gerencie seus investimentos na B3</p>
            </div>

            <!-- Card Internacional -->
            <div class="card-cadastro" onclick="location.href='acoes_internacionais.php'" style="cursor:pointer;">
                <h2>Ações Internacionais</h2>
                <p>Gerencie seus ativos no exterior</p>
            </div>
        </div>
    </main>

    <?php include('../includes/footer.php'); ?>
</body>
</html>
