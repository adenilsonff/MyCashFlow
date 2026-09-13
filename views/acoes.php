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
require_once __DIR__ . '/../includes/mercado_api.php';

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

function resumoDolar(&$diagnostico) {
    $q = mercadoApi()->fx()['USD'];
    $diagnostico = mercadoLegenda($q);
    return $q['price'] === null ? null : ['valor'=>(float)$q['price'], 'timestamp'=>$q['market_time'], 'fonte'=>mercadoLegenda($q)];
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ações</title>
    <link rel="stylesheet" href="../assets/css/style-acoes-menu.css?v=7">
</head>
<body class="acoes-menu-page">
    <?php include('../includes/header.php'); ?>
    <?php include('../includes/menu.php'); ?>

    <main class="acoes-page">
        <div class="acoes-top">
            <div>
                <h1>Ações</h1>
                <p>Acompanhe suas posições e acesse as carteiras.</p>
            </div>
        </div>

        <?php foreach ($resumo_erros as $resumo_erro) { ?>
            <p class="acoes-aviso" role="alert"><?= resumoEscape($resumo_erro) ?></p>
        <?php } ?>

        <section class="acoes-card" aria-labelledby="acoes-resumo-titulo">
            <div class="acoes-summary">
                <div>
                    <h2 id="acoes-resumo-titulo">Resumo da carteira</h2>
                    <p>Custo das ações que permanecem em carteira.</p>
                </div>
                <div class="acoes-total">
                    <span>Consolidado em reais · estimativa</span>
                    <strong><?= $total_geral !== null ? 'R$ ' . number_format($total_geral, 2, ',', '.') : 'Indisponível' ?></strong>
                </div>
            </div>
            <div class="acoes-scroll" tabindex="0" role="region" aria-label="Custos por carteira">
                <table class="acoes-table">
                    <thead>
                        <tr>
                            <th scope="col">Carteira</th>
                            <th scope="col" class="acoes-number">Custo das posições atuais</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Nacional <span class="acoes-moeda">BRL</span></th>
                            <td class="acoes-number"><?= $total_nacional !== null ? 'R$ ' . number_format($total_nacional, 2, ',', '.') : 'Indisponível' ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Internacional <span class="acoes-moeda">USD</span></th>
                            <td class="acoes-number"><?= $total_internacional !== null ? 'US$ ' . number_format($total_internacional, 2, ',', '.') : 'Indisponível' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="acoes-notas">
                <?php if ($dolar !== null) { ?>
                    <p><strong>Câmbio utilizado:</strong> US$ 1 = R$ <?= number_format($dolar['valor'], 4, ',', '.') ?>.
                        <?= resumoEscape($dolar['fonte']) ?><?php if ($dolar['timestamp'] !== null) { ?>
                            · <?= (new DateTimeImmutable('@' . $dolar['timestamp']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i') ?> (Brasília)<?php } ?>.
                    </p>
                    <p>O consolidado converte o custo em dólares pelo câmbio informado. Não representa o valor de mercado nem o custo histórico em reais.</p>
                <?php } elseif ($total_internacional !== null && bccomp($total_internacional, '0', 24) > 0) { ?>
                    <p role="status">Câmbio indisponível. Consulte os custos separados em reais e dólares.</p>
                    <?php if ($diagnostico_cambio !== '') { ?>
                        <details>
                            <summary>Detalhes da consulta de câmbio</summary>
                            <p><?= resumoEscape($diagnostico_cambio) ?></p>
                        </details>
                    <?php } ?>
                <?php } else { ?>
                    <p>As compras aumentam o custo da posição; as vendas retiram o custo pelo preço médio.</p>
                <?php } ?>
            </div>
        </section>

        <section class="acoes-acessos" aria-label="Acessar carteiras">
            <a class="acoes-acesso" href="acoes_nacionais.php">
                <div>
                    <span class="acoes-etiqueta">MERCADO NACIONAL</span>
                    <h2>Ações Nacionais</h2>
                    <p>Consulte a carteira na B3 e registre compras e vendas.</p>
                </div>
                <span class="acoes-abrir">Acessar carteira <span aria-hidden="true">→</span></span>
            </a>
            <a class="acoes-acesso" href="acoes_internacionais.php">
                <div>
                    <span class="acoes-etiqueta">MERCADO INTERNACIONAL</span>
                    <h2>Ações Internacionais</h2>
                    <p>Gerencie suas posições em dólares e operações fracionadas.</p>
                </div>
                <span class="acoes-abrir">Acessar carteira <span aria-hidden="true">→</span></span>
            </a>
        </section>
    </main>

    <?php include('../includes/footer.php'); ?>
</body>
</html>
