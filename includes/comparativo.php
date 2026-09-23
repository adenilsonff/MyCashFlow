<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(404);exit;}
require_once __DIR__.'/visao_conjunta.php';
function mcfParticipacao(array $carteira): array {
 $out=[];
 foreach($carteira['individuais'] as $r) {
  $key=$r['moeda'].'|'.$r['titular']['id'];
  $out[$key]??=['pessoa'=>$r['titular'],'moeda'=>$r['moeda'],'custo'=>'0','mercado'=>'0','completo'=>true];
  $out[$key]['custo']=bcadd($out[$key]['custo'],$r['custo'],12);
  if($r['mercado']===null)$out[$key]['completo']=false;else $out[$key]['mercado']=bcadd($out[$key]['mercado'],$r['mercado'],12);
 }
 foreach($out as &$r){$total=$carteira['totais'][$r['moeda']];$r['percentual_custo']=bccomp($total['custo'],'0',12)>0?bcmul(bcdiv($r['custo'],$total['custo'],12),'100',4):'0';$r['percentual_mercado']=$r['completo']&&$total['mercado']!==null&&bccomp($total['mercado'],'0',12)>0?bcmul(bcdiv($r['mercado'],$total['mercado'],12),'100',4):null;if(!$r['completo'])$r['mercado']=null;}unset($r);
 return array_values($out);
}
function mcfEvolucaoCalcular(array $historicos,array $datas): array {
 $out=[];
 foreach($datas as $data){$row=['data'=>$data,'BRL'=>['total'=>'0','pessoas'=>[]],'USD'=>['total'=>'0','pessoas'=>[]]];
  foreach($historicos as $h){$ops=array_values(array_filter($h['operacoes'],static fn($o)=>$o['data']<=$data));$m=$h['moeda'];$pos=$m==='BRL'?acoesConsolidar($ops):internacionalConsolidar($ops);$cost='0';foreach($pos as $p)$cost=bcadd($cost,mcfDecimalConjunto($p[$m==='BRL'?'total_investido':'total_investido_usd']),12);$row[$m]['pessoas'][(int)$h['pessoa']['id']]=$cost;$row[$m]['total']=bcadd($row[$m]['total'],$cost,12);}
  $out[]=$row;
 }
 return $out;
}
function mcfEvolucao(mysqli $c,array $pessoas): array {
 $historicos=[];
 foreach($pessoas as $p)foreach(['BRL'=>'investimentos_nacionais','USD'=>'investimentos_internacionais'] as $m=>$t){$s=$c->prepare("SELECT id,ticker,tipo_ativo,data,tipo_operacao,quantidade,valor_unitario FROM `$t` WHERE usuario_id=? ORDER BY data,id");$s->bind_param('i',$p['id']);$s->execute();$historicos[]=['pessoa'=>$p,'moeda'=>$m,'operacoes'=>$s->get_result()->fetch_all(MYSQLI_ASSOC)];$s->close();}
 $datas=[];$first=new DateTimeImmutable('first day of this month');for($i=11;$i>=0;$i--)$datas[]=$i===0?date('Y-m-d'):$first->modify('-'.$i.' months')->modify('last day of this month')->format('Y-m-d');
 return mcfEvolucaoCalcular($historicos,$datas);
}
