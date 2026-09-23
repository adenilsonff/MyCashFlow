<?php
require_once __DIR__.'/../includes/comparativo.php';
$n=0;function check(bool $ok,string $label):void{global $n;if(!$ok)throw new RuntimeException($label);$n++;}
function operation(int $id,int $q,int $price,string $date,string $type='compra'):array{return ['id'=>$id,'ticker'=>'TEST3','tipo_ativo'=>'acao','quantidade'=>$q,'valor_unitario'=>$price,'data'=>$date,'tipo_operacao'=>$type];}
$h=[['pessoa'=>['id'=>1],'moeda'=>'BRL','operacoes'=>[operation(1,10,10,'2026-01-10'),operation(2,-5,50,'2026-03-10','venda')]],['pessoa'=>['id'=>2],'moeda'=>'BRL','operacoes'=>[operation(3,5,30,'2026-02-10')]]];
$e=mcfEvolucaoCalcular($h,['2025-12-31','2026-01-31','2026-02-28','2026-03-31']);
foreach(['0','100','250','200'] as $i=>$v)check(bccomp($e[$i]['BRL']['total'],$v,8)===0,'custo no fechamento '.$i);
check(bccomp($e[3]['BRL']['pessoas'][1],'50',8)===0,'venda retira custo e não valor recebido');check(bccomp($e[3]['BRL']['pessoas'][2],'150',8)===0,'históricos independentes');check(bccomp($e[3]['USD']['total'],'0',8)===0,'moedas separadas');
$port=[];foreach($h as $r)$port[]=['pessoa'=>$r['pessoa'],'moeda'=>'BRL','posicoes'=>acoesConsolidar($r['operacoes'])];
$r=mcfCalcularConjunto($port,['BRL'=>['TEST3'=>['price'=>25,'currency'=>'BRL']]]);$p=mcfParticipacao($r);
check(bccomp($p[0]['percentual_custo'],'25',4)===0,'participação por custo');check(bccomp($p[1]['percentual_custo'],'75',4)===0,'participações somam 100');check(bccomp($p[0]['percentual_mercado'],'50',4)===0,'participação mercado difere de custo');
$p=mcfParticipacao(mcfCalcularConjunto($port,[]));check($p[0]['percentual_mercado']===null,'cotação ausente não gera percentual fictício');
echo "PASS: $n verificações de comparativo\n";
