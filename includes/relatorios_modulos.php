<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/relatorios_core.php';
require_once __DIR__.'/relatorios_mercado.php';
function relModulo(string $modulo): array {
    $common=' A situação é a atual, sem data efetiva de liquidação; não representa caixa histórico. O consolidado soma registros dos titulares, sem identificação automática de duplicatas entre perfis.';
    return match($modulo) {
        'gastos'=>['permissao'=>'despesas','titulo'=>'Gastos','labels'=>['total'=>'Total previsto','efetivado'=>'Pago','pendente'=>'A pagar','atrasado'=>'Vencido','recorrente'=>'Recorrente','parcelada'=>'Parcelado'],'series'=>['Pago','A pagar'],'aviso'=>'Base: vencimento das despesas. Pessoal/conjunta indica natureza, não finalidade. Parcelas de cartão não são incluídas.'.$common],
        'receitas'=>['permissao'=>'receitas','titulo'=>'Receitas','labels'=>['total'=>'Total previsto','efetivado'=>'Recebido','pendente'=>'A receber','atrasado'=>'Em atraso','recorrente'=>'Recorrente','parcelada'=>'Parcelado'],'series'=>['Recebido','A receber'],'aviso'=>'Base: data cadastrada da receita. Regular/extra é a classificação disponível. Transferências e investimentos não são incluídos automaticamente.'.$common],
        'cartao'=>['permissao'=>'cartao','titulo'=>'Cartões','labels'=>['total'=>'Valor líquido','compras'=>'Compras / débitos','creditos'=>'Créditos (negativos)','efetivado'=>'Liquidado líquido','pendente'=>'Em aberto líquido','atrasado'=>'Aberto em datas passadas'],'series'=>['Compras / débitos','Créditos'],'aviso'=>'Base: data de cada parcela da fatura, que não comprova o vencimento bancário. Créditos negativos reduzem o valor líquido. O valor total da compra e pagamentos em despesas não são somados novamente. Não há identificação de emissor/cartão nesta base.'.$common],
        'investimentos','proventos','daytrade'=>relMercadoDef($modulo),
        default=>throw new DomainException('Módulo de relatório inválido.')
    };
}
function relModuloAgregar(array $rows,array $p,array $f,string $hoje,string $modulo): array {
    $empty=array_fill_keys(['total','efetivado','pendente','atrasado','recorrente','parcelada','compras','creditos','n'],0);
    $buckets=[];
    for($d=new DateTimeImmutable($p['inicio']);$d->format('Y-m-d')<=$p['fim'];$d=$d->modify('+1 day')){$key=relChave($d->format('Y-m-d'),$f['agrupamento']);$buckets[$key]=['chave'=>$key]+$empty;}
    $detalhes=[];$naturezas=[];$titulares=[];
    foreach($rows as $r){
        if($r['data']<$p['inicio']||$r['data']>$p['fim'])continue;
        if($f['situacao']==='realizados'&&!$r['realizado'] || $f['situacao']==='pendentes'&&$r['realizado'] || $f['situacao']==='vencidos'&&($r['realizado']||$r['data']>=$hoje))continue;
        if($f['tipo']!=='todos'&&$r['tipo']!==$f['tipo'])continue;
        $filter=$modulo==='receitas'?'classificacao':'natureza';
        if($f[$filter]!=='todos'&&$r['categoria']!==$f[$filter])continue;
        $key=relChave($r['data'],$f['agrupamento']);$b=&$buckets[$key];$v=$r['centavos'];$b['n']++;$b['total']+=$v;
        $b[$r['realizado']?'efetivado':'pendente']+=$v;
        if(!$r['realizado']&&$r['data']<$hoje)$b['atrasado']+=$v;
        if(in_array($r['tipo'],['recorrente','parcelada'],true))$b[$r['tipo']]+=$v;
        $b[$v<0?'creditos':'compras']+=$v;unset($b);
        $naturezas[$r['categoria']]??=0;$naturezas[$r['categoria']]+=$v;
        $titulares[$r['usuario_id']]??=['nome'=>$r['titular'],'total'=>0,'efetivado'=>0,'pendente'=>0];
        $titulares[$r['usuario_id']]['total']+=$v;$titulares[$r['usuario_id']][$r['realizado']?'efetivado':'pendente']+=$v;
        $r['grupo']=$key;$detalhes[]=$r;
    }
    $total=$empty;foreach($buckets as $b)foreach($empty as $k=>$_)$total[$k]+=$b[$k];
    return $p+['grupos'=>array_values($buckets),'total'=>$total,'detalhes'=>$detalhes,'naturezas'=>$naturezas,'titulares'=>$titulares];
}
function relModuloCarregar(mysqli $c,array $f,array $pessoas,string $modulo): array {
    relModulo($modulo);$periodos=relPeriodos($f);$inicio=min(array_column($periodos,'inicio'));$fim=max(array_column($periodos,'fim'));$rows=[];
    $sql=match($modulo){
        'gastos'=>'SELECT id,nome,tipo,vencimento AS data,paga AS realizado,categoria,valor FROM contas WHERE usuario_id=? AND vencimento BETWEEN ? AND ? ORDER BY vencimento,id LIMIT 6001',
        'receitas'=>'SELECT id,nome,tipo,data,recebido AS realizado,classificacao AS categoria,valor FROM rendas WHERE usuario_id=? AND data BETWEEN ? AND ? ORDER BY data,id LIMIT 6001',
        'cartao'=>"SELECT c.id,b.nome,IF(b.total_parcelas>1,'parcelada','unica') AS tipo,c.data,c.paga AS realizado,b.categoria,c.valor,c.parcela,b.total_parcelas,c.compra_id FROM cartoes c JOIN compras b ON b.id=c.compra_id AND b.usuario_id=c.usuario_id WHERE c.usuario_id=? AND c.data BETWEEN ? AND ? ORDER BY c.data,c.id LIMIT 6001"
    };
    $c->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
    try{foreach($pessoas as $p){$uid=(int)$p['id'];$stmt=$c->prepare($sql);$stmt->bind_param('iss',$uid,$inicio,$fim);$stmt->execute();
        foreach($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r){if(count($rows)>=6000)throw new DomainException('A consulta supera 6.000 lançamentos. Reduza o intervalo ou os perfis.');$r['centavos']=relCentavos($r['valor']);unset($r['valor']);$r['titular']=$p['nome']?:$p['email'];$r['usuario_id']=$uid;$rows[]=$r;}$stmt->close();
    }$c->commit();}catch(Throwable $e){$c->rollback();throw $e;}
    usort($rows,fn($a,$b)=>[$a['data'],$a['usuario_id'],$a['id']]<=>[$b['data'],$b['usuario_id'],$b['id']]);$hoje=date('Y-m-d');
    return ['modulo'=>$modulo,'filtros'=>$f,'pessoas'=>array_values($pessoas),'gerado'=>date('d/m/Y H:i:s'),'hoje'=>$hoje,'periodos'=>array_map(fn($p)=>relModuloAgregar($rows,$p,$f,$hoje,$modulo),$periodos)];
}
