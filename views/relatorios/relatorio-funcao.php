<?php if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; } ?>
<?php
function getResumo($conn, $tabela) {
    $sql = "SELECT 
                ticker,
                SUM(quantidade) AS quantidade_total,
                SUM(quantidade * valor_unitario) AS total_investido,
                MAX(valor_mercado) AS valor_mercado_atual,
                (MAX(valor_mercado) * SUM(quantidade)) - SUM(quantidade * valor_unitario) AS resultado
            FROM $tabela
            GROUP BY ticker
            HAVING quantidade_total > 0";
    return $conn->query($sql);
}

function getDetalhes($conn, $tabela) {
    $sql = "SELECT ticker, data, quantidade, valor_unitario, valor_mercado
            FROM $tabela
            ORDER BY data ASC";
    return $conn->query($sql);
}
?>
