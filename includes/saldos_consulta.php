<?php
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

// Consulta única de saldos usada por Saldos e Patrimônio.
function saldosListarContas($conn, $usuario_id) {
$contas = [];

$stmt = $conn->prepare("
    SELECT
        cf.id,
        cf.nome,
        cf.instituicao,
        cf.icone,
        cf.tipo,
        cf.saldo_inicial,
        cf.data_saldo_inicial,
        cf.ativa,
        COUNT(mf.id) AS quantidade_movimentacoes,
        cf.saldo_inicial +
        COALESCE(
            SUM(
                CASE
                    WHEN mf.data < cf.data_saldo_inicial THEN 0
                    WHEN mf.tipo IN (
                        'entrada',
                        'transferencia_entrada'
                    ) THEN mf.valor
                    WHEN mf.tipo IN (
                        'saida',
                        'transferencia_saida'
                    ) THEN -mf.valor
                    ELSE 0
                END
            ),
            0
        ) AS saldo_atual
    FROM contas_financeiras cf
    LEFT JOIN movimentacoes_financeiras mf
        ON mf.conta_id = cf.id
        AND mf.usuario_id = cf.usuario_id
    WHERE cf.usuario_id = ?
    GROUP BY
        cf.id,
        cf.nome,
        cf.instituicao,
        cf.icone,
        cf.tipo,
        cf.saldo_inicial,
        cf.data_saldo_inicial,
        cf.ativa
    ORDER BY
        cf.ativa DESC,
        cf.nome ASC
");

$stmt->bind_param('i', $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

while ($row = $resultado->fetch_assoc()) {
    $contas[] = $row;
}

$stmt->close();

return $contas;
}
