<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../includes/mercado_api.php';
require __DIR__.'/../includes/comunicacao.php';
require __DIR__.'/../includes/analise.php';
$dir=sys_get_temp_dir().'/mcf-falhas-'.bin2hex(random_bytes(8));
$cfg=['cache_dir'=>$dir,'brapi_token'=>'fixture','awesome_token'=>'','ttl'=>180,'retry_after'=>60,'stale_max'=>604800,'batch_size'=>10];
$now=1800000000;$n=0;
function conferir($v,$msg){global $n;if(!$v)throw new RuntimeException($msg);$n++;echo 'PASS '.$msg."\n";}
$ok=function($req)use(&$now){return array_map(function($r)use(&$now){
 if(str_contains($r['url'],'/stocks/quote'))return ['status'=>200,'data'=>['results'=>[['requestedSymbol'=>'PETR4','symbol'=>'PETR4','data'=>['currency'=>'BRL','regularMarketPrice'=>40,'regularMarketTime'=>$now]]]]];
 if(str_contains($r['url'],'/crypto'))return ['status'=>200,'data'=>['coins'=>[['coin'=>'BTC','currency'=>'USD','regularMarketPrice'=>60000,'regularMarketTime'=>$now]]]];
 return ['status'=>200,'data'=>['USDBRL'=>['code'=>'USD','codein'=>'BRL','bid'=>5,'timestamp'=>$now],'EURBRL'=>['code'=>'EUR','codein'=>'BRL','bid'=>6,'timestamp'=>$now]]];
},$req);};
$fail=fn($rs)=>array_map(fn($r)=>['status'=>503,'data'=>[]],$rs);
try {
 $api=new MercadoApi($cfg,$ok,fn()=>$now);$api->stocks(['PETR4'],'BRL');$api->crypto(['BTC']);$api->fx();$initial=$now;$now+=30*86400;
 foreach([0,401,403,429,500,503] as $status){
  $api=new MercadoApi($cfg,fn($rs)=>array_map(fn($r)=>['status'=>$status,'data'=>[]],$rs),fn()=>$now);
  $q=$api->stocks(['PETR4'],'BRL')['PETR4'];conferir($q['price']==='40.00000000'&&$q['fetched_at']===$initial,'Falha '.$status.' mantém valor e horário anteriores');
  conferir($q['stale']&&$q['very_old']&&!empty($q['error']),'Falha '.$status.' sinaliza desatualização');$now+=61;
 }
 $api=new MercadoApi($cfg,$fail,fn()=>$now);$q=$api->crypto(['BTC'])['BTC'];conferir($q['price']==='60000.00000000'&&$q['stale'],'Cripto conserva cotação após 30 dias');
 $fx=$api->fx();conferir($fx['USD']['price']==='5.00000000'&&$fx['EUR']['price']==='6.00000000'&&$fx['USD']['stale'],'Câmbio conserva valor quando as fontes falham');
 $html=mcfAvisoMercado([$q]);conferir(str_contains($html,'Falha ao atualizar')&&str_contains($html,'Última cotação: USD 60.000,00')&&str_contains($html,'mais de 7 dias')&&str_contains($html,'Última consulta bem-sucedida'),'Aviso expõe preço, idade e horário');
 $missing=$api->stocks(['VALE3'],'BRL')['VALE3'];conferir($missing['price']===null&&str_contains(mcfAvisoMercado([$missing]),'Ainda não há um preço válido'),'Primeira falha não inventa preço zero');
 $now+=61;$api=new MercadoApi($cfg,$ok,fn()=>$now);$q=$api->stocks(['PETR4'],'BRL')['PETR4'];conferir(!$q['stale']&&!$q['very_old']&&$q['fetched_at']===$now&&mcfAvisoMercado([$q])==='','Recuperação renova preço e remove alerta');
 // Um histórico antigo precisa continuar disponível quando o provedor cai.
 $histDir=$dir.'/analise-v1';mkdir($histDir,0700,true);$p=$histDir.'/'.hash('sha256','fixture|PETR4|1mo|1d').'.json';
 $good=['ok'=>true,'bars'=>[['time'=>'2026-09-01','open'=>40,'high'=>42,'low'=>39,'close'=>41,'volume'=>100]],'fetched_at'=>time()-30*86400,'stale'=>false,'source'=>'BRAPI','interval'=>'1d','range'=>'1mo'];
 file_put_contents($p,json_encode(['next'=>0,'payload'=>$good,'good'=>$good]));$hist=new AnaliseHistorico($cfg,fn($url)=>[503,'{}']);$got=$hist->obter('PETR4','1mo','1d');
 conferir($got['ok']&&$got['stale']&&$got['bars']===$good['bars']&&$got['fetched_at']===$good['fetched_at'],'Gráfico preserva candles antigos sem renovar data');
}finally{foreach(glob($dir.'/analise-v1/*.json')?:[] as $f)unlink($f);if(is_dir($dir.'/analise-v1'))rmdir($dir.'/analise-v1');foreach(glob($dir.'/*.json')?:[] as $f)unlink($f);if(is_dir($dir))rmdir($dir);}
echo "TOTAL: $n verificações aprovadas.\n";
