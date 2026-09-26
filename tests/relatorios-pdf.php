<?php
require_once __DIR__.'/../includes/relatorios_pdf.php';
$out=__DIR__.'/../../resultados/relatorios';if(!is_dir($out))mkdir($out,0777,true);
$f=relFiltros(['periodo'=>'ytd','referencia'=>'2026-09-26','comparacao'=>'tres_anos']);$rows=[];
for($year=2024;$year<=2026;$year++)for($month=1;$month<=9;$month++){
    if($month===2)continue;
    for($i=1;$i<=12;$i++)foreach(['receitas','despesas'] as $mod){$rows[]=['id'=>count($rows)+1,'data'=>sprintf('%04d-%02d-%02d',$year,$month,$i),'centavos'=>$month===1?0:($mod==='receitas'?120000:37559),'modulo'=>$mod,'realizado'=>$i%3!==0,'tipo'=>'unica','categoria'=>$mod==='receitas'?'regular':'pessoal','nome'=>'Lançamento fictício '.$i.' · alimentação e serviços','titular'=>'Perfil de teste','usuario_id'=>1];}
}
$r=['filtros'=>$f,'pessoas'=>[['id'=>1,'nome'=>'Perfil de teste','email'=>'teste@example.test']],'gerado'=>'26/09/2026 10:00:00','hoje'=>'2026-09-26','periodos'=>array_map(fn($p)=>relAgregar($rows,$p,$f,'2026-09-26'),relPeriodos($f))];
file_put_contents($out.'/financeiro-resumido.pdf',relPdf($r,false));
file_put_contents($out.'/financeiro-detalhado.pdf',relPdf($r,true));
foreach(['horizontal','empilhadas','linhas','area'] as $tipo){$r['filtros']['grafico']=$tipo;file_put_contents($out.'/grafico-'.$tipo.'.pdf',relPdf($r,false));}
file_put_contents($out.'/fixture.json',json_encode($r,JSON_UNESCAPED_UNICODE));
echo "PDFs fictícios gerados em $out\n";
