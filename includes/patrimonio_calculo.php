<?php
require_once __DIR__ . '/carteiras_posicoes.php';
require_once __DIR__ . '/saldos_consulta.php';

function patrimonioSomar($a, $b) {
    return $a === null || $b === null ? null : bcadd((string)$a, (string)$b, 24);
}

function patrimonioDisponibilidades(array $contas) {
    $total = '0';
    foreach ($contas as $conta) {
        if ((int)$conta['ativa'] === 1) {
            $total = patrimonioSomar($total, $conta['saldo_atual']);
        }
    }
    return $total;
}

function patrimonioOperacoes($conn, $usuario_id, $nacional) {
    // Os nomes são internos e fixos; somente o usuário é um parâmetro da consulta.
    $tabela = $nacional ? 'investimentos_nacionais' : 'investimentos_internacionais';
    $stmt = $conn->prepare("SELECT id, ticker, tipo_ativo, quantidade,
        valor_unitario, data, tipo_operacao, logo FROM $tabela
        WHERE usuario_id = ? ORDER BY data, id");
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $operacoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $operacoes;
}

function patrimonioAvaliar(array $posicoes, array $cotacoes, $nacional, $cambio = null) {
    $total = '0';
    foreach ($posicoes as &$p) {
        $p['api'] = $cotacoes[$p['ticker']] ?? null;
        $preco = $p['api']['price'] ?? null;
        $p['valor_origem'] = $preco === null ? null
            : bcmul((string)$preco, (string)$p['quantidade_total'], 24);
        // A carteira internacional e sua cotação estão em USD. Converter só aqui.
        $p['valor_brl'] = $nacional ? $p['valor_origem']
            : ($p['valor_origem'] === null || $cambio === null ? null
                : bcmul($p['valor_origem'], (string)$cambio, 24));
        $total = patrimonioSomar($total, $p['valor_brl']);
    }
    unset($p);
    return ['posicoes' => $posicoes, 'total' => $total];
}

function patrimonioCarteira($conn, $usuario_id, $nacional, $api) {
    $operacoes = patrimonioOperacoes($conn, $usuario_id, $nacional);
    $posicoes = $nacional ? acoesConsolidar($operacoes) : internacionalConsolidar($operacoes);
    $cotacoes = [];
    $cambio = null;
    if ($posicoes) {
        $cotacoes = $api->stocks(array_values(array_unique(array_column($posicoes, 'ticker'))),
            $nacional ? 'BRL' : 'USD');
        if (!$nacional) {
            $cambio = $api->fx()['USD'] ?? null;
        }
    }
    $resultado = patrimonioAvaliar($posicoes, $cotacoes, $nacional, $cambio['price'] ?? null);
    $resultado['cambio'] = $cambio;
    return $resultado;
}
