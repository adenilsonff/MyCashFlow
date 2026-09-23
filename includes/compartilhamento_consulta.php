<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/compartilhamento.php';

// Consultas próprias da área de leitura. Nunca altera a identidade ou @mcf_usuario_id.
function mcfCompartilhamentoSecoes(): array {
    return [
        'despesas'=>['despesas'=>['Despesas','SELECT nome,categoria,vencimento,paga,valor FROM contas WHERE usuario_id=?','vencimento','vencimento,id', ['nome'=>['Descrição','texto'],'categoria'=>['Categoria','texto'],'vencimento'=>['Vencimento','data'],'paga'=>['Paga','sim'],'valor'=>['Valor','brl']], 'valor']],
        'receitas'=>['receitas'=>['Receitas','SELECT nome,descricao,classificacao,data,recebido,valor FROM rendas WHERE usuario_id=?','data','data,id', ['nome'=>['Nome','texto'],'descricao'=>['Descrição','texto'],'classificacao'=>['Classificação','texto'],'data'=>['Data','data'],'recebido'=>['Recebida','sim'],'valor'=>['Valor','brl']], 'valor']],
        'cartao'=>['parcelas'=>['Parcelas do cartão','SELECT p.nome,p.categoria,c.data,c.parcela,c.paga,c.valor FROM cartoes c JOIN compras p ON p.id=c.compra_id AND p.usuario_id=c.usuario_id WHERE c.usuario_id=?','c.data','c.data,c.id', ['nome'=>['Compra','texto'],'categoria'=>['Categoria','texto'],'data'=>['Fatura','data'],'parcela'=>['Parcela','texto'],'paga'=>['Paga','sim'],'valor'=>['Valor','brl']], 'valor']],
        'saldos'=>[
            'contas'=>['Contas e saldos atuais',null,null,null,['nome'=>['Conta','texto'],'instituicao'=>['Instituição','texto'],'tipo'=>['Tipo','texto'],'ativa'=>['Ativa','sim'],'saldo_atual'=>['Saldo atual','brl']],null],
            'movimentacoes'=>['Movimentações','SELECT c.nome,m.data,m.tipo,m.descricao,m.valor FROM movimentacoes_financeiras m JOIN contas_financeiras c ON c.id=m.conta_id AND c.usuario_id=m.usuario_id WHERE m.usuario_id=?','m.data','m.data,m.id',['nome'=>['Conta','texto'],'data'=>['Data','data'],'tipo'=>['Tipo','texto'],'descricao'=>['Descrição','texto'],'valor'=>['Valor','brl']],null]],
        'investimentos'=>[
            'posicoes_nacionais'=>['Posições nacionais ao custo',null,null,null,['ticker'=>['Ativo','texto'],'tipo_ativo'=>['Tipo','texto'],'quantidade_total'=>['Quantidade','numero'],'valor_medio_ponderado'=>['Preço médio','brl'],'total_investido'=>['Custo da posição','brl']],null],
            'posicoes_internacionais'=>['Posições internacionais ao custo',null,null,null,['ticker'=>['Ativo','texto'],'tipo_ativo'=>['Tipo','texto'],'quantidade_total'=>['Quantidade','numero'],'valor_medio_ponderado'=>['Preço médio','usd'],'total_investido_usd'=>['Custo da posição','usd']],null],
            'operacoes_nacionais'=>['Operações nacionais','SELECT ticker,tipo_ativo,data,tipo_operacao,quantidade,valor_unitario FROM investimentos_nacionais WHERE usuario_id=?','data','data,id',['ticker'=>['Ativo','texto'],'tipo_ativo'=>['Tipo','texto'],'data'=>['Data','data'],'tipo_operacao'=>['Operação','texto'],'quantidade'=>['Quantidade','numero'],'valor_unitario'=>['Preço unitário','brl']],null],
            'operacoes_internacionais'=>['Operações internacionais','SELECT ticker,tipo_ativo,data,tipo_operacao,quantidade,valor_unitario FROM investimentos_internacionais WHERE usuario_id=?','data','data,id',['ticker'=>['Ativo','texto'],'tipo_ativo'=>['Tipo','texto'],'data'=>['Data','data'],'tipo_operacao'=>['Operação','texto'],'quantidade'=>['Quantidade','numero'],'valor_unitario'=>['Preço unitário','usd']],null],
            'proventos'=>['Calendário de proventos (valor por ativo)','SELECT ticker,tipo,datacom,datapag,valor FROM div_datacom WHERE usuario_id=?','datacom','datacom,id',['ticker'=>['Ativo','texto'],'tipo'=>['Tipo','texto'],'datacom'=>['Data com','data'],'datapag'=>['Pagamento','data'],'valor'=>['Valor por unidade','decimal']],null]],
        'daytrade'=>['operacoes'=>['Operações de day trade','SELECT c.nome,o.data,o.acao,o.quantidade,o.total_compra,o.total_venda,o.taxas,o.darf,o.lucro_final FROM operacoes o JOIN corretoras c ON c.id=o.corretora_id AND c.usuario_id=o.usuario_id WHERE o.usuario_id=?','o.data','o.data,o.id',['nome'=>['Corretora','texto'],'data'=>['Data','data'],'acao'=>['Ativo','texto'],'quantidade'=>['Quantidade','numero'],'total_compra'=>['Compras','brl'],'total_venda'=>['Vendas','brl'],'taxas'=>['Taxas','brl'],'darf'=>['DARF','brl'],'lucro_final'=>['Lucro final','brl']],'lucro_final']],
        'analise'=>[
            'marcacoes'=>['Linhas e notas','SELECT ticker,tipo_ativo,tipo,titulo,preco,texto FROM analise_marcacoes WHERE usuario_id=?',null,'ticker,id',['ticker'=>['Ativo','texto'],'tipo_ativo'=>['Tipo de ativo','texto'],'tipo'=>['Marcação','texto'],'titulo'=>['Título','texto'],'preco'=>['Preço da linha','decimal'],'texto'=>['Nota','texto']],null],
            'acompanhamento'=>['Ativos acompanhados','SELECT ticker,tipo_ativo FROM analise_acompanhamento WHERE usuario_id=?',null,'ticker,tipo_ativo',['ticker'=>['Ativo','texto'],'tipo_ativo'=>['Tipo','texto']],null]]
    ];
}
function mcfCompartilhamentoData(string $v): string {
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if (!$d || $d->format('Y-m-d')!==$v) throw new DomainException('Informe datas válidas.');
    return $v;
}
function mcfCompartilhamentoConsultar(mysqli $c, int $convite, string $modulo, string $secao, string $inicio, string $fim, int $pagina): array {
    // Autorização é reavaliada em cada consulta, sem armazenar permissões em sessão.
    $pessoa=$convite===0 ? ['proprietario_id'=>mcfUsuarioId(),'nome'=>$_SESSION['usuario_nome'] ?? null,'email'=>$_SESSION['usuario_email']] : mcfCompartilhamentoExigir($c,$convite,$modulo);
    $def=mcfCompartilhamentoSecoes()[$modulo][$secao] ?? null;
    if (!$def) mcfFalhar(404,'Consulta não disponível.');
    $uid=(int)$pessoa['proprietario_id'];
    [$titulo,$sql,$data,$ordem,$colunas,$somar]=$def;
    $offset=($pagina-1)*50; $totalValor=null;
    if ($sql===null) {
        if ($secao==='contas') { require_once __DIR__.'/saldos_consulta.php'; $todos=saldosListarContas($c,$uid); }
        else {
            require_once __DIR__.'/carteiras_posicoes.php';
            $internacional=$secao==='posicoes_internacionais';
            $tabela=$internacional ? 'investimentos_internacionais' : 'investimentos_nacionais';
            $s=$c->prepare('SELECT id,ticker,tipo_ativo,data,tipo_operacao,quantidade,valor_unitario FROM '.$tabela.' WHERE usuario_id=? ORDER BY data,id');
            $s->bind_param('i',$uid); $s->execute(); $ops=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
            $todos=array_values($internacional ? internacionalConsolidar($ops) : acoesConsolidar($ops));
        }
        $quantidade=count($todos); $rows=array_slice($todos,$offset,50);
    } else {
        if ($data!==null) $sql.=' AND '.$data.' BETWEEN ? AND ?';
        $s=$c->prepare('SELECT COUNT(*) quantidade'.($somar ? ',COALESCE(SUM(`'.$somar.'`),0) total' : '').' FROM ('.$sql.') dados');
        if ($data!==null) $s->bind_param('iss',$uid,$inicio,$fim); else $s->bind_param('i',$uid);
        $s->execute(); $res=$s->get_result()->fetch_assoc(); $s->close(); $quantidade=(int)$res['quantidade']; $totalValor=$res['total'] ?? null;
        $s=$c->prepare($sql.' ORDER BY '.$ordem.' LIMIT 50 OFFSET ?');
        if ($data!==null) $s->bind_param('issi',$uid,$inicio,$fim,$offset); else $s->bind_param('ii',$uid,$offset);
        $s->execute(); $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close();
    }
    return compact('pessoa','titulo','data','colunas','rows','quantidade','totalValor','somar');
}
function mcfCompartilhamentoFormatar(mixed $v, string $tipo): string {
    if ($v===null) return '—';
    return match($tipo) {
        'data'=>date('d/m/Y',strtotime((string)$v)), 'sim'=>(int)$v===1 ? 'Sim' : 'Não',
        'brl'=>'R$ '.number_format((float)$v,2,',','.'), 'usd'=>'US$ '.number_format((float)$v,4,',','.'),
        'numero','decimal'=>rtrim(rtrim(number_format((float)$v,8,',','.'),'0'),','), default=>(string)$v
    };
}
