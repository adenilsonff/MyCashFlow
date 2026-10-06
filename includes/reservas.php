<?php
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    http_response_code(404);
    exit;
}

function rmSql($conn, $sql, $types = '', array $args = [])
{
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$args);
    }
    $stmt->execute();
    return $stmt;
}

function rmRows($conn, $sql, $types = '', array $args = [])
{
    $stmt = rmSql($conn, $sql, $types, $args);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function rmExec($conn, $sql, $types = '', array $args = [])
{
    rmSql($conn, $sql, $types, $args)->close();
}

function rmCentavos($valor)
{
    if (!is_scalar($valor)) {
        throw new DomainException('Informe um valor válido.');
    }
    $valor = trim((string) $valor);
    if (str_contains($valor, ',')) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }
    if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/D', $valor)) {
        throw new DomainException('Use um valor positivo com até duas casas decimais.');
    }
    $partes = explode('.', $valor);
    return (int) $partes[0] * 100 + (int) str_pad($partes[1] ?? '', 2, '0');
}

function rmData($data)
{
    $d = is_string($data) ? DateTime::createFromFormat('!Y-m-d', $data) : false;
    if (!$d || $d->format('Y-m-d') !== $data || $data < '2000-01-01' || $data > date('Y-m-d')) {
        throw new DomainException('Informe uma data real, até hoje.');
    }
    return $data;
}

function rmTexto($texto, $limite = 255)
{
    if (!is_string($texto) || trim($texto) === '' || mb_strlen(trim($texto)) > $limite) {
        throw new DomainException('Preencha a descrição dentro do limite de caracteres.');
    }
    return trim($texto);
}

function rmBolsos($conn, $uid)
{
    $bolsos = [
        'livre' => 'Disponível / sobra do mês',
        'extras' => 'Extras recebidos — aguardando divisão',
        'investir' => 'Separado para investir',
        'necessary' => 'Necessary/Funny — 30%',
        'poupado' => 'Economia do orçamento diário',
        'daytrade' => 'Day trade líquido disponível',
        'reserva' => 'RESERV_MENS',
        'dividendos' => 'Dividendos do ano',
        'juros' => 'Juros ainda não incorporados',
        'viagem' => 'Viagem',
        'investido' => 'Enviado ao investimento',
        'utilizado' => 'Utilizado — fora da reserva'
    ];
    foreach (rmRows($conn, 'SELECT * FROM rm_objetivos WHERE usuario_id=? ORDER BY nome', 'i', [$uid]) as $o) {
        $bolsos['o:' . $o['chave']] = $o['nome'];
    }
    return $bolsos;
}

function rmResumo($conn, $uid)
{
    $rows = rmRows(
        $conn,
        'SELECT p.*, f.descricao, f.origem, f.conta_id, f.data AS data_fonte
         FROM rm_partes p JOIN rm_fontes f ON f.usuario_id=p.usuario_id AND f.chave=p.fonte_chave
         WHERE p.usuario_id=? AND p.valor>0 ORDER BY f.data, p.chave',
        'i', [$uid]
    );
    $saldos = []; $contas = []; $metas = [];
    foreach ($rows as $r) {
        $v = (int) $r['valor'];
        $saldos[$r['bolso']] = ($saldos[$r['bolso']] ?? 0) + $v;
        if (!in_array($r['bolso'], ['investido', 'utilizado'], true)) {
            $contas[$r['conta_id']] = ($contas[$r['conta_id']] ?? 0) + $v;
        }
        if ($r['meta_ano'] && $r['bolso'] !== 'utilizado') {
            $a = (int) $r['meta_ano']; $m = (int) $r['meta_mes']; $g = $r['meta_grupo'];
            $metas[$a][$m][$g] = ($metas[$a][$m][$g] ?? 0) + $v;
        }
    }
    return ['partes' => $rows, 'saldos' => $saldos, 'contas' => $contas, 'metas' => $metas];
}

function rmParte($conn, $uid, $chave)
{
    $r = rmRows($conn, 'SELECT * FROM rm_partes WHERE usuario_id=? AND chave=? FOR UPDATE', 'is', [$uid, $chave]);
    if (!$r || (int) $r[0]['valor'] <= 0 || in_array($r[0]['bolso'], ['investido', 'utilizado'], true)) {
        throw new DomainException('Selecione um saldo disponível. Valores utilizados ou investidos não podem ser reutilizados.');
    }
    return $r[0];
}

function rmGravarParte($conn, $uid, $fonte, $bolso, $valor, $ano = 0, $mes = 0, $grupo = '')
{
    $chave = bin2hex(random_bytes(16));
    rmExec($conn, 'INSERT INTO rm_partes VALUES(?,?,?,?,?,?,?,?)', 'isssiiis', [$uid, $chave, $fonte, $bolso, $valor, $ano, $mes, $grupo]);
    return $chave;
}

function rmMoverParte($conn, $uid, $p, $destino, $valor, $data, $grupo, &$undo)
{
    if ($valor < 1 || $valor > (int) $p['valor']) {
        throw new DomainException('O valor excede o saldo dessa origem.');
    }
    if ($data < rmRows($conn, 'SELECT data FROM rm_fontes WHERE usuario_id=? AND chave=?', 'is', [$uid, $p['fonte_chave']])[0]['data']) {
        throw new DomainException('A movimentação não pode ser anterior à entrada do dinheiro.');
    }
    $ano = (int) $p['meta_ano']; $mes = (int) $p['meta_mes']; $g = $p['meta_grupo'];
    if ($grupo !== '') {
        if ($ano !== 0) {
            throw new DomainException('Este valor já participa da meta. Transfira sem marcar uma nova contribuição.');
        }
        if (!in_array($grupo, ['mensal', 'extras', 'pontuais', 'outros'], true)
            || in_array($destino, ['livre', 'necessary', 'extras', 'daytrade', 'utilizado'], true)) {
            throw new DomainException('Para contar na meta, escolha uma reserva ou investimento como destino.');
        }
        $ano = (int) substr($data, 0, 4); $mes = (int) substr($data, 5, 2); $g = $grupo;
    }
    if (in_array($destino, ['livre', 'necessary', 'extras', 'daytrade', 'utilizado'], true)) {
        $ano = 0; $mes = 0; $g = '';
    }
    $undo['partes'][] = $p;
    rmExec($conn, 'UPDATE rm_partes SET valor=valor-? WHERE usuario_id=? AND chave=?', 'iis', [$valor, $uid, $p['chave']]);
    $undo['criadas'][] = rmGravarParte($conn, $uid, $p['fonte_chave'], $destino, $valor, $ano, $mes, $g);
}

function rmOperar($conn, $uid, array $in)
{
    $token = $in['operacao'] ?? '';
    if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/D', $token)) {
        throw new DomainException('Reabra o formulário.');
    }
    $acao = $in['acao'] ?? '';
    $data = rmData($in['data'] ?? date('Y-m-d'));
    $lock = rmRows($conn, 'SELECT GET_LOCK(?,10) AS ok', 's', ['mcf_reservas_' . $uid]);
    if ((int) $lock[0]['ok'] !== 1) {
        throw new DomainException('Outra alteração está em andamento. Tente novamente.');
    }
    try {
        $conn->begin_transaction();
        if (rmRows($conn, 'SELECT chave FROM rm_eventos WHERE usuario_id=? AND chave=?', 'is', [$uid, $token])) {
            $conn->rollback();
            return 'Esta operação já foi processada.';
        }
        $undo = ['partes' => [], 'criadas' => []];
        $descricao = rmTexto($in['descricao'] ?? 'Movimentação de reserva', 1000);
        $bolsos = rmBolsos($conn, $uid);

        if ($acao === 'entrada') {
            $descricao = rmTexto($descricao, 255);
            $conta = (int) ($in['conta_id'] ?? 0);
            if (!rmRows($conn, 'SELECT id FROM contas_financeiras WHERE usuario_id=? AND id=? AND ativa=1', 'ii', [$uid, $conta])) {
                throw new DomainException('Selecione uma conta ativa sua.');
            }
            $origem = $in['origem'] ?? '';
            if (!in_array($origem, ['inicial', 'salario', 'servico', 'decimo', 'ferias', 'restituicao', 'dividendo', 'juros', 'daytrade', 'outros'], true)) {
                throw new DomainException('Origem inválida.');
            }
            $valor = rmCentavos($in['valor'] ?? '');
            if ($valor < 1) { throw new DomainException('Informe um valor maior que zero.'); }
            $renda = (int) ($in['renda_id'] ?? 0);
            if ($renda) {
                $receitas = rmRows($conn, 'SELECT id, data, valor FROM rendas WHERE usuario_id=? AND id=? AND recebido=1', 'ii', [$uid, $renda]);
                if (!$receitas || rmCentavos($receitas[0]['valor']) !== $valor || $data < $receitas[0]['data']) {
                    throw new DomainException('Use o valor integral de uma receita recebida e data igual ou posterior a ela.');
                }
                if (rmRows($conn, 'SELECT chave FROM rm_fontes WHERE usuario_id=? AND renda_id=?', 'ii', [$uid, $renda])) {
                    throw new DomainException('Esta receita já foi vinculada. Movimente o saldo existente em vez de registrá-la novamente.');
                }
            }
            $destino = $in['destino'] ?? 'livre';
            if (!isset($bolsos[$destino]) || in_array($destino, ['investido', 'utilizado'], true)) {
                throw new DomainException('Selecione onde separar o dinheiro.');
            }
            $saldos = saldosListarContas($conn, $uid);
            $real = null;
            foreach ($saldos as $c) { if ((int) $c['id'] === $conta) { $real = (int) round((float) $c['saldo_atual'] * 100); } }
            $reservado = rmResumo($conn, $uid)['contas'][$conta] ?? 0;
            if ($real === null || $reservado + $valor > $real) {
                throw new DomainException('A conta não tem saldo sem destinação suficiente. Confira Contas e Saldos antes de registrar.');
            }
            rmExec($conn, 'INSERT INTO rm_fontes VALUES(?,?,?,?,?,?,?,?)', 'isiisssi', [$uid, $token, $conta, $renda ?: null, $data, $origem, $descricao, $valor]);
            $undo['fonte'] = $token;
            $undo['movimento'] = ['valor' => $valor, 'destino' => $destino];
            $undo['criadas'][] = rmGravarParte($conn, $uid, $token, $destino, $valor);
        } elseif (in_array($acao, ['mover', 'dividir', 'usar', 'repor'], true)) {
            $p = rmParte($conn, $uid, $in['parte'] ?? '');
            $valor = rmCentavos($in['valor'] ?? '');
            $undo['movimento'] = ['valor' => $valor, 'origem' => $p['bolso']];
            $grupo = is_string($in['grupo'] ?? '') ? ($in['grupo'] ?? '') : '';
            if ($acao === 'dividir') {
                if ($p['bolso'] !== 'extras' || (int) $p['meta_ano'] !== 0 || $valor > (int) $p['valor'] || $valor < 2) {
                    throw new DomainException('A divisão usa somente extras ainda não divididos, a partir de R$ 0,02.');
                }
                $investir = intdiv($valor * 70 + 50, 100);
                rmMoverParte($conn, $uid, $p, 'investir', $investir, $data, 'extras', $undo);
                $p2 = rmParte($conn, $uid, $p['chave']);
                rmMoverParte($conn, $uid, $p2, 'necessary', $valor - $investir, $data, '', $undo);
            } else {
                $destino = $acao === 'usar' ? 'utilizado' : ($in['destino'] ?? '');
                if ($acao === 'repor') {
                    $repos = rmRows($conn, 'SELECT * FROM rm_reposicoes WHERE usuario_id=? AND chave=? FOR UPDATE', 'is', [$uid, $in['reposicao'] ?? '']);
                    if (!$repos || $valor > (int) $repos[0]['valor'] - (int) $repos[0]['reposto']) {
                        throw new DomainException('Reposição inválida ou superior ao valor pendente.');
                    }
                    $repo = $repos[0]; $destino = $repo['bolso'];
                    if ($p['bolso'] === $destino) { throw new DomainException('A reposição deve trazer dinheiro de outra destinação.'); }
                    $undo['reposicao'] = $repo;
                    rmExec($conn, 'UPDATE rm_reposicoes SET reposto=reposto+? WHERE usuario_id=? AND chave=?', 'iis', [$valor, $uid, $repo['chave']]);
                }
                if (!isset($bolsos[$destino]) || ($destino === $p['bolso'] && $grupo === '')) {
                    throw new DomainException('Escolha outro destino ou marque a contribuição na meta.');
                }
                rmMoverParte($conn, $uid, $p, $destino, $valor, $data, $grupo, $undo);
                $undo['movimento']['destino'] = $destino;
                if ($acao === 'usar' && isset($in['repor_depois'])) {
                    $descricao = rmTexto($descricao, 255);
                    rmExec($conn, 'INSERT INTO rm_reposicoes VALUES(?,?,?,?,?,?,0)', 'issssi', [$uid, $token, $p['bolso'], $data, $descricao, $valor]);
                    $undo['reposicao_criada'] = $token;
                }
            }
        } elseif ($acao === 'objetivo') {
            $nome = rmTexto($in['nome'] ?? '', 100);
            $alvo = rmCentavos($in['alvo'] ?? '0');
            $prazo = $in['prazo'] ?? '';
            if ($prazo !== '') {
                $dt = DateTime::createFromFormat('!Y-m-d', $prazo);
                if (!$dt || $dt->format('Y-m-d') !== $prazo || $prazo < $data) { throw new DomainException('Prazo inválido.'); }
            }
            rmExec($conn, 'INSERT INTO rm_objetivos VALUES(?,?,?,?,?)', 'issis', [$uid, $token, $nome, $alvo, $prazo ?: null]);
        } elseif ($acao === 'metas') {
            $ano = filter_var($in['ano'] ?? '', FILTER_VALIDATE_INT);
            if (!$ano || $ano < 2000 || $ano > 2100) { throw new DomainException('Ano inválido.'); }
            $v = [];
            foreach (['mensal', 'decimo_primeira', 'decimo_segunda', 'ferias', 'restituicao', 'sonho', 'desafio'] as $campo) { $v[] = rmCentavos($in[$campo] ?? '0'); }
            rmExec($conn, 'INSERT INTO rm_metas VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE mensal=VALUES(mensal),decimo_primeira=VALUES(decimo_primeira),decimo_segunda=VALUES(decimo_segunda),ferias=VALUES(ferias),restituicao=VALUES(restituicao),sonho=VALUES(sonho),desafio=VALUES(desafio)', 'iiiiiiiii', array_merge([$uid, $ano], $v));
        } elseif ($acao === 'nota' || $acao === 'fechar') {
            if ($acao === 'fechar') {
                if ($data !== date('Y-m-d')) { throw new DomainException('A conferência registra os saldos de hoje.'); }
                $undo['fechamento'] = rmResumo($conn, $uid);
                unset($undo['fechamento']['partes']);
            }
        } elseif ($acao === 'estornar') {
            $ultimos = rmRows($conn, "SELECT * FROM rm_eventos WHERE usuario_id=? AND estornado=0 AND acao IN ('entrada','mover','dividir','usar','repor') ORDER BY criado_em DESC LIMIT 1 FOR UPDATE", 'i', [$uid]);
            if (!$ultimos || $ultimos[0]['chave'] !== ($in['evento'] ?? '')) { throw new DomainException('Só é possível desfazer a última movimentação financeira.'); }
            $evento = $ultimos[0]; $d = json_decode($evento['detalhes'], true, 512, JSON_THROW_ON_ERROR);
            foreach ($d['criadas'] as $chave) { rmExec($conn, 'DELETE FROM rm_partes WHERE usuario_id=? AND chave=?', 'is', [$uid, $chave]); }
            foreach (array_reverse($d['partes']) as $parte) { rmExec($conn, 'UPDATE rm_partes SET valor=? WHERE usuario_id=? AND chave=?', 'iis', [(int) $parte['valor'], $uid, $parte['chave']]); }
            if (isset($d['fonte'])) { rmExec($conn, 'DELETE FROM rm_fontes WHERE usuario_id=? AND chave=?', 'is', [$uid, $d['fonte']]); }
            if (isset($d['reposicao_criada'])) { rmExec($conn, 'DELETE FROM rm_reposicoes WHERE usuario_id=? AND chave=?', 'is', [$uid, $d['reposicao_criada']]); }
            if (isset($d['reposicao'])) { rmExec($conn, 'UPDATE rm_reposicoes SET reposto=? WHERE usuario_id=? AND chave=?', 'iis', [(int) $d['reposicao']['reposto'], $uid, $d['reposicao']['chave']]); }
            rmExec($conn, 'UPDATE rm_eventos SET estornado=1 WHERE usuario_id=? AND chave=?', 'is', [$uid, $evento['chave']]);
            $undo['evento_estornado'] = $evento['chave'];
        } else {
            throw new DomainException('Operação inválida.');
        }
        rmExec($conn, 'INSERT INTO rm_eventos VALUES(?,?,NOW(6),?,?,?,?,0)', 'isssss', [$uid, $token, $data, $acao, $descricao, json_encode($undo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        $conn->commit();
        return 'Operação registrada. Os saldos bancários e as receitas não foram duplicados.';
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    } finally {
        rmRows($conn, 'SELECT RELEASE_LOCK(?)', 's', ['mcf_reservas_' . $uid]);
    }
}
