<?php
// Regras compartilhadas com as carteiras; preservadas sem alteração.
function acoesTicker($ticker) {
    return preg_replace('/\.SA$/', '', strtoupper(trim($ticker)));
}

function acoesConsolidar(array $operacoes) {
    usort($operacoes, function ($a, $b) {
        return strcmp($a['data'], $b['data']) ?: ($a['id'] <=> $b['id']);
    });

    $posicoes = [];

    foreach ($operacoes as $op) {
        $ticker = acoesTicker($op['ticker']);
        $tipoAtivo = $op['tipo_ativo'] ?? '';
        $chave = $ticker . '|' . $tipoAtivo;

        if (!isset($posicoes[$chave])) {
            $posicoes[$chave] = [
                'ticker' => $ticker,
                'tipo_ativo' => $tipoAtivo,
                'quantidade_total' => 0,
                'total_investido' => 0.0,
                'valor_medio_ponderado' => 0.0,
                'logo' => null
            ];
        }

        $p = &$posicoes[$chave];
        $q = abs((int)$op['quantidade']);
        $preco = (float)$op['valor_unitario'];
        $tipoOperacao = $op['tipo_operacao'] ?? '';

        if (
            $q === 0 ||
            $preco <= 0 ||
            !is_finite($preco) ||
            !in_array($tipoOperacao, ['compra', 'venda'], true) ||
            !in_array($tipoAtivo, ['acao', 'fii', 'etf', 'bdr'], true) ||
            ($tipoOperacao === 'compra' && $op['quantidade'] < 0)
        ) {
            throw new DomainException(
                "Operação inválida no histórico de " . $ticker . "."
            );
        }

        if ($tipoOperacao === 'compra') {
            $p['total_investido'] += $q * $preco;
            $p['quantidade_total'] += $q;
            $p['valor_medio_ponderado'] =
                $p['total_investido'] / $p['quantidade_total'];
        } else {
            if ($q > $p['quantidade_total']) {
                throw new DomainException(
                    "Venda de " . $q . " unidades de " . $ticker .
                    " em " . date('d/m/Y', strtotime($op['data'])) .
                    " excede a posição disponível (" .
                    $p['quantidade_total'] . " unidades)."
                );
            }

            $p['quantidade_total'] -= $q;
            $p['total_investido'] =
                $p['quantidade_total'] * $p['valor_medio_ponderado'];

            if ($p['quantidade_total'] === 0) {
                $p['total_investido'] = 0.0;
                $p['valor_medio_ponderado'] = 0.0;
            }
        }

        if (
            !empty($op['logo']) &&
            filter_var($op['logo'], FILTER_VALIDATE_URL) &&
            strtolower(parse_url($op['logo'], PHP_URL_SCHEME) ?? '') === 'https'
        ) {
            $p['logo'] = $op['logo'];
        }

        unset($p);
    }

    ksort($posicoes);

    return array_filter(
        $posicoes,
        function ($p) {
            return $p['quantidade_total'] > 0;
        }
    );
}

function internacionalQuantidade($v) {
    $v = (string)$v;

    if (strpos($v, '.') !== false) {
        $v = rtrim(rtrim($v, '0'), '.');
    }

    return str_replace('.', ',', $v);
}

function internacionalConsolidar(array $operacoes) {
    usort(
        $operacoes,
        function ($a, $b) {
            return strcmp($a['data'], $b['data']) ?:
                ($a['id'] <=> $b['id']);
        }
    );

    $posicoes = [];

    foreach ($operacoes as $op) {
        $ticker =
            strtoupper(trim($op['ticker']));

        $tipoAtivo =
            $op['tipo_ativo'] ?? '';

        $chave =
            $ticker . '|' . $tipoAtivo;

        if (!isset($posicoes[$chave])) {
            $posicoes[$chave] = [
                'ticker' => $ticker,
                'tipo_ativo' => $tipoAtivo,
                'quantidade_total' => '0',
                'total_investido_usd' => '0',
                'valor_medio_ponderado' => '0',
                'logo' => null
            ];
        }

        $p = &$posicoes[$chave];

        $q =
            ltrim(
                (string)$op['quantidade'],
                '-'
            );

        $preco =
            (string)$op['valor_unitario'];

        $tipoOperacao =
            $op['tipo_operacao'] ?? '';

        if (
            !in_array(
                $tipoAtivo,
                ['stock', 'etf', 'reit', 'adr'],
                true
            ) ||
            !in_array(
                $tipoOperacao,
                ['compra', 'venda'],
                true
            ) ||
            bccomp($q, '0', 8) <= 0 ||
            bccomp($preco, '0', 4) <= 0 ||
            (
                $tipoOperacao === 'compra' &&
                bccomp(
                    $op['quantidade'],
                    '0',
                    8
                ) < 0
            )
        ) {
            throw new DomainException(
                'Operação inválida no histórico de ' .
                $ticker . '.'
            );
        }

        if ($tipoOperacao === 'compra') {
            $p['total_investido_usd'] =
                bcadd(
                    $p['total_investido_usd'],
                    bcmul($q, $preco, 24),
                    24
                );

            $p['quantidade_total'] =
                bcadd(
                    $p['quantidade_total'],
                    $q,
                    8
                );

            $p['valor_medio_ponderado'] =
                bcdiv(
                    $p['total_investido_usd'],
                    $p['quantidade_total'],
                    24
                );

        } else {
            if (
                bccomp(
                    $q,
                    $p['quantidade_total'],
                    8
                ) > 0
            ) {
                throw new DomainException(
                    'Venda de ' .
                    internacionalQuantidade($q) .
                    ' unidades de ' .
                    $ticker .
                    ' em ' .
                    date(
                        'd/m/Y',
                        strtotime($op['data'])
                    ) .
                    ' excede a posição disponível (' .
                    internacionalQuantidade(
                        $p['quantidade_total']
                    ) .
                    ' unidades).'
                );
            }

            $p['quantidade_total'] =
                bcsub(
                    $p['quantidade_total'],
                    $q,
                    8
                );

            $p['total_investido_usd'] =
                bcmul(
                    $p['quantidade_total'],
                    $p['valor_medio_ponderado'],
                    24
                );

            if (
                bccomp(
                    $p['quantidade_total'],
                    '0',
                    8
                ) === 0
            ) {
                $p['total_investido_usd'] = '0';
                $p['valor_medio_ponderado'] = '0';
            }
        }

        if (
            !empty($op['logo']) &&
            filter_var(
                $op['logo'],
                FILTER_VALIDATE_URL
            ) &&
            strtolower(
                parse_url(
                    $op['logo'],
                    PHP_URL_SCHEME
                ) ?? ''
            ) === 'https'
        ) {
            $p['logo'] = $op['logo'];
        }

        unset($p);
    }

    ksort($posicoes);

    return array_filter(
        $posicoes,
        function ($p) {
            return bccomp(
                $p['quantidade_total'],
                '0',
                8
            ) > 0;
        }
    );
}

