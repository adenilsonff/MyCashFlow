<?php
require_once __DIR__.'/../includes/visao_conjunta.php';
$n=0;
function verificar(bool $v,string $label): void { global $n;if (!$v) throw new RuntimeException($label);$n++; }
function op(int $id,int $q,int $v,string $data,string $tipo='compra'): array { return ['id'=>$id,'ticker'=>'TEST3','tipo_ativo'=>'acao','quantidade'=>$q,'valor_unitario'=>$v,'data'=>$data,'tipo_operacao'=>$tipo]; }
$a=acoesConsolidar([op(1,10,10,'2026-01-01'),op(3,-5,20,'2026-03-01','venda')]);
$b=acoesConsolidar([op(2,5,30,'2026-02-01')]);
$carteiras=[['pessoa'=>['id'=>1],'moeda'=>'BRL','posicoes'=>$a],['pessoa'=>['id'=>2],'moeda'=>'BRL','posicoes'=>$b]];
$q=['BRL'=>['TEST3'=>['price'=>'25.00','currency'=>'BRL','source'=>'Fixture','market_time'=>1,'stale'=>false]]];
$r=mcfCalcularConjunto($carteiras,$q);
verificar(count($r['individuais'])===2,'mantém linhas dos titulares');
verificar(count($r['agrupadas'])===1,'agrupa mesmo ativo e moeda');
verificar(bccomp($r['agrupadas'][0]['quantidade'],'10',8)===0,'quantidade conjunta');
verificar(bccomp($r['totais']['BRL']['custo'],'200',8)===0,'custo calculado por dono antes de somar');
verificar(bccomp($r['agrupadas'][0]['medio'],'20',8)===0,'média ponderada das posições remanescentes');
verificar(bccomp($r['totais']['BRL']['mercado'],'250',8)===0,'mercado conjunto');
verificar(bccomp($r['totais']['BRL']['resultado'],'50',8)===0,'lucro não realizado');
verificar(bccomp($r['individuais'][0]['resultado'],'75',8)===0,'lucro do primeiro titular');
verificar(bccomp($r['individuais'][1]['resultado'],'-25',8)===0,'prejuízo do segundo titular');
verificar(bccomp($r['agrupadas'][0]['percentual'],'25',8)===0,'percentual sobre custo');
$r=mcfCalcularConjunto($carteiras,[]);verificar($r['totais']['BRL']['mercado']===null && $r['totais']['BRL']['resultado']===null,'cotação ausente não vira zero');
verificar($r['totais']['BRL']['faltam']===1,'conta ativos sem preço');
$q['BRL']['TEST3']['currency']='USD';$r=mcfCalcularConjunto($carteiras,$q);verificar($r['totais']['BRL']['mercado']===null,'moeda incorreta não é somada');
$q['BRL']['TEST3']['currency']='BRL';$q['BRL']['TEST3']['stale']=true;$r=mcfCalcularConjunto($carteiras,$q);verificar($r['totais']['BRL']['stale'],'indica cotação antiga');
$carteiras[]=['pessoa'=>['id'=>1],'moeda'=>'USD','posicoes'=>[['ticker'=>'TEST3','tipo_ativo'=>'stock','quantidade_total'=>'0.125','total_investido_usd'=>'10.5']]];
$q['USD']['TEST3']=['price'=>'100','currency'=>'USD'];$r=mcfCalcularConjunto($carteiras,$q);
verificar(count($r['agrupadas'])===2,'separa mesmo ticker em moedas diferentes');
verificar(bccomp($r['totais']['USD']['mercado'],'12.5',8)===0,'preserva frações internacionais');
verificar(bccomp($r['totais']['USD']['resultado'],'2',8)===0,'resultado internacional');
echo "PASS: $n verificações de cálculo conjunto\n";
