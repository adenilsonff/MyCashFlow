<?php
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

function mcfModulosDisponiveis(): array
{
    return [
        'patrimonio' => [
            'nome' => 'Patrimônio',
            'pai' => null
        ],
        'despesas' => [
            'nome' => 'Despesas',
            'pai' => null
        ],
        'cartao' => [
            'nome' => 'Cartão de crédito',
            'pai' => 'despesas'
        ],
        'receitas' => [
            'nome' => 'Receitas',
            'pai' => null
        ],
        'investimentos' => [
            'nome' => 'Investimentos',
            'pai' => null
        ],
        'dividendos' => [
            'nome' => 'Dividendos',
            'pai' => 'investimentos'
        ],
        'daytrade' => [
            'nome' => 'Day Trade',
            'pai' => 'investimentos'
        ],
        'analise' => [
            'nome' => 'Análise',
            'pai' => null
        ],
        'relatorios' => [
            'nome' => 'Relatórios',
            'pai' => null
        ]
    ];
}

function mcfModuloAtivo(mysqli $conn, int $usuarioId, string $modulo): bool
{
    $modulos = mcfModulosDisponiveis();

    if (!isset($modulos[$modulo])) {
        return false;
    }

    $pai = $modulos[$modulo]['pai'];

    if ($pai !== null && !mcfModuloAtivo($conn, $usuarioId, $pai)) {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT habilitado
         FROM usuario_modulos
         WHERE usuario_id = ? AND modulo = ?
         LIMIT 1'
    );

    $stmt->bind_param('is', $usuarioId, $modulo);
    $stmt->execute();

    $resultado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$resultado) {
        return true;
    }

    return (int)$resultado['habilitado'] === 1;
}

function mcfModuloDaRota(string $rota): ?string
{
    $rotas = [
        '/views/patrimonio.php' => 'patrimonio',
        '/views/saldos.php' => 'patrimonio',
        '/views/reservas.php' => 'patrimonio',

        '/views/contas.php' => 'despesas',

        '/views/cartao.php' => 'cartao',

        '/views/rendas.php' => 'receitas',

        '/views/investimentos.php' => 'investimentos',
        '/views/investimentos_nacionais.php' => 'investimentos',
        '/views/investimentos_internacionais.php' => 'investimentos',

        '/views/dividendos.php' => 'dividendos',
        '/views/div_datacom.php' => 'dividendos',
        '/views/div_valor.php' => 'dividendos',
        '/views/div_compra.php' => 'dividendos',

        '/views/daytrade.php' => 'daytrade',
        '/views/daytrade/editar_corretora.php' => 'daytrade',

        '/views/analise.php' => 'analise',
        '/views/analise/api.php' => 'analise',

        '/views/relatorios/relatorios.php' => 'relatorios',
        '/views/relatorios/financeiro.php' => 'relatorios',
        '/views/relatorios/gastos.php' => 'relatorios',
        '/views/relatorios/receitas.php' => 'relatorios',
        '/views/relatorios/cartao.php' => 'relatorios',
        '/views/relatorios/investimentos.php' => 'relatorios',
        '/views/relatorios/proventos.php' => 'relatorios',
        '/views/relatorios/daytrade.php' => 'relatorios',
        '/views/relatorios/rel-acoes.php' => 'relatorios'
    ];

    return $rotas[$rota] ?? null;
}

function mcfExigirModuloAtivo(mysqli $conn, int $usuarioId, string $rota): void
{
    $modulo = mcfModuloDaRota($rota);

    if ($modulo === null) {
        return;
    }

    if (!mcfModuloAtivo($conn, $usuarioId, $modulo)) {
        mcfFalhar(
            403,
            'Este módulo está desativado. Você pode habilitá-lo novamente em Configuração.'
        );
    }
}
