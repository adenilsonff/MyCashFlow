<?php
include __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

if (empty($_SESSION['csrf_saldos'])) {
    $_SESSION['csrf_saldos'] = bin2hex(random_bytes(32));
}

$csrf = $_SESSION['csrf_saldos'];

function normalizarValorSaldo($valor) {
    $valor = trim((string)$valor);

    if ($valor === '') {
        return 0;
    }

    $valor = str_replace(['R$', ' '], '', $valor);

    if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } else {
        $valor = str_replace(',', '.', $valor);
    }

    return is_numeric($valor) ? (float)$valor : 0;
}

function dataSaldoValida($data) {
    $obj = DateTime::createFromFormat('Y-m-d', $data);

    return $obj && $obj->format('Y-m-d') === $data;
}

function nomeTipoConta($tipo) {
    $tipos = [
        'corrente' => 'Conta corrente',
        'poupanca' => 'Poupança',
        'carteira' => 'Carteira / Dinheiro',
        'corretora' => 'Corretora',
        'outros' => 'Outros'
    ];

    return $tipos[$tipo] ?? $tipo;
}

function instituicoesSaldo() {
    return [
        'nubank' => [
            'nome' => 'Nubank',
            'icone' => 'nubank.png'
        ],
        'banco_do_brasil' => [
            'nome' => 'Banco do Brasil',
            'icone' => 'banco-do-brasil.png'
        ],
        'caixa' => [
            'nome' => 'Caixa',
            'icone' => 'caixa.png'
        ],
        'itau' => [
            'nome' => 'Itaú',
            'icone' => 'itau.png'
        ],
        'bradesco' => [
            'nome' => 'Bradesco',
            'icone' => 'bradesco.png'
        ],
        'santander' => [
            'nome' => 'Santander',
            'icone' => 'santander.png'
        ],
        'inter' => [
            'nome' => 'Inter',
            'icone' => 'inter.png'
        ],
        'c6_bank' => [
            'nome' => 'C6 Bank',
            'icone' => 'c6-bank.png'
        ],
        'btg_pactual' => [
            'nome' => 'BTG Pactual',
            'icone' => 'btg-pactual.png'
        ],
        'xp' => [
            'nome' => 'XP',
            'icone' => 'xp.png'
        ],
        'rico' => [
            'nome' => 'Rico',
            'icone' => 'rico.png'
        ],
        'mercado_pago' => [
            'nome' => 'Mercado Pago',
            'icone' => 'mercado-pago.png'
        ],
        'picpay' => [
            'nome' => 'PicPay',
            'icone' => 'picpay.png'
        ],
        'pagbank' => [
            'nome' => 'PagBank',
            'icone' => 'pagbank.png'
        ]
    ];
}

function resolverInstituicaoSaldo($chave, $outra, $tipo) {
    if ($tipo === 'carteira') {
        return [
            'instituicao' => 'Carteira / Dinheiro',
            'icone' => 'carteira.png'
        ];
    }

    $instituicoes = instituicoesSaldo();

    if (isset($instituicoes[$chave])) {
        return [
            'instituicao' => $instituicoes[$chave]['nome'],
            'icone' => $instituicoes[$chave]['icone']
        ];
    }

    $outra = trim($outra);

    if ($outra === '') {
        return [
            'instituicao' => null,
            'icone' => null
        ];
    }

    return [
        'instituicao' => $outra,
        'icone' => 'generico.png'
    ];
}

function chaveInstituicaoSaldo($instituicao) {
    $instituicoes = instituicoesSaldo();

    foreach ($instituicoes as $chave => $dados) {
        if ($dados['nome'] === $instituicao) {
            return $chave;
        }
    }

    return 'outra';
}

function urlIconeSaldo($icone) {
    $icone = basename((string)$icone);

    if ($icone === '') {
        return null;
    }

    $arquivo = __DIR__ . '/../assets/img/bancos/' . $icone;

    if (!is_file($arquivo)) {
        return null;
    }

    return '/MyCashFlow/assets/img/bancos/' . rawurlencode($icone);
}

function iniciaisInstituicaoSaldo($instituicao, $nome) {
    $texto = trim((string)$instituicao);

    if ($texto === '') {
        $texto = trim((string)$nome);
    }

    if ($texto === '') {
        return 'C';
    }

    $partes = preg_split('/\s+/u', $texto);
    $iniciais = '';

    foreach ($partes as $parte) {
        if ($parte === '') {
            continue;
        }

        $iniciais .= mb_strtoupper(
            mb_substr($parte, 0, 1, 'UTF-8'),
            'UTF-8'
        );

        if (mb_strlen($iniciais, 'UTF-8') >= 2) {
            break;
        }
    }

    return $iniciais ?: 'C';
}

function descricaoBaseTransferencia($descricao, $nomeRelacionado, $seta) {
    $sufixo = ' ' . $seta . ' ' . $nomeRelacionado;

    if (
        $nomeRelacionado !== '' &&
        substr($descricao, -strlen($sufixo)) === $sufixo
    ) {
        return substr($descricao, 0, -strlen($sufixo));
    }

    return $descricao;
}

function redirecionarSaldos($mensagem, $tipo = 'sucesso') {
    header(
        'Location: saldos.php?msg=' .
        urlencode($mensagem) .
        '&tipo_msg=' .
        urlencode($tipo)
    );
    exit;
}

$tiposContaPermitidos = [
    'corrente',
    'poupanca',
    'carteira',
    'corretora',
    'outros'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenRecebido = $_POST['csrf'] ?? '';

    if (
        !is_string($tokenRecebido) ||
        !hash_equals($_SESSION['csrf_saldos'], $tokenRecebido)
    ) {
        redirecionarSaldos(
            'A sessão do formulário expirou. Tente novamente.',
            'erro'
        );
    }

    $acao = trim($_POST['acao'] ?? '');

    if ($acao === 'nova_conta') {
        $nome = trim($_POST['nome'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $instituicaoChave = trim($_POST['instituicao'] ?? '');
        $outraInstituicao = trim($_POST['outra_instituicao'] ?? '');
        $saldoInicial = normalizarValorSaldo($_POST['saldo_inicial'] ?? 0);
        $dataInicial = trim($_POST['data_saldo_inicial'] ?? '');

        if ($nome === '') {
            redirecionarSaldos('Informe o nome da conta.', 'erro');
        }

        if (!in_array($tipo, $tiposContaPermitidos, true)) {
            redirecionarSaldos('Tipo de conta inválido.', 'erro');
        }

        if (!dataSaldoValida($dataInicial)) {
            redirecionarSaldos('Informe uma data inicial válida.', 'erro');
        }

        $dadosInstituicao = resolverInstituicaoSaldo(
            $instituicaoChave,
            $outraInstituicao,
            $tipo
        );

        $instituicao = $dadosInstituicao['instituicao'];
        $icone = $dadosInstituicao['icone'];

        $stmt = $conn->prepare("
            INSERT INTO contas_financeiras
            (
                usuario_id,
                nome,
                instituicao,
                icone,
                tipo,
                saldo_inicial,
                data_saldo_inicial,
                ativa
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        if (!$stmt) {
            redirecionarSaldos(
                'Não foi possível preparar o cadastro da conta.',
                'erro'
            );
        }

        $stmt->bind_param(
            'issssds',
            $usuario_id,
            $nome,
            $instituicao,
            $icone,
            $tipo,
            $saldoInicial,
            $dataInicial
        );

        if (!$stmt->execute()) {
            $stmt->close();
            redirecionarSaldos(
                'Não foi possível cadastrar a conta.',
                'erro'
            );
        }

        $stmt->close();

        redirecionarSaldos('Conta cadastrada com sucesso.');
    }

    if ($acao === 'editar_conta') {
        $contaId = (int)($_POST['conta_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $instituicaoChave = trim($_POST['instituicao'] ?? '');
        $outraInstituicao = trim($_POST['outra_instituicao'] ?? '');
        $saldoInicial = normalizarValorSaldo($_POST['saldo_inicial'] ?? 0);
        $dataInicial = trim($_POST['data_saldo_inicial'] ?? '');

        if ($contaId <= 0 || $nome === '') {
            redirecionarSaldos('Dados da conta inválidos.', 'erro');
        }

        if (!in_array($tipo, $tiposContaPermitidos, true)) {
            redirecionarSaldos('Tipo de conta inválido.', 'erro');
        }

        if (!dataSaldoValida($dataInicial)) {
            redirecionarSaldos('Informe uma data inicial válida.', 'erro');
        }

        $stmt = $conn->prepare("
            SELECT
                cf.id,
                MIN(mf.data) AS primeira_movimentacao
            FROM contas_financeiras cf
            LEFT JOIN movimentacoes_financeiras mf
                ON mf.conta_id = cf.id
                AND mf.usuario_id = cf.usuario_id
            WHERE cf.id = ?
            AND cf.usuario_id = ?
            GROUP BY cf.id
            LIMIT 1
        ");

        $stmt->bind_param('ii', $contaId, $usuario_id);
        $stmt->execute();
        $contaAtual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$contaAtual) {
            redirecionarSaldos('Conta financeira não encontrada.', 'erro');
        }

        if (
            !empty($contaAtual['primeira_movimentacao']) &&
            $dataInicial > $contaAtual['primeira_movimentacao']
        ) {
            redirecionarSaldos(
                'A data inicial não pode ser posterior à primeira movimentação da conta.',
                'erro'
            );
        }

        $dadosInstituicao = resolverInstituicaoSaldo(
            $instituicaoChave,
            $outraInstituicao,
            $tipo
        );

        $instituicao = $dadosInstituicao['instituicao'];
        $icone = $dadosInstituicao['icone'];

        $stmt = $conn->prepare("
            UPDATE contas_financeiras
            SET
                nome = ?,
                instituicao = ?,
                icone = ?,
                tipo = ?,
                saldo_inicial = ?,
                data_saldo_inicial = ?
            WHERE id = ?
            AND usuario_id = ?
        ");

        $stmt->bind_param(
            'ssssdsii',
            $nome,
            $instituicao,
            $icone,
            $tipo,
            $saldoInicial,
            $dataInicial,
            $contaId,
            $usuario_id
        );

        if (!$stmt->execute()) {
            $stmt->close();
            redirecionarSaldos(
                'Não foi possível atualizar a conta.',
                'erro'
            );
        }

        $stmt->close();

        redirecionarSaldos('Conta atualizada com sucesso.');
    }

    if ($acao === 'inativar_conta' || $acao === 'reativar_conta') {
        $contaId = (int)($_POST['conta_id'] ?? 0);

        if ($contaId <= 0) {
            redirecionarSaldos('Conta inválida.', 'erro');
        }

        $ativa = $acao === 'reativar_conta' ? 1 : 0;

        $stmt = $conn->prepare("
            UPDATE contas_financeiras
            SET ativa = ?
            WHERE id = ?
            AND usuario_id = ?
        ");

        $stmt->bind_param(
            'iii',
            $ativa,
            $contaId,
            $usuario_id
        );

        $stmt->execute();
        $stmt->close();

        redirecionarSaldos(
            $ativa
                ? 'Conta reativada com sucesso.'
                : 'Conta inativada com sucesso.'
        );
    }

    if ($acao === 'excluir_conta') {
        $contaId = (int)($_POST['conta_id'] ?? 0);

        if ($contaId <= 0) {
            redirecionarSaldos('Conta inválida.', 'erro');
        }

        $stmt = $conn->prepare("
            SELECT
                cf.id,
                COUNT(mf.id) AS quantidade_movimentacoes
            FROM contas_financeiras cf
            LEFT JOIN movimentacoes_financeiras mf
                ON mf.conta_id = cf.id
                AND mf.usuario_id = cf.usuario_id
            WHERE cf.id = ?
            AND cf.usuario_id = ?
            GROUP BY cf.id
            LIMIT 1
        ");

        $stmt->bind_param('ii', $contaId, $usuario_id);
        $stmt->execute();
        $conta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$conta) {
            redirecionarSaldos('Conta não encontrada.', 'erro');
        }

        if ((int)$conta['quantidade_movimentacoes'] > 0) {
            redirecionarSaldos(
                'Esta conta possui histórico e não pode ser excluída. Inative-a para preservar os registros.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            DELETE FROM contas_financeiras
            WHERE id = ?
            AND usuario_id = ?
        ");

        $stmt->bind_param('ii', $contaId, $usuario_id);
        $stmt->execute();
        $stmt->close();

        redirecionarSaldos('Conta excluída com sucesso.');
    }

    if ($acao === 'nova_movimentacao') {
        $contaId = (int)($_POST['conta_id'] ?? 0);
        $tipo = trim($_POST['tipo_movimentacao'] ?? '');
        $data = trim($_POST['data'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = normalizarValorSaldo($_POST['valor'] ?? 0);

        if (!in_array($tipo, ['entrada', 'saida'], true)) {
            redirecionarSaldos('Tipo de movimentação inválido.', 'erro');
        }

        if ($contaId <= 0 || $descricao === '' || $valor <= 0) {
            redirecionarSaldos(
                'Preencha corretamente os dados da movimentação.',
                'erro'
            );
        }

        if (!dataSaldoValida($data)) {
            redirecionarSaldos('Informe uma data válida.', 'erro');
        }

        $stmt = $conn->prepare("
            SELECT id, data_saldo_inicial
            FROM contas_financeiras
            WHERE id = ?
            AND usuario_id = ?
            AND ativa = 1
            LIMIT 1
        ");

        $stmt->bind_param('ii', $contaId, $usuario_id);
        $stmt->execute();
        $conta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$conta) {
            redirecionarSaldos('Conta financeira inválida.', 'erro');
        }

        if ($data < $conta['data_saldo_inicial']) {
            redirecionarSaldos(
                'A movimentação não pode ser anterior ao saldo inicial da conta.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            INSERT INTO movimentacoes_financeiras
            (
                usuario_id,
                conta_id,
                tipo,
                data,
                descricao,
                valor,
                origem_modulo
            )
            VALUES (?, ?, ?, ?, ?, ?, 'manual')
        ");

        $stmt->bind_param(
            'iisssd',
            $usuario_id,
            $contaId,
            $tipo,
            $data,
            $descricao,
            $valor
        );

        if (!$stmt->execute()) {
            $stmt->close();
            redirecionarSaldos(
                'Não foi possível registrar a movimentação.',
                'erro'
            );
        }

        $stmt->close();

        redirecionarSaldos('Movimentação registrada com sucesso.');
    }

    if ($acao === 'editar_movimentacao') {
        $movimentacaoId = (int)($_POST['movimentacao_id'] ?? 0);
        $tipo = trim($_POST['tipo_movimentacao'] ?? '');
        $data = trim($_POST['data'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = normalizarValorSaldo($_POST['valor'] ?? 0);

        if (
            $movimentacaoId <= 0 ||
            !in_array($tipo, ['entrada', 'saida'], true) ||
            $descricao === '' ||
            $valor <= 0 ||
            !dataSaldoValida($data)
        ) {
            redirecionarSaldos(
                'Dados da movimentação inválidos.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            SELECT
                mf.id,
                cf.data_saldo_inicial
            FROM movimentacoes_financeiras mf
            INNER JOIN contas_financeiras cf
                ON cf.id = mf.conta_id
                AND cf.usuario_id = mf.usuario_id
            WHERE mf.id = ?
            AND mf.usuario_id = ?
            AND mf.origem_modulo = 'manual'
            AND mf.transferencia_id IS NULL
            AND mf.tipo IN ('entrada', 'saida')
            LIMIT 1
        ");

        $stmt->bind_param(
            'ii',
            $movimentacaoId,
            $usuario_id
        );

        $stmt->execute();
        $movimentacao = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$movimentacao) {
            redirecionarSaldos(
                'Esta movimentação não pode ser editada.',
                'erro'
            );
        }

        if ($data < $movimentacao['data_saldo_inicial']) {
            redirecionarSaldos(
                'A movimentação não pode ser anterior ao saldo inicial da conta.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            UPDATE movimentacoes_financeiras
            SET
                tipo = ?,
                data = ?,
                descricao = ?,
                valor = ?
            WHERE id = ?
            AND usuario_id = ?
            AND origem_modulo = 'manual'
            AND transferencia_id IS NULL
        ");

        $stmt->bind_param(
            'sssdii',
            $tipo,
            $data,
            $descricao,
            $valor,
            $movimentacaoId,
            $usuario_id
        );

        $stmt->execute();
        $stmt->close();

        redirecionarSaldos('Movimentação atualizada com sucesso.');
    }

    if ($acao === 'excluir_movimentacao') {
        $movimentacaoId = (int)($_POST['movimentacao_id'] ?? 0);

        if ($movimentacaoId <= 0) {
            redirecionarSaldos('Movimentação inválida.', 'erro');
        }

        $stmt = $conn->prepare("
            DELETE FROM movimentacoes_financeiras
            WHERE id = ?
            AND usuario_id = ?
            AND origem_modulo = 'manual'
            AND transferencia_id IS NULL
            AND tipo IN ('entrada', 'saida')
        ");

        $stmt->bind_param(
            'ii',
            $movimentacaoId,
            $usuario_id
        );

        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();

            redirecionarSaldos(
                'Esta movimentação não pode ser excluída.',
                'erro'
            );
        }

        $stmt->close();

        redirecionarSaldos('Movimentação excluída com sucesso.');
    }

    if ($acao === 'transferir') {
        $contaOrigem = (int)($_POST['conta_origem'] ?? 0);
        $contaDestino = (int)($_POST['conta_destino'] ?? 0);
        $data = trim($_POST['data'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = normalizarValorSaldo($_POST['valor'] ?? 0);

        if (
            $contaOrigem <= 0 ||
            $contaDestino <= 0 ||
            $contaOrigem === $contaDestino
        ) {
            redirecionarSaldos(
                'Selecione duas contas diferentes.',
                'erro'
            );
        }

        if ($descricao === '') {
            $descricao = 'Transferência';
        }

        if ($valor <= 0 || !dataSaldoValida($data)) {
            redirecionarSaldos(
                'Informe uma data e um valor válidos.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            SELECT id, nome, data_saldo_inicial
            FROM contas_financeiras
            WHERE usuario_id = ?
            AND ativa = 1
            AND id IN (?, ?)
        ");

        $stmt->bind_param(
            'iii',
            $usuario_id,
            $contaOrigem,
            $contaDestino
        );

        $stmt->execute();
        $resultado = $stmt->get_result();

        $contasTransferencia = [];

        while ($row = $resultado->fetch_assoc()) {
            $contasTransferencia[(int)$row['id']] = $row;
        }

        $stmt->close();

        if (
            !isset($contasTransferencia[$contaOrigem]) ||
            !isset($contasTransferencia[$contaDestino])
        ) {
            redirecionarSaldos(
                'Conta de origem ou destino inválida.',
                'erro'
            );
        }

        if (
            $data < $contasTransferencia[$contaOrigem]['data_saldo_inicial'] ||
            $data < $contasTransferencia[$contaDestino]['data_saldo_inicial']
        ) {
            redirecionarSaldos(
                'A transferência não pode ser anterior ao saldo inicial das contas.',
                'erro'
            );
        }

        $transferenciaId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );

        $descricaoSaida =
            $descricao .
            ' → ' .
            $contasTransferencia[$contaDestino]['nome'];

        $descricaoEntrada =
            $descricao .
            ' ← ' .
            $contasTransferencia[$contaOrigem]['nome'];

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO movimentacoes_financeiras
                (
                    usuario_id,
                    conta_id,
                    tipo,
                    data,
                    descricao,
                    valor,
                    transferencia_id,
                    origem_modulo
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 'manual')
            ");

            if (!$stmt) {
                throw new Exception('Erro ao preparar transferência.');
            }

            $tipoSaida = 'transferencia_saida';

            $stmt->bind_param(
                'iisssds',
                $usuario_id,
                $contaOrigem,
                $tipoSaida,
                $data,
                $descricaoSaida,
                $valor,
                $transferenciaId
            );

            if (!$stmt->execute()) {
                throw new Exception('Erro na saída da transferência.');
            }

            $tipoEntrada = 'transferencia_entrada';

            $stmt->bind_param(
                'iisssds',
                $usuario_id,
                $contaDestino,
                $tipoEntrada,
                $data,
                $descricaoEntrada,
                $valor,
                $transferenciaId
            );

            if (!$stmt->execute()) {
                throw new Exception('Erro na entrada da transferência.');
            }

            $stmt->close();

            $conn->commit();

        } catch (Throwable $e) {
            $conn->rollback();

            redirecionarSaldos(
                'Não foi possível concluir a transferência.',
                'erro'
            );
        }

        redirecionarSaldos('Transferência realizada com sucesso.');
    }

    if ($acao === 'editar_transferencia') {
        $transferenciaId = trim($_POST['transferencia_id'] ?? '');
        $data = trim($_POST['data'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $valor = normalizarValorSaldo($_POST['valor'] ?? 0);

        if (
            $transferenciaId === '' ||
            $descricao === '' ||
            $valor <= 0 ||
            !dataSaldoValida($data)
        ) {
            redirecionarSaldos(
                'Dados da transferência inválidos.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            SELECT
                mf.id,
                mf.tipo,
                mf.conta_id,
                cf.nome,
                cf.data_saldo_inicial
            FROM movimentacoes_financeiras mf
            INNER JOIN contas_financeiras cf
                ON cf.id = mf.conta_id
                AND cf.usuario_id = mf.usuario_id
            WHERE mf.usuario_id = ?
            AND mf.transferencia_id = ?
            AND mf.origem_modulo = 'manual'
            AND mf.tipo IN (
                'transferencia_saida',
                'transferencia_entrada'
            )
            ORDER BY mf.id
        ");

        $stmt->bind_param(
            'is',
            $usuario_id,
            $transferenciaId
        );

        $stmt->execute();
        $resultado = $stmt->get_result();

        $linhas = [];

        while ($row = $resultado->fetch_assoc()) {
            $linhas[$row['tipo']] = $row;
        }

        $stmt->close();

        if (
            count($linhas) !== 2 ||
            !isset($linhas['transferencia_saida']) ||
            !isset($linhas['transferencia_entrada'])
        ) {
            redirecionarSaldos(
                'A transferência está incompleta e não pode ser alterada automaticamente.',
                'erro'
            );
        }

        if (
            $data < $linhas['transferencia_saida']['data_saldo_inicial'] ||
            $data < $linhas['transferencia_entrada']['data_saldo_inicial']
        ) {
            redirecionarSaldos(
                'A transferência não pode ser anterior ao saldo inicial das contas.',
                'erro'
            );
        }

        $descricaoSaida =
            $descricao .
            ' → ' .
            $linhas['transferencia_entrada']['nome'];

        $descricaoEntrada =
            $descricao .
            ' ← ' .
            $linhas['transferencia_saida']['nome'];

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                UPDATE movimentacoes_financeiras
                SET
                    data = ?,
                    descricao = ?,
                    valor = ?
                WHERE id = ?
                AND usuario_id = ?
                AND transferencia_id = ?
            ");

            if (!$stmt) {
                throw new Exception('Erro ao preparar atualização.');
            }

            $idSaida = (int)$linhas['transferencia_saida']['id'];

            $stmt->bind_param(
                'ssdiis',
                $data,
                $descricaoSaida,
                $valor,
                $idSaida,
                $usuario_id,
                $transferenciaId
            );

            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar saída.');
            }

            $idEntrada = (int)$linhas['transferencia_entrada']['id'];

            $stmt->bind_param(
                'ssdiis',
                $data,
                $descricaoEntrada,
                $valor,
                $idEntrada,
                $usuario_id,
                $transferenciaId
            );

            if (!$stmt->execute()) {
                throw new Exception('Erro ao atualizar entrada.');
            }

            $stmt->close();

            $conn->commit();

        } catch (Throwable $e) {
            $conn->rollback();

            redirecionarSaldos(
                'Não foi possível atualizar a transferência.',
                'erro'
            );
        }

        redirecionarSaldos('Transferência atualizada com sucesso.');
    }

    if ($acao === 'excluir_transferencia') {
        $transferenciaId = trim($_POST['transferencia_id'] ?? '');

        if ($transferenciaId === '') {
            redirecionarSaldos('Transferência inválida.', 'erro');
        }

        $stmt = $conn->prepare("
            SELECT id, tipo
            FROM movimentacoes_financeiras
            WHERE usuario_id = ?
            AND transferencia_id = ?
            AND origem_modulo = 'manual'
            AND tipo IN (
                'transferencia_saida',
                'transferencia_entrada'
            )
        ");

        $stmt->bind_param(
            'is',
            $usuario_id,
            $transferenciaId
        );

        $stmt->execute();
        $resultado = $stmt->get_result();

        $tiposEncontrados = [];

        while ($row = $resultado->fetch_assoc()) {
            $tiposEncontrados[] = $row['tipo'];
        }

        $stmt->close();

        if (
            count($tiposEncontrados) !== 2 ||
            !in_array('transferencia_saida', $tiposEncontrados, true) ||
            !in_array('transferencia_entrada', $tiposEncontrados, true)
        ) {
            redirecionarSaldos(
                'A transferência está incompleta e não pode ser excluída automaticamente.',
                'erro'
            );
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                DELETE FROM movimentacoes_financeiras
                WHERE usuario_id = ?
                AND transferencia_id = ?
                AND origem_modulo = 'manual'
                AND tipo IN (
                    'transferencia_saida',
                    'transferencia_entrada'
                )
            ");

            if (!$stmt) {
                throw new Exception('Erro ao preparar exclusão.');
            }

            $stmt->bind_param(
                'is',
                $usuario_id,
                $transferenciaId
            );

            if (!$stmt->execute() || $stmt->affected_rows !== 2) {
                throw new Exception('Transferência incompleta.');
            }

            $stmt->close();

            $conn->commit();

        } catch (Throwable $e) {
            $conn->rollback();

            redirecionarSaldos(
                'Não foi possível excluir a transferência.',
                'erro'
            );
        }

        redirecionarSaldos('Transferência excluída com sucesso.');
    }
}

require_once __DIR__ . '/../includes/saldos_consulta.php';
$contas = saldosListarContas($conn, $usuario_id);

$saldoTotal = 0;
$quantidadeAtivas = 0;

foreach ($contas as $conta) {
    if ((int)$conta['ativa'] === 1) {
        $saldoTotal += (float)$conta['saldo_atual'];
        $quantidadeAtivas++;
    }
}

$movimentacoesNormais = [];

$stmt = $conn->prepare("
    SELECT
        mf.id,
        mf.data,
        mf.descricao,
        mf.valor,
        mf.tipo,
        mf.origem_modulo,
        cf.nome AS conta_nome
    FROM movimentacoes_financeiras mf
    INNER JOIN contas_financeiras cf
        ON cf.id = mf.conta_id
        AND cf.usuario_id = mf.usuario_id
    WHERE mf.usuario_id = ?
    AND mf.transferencia_id IS NULL
    ORDER BY mf.data DESC, mf.id DESC
    LIMIT 150
");

$stmt->bind_param('i', $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

while ($row = $resultado->fetch_assoc()) {
    $row['registro_tipo'] = 'movimentacao';
    $row['ordem_id'] = (int)$row['id'];
    $movimentacoesNormais[] = $row;
}

$stmt->close();

$transferencias = [];

$stmt = $conn->prepare("
    SELECT
        saida.id AS ordem_id,
        saida.transferencia_id,
        saida.data,
        saida.descricao AS descricao_saida,
        saida.valor,
        origem.nome AS conta_origem,
        destino.nome AS conta_destino
    FROM movimentacoes_financeiras saida
    INNER JOIN movimentacoes_financeiras entrada
        ON entrada.usuario_id = saida.usuario_id
        AND entrada.transferencia_id = saida.transferencia_id
        AND entrada.tipo = 'transferencia_entrada'
    INNER JOIN contas_financeiras origem
        ON origem.id = saida.conta_id
        AND origem.usuario_id = saida.usuario_id
    INNER JOIN contas_financeiras destino
        ON destino.id = entrada.conta_id
        AND destino.usuario_id = entrada.usuario_id
    WHERE saida.usuario_id = ?
    AND saida.tipo = 'transferencia_saida'
    AND saida.transferencia_id IS NOT NULL
    ORDER BY saida.data DESC, saida.id DESC
    LIMIT 150
");

$stmt->bind_param('i', $usuario_id);
$stmt->execute();

$resultado = $stmt->get_result();

while ($row = $resultado->fetch_assoc()) {
    $row['registro_tipo'] = 'transferencia';

    $row['descricao'] = descricaoBaseTransferencia(
        $row['descricao_saida'],
        $row['conta_destino'],
        '→'
    );

    $transferencias[] = $row;
}

$stmt->close();

$historico = array_merge(
    $movimentacoesNormais,
    $transferencias
);

usort(
    $historico,
    function ($a, $b) {
        $comparacaoData = strcmp($b['data'], $a['data']);

        if ($comparacaoData !== 0) {
            return $comparacaoData;
        }

        return (int)$b['ordem_id'] <=> (int)$a['ordem_id'];
    }
);

$historico = array_slice($historico, 0, 100);

$msg = trim($_GET['msg'] ?? '');
$tipoMsg = trim($_GET['tipo_msg'] ?? 'sucesso');
$hoje = date('Y-m-d');
$instituicoes = instituicoesSaldo();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Saldos</title>

    <link
        rel="stylesheet"
        href="/MyCashFlow/assets/css/style-saldos.css?v=2"
    >
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<main class="saldos-layout">

    <div class="saldos-cabecalho">

        <div>
            <h1>Contas e Saldos</h1>

            <p>
                Controle do dinheiro disponível nas suas contas financeiras.
            </p>
        </div>

        <div class="saldos-acoes">

            <button
                type="button"
                class="saldos-btn"
                data-modal="modal-nova-conta"
            >
                Nova conta
            </button>

            <button
                type="button"
                class="saldos-btn"
                data-modal="modal-movimentacao"
                <?= $quantidadeAtivas === 0 ? 'disabled' : '' ?>
            >
                Nova movimentação
            </button>

            <button
                type="button"
                class="saldos-btn"
                data-modal="modal-transferencia"
                <?= $quantidadeAtivas < 2 ? 'disabled' : '' ?>
            >
                Transferir
            </button>

        </div>

    </div>

    <?php if ($msg !== ''): ?>

        <div class="saldos-mensagem <?= $tipoMsg === 'erro' ? 'erro' : 'sucesso' ?>">
            <?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php endif; ?>

    <section class="saldos-secao">

        <div class="saldos-titulo">
            <h2>Posição financeira</h2>

            <span>
                Saldos calculados a partir do saldo inicial e das movimentações.
            </span>
        </div>

        <div class="saldos-resumo">

            <div class="saldos-card saldos-card-total">
                <span>Saldo financeiro total</span>

                <strong class="<?= $saldoTotal > 0 ? 'positivo' : ($saldoTotal < 0 ? 'negativo' : 'neutro') ?>">
                    R$ <?= number_format($saldoTotal, 2, ',', '.') ?>
                </strong>
            </div>

            <div class="saldos-card">
                <span>Contas ativas</span>
                <strong><?= $quantidadeAtivas ?></strong>
            </div>

        </div>

    </section>

    <section class="saldos-secao">

        <div class="saldos-titulo">
            <h2>Contas financeiras</h2>
            <span>Dinheiro disponível por conta.</span>
        </div>

        <?php if (!empty($contas)): ?>

            <div class="saldos-contas-grid">

                <?php foreach ($contas as $conta): ?>

                    <?php
                    $urlIcone = urlIconeSaldo($conta['icone']);

                    $chaveInstituicao = chaveInstituicaoSaldo(
                        $conta['instituicao']
                    );

                    $outraInstituicao = '';

                    if (
                        $chaveInstituicao === 'outra' &&
                        !empty($conta['instituicao']) &&
                        $conta['tipo'] !== 'carteira'
                    ) {
                        $outraInstituicao = $conta['instituicao'];
                    }
                    ?>

                    <article class="saldos-conta <?= (int)$conta['ativa'] === 0 ? 'inativa' : '' ?>">

                        <div class="saldos-conta-topo">

                            <div class="saldos-identificacao">

                                <div class="saldos-logo">

                                    <?php if ($urlIcone): ?>

                                        <img
                                            src="<?= htmlspecialchars($urlIcone, ENT_QUOTES, 'UTF-8') ?>"
                                            alt=""
                                        >

                                    <?php else: ?>

                                        <span>
                                            <?= htmlspecialchars(
                                                iniciaisInstituicaoSaldo(
                                                    $conta['instituicao'],
                                                    $conta['nome']
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </div>

                                <div>
                                    <h3>
                                        <?= htmlspecialchars(
                                            $conta['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </h3>

                                    <?php if (!empty($conta['instituicao'])): ?>
                                        <span class="saldos-instituicao">
                                            <?= htmlspecialchars(
                                                $conta['instituicao'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>
                                    <?php endif; ?>

                                    <span>
                                        <?= htmlspecialchars(
                                            nomeTipoConta($conta['tipo']),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                </div>

                            </div>

                            <?php if ((int)$conta['ativa'] === 0): ?>
                                <span class="saldos-status-inativa">
                                    Inativa
                                </span>
                            <?php endif; ?>

                        </div>

                        <strong class="saldos-conta-valor <?= (float)$conta['saldo_atual'] > 0 ? 'positivo' : ((float)$conta['saldo_atual'] < 0 ? 'negativo' : 'neutro') ?>">
                            R$ <?= number_format(
                                (float)$conta['saldo_atual'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                        <div class="saldos-conta-detalhes">

                            <span>
                                Inicial:
                                R$ <?= number_format(
                                    (float)$conta['saldo_inicial'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </span>

                            <span>
                                Desde:
                                <?= date(
                                    'd/m/Y',
                                    strtotime($conta['data_saldo_inicial'])
                                ) ?>
                            </span>

                        </div>

                        <div class="saldos-conta-acoes">

                            <button
                                type="button"
                                class="saldos-btn-secundario saldos-editar-conta"
                                data-modal="modal-editar-conta"
                                data-id="<?= (int)$conta['id'] ?>"
                                data-nome="<?= htmlspecialchars($conta['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                data-tipo="<?= htmlspecialchars($conta['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                data-instituicao="<?= htmlspecialchars($chaveInstituicao, ENT_QUOTES, 'UTF-8') ?>"
                                data-outra="<?= htmlspecialchars($outraInstituicao, ENT_QUOTES, 'UTF-8') ?>"
                                data-saldo="<?= number_format((float)$conta['saldo_inicial'], 2, ',', '') ?>"
                                data-data="<?= htmlspecialchars($conta['data_saldo_inicial'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                Editar
                            </button>

                            <?php if ((int)$conta['ativa'] === 1): ?>

                                <form
                                    method="post"
                                    onsubmit="return confirm('Inativar esta conta? O histórico será preservado.');"
                                >
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="acao" value="inativar_conta">
                                    <input type="hidden" name="conta_id" value="<?= (int)$conta['id'] ?>">

                                    <button
                                        type="submit"
                                        class="saldos-btn-secundario"
                                    >
                                        Inativar
                                    </button>
                                </form>

                            <?php else: ?>

                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="acao" value="reativar_conta">
                                    <input type="hidden" name="conta_id" value="<?= (int)$conta['id'] ?>">

                                    <button
                                        type="submit"
                                        class="saldos-btn-secundario"
                                    >
                                        Reativar
                                    </button>
                                </form>

                            <?php endif; ?>

                            <?php if ((int)$conta['quantidade_movimentacoes'] === 0): ?>

                                <form
                                    method="post"
                                    onsubmit="return confirm('Excluir definitivamente esta conta?');"
                                >
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="acao" value="excluir_conta">
                                    <input type="hidden" name="conta_id" value="<?= (int)$conta['id'] ?>">

                                    <button
                                        type="submit"
                                        class="saldos-btn-perigo"
                                    >
                                        Excluir
                                    </button>
                                </form>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="saldos-vazio">
                Nenhuma conta financeira cadastrada.
                Cadastre sua primeira conta para iniciar o controle de saldos.
            </div>

        <?php endif; ?>

    </section>

    <section class="saldos-secao">

        <div class="saldos-titulo">
            <h2>Movimentações recentes</h2>

            <span>
                Últimas 100 operações. Transferências são exibidas como uma única operação.
            </span>
        </div>

        <?php if (!empty($historico)): ?>

            <div class="saldos-tabela-wrapper">

                <table class="saldos-tabela">

                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Conta / Transferência</th>
                            <th>Descrição</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($historico as $registro): ?>

                        <?php if ($registro['registro_tipo'] === 'transferencia'): ?>

                            <tr>
                                <td>
                                    <?= date(
                                        'd/m/Y',
                                        strtotime($registro['data'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $registro['conta_origem'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                    →
                                    <?= htmlspecialchars(
                                        $registro['conta_destino'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $registro['descricao'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>Transferência</td>

                                <td class="neutro">
                                    R$ <?= number_format(
                                        (float)$registro['valor'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <div class="saldos-tabela-acoes">

                                        <button
                                            type="button"
                                            class="saldos-btn-tabela saldos-editar-transferencia"
                                            data-modal="modal-editar-transferencia"
                                            data-id="<?= htmlspecialchars($registro['transferencia_id'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-data="<?= htmlspecialchars($registro['data'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-descricao="<?= htmlspecialchars($registro['descricao'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-valor="<?= number_format((float)$registro['valor'], 2, ',', '') ?>"
                                            data-origem="<?= htmlspecialchars($registro['conta_origem'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-destino="<?= htmlspecialchars($registro['conta_destino'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            Editar
                                        </button>

                                        <form
                                            method="post"
                                            onsubmit="return confirm('Excluir esta transferência? As duas movimentações serão removidas.');"
                                        >
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="acao" value="excluir_transferencia">
                                            <input type="hidden" name="transferencia_id" value="<?= htmlspecialchars($registro['transferencia_id'], ENT_QUOTES, 'UTF-8') ?>">

                                            <button
                                                type="submit"
                                                class="saldos-btn-tabela perigo"
                                            >
                                                Excluir
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php
                            $entrada = $registro['tipo'] === 'entrada';

                            $podeManter =
                                $registro['origem_modulo'] === 'manual' &&
                                in_array(
                                    $registro['tipo'],
                                    ['entrada', 'saida'],
                                    true
                                );
                            ?>

                            <tr>
                                <td>
                                    <?= date(
                                        'd/m/Y',
                                        strtotime($registro['data'])
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $registro['conta_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $registro['descricao'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $entrada ? 'Entrada' : 'Saída' ?>
                                </td>

                                <td class="<?= $entrada ? 'positivo' : 'negativo' ?>">
                                    <?= $entrada ? '+' : '-' ?>
                                    R$ <?= number_format(
                                        (float)$registro['valor'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>

                                    <?php if ($podeManter): ?>

                                        <div class="saldos-tabela-acoes">

                                            <button
                                                type="button"
                                                class="saldos-btn-tabela saldos-editar-movimentacao"
                                                data-modal="modal-editar-movimentacao"
                                                data-id="<?= (int)$registro['id'] ?>"
                                                data-tipo="<?= htmlspecialchars($registro['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-data="<?= htmlspecialchars($registro['data'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-descricao="<?= htmlspecialchars($registro['descricao'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-valor="<?= number_format((float)$registro['valor'], 2, ',', '') ?>"
                                            >
                                                Editar
                                            </button>

                                            <form
                                                method="post"
                                                onsubmit="return confirm('Excluir esta movimentação?');"
                                            >
                                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="acao" value="excluir_movimentacao">
                                                <input type="hidden" name="movimentacao_id" value="<?= (int)$registro['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="saldos-btn-tabela perigo"
                                                >
                                                    Excluir
                                                </button>
                                            </form>

                                        </div>

                                    <?php else: ?>

                                        <span class="saldos-sem-acao">—</span>

                                    <?php endif; ?>

                                </td>
                            </tr>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="saldos-vazio">
                Nenhuma movimentação registrada.
            </div>

        <?php endif; ?>

    </section>

</main>

<div class="saldos-modal" id="modal-nova-conta">
    <div class="saldos-modal-conteudo">

        <button type="button" class="saldos-modal-fechar">&times;</button>

        <h2>Nova conta</h2>

        <form method="post" class="saldos-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="nova_conta">

            <label for="nome">Nome da conta</label>
            <input type="text" id="nome" name="nome" maxlength="100" required>

            <label for="tipo">Tipo</label>

            <select id="tipo" name="tipo" class="saldos-tipo-conta" required>
                <option value="corrente">Conta corrente</option>
                <option value="poupanca">Poupança</option>
                <option value="carteira">Carteira / Dinheiro</option>
                <option value="corretora">Corretora</option>
                <option value="outros">Outros</option>
            </select>

            <div class="saldos-campo-instituicao">

                <label for="instituicao">Instituição</label>

                <select id="instituicao" name="instituicao" class="saldos-instituicao-select">
                    <option value="">Não informada</option>

                    <?php foreach ($instituicoes as $chave => $dados): ?>
                        <option value="<?= htmlspecialchars($chave, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($dados['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>

                    <option value="outra">Outra instituição</option>
                </select>

                <div class="saldos-outra-instituicao">
                    <label for="outra_instituicao">Nome da instituição</label>
                    <input
                        type="text"
                        id="outra_instituicao"
                        name="outra_instituicao"
                        maxlength="100"
                    >
                </div>

            </div>

            <label for="saldo_inicial">Saldo inicial</label>

            <input
                type="text"
                id="saldo_inicial"
                name="saldo_inicial"
                value="0,00"
                inputmode="decimal"
                class="saldos-campo-valor"
                required
            >

            <label for="data_saldo_inicial">Data do saldo inicial</label>

            <input
                type="date"
                id="data_saldo_inicial"
                name="data_saldo_inicial"
                value="<?= $hoje ?>"
                required
            >

            <button type="submit" class="saldos-btn">
                Cadastrar conta
            </button>

        </form>

    </div>
</div>

<div class="saldos-modal" id="modal-editar-conta">
    <div class="saldos-modal-conteudo">

        <button type="button" class="saldos-modal-fechar">&times;</button>

        <h2>Editar conta</h2>

        <form method="post" class="saldos-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="editar_conta">
            <input type="hidden" name="conta_id" id="editar_conta_id">

            <label for="editar_nome">Nome da conta</label>
            <input type="text" id="editar_nome" name="nome" maxlength="100" required>

            <label for="editar_tipo">Tipo</label>

            <select id="editar_tipo" name="tipo" class="saldos-tipo-conta" required>
                <option value="corrente">Conta corrente</option>
                <option value="poupanca">Poupança</option>
                <option value="carteira">Carteira / Dinheiro</option>
                <option value="corretora">Corretora</option>
                <option value="outros">Outros</option>
            </select>

            <div class="saldos-campo-instituicao">

                <label for="editar_instituicao">Instituição</label>

                <select
                    id="editar_instituicao"
                    name="instituicao"
                    class="saldos-instituicao-select"
                >
                    <option value="">Não informada</option>

                    <?php foreach ($instituicoes as $chave => $dados): ?>
                        <option value="<?= htmlspecialchars($chave, ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($dados['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>

                    <option value="outra">Outra instituição</option>
                </select>

                <div class="saldos-outra-instituicao">

                    <label for="editar_outra_instituicao">
                        Nome da instituição
                    </label>

                    <input
                        type="text"
                        id="editar_outra_instituicao"
                        name="outra_instituicao"
                        maxlength="100"
                    >

                </div>

            </div>

            <label for="editar_saldo_inicial">Saldo inicial</label>

            <input
                type="text"
                id="editar_saldo_inicial"
                name="saldo_inicial"
                inputmode="decimal"
                class="saldos-campo-valor"
                required
            >

            <div class="saldos-aviso">
                Alterar o saldo inicial recalcula o saldo atual da conta.
            </div>

            <label for="editar_data_saldo_inicial">
                Data do saldo inicial
            </label>

            <input
                type="date"
                id="editar_data_saldo_inicial"
                name="data_saldo_inicial"
                required
            >

            <button type="submit" class="saldos-btn">
                Salvar alterações
            </button>

        </form>

    </div>
</div>

<div class="saldos-modal" id="modal-movimentacao">
    <div class="saldos-modal-conteudo">

        <button type="button" class="saldos-modal-fechar">&times;</button>

        <h2>Nova movimentação</h2>

        <form method="post" class="saldos-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="nova_movimentacao">

            <label for="mov_conta">Conta</label>

            <select id="mov_conta" name="conta_id" required>
                <option value="">Selecione...</option>

                <?php foreach ($contas as $conta): ?>
                    <?php if ((int)$conta['ativa'] === 1): ?>

                        <option value="<?= (int)$conta['id'] ?>">
                            <?= htmlspecialchars($conta['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>

                    <?php endif; ?>
                <?php endforeach; ?>

            </select>

            <label for="tipo_movimentacao">Tipo</label>

            <select id="tipo_movimentacao" name="tipo_movimentacao" required>
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>

            <label for="mov_data">Data</label>
            <input type="date" id="mov_data" name="data" value="<?= $hoje ?>" required>

            <label for="mov_descricao">Descrição</label>
            <input type="text" id="mov_descricao" name="descricao" maxlength="150" required>

            <label for="mov_valor">Valor</label>

            <input
                type="text"
                id="mov_valor"
                name="valor"
                inputmode="decimal"
                class="saldos-campo-valor"
                required
            >

            <button type="submit" class="saldos-btn">
                Registrar movimentação
            </button>

        </form>

    </div>
</div>

<div class="saldos-modal" id="modal-editar-movimentacao">
    <div class="saldos-modal-conteudo">

        <button type="button" class="saldos-modal-fechar">&times;</button>

        <h2>Editar movimentação</h2>

        <form method="post" class="saldos-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="editar_movimentacao">
            <input type="hidden" name="movimentacao_id" id="editar_mov_id">

            <label for="editar_mov_tipo">Tipo</label>

            <select id="editar_mov_tipo" name="tipo_movimentacao" required>
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>

            <label for="editar_mov_data">Data</label>
            <input type="date" id="editar_mov_data" name="data" required>

            <label for="editar_mov_descricao">Descrição</label>

            <input
                type="text"
                id="editar_mov_descricao"
                name="descricao"
                maxlength="150"
                required
            >

            <label for="editar_mov_valor">Valor</label>

            <input
                type="text"
                id="editar_mov_valor"
                name="valor"
                inputmode="decimal"
                class="saldos-campo-valor"
                required
            >

            <button type="submit" class="saldos-btn">
                Salvar alterações
            </button>

        </form>

    </div>
</div>

<div class="saldos-modal" id="modal-transferencia">
    <div class="saldos-modal-conteudo">

        <button type="button" class="saldos-modal-fechar">&times;</button>

        <h2>Transferir entre contas</h2>

        <form method="post" class="saldos-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="transferir">

            <label for="conta_origem">Conta de origem</label>

            <select id="conta_origem" name="conta_origem" required>
                <option value="">Selecione...</option>

                <?php foreach ($contas as $conta): ?>
                    <?php if ((int)$conta['ativa'] === 1): ?>

                        <option value="<?= (int)$conta['id'] ?>">
                            <?= htmlspecialchars($conta['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>

                    <?php endif; ?>
                <?php endforeach; ?>

            </select>

            <label for="conta_destino">Conta de destino</label>

            <select id="conta_destino" name="conta_destino" required>
                <option value="">Selecione...</option>

                <?php foreach ($contas as $conta): ?>
                    <?php if ((int)$conta['ativa'] === 1): ?>

                        <option value="<?= (int)$conta['id'] ?>">
                            <?= htmlspecialchars($conta['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>

                    <?php endif; ?>
                <?php endforeach; ?>

            </select>

            <label for="trans_data">Data</label>
            <input type="date" id="trans_data" name="data" value="<?= $hoje ?>" required>

            <label for="trans_descricao">Descrição</label>

            <input
                type="text"
                id="trans_descricao"
                name="descricao"
                maxlength="150"
                value="Transferência"
                required
            >

            <label for="trans_valor">Valor</label>

            <input
                type="text"
                id="trans_valor"
                name="valor"
                inputmode="decimal"
                class="saldos-campo-valor"
                required
            >

            <button type="submit" class="saldos-btn">
                Transferir
            </button>

        </form>

    </div>
</div>

<div class="saldos-modal" id="modal-editar-transferencia">
    <div class="saldos-modal-conteudo">

        <button type="button" class="saldos-modal-fechar">&times;</button>

        <h2>Editar transferência</h2>

        <form method="post" class="saldos-form">

            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="acao" value="editar_transferencia">
            <input type="hidden" name="transferencia_id" id="editar_trans_id">

            <div class="saldos-transferencia-info" id="editar_trans_contas"></div>

            <label for="editar_trans_data">Data</label>
            <input type="date" id="editar_trans_data" name="data" required>

            <label for="editar_trans_descricao">Descrição</label>

            <input
                type="text"
                id="editar_trans_descricao"
                name="descricao"
                maxlength="150"
                required
            >

            <label for="editar_trans_valor">Valor</label>

            <input
                type="text"
                id="editar_trans_valor"
                name="valor"
                inputmode="decimal"
                class="saldos-campo-valor"
                required
            >

            <button type="submit" class="saldos-btn">
                Salvar transferência
            </button>

        </form>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
function atualizarInstituicao(form) {
    const tipo = form.querySelector('.saldos-tipo-conta');
    const bloco = form.querySelector('.saldos-campo-instituicao');
    const select = form.querySelector('.saldos-instituicao-select');
    const outra = form.querySelector('.saldos-outra-instituicao');

    if (!tipo || !bloco || !select || !outra) {
        return;
    }

    if (tipo.value === 'carteira') {
        bloco.style.display = 'none';
        return;
    }

    bloco.style.display = 'block';

    outra.style.display =
        select.value === 'outra'
            ? 'block'
            : 'none';
}

document.querySelectorAll('.saldos-form').forEach(function(form) {
    const tipo = form.querySelector('.saldos-tipo-conta');
    const instituicao = form.querySelector('.saldos-instituicao-select');

    if (tipo) {
        tipo.addEventListener('change', function() {
            atualizarInstituicao(form);
        });
    }

    if (instituicao) {
        instituicao.addEventListener('change', function() {
            atualizarInstituicao(form);
        });
    }

    atualizarInstituicao(form);
});

document.querySelectorAll('[data-modal]').forEach(function(botao) {
    botao.addEventListener('click', function() {
        if (this.disabled) {
            return;
        }

        if (this.classList.contains('saldos-editar-conta')) {
            document.getElementById('editar_conta_id').value = this.dataset.id;
            document.getElementById('editar_nome').value = this.dataset.nome;
            document.getElementById('editar_tipo').value = this.dataset.tipo;
            document.getElementById('editar_instituicao').value = this.dataset.instituicao;
            document.getElementById('editar_outra_instituicao').value = this.dataset.outra;
            document.getElementById('editar_saldo_inicial').value = this.dataset.saldo;
            document.getElementById('editar_data_saldo_inicial').value = this.dataset.data;

            atualizarInstituicao(
                document.getElementById('editar_conta_id').closest('form')
            );
        }

        if (this.classList.contains('saldos-editar-movimentacao')) {
            document.getElementById('editar_mov_id').value = this.dataset.id;
            document.getElementById('editar_mov_tipo').value = this.dataset.tipo;
            document.getElementById('editar_mov_data').value = this.dataset.data;
            document.getElementById('editar_mov_descricao').value = this.dataset.descricao;
            document.getElementById('editar_mov_valor').value = this.dataset.valor;
        }

        if (this.classList.contains('saldos-editar-transferencia')) {
            document.getElementById('editar_trans_id').value = this.dataset.id;
            document.getElementById('editar_trans_data').value = this.dataset.data;
            document.getElementById('editar_trans_descricao').value = this.dataset.descricao;
            document.getElementById('editar_trans_valor').value = this.dataset.valor;

            document.getElementById('editar_trans_contas').textContent =
                this.dataset.origem + ' → ' + this.dataset.destino;
        }

        const modal = document.getElementById(this.dataset.modal);

        if (modal) {
            modal.style.display = 'flex';
        }
    });
});

document.querySelectorAll('.saldos-modal-fechar').forEach(function(botao) {
    botao.addEventListener('click', function() {
        const modal = this.closest('.saldos-modal');

        if (modal) {
            modal.style.display = 'none';
        }
    });
});

document.querySelectorAll('.saldos-modal').forEach(function(modal) {
    modal.addEventListener('click', function(event) {
        if (event.target === this) {
            this.style.display = 'none';
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.saldos-modal').forEach(function(modal) {
            modal.style.display = 'none';
        });
    }
});

document.querySelectorAll('.saldos-campo-valor').forEach(function(campo) {
    campo.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9,.\-]/g, '');
    });
});
</script>

</body>
</html>