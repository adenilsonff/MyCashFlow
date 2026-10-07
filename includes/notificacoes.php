<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/modulos.php';

function mcfNotificacaoHoje(): DateTimeImmutable {
    return new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo'));
}
function mcfNotificacaoData(string $valor): DateTimeImmutable {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $valor, new DateTimeZone('America/Sao_Paulo'));
    if (!$d || $d->format('Y-m-d') !== $valor) throw new DomainException('Data inválida.');
    return $d;
}
function mcfProximoFechamento(int $dia, DateTimeImmutable $hoje): string {
    if ($dia < 1 || $dia > 31) throw new DomainException('Informe um dia de 1 a 31.');
    $mes = $hoje->modify('first day of this month');
    $data = $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'), min($dia, (int)$mes->format('t')));
    if ($data < $hoje) {
        $mes = $mes->modify('first day of next month');
        $data = $mes->setDate((int)$mes->format('Y'), (int)$mes->format('m'), min($dia, (int)$mes->format('t')));
    }
    return $data->format('Y-m-d');
}
function mcfNotificacaoRegistros(mysqli $c, string $sql, int $uid): array {
    $s = $c->prepare($sql); $s->bind_param('i', $uid); $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); return $rows;
}
function mcfLembretesCartao(mysqli $c, int $uid): array {
    return mcfNotificacaoRegistros($c, 'SELECT id,nome,dia_fechamento FROM notificacao_cartoes WHERE usuario_id=? ORDER BY nome,id', $uid);
}
/** Avisos sempre pertencem à pessoa conectada, inclusive em páginas compartilhadas. */
function mcfNotificacoes(mysqli $c, int $uid, ?DateTimeImmutable $hoje = null): array {
    $hoje = $hoje ?? mcfNotificacaoHoje();
    $hoje = mcfNotificacaoData($hoje->format('Y-m-d'));
    $inicio = $hoje->format('Y-m-d'); $fim = $hoje->modify('+7 days')->format('Y-m-d');
    $avisos = [];
    $adicionar = static function(string $tipo, string $referencia, string $data, string $titulo, string $texto, string $url, bool $urgente = false) use (&$avisos, $uid): void {
        // A mesma ocorrência não reaparece diariamente; alterações relevantes geram outra chave.
        $chave = hash('sha256', json_encode([$uid,$tipo,$referencia,$data,$titulo,$texto], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        $avisos[] = compact('chave','tipo','data','titulo','texto','url','urgente');
    };
    if (mcfModuloAtivo($c, $uid, 'despesas')) {
        $s=$c->prepare('SELECT id,nome,vencimento,valor FROM contas WHERE usuario_id=? AND paga=0 AND vencimento<=? ORDER BY vencimento,id');
        $s->bind_param('is',$uid,$fim);$s->execute();$contas=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        foreach ($contas as $r) {
            $atrasada = $r['vencimento'] < $inicio;
            $d=mcfNotificacaoData($r['vencimento']);
            $adicionar($atrasada?'atraso':'conta', (string)$r['id'], $r['vencimento'], ($atrasada?'Conta em atraso: ':'Conta a vencer: ').$r['nome'],
                'R$ '.number_format((float)$r['valor'],2,',','.').' · Vencimento em '.$d->format('d/m/Y').'.',
                '/MyCashFlow/views/contas.php?mes='.$d->format('n').'&ano='.$d->format('Y'), $atrasada || $r['vencimento']===$inicio);
        }
    }
    if (mcfModuloAtivo($c,$uid,'cartao')) {
        foreach (mcfLembretesCartao($c,$uid) as $r) {
            $data=mcfProximoFechamento((int)$r['dia_fechamento'],$hoje);$d=mcfNotificacaoData($data);
            if ($data > $hoje->modify('+3 days')->format('Y-m-d')) continue;
            $adicionar('cartao',(string)$r['id'],$data,'Fechamento do cartão: '.$r['nome'],
                'Fechamento previsto em '.$d->format('d/m/Y').'. Confira a data no aplicativo do cartão.',
                '/MyCashFlow/views/cartao.php?mes='.$d->format('n').'&ano='.$d->format('Y'),$data===$inicio);
        }
    }
    if (mcfModuloAtivo($c,$uid,'dividendos')) {
        $s=$c->prepare("SELECT d.id,d.ticker,d.tipo_ativo,d.datacom,d.datapag,d.valor,d.tipo,
            (SELECT COALESCE(SUM(CASE WHEN a.tipo_operacao='compra' THEN ABS(a.quantidade) WHEN a.tipo_operacao='venda' THEN -ABS(a.quantidade) ELSE 0 END),0)
             FROM investimentos_nacionais a WHERE a.usuario_id=d.usuario_id AND UPPER(a.ticker)=UPPER(d.ticker) AND a.tipo_ativo=d.tipo_ativo AND a.data<=d.datacom) AS quantidade
            FROM div_datacom d WHERE d.usuario_id=? AND d.datapag BETWEEN ? AND ? ORDER BY d.datapag,d.id");
        $s->bind_param('iss',$uid,$inicio,$fim);$s->execute();$dividendos=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        foreach($dividendos as $r) {
            if (bccomp((string)$r['quantidade'],'0',8)<=0) continue;
            $d=mcfNotificacaoData($r['datapag']);
            $valor=bcmul((string)$r['quantidade'],(string)$r['valor'],8);
            $adicionar('dividendo',implode(':',[$r['id'],$r['datacom'],$r['quantidade'],$r['valor']]),$r['datapag'],
                'Provento previsto: '.$r['ticker'].' ('.$r['tipo'].')',
                'Pagamento em '.$d->format('d/m/Y').' · Estimativa bruta de R$ '.number_format((float)$valor,2,',','.').'. Baseada nos lançamentos e na posição na data com; não confirma recebimento.',
                '/MyCashFlow/views/div_valor.php?mes='.$d->format('n').'&ano='.$d->format('Y'));
        }
    }
    $u=mcfNotificacaoRegistros($c,'SELECT data_expiracao,status_assinatura FROM usuarios WHERE id=?',$uid)[0]??null;
    if ($u && $u['status_assinatura']==='ativo' && $u['data_expiracao'] >= $inicio && $u['data_expiracao'] <= $hoje->modify('+15 days')->format('Y-m-d')) {
        $adicionar('assinatura',(string)$uid,$u['data_expiracao'],'Assinatura próxima do vencimento',
            'Seu acesso vence em '.mcfNotificacaoData($u['data_expiracao'])->format('d/m/Y').'. Consulte os dados da assinatura nas configurações.',
            '/MyCashFlow/views/configuracao.php',$u['data_expiracao']===$inicio);
    }
    $lidos=array_fill_keys(array_column(mcfNotificacaoRegistros($c,'SELECT chave FROM notificacao_lidos WHERE usuario_id=?',$uid),'chave'),true);
    foreach($avisos as &$aviso) $aviso['lido']=isset($lidos[$aviso['chave']]); unset($aviso);
    usort($avisos,static fn($a,$b)=>[$a['lido'],!$a['urgente'],$a['data'],$a['chave']]<=>[$b['lido'],!$b['urgente'],$b['data'],$b['chave']]);
    return $avisos;
}
function mcfNotificacoesDaSessao(mysqli $c): array {
    static $cache=[];
    $uid=mcfUsuarioId();$key=spl_object_id($c).':'.$uid;
    return $cache[$key] ??= mcfNotificacoes($c,$uid);
}
function mcfNotificacaoMarcarLida(mysqli $c, int $uid, string $chave): void {
    if (!preg_match('/^[a-f0-9]{64}$/D',$chave)) throw new DomainException('Aviso inválido.');
    if (!in_array($chave,array_column(mcfNotificacoes($c,$uid),'chave'),true)) throw new DomainException('Este aviso não está mais disponível. Atualize a página.');
    $s=$c->prepare('INSERT INTO notificacao_lidos(usuario_id,chave) VALUES(?,?) ON DUPLICATE KEY UPDATE chave=VALUES(chave)');
    $s->bind_param('is',$uid,$chave);$s->execute();$s->close();
}
function mcfLembreteCartaoSalvar(mysqli $c,int $uid,array $dados): void {
    $nome=$dados['nome']??null;$dia=$dados['dia_fechamento']??null;$id=$dados['id']??'0';
    if (!is_string($nome)||!is_string($dia)||!is_string($id)||!ctype_digit($id)||!ctype_digit($dia)) throw new DomainException('Informe o nome e o dia de fechamento.');
    $nome=trim($nome);$dia=(int)$dia;$id=(int)$id;
    if ($nome===''||mb_strlen($nome,'UTF-8')>80||preg_match('/[\x00-\x1F\x7F]/u',$nome)||$dia<1||$dia>31) throw new DomainException('Use um nome de até 80 caracteres e um dia de 1 a 31.');
    if ($id && !in_array($id,array_map('intval',array_column(mcfLembretesCartao($c,$uid),'id')),true)) throw new DomainException('Lembrete não encontrado.');
    if (!$id && count(mcfLembretesCartao($c,$uid))>=30) throw new DomainException('Limite de 30 lembretes de cartão atingido.');
    try {
        if ($id) {$s=$c->prepare('UPDATE notificacao_cartoes SET nome=?,dia_fechamento=? WHERE id=? AND usuario_id=?');$s->bind_param('siii',$nome,$dia,$id,$uid);}
        else {$s=$c->prepare('INSERT INTO notificacao_cartoes(usuario_id,nome,dia_fechamento) VALUES(?,?,?)');$s->bind_param('isi',$uid,$nome,$dia);}
        $s->execute();$s->close();
    } catch(mysqli_sql_exception $e) {if($e->getCode()===1062) throw new DomainException('Já existe um cartão com esse nome. Edite o lembrete existente.');throw $e;}
}
