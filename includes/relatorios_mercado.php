<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(404);exit;}
require_once __DIR__.'/relatorios_core.php';
function relMercadoDef(string $m): array {
    return match($m){
        'investimentos'=>['permissao'=>'investimentos','titulo'=>'Investimentos · movimentações','labels'=>['total'=>'Compras menos vendas','efetivado'=>'Compras','pendente'=>'Vendas'],'series'=>['Compras','Vendas'],'grupo'=>'Classe de ativo','aviso'=>'Valores negociados: quantidade × preço unitário, arredondados por operação. Compras menos vendas mede movimentação, não lucro, rentabilidade ou valor da carteira. BRL e USD são separados, sem conversão cambial. Taxas não estão disponíveis nestes registros. O gráfico empilhado mostra o volume de compras e vendas, não o saldo.'],
        'proventos'=>['permissao'=>'investimentos','titulo'=>'Proventos · estimativas','labels'=>['total'=>'Estimativa bruta','efetivado'=>'Com data de pagamento','pendente'=>'Sem data de pagamento','dividendos'=>'Dividendos','jcp'=>'JCP','rendimentos'=>'Rendimentos'],'series'=>['Com data de pagamento','Sem data de pagamento'],'grupo'=>'Classe de ativo','aviso'=>'Estimativa bruta: posição líquida na data-com × valor por unidade, arredondada por evento. Considera compras e vendas até a data-com, inclusive. Não confirma recebimento, não desconta tributos e não inclui ativos internacionais. Posições não positivas são excluídas. Na base pagamento, eventos sem data ficam fora da consulta.'],
        'daytrade'=>['permissao'=>'daytrade','titulo'=>'Day trade · resultados registrados','labels'=>['total'=>'Resultado final registrado','efetivado'=>'Resultados positivos','pendente'=>'Resultados negativos','bruto'=>'Resultado bruto','taxas'=>'Taxas registradas','darf'=>'DARF registrado'],'series'=>['Resultados positivos','Resultados negativos'],'grupo'=>'Ativo','aviso'=>'Soma os campos registrados das operações. Não recalcula impostos, compensações de prejuízo ou DARF devido. Resultado final e DARF podem refletir estimativas do cadastro; não comprovam recolhimento. Operações abertas podem ter resultados provisórios. Resultados positivos e negativos reconciliam com o final, enquanto bruto, taxas e DARF são apresentados separadamente.'],
        default=>throw new DomainException('Relatório inválido.')};
}
function relMercadoFiltros(array $q,string $m): array {
    $f=relFiltros($q);$f['moeda']=$m==='investimentos'?relOpcao($q,'moeda',['BRL','USD'],'BRL'):'BRL';
    $types=$m==='investimentos'&&$f['moeda']==='USD'?['todos','stock','etf','reit','adr','cripto']:['todos','acao','fii','etf','bdr'];
    $f['tipo_ativo']=relOpcao($q,'tipo_ativo',$types,'todos');
    $f['operacao']=relOpcao($q,'operacao',$m==='investimentos'?['todos','compra','venda']:($m==='proventos'?['todos','DIV','JCP','REND']:['todos','abertas','concluidas']),'todos');
    $f['data_base']=relOpcao($q,'data_base',['datacom','datapag'],'datacom');
    $ticker=$q['ticker']??'';if(!is_string($ticker)||($ticker!==''&&!preg_match('/^[A-Za-z0-9.\-]{1,20}$/D',$ticker)))throw new DomainException('Ativo inválido.');$f['ticker']=strtoupper($ticker);
    $corretora=$q['corretora_id']??'0';if(!is_scalar($corretora)||filter_var($corretora,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]])===false)throw new DomainException('Corretora inválida.');$f['corretora_id']=(int)$corretora;
    return $f;
}
function relDecimalCentavos(string $v): int {
    $negative=bccomp($v,'0',16)<0;$abs=ltrim($v,'-+');$c=bcadd(bcmul($abs,'100',16),'0.5',0);
    if(bccomp($c,'100000000000000',0)>0)throw new DomainException('Valor fora do limite suportado para relatório.');
    return ($negative?-1:1)*(int)$c;
}
function relMercadoNormalizar(array $r,string $m): array {
    $metrics=array_fill_keys(array_keys(relMercadoDef($m)['labels']),0);
    if($m==='investimentos'){
        $r['tipo']=$r['tipo_operacao']==='venda'||bccomp((string)$r['quantidade'],'0',8)<0?'venda':'compra';
        $v=relDecimalCentavos(bcmul(ltrim((string)$r['quantidade'],'-+'),$r['valor_unitario'],16));
        $metrics[$r['tipo']==='compra'?'efetivado':'pendente']=$v;$metrics['total']=$r['tipo']==='compra'?$v:-$v;
        $r['info']='Quantidade: '.$r['quantidade'].' · preço unitário: '.$r['valor_unitario'];
    }elseif($m==='proventos'){
        $q=(string)$r['quantidade_elegivel'];$v=relDecimalCentavos(bcmul($q,$r['valor'],16));$metrics['total']=$v;
        $metrics[$r['datapag']?'efetivado':'pendente']=$v;$metrics[match($r['tipo']){'DIV'=>'dividendos','JCP'=>'jcp',default=>'rendimentos'}]=$v;
        $r['info']='Data-com: '.$r['datacom'].' · pagamento previsto: '.($r['datapag']?:'não informado').' · quantidade elegível: '.$q.' · valor unitário: '.$r['valor'];
    }else{
        foreach(['total'=>'lucro_final','bruto'=>'lucro_bruto','taxas'=>'taxas','darf'=>'darf'] as $k=>$source)$metrics[$k]=relCentavos($r[$source]??'0');
        $metrics[$metrics['total']>=0?'efetivado':'pendente']=$metrics['total'];
        $r['tipo']=bccomp($r['total_compra'],'0',2)>0&&bccomp($r['total_venda'],'0',2)>0?'concluidas':'abertas';
        $r['info']=$r['corretora_nome'].' · bruto: '.relMoeda($metrics['bruto']).' · taxas: '.relMoeda($metrics['taxas']).' · DARF registrado: '.relMoeda($metrics['darf']);
    }
    $r['nome']=$r['ticker'];$r['categoria']=$m==='daytrade'?$r['ticker']:$r['tipo_ativo'];$r['centavos']=$metrics['total'];$r['metrics']=$metrics;$r['realizado']=false;
    return $r;
}
function relMercadoAgregar(array $rows,array $p,array $f,string $m): array {
    $zero=array_fill_keys(array_merge(array_keys(relMercadoDef($m)['labels']),['n']),0);$buckets=[];
    for($d=new DateTimeImmutable($p['inicio']);$d->format('Y-m-d')<=$p['fim'];$d=$d->modify('+1 day')){$k=relChave($d->format('Y-m-d'),$f['agrupamento']);$buckets[$k]=['chave'=>$k]+$zero;}
    $detalhes=[];$naturezas=[];$titulares=[];
    foreach($rows as $r){
        if($r['data']<$p['inicio']||$r['data']>$p['fim'])continue;
        if($f['ticker']!==''&&strtoupper(trim($r['ticker']))!==$f['ticker'])continue;
        if($m!=='daytrade'&&$f['tipo_ativo']!=='todos'&&$r['tipo_ativo']!==$f['tipo_ativo'])continue;
        if($f['operacao']!=='todos'&&$r['tipo']!==$f['operacao'])continue;
        if($m==='daytrade'&&$f['corretora_id']&&$r['corretora_id']!=$f['corretora_id'])continue;
        $k=relChave($r['data'],$f['agrupamento']);$buckets[$k]['n']++;foreach($r['metrics'] as $metric=>$v)$buckets[$k][$metric]+=$v;
        $naturezas[$r['categoria']]??=0;$naturezas[$r['categoria']]+=$r['centavos'];
        $titulares[$r['usuario_id']]??=['nome'=>$r['titular'],'total'=>0,'efetivado'=>0,'pendente'=>0];foreach(['total','efetivado','pendente'] as $metric)$titulares[$r['usuario_id']][$metric]+=$r['metrics'][$metric];
        $r['grupo']=$k;$detalhes[]=$r;
    }
    $total=$zero;foreach($buckets as $b)foreach($zero as $k=>$_)$total[$k]+=$b[$k];
    return $p+['grupos'=>array_values($buckets),'total'=>$total,'detalhes'=>$detalhes,'naturezas'=>$naturezas,'titulares'=>$titulares];
}
function relMercadoCarregar(mysqli $c,array $f,array $pessoas,string $m): array {
    $periodos=relPeriodos($f);$inicio=min(array_column($periodos,'inicio'));$fim=max(array_column($periodos,'fim'));$rows=[];
    if($m==='investimentos'){$t=$f['moeda']==='USD'?'investimentos_internacionais':'investimentos_nacionais';$sql="SELECT id,ticker,tipo_ativo,tipo_operacao,quantidade,valor_unitario,data FROM $t WHERE usuario_id=? AND data BETWEEN ? AND ? ORDER BY data,id LIMIT 6001";}
    elseif($m==='proventos'){$d=$f['data_base'];$sql="SELECT d.*,d.$d AS data,COALESCE((SELECT SUM(CASE WHEN a.tipo_operacao='venda' OR a.quantidade<0 THEN -ABS(a.quantidade) ELSE ABS(a.quantidade) END) FROM investimentos_nacionais a WHERE a.usuario_id=d.usuario_id AND UPPER(TRIM(a.ticker))=UPPER(TRIM(d.ticker)) AND a.tipo_ativo=d.tipo_ativo AND a.data<=d.datacom),0) AS quantidade_elegivel FROM div_datacom d WHERE d.usuario_id=? AND d.$d BETWEEN ? AND ? ORDER BY d.$d,d.id LIMIT 6001";}
    else{$sql='SELECT o.*,o.acao AS ticker,c.nome AS corretora_nome FROM operacoes o JOIN corretoras c ON c.id=o.corretora_id AND c.usuario_id=o.usuario_id WHERE o.usuario_id=? AND o.data BETWEEN ? AND ? ORDER BY o.data,o.id LIMIT 6001';}
    $c->begin_transaction(MYSQLI_TRANS_START_READ_ONLY|MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);$count=0;
    try{foreach($pessoas as $person){$uid=(int)$person['id'];$s=$c->prepare($sql);$s->bind_param('iss',$uid,$inicio,$fim);$s->execute();foreach($s->get_result()->fetch_all(MYSQLI_ASSOC) as $row){if(++$count>6000)throw new DomainException('A consulta supera 6.000 registros. Reduza o período ou os perfis.');if($m==='proventos'&&bccomp((string)$row['quantidade_elegivel'],'0',8)<=0)continue;$row['usuario_id']=$uid;$row['titular']=$person['nome']?:$person['email'];$rows[]=relMercadoNormalizar($row,$m);}$s->close();}$c->commit();}catch(Throwable $e){$c->rollback();throw $e;}
    usort($rows,fn($a,$b)=>[$a['data'],$a['usuario_id'],$a['id']]<=>[$b['data'],$b['usuario_id'],$b['id']]);
    return ['modulo'=>$m,'moeda'=>$f['moeda'],'filtros'=>$f,'pessoas'=>array_values($pessoas),'gerado'=>date('d/m/Y H:i:s'),'hoje'=>date('Y-m-d'),'periodos'=>array_map(fn($p)=>relMercadoAgregar($rows,$p,$f,$m),$periodos)];
}
