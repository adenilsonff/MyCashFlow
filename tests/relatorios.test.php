<?php
require_once __DIR__.'/../includes/relatorios_core.php';
$n=0;function check($v,$name){global $n;if(!$v)throw new RuntimeException($name);$n++;}
function row($date,$value,$mod='receitas',$done=0){return ['id'=>1,'data'=>$date,'centavos'=>relCentavos($value),'modulo'=>$mod,'realizado'=>$done,'tipo'=>'unica','categoria'=>$mod==='receitas'?'regular':'pessoal','nome'=>'Teste','titular'=>'Alfa','usuario_id'=>1];}
check(relCentavos('0.01')===1,'centavo');check(relCentavos('-12.34')===-1234,'negativo');check(relCentavos('99999999.99')===9999999999,'limite decimal');
foreach(['semana','mes','trimestre','semestre','ano','ytd','36meses'] as $periodo){$f=relFiltros(['periodo'=>$periodo,'referencia'=>'2026-01-01']);check($f['inicio']<=$f['fim'],'intervalo '.$periodo);}
$f=relFiltros(['periodo'=>'semana','referencia'=>'2026-01-01']);check($f['inicio']==='2025-12-29'&&$f['fim']==='2026-01-04','virada semanal');
$f=relFiltros(['periodo'=>'36meses','referencia'=>'2026-09-26']);check($f['inicio']==='2023-10-01'&&$f['fim']==='2026-09-26','36 meses corte');
check(relAnoAnterior('2024-02-29',1)==='2023-02-28','bissexto');
$f=relFiltros(['periodo'=>'ytd','referencia'=>'2026-09-26','comparacao'=>'tres_anos']);$p=relPeriodos($f);check($p[2]['fim']==='2024-09-26','corte anual igual');
$f=relFiltros(['periodo'=>'personalizado','inicio'=>'2025-12-31','fim'=>'2026-01-02','comparacao'=>'anterior']);$p=relPeriodos($f);check($p[1]['inicio']==='2025-12-28'&&$p[1]['fim']==='2025-12-30','anterior mesma duração');
$f=relFiltros(['ano'=>'2026']);$p=relPeriodos($f)[0];
$rows=[row('2026-01-01','0.00'),row('2026-03-01','10.10','receitas',1),row('2026-03-02','3.33','despesas',1),row('2026-04-01','5.00','despesas'),row('2026-10-01','8.00','receitas')];
$r=relAgregar($rows,$p,$f,'2026-09-26');check($r['grupos'][0]['n']===1&&$r['grupos'][0]['saldo']===0,'zero registrado');check($r['grupos'][1]['n']===0,'sem dados');check($r['total']['saldo']===977,'saldo exato');check($r['total']['realizado']===677,'realizado exato');
foreach(['realizados'=>2,'pendentes'=>3,'vencidos'=>2] as $s=>$count){$g=$f;$g['situacao']=$s;check(relAgregar($rows,$p,$g,'2026-09-26')['total']['n']===$count,'situação '.$s);}
check(relDiferenca(100,0)['percentual']===null,'base zero');check(relDiferenca(null,100)['valor']===null,'ausência não zero');check(relDiferenca(-50,-100)['percentual']==50,'base negativa');
foreach([['periodo'=>[]],['inicio'=>'2026-02-30','fim'=>'2026-03-01','periodo'=>'personalizado'],['inicio'=>'2026-03-01','fim'=>'2025-01-01','periodo'=>'personalizado'],['agrupamento'=>'sql'],['pessoas'=>'x','grafico'=>'invalido']] as $invalid){$caught=false;try{relFiltros($invalid);}catch(DomainException $e){$caught=true;}check($caught,'entrada inválida');}
foreach(['dia','semana','mes','ano'] as $g){$f['agrupamento']=$g;$r=relAgregar($rows,$p,$f,'2026-09-26');check($r['total']['saldo']===977,'invariância '.$g);}
echo "PASS: $n verificações de cálculo e validação\n";
