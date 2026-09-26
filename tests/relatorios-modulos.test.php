<?php
require __DIR__.'/../includes/relatorios_modulos_view.php';
$n=0;function check($v,$msg){global $n;if(!$v)throw new RuntimeException($msg);$n++;}
$f=relFiltros(['ano'=>2026]);$p=relPeriodos($f)[0];
$base=['data'=>'2026-03-01','realizado'=>0,'tipo'=>'parcelada','categoria'=>'pessoal','usuario_id'=>1,'titular'=>'Alfa','nome'=>'Teste','id'=>1];
$rows=[$base+['centavos'=>10000],array_replace($base,['centavos'=>-2500,'realizado'=>1,'id'=>2]),array_replace($base,['centavos'=>0,'data'=>'2026-01-01','id'=>3])];
$a=relModuloAgregar($rows,$p,$f,'2026-09-26','cartao');
foreach(['total'=>7500,'compras'=>10000,'creditos'=>-2500,'efetivado'=>-2500,'pendente'=>10000,'atrasado'=>10000,'n'=>3] as $key=>$v)check($a['total'][$key]===$v,$key);
check(relModuloValor($a['grupos'][0],'total')===0,'zero real');check(relModuloValor($a['grupos'][1],'total')===null,'sem dados');
check($a['naturezas']['pessoal']===7500,'natureza com crédito');check($a['titulares'][1]['total']===7500,'participação');
check(count(relModuloAgregar($rows,$p,array_replace($f,['situacao'=>'realizados']),'2026-09-26','cartao')['detalhes'])===1,'situação');
check(count(relModuloAgregar($rows,$p,array_replace($f,['natureza'=>'conjunta']),'2026-09-26','cartao')['detalhes'])===0,'natureza');
$r=[$base+['centavos'=>12345],array_replace($base,['centavos'=>4000,'tipo'=>'recorrente','realizado'=>1])];
foreach(['gastos','receitas'] as $mod){$a=relModuloAgregar($r,$p,$f,'2026-09-26',$mod);check($a['total']['total']===16345,'total');check($a['total']['recorrente']===4000,'recorrente');check($a['total']['parcelada']===12345,'parcelada');check($a['total']['efetivado']+$a['total']['pendente']===$a['total']['total'],'reconciliação');}
$svg=relModuloSvg([['chave'=>'2026-03','compras'=>10000,'creditos'=>-2500,'n'=>2]],'empilhadas','cartao');check(str_contains($svg,'Créditos')&&!str_contains($svg,'Receitas previstas'),'legenda');
echo "PASS: $n verificações de módulos\n";
