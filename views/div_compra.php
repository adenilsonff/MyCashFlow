<?php
include __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mes = isset($_POST['mes']) ? (int)$_POST['mes'] : (int)date("n");
$ano = isset($_POST['ano']) ? (int)$_POST['ano'] : (int)date("Y");
$capital = isset($_POST['capital']) ? (float)$_POST['capital'] : 0;

if ($mes < 1 || $mes > 12) {
    $mes = (int)date("n");
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date("Y");
}

$meses = [
    1 => "Janeiro",
    2 => "Fevereiro",
    3 => "Março",
    4 => "Abril",
    5 => "Maio",
    6 => "Junho",
    7 => "Julho",
    8 => "Agosto",
    9 => "Setembro",
    10 => "Outubro",
    11 => "Novembro",
    12 => "Dezembro"
];

function formatarValorProvento($valor) {
    $valorFormatado = number_format((float)$valor, 8, ',', '');
    $valorFormatado = rtrim($valorFormatado, '0');
    $valorFormatado = rtrim($valorFormatado, ',');

    return $valorFormatado;
}

function obterDadosSalvosAcao($conn, $ticker) {
    $stmt = $conn->prepare("
        SELECT valor_mercado, logo
        FROM acoes_nacionais
        WHERE UPPER(ticker) = UPPER(?)
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $ticker);
    $stmt->execute();
    $result = $stmt->get_result();

    $dados = [
        'valor_mercado' => 0,
        'logo' => null
    ];

    if ($row = $result->fetch_assoc()) {
        $dados['valor_mercado'] = (float)$row['valor_mercado'];
        $dados['logo'] = $row['logo'];
    }

    $stmt->close();

    return $dados;
}

function obterCotacaoAtual($ticker) {
    $tickerConsulta = strtoupper(trim($ticker));

    if ($tickerConsulta === '') {
        return null;
    }

    if (!str_ends_with($tickerConsulta, '.SA')) {
        $tickerConsulta .= '.SA';
    }

    $url = 'https://brapi.dev/api/quote/' . urlencode($tickerConsulta);

    $contexto = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);

    $resposta = @file_get_contents($url, false, $contexto);

    if ($resposta === false) {
        return null;
    }

    $dados = json_decode($resposta, true);

    if (
        !isset($dados['results'][0]['regularMarketPrice']) ||
        !is_numeric($dados['results'][0]['regularMarketPrice'])
    ) {
        return null;
    }

    return [
        'valor' => (float)$dados['results'][0]['regularMarketPrice'],
        'logo' => $dados['results'][0]['logourl'] ?? null
    ];
}

$resultados = [];

if (isset($_POST['simular_todas']) && $capital > 0) {
    $hoje = date("Y-m-d");

    $stmt = $conn->prepare("
        SELECT
            ticker,
            MIN(datacom) AS primeira_datacom
        FROM div_datacom
        WHERE MONTH(datacom) = ?
          AND YEAR(datacom) = ?
          AND datacom >= ?
        GROUP BY ticker
        ORDER BY ticker ASC
    ");
    $stmt->bind_param("iis", $mes, $ano, $hoje);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($acao = $result->fetch_assoc()) {
        $ticker = strtoupper($acao['ticker']);

        $dadosSalvos = obterDadosSalvosAcao($conn, $ticker);
        $cotacaoAtual = obterCotacaoAtual($ticker);

        if ($cotacaoAtual !== null && $cotacaoAtual['valor'] > 0) {
            $valorMercado = $cotacaoAtual['valor'];
            $logo = !empty($cotacaoAtual['logo'])
                ? $cotacaoAtual['logo']
                : $dadosSalvos['logo'];
            $fonteCotacao = 'Atual';
        } else {
            $valorMercado = $dadosSalvos['valor_mercado'];
            $logo = $dadosSalvos['logo'];
            $fonteCotacao = 'Salva';
        }

        if ($valorMercado <= 0) {
            continue;
        }

        $quantidade = (int)floor($capital / $valorMercado);

        if ($quantidade <= 0) {
            continue;
        }

        $capitalInvestido = $quantidade * $valorMercado;
        $saldo = $capital - $capitalInvestido;

        $stmtDiv = $conn->prepare("
            SELECT
                datacom,
                datapag,
                valor,
                tipo
            FROM div_datacom
            WHERE UPPER(ticker) = UPPER(?)
              AND MONTH(datacom) = ?
              AND YEAR(datacom) = ?
              AND datacom >= ?
            ORDER BY datacom ASC, id ASC
        ");
        $stmtDiv->bind_param(
            "siis",
            $ticker,
            $mes,
            $ano,
            $hoje
        );
        $stmtDiv->execute();
        $resultDiv = $stmtDiv->get_result();

        $dividendoTotal = 0;
        $detalhesDiv = [];

        while ($div = $resultDiv->fetch_assoc()) {
            $rendimento = $quantidade * (float)$div['valor'];
            $dividendoTotal += $rendimento;

            $detalhesDiv[] = [
                'tipo' => $div['tipo'],
                'valor' => (float)$div['valor'],
                'datacom' => $div['datacom'],
                'datapag' => $div['datapag'],
                'rendimento' => $rendimento
            ];
        }

        $stmtDiv->close();

        if ($dividendoTotal <= 0) {
            continue;
        }

        $retornoPercentual = $capitalInvestido > 0
            ? ($dividendoTotal / $capitalInvestido) * 100
            : 0;

        $resultados[] = [
            'ticker' => $ticker,
            'logo' => $logo,
            'valor_mercado' => $valorMercado,
            'fonte_cotacao' => $fonteCotacao,
            'capital_investido' => $capitalInvestido,
            'saldo' => $saldo,
            'quantidade' => $quantidade,
            'dividendo_total' => $dividendoTotal,
            'retorno_percentual' => $retornoPercentual,
            'detalhes' => $detalhesDiv
        ];
    }

    $stmt->close();

    usort($resultados, function ($a, $b) {
        return $b['dividendo_total'] <=> $a['dividendo_total'];
    });
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Simulador de Dividendos</title>
    <link rel="stylesheet" href="../assets/css/style-divis.css?v=1">
</head>
<body>
    <?php include("../includes/header.php"); ?>
    <?php include("../includes/menu.php"); ?>

    <main class="rendas-layout">
        <div class="rendas-container">
            <div class="card-cadastro-renda">
                <h2>Simular Comparativo de Dividendos</h2>

                <form method="POST" class="form-rendas">
                    <label for="capital">Valor disponível para investir (R$):</label>

                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="capital"
                        id="capital"
                        value="<?= $capital > 0 ? htmlspecialchars((string)$capital) : '' ?>"
                        required
                    >

                    <label for="mes">Mês da Data COM:</label>

                    <select name="mes" id="mes" required>
                        <?php foreach ($meses as $numero => $nome) { ?>
                            <option
                                value="<?= $numero ?>"
                                <?= $numero === $mes ? 'selected' : '' ?>
                            >
                                <?= $nome ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label for="ano">Ano:</label>

                    <select name="ano" id="ano" required>
                        <?php for ($y = (int)date("Y"); $y <= (int)date("Y") + 5; $y++) { ?>
                            <option
                                value="<?= $y ?>"
                                <?= $y === $ano ? 'selected' : '' ?>
                            >
                                <?= $y ?>
                            </option>
                        <?php } ?>
                    </select>

                    <button type="submit" name="simular_todas">
                        Simular
                    </button>
                </form>
            </div>
        </div>

        <?php if (isset($_POST['simular_todas'])) { ?>
            <div class="card-lista-renda">
                <h2>
                    Comparativo de Dividendos -
                    <?= $meses[$mes] ?>/<?= $ano ?>
                </h2>

                <?php if (!empty($resultados)) { ?>
                    <table class="tabela-rendas">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Ticker</th>
                                <th>Cotação</th>
                                <th>Investido</th>
                                <th>Saldo</th>
                                <th>Qtd.</th>
                                <th>Dividendos</th>
                                <th>Retorno</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($resultados as $indice => $acao) { ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($acao['logo'])) { ?>
                                            <img
                                                src="<?= htmlspecialchars($acao['logo']) ?>"
                                                alt="<?= htmlspecialchars($acao['ticker']) ?>"
                                                style="height:40px;"
                                            >
                                        <?php } else { ?>
                                            -
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($acao['ticker']) ?>

                                        <?php if ($indice === 0) { ?>
                                            <strong> - Melhor resultado</strong>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        R$ <?= number_format(
                                            $acao['valor_mercado'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                        <br>
                                        <small>
                                            <?= htmlspecialchars($acao['fonte_cotacao']) ?>
                                        </small>
                                    </td>

                                    <td>
                                        R$ <?= number_format(
                                            $acao['capital_investido'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        R$ <?= number_format(
                                            $acao['saldo'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= (int)$acao['quantidade'] ?>
                                    </td>

                                    <td>
                                        R$ <?= number_format(
                                            $acao['dividendo_total'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $acao['retorno_percentual'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>%
                                    </td>
                                </tr>

                                <?php foreach ($acao['detalhes'] as $d) { ?>
                                    <tr>
                                        <td colspan="2">
                                            <?= htmlspecialchars($d['tipo']) ?>
                                        </td>

                                        <td colspan="2">
                                            R$ <?= formatarValorProvento($d['valor']) ?>
                                            por ação
                                        </td>

                                        <td colspan="2">
                                            Data COM:
                                            <?= date("d/m/Y", strtotime($d['datacom'])) ?>
                                        </td>

                                        <td colspan="2">
                                            Estimado:
                                            R$ <?= number_format(
                                                $d['rendimento'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p>
                        Nenhuma oportunidade de dividendo com Data COM válida
                        foi encontrada para este período.
                    </p>
                <?php } ?>
            </div>
        <?php } ?>
    </main>

    <?php include("../includes/footer.php"); ?>
</body>
</html>