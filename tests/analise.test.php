<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../includes/analise.php';
function verify($value,$msg){if(!$value)throw new RuntimeException($msg);echo 'PASS '.$msg.PHP_EOL;}
$raw=[['date'=>strtotime('2026-09-01T03:00:00Z'),'open'=>10,'high'=>12,'low'=>9,'close'=>11,'volume'=>100],['date'=>strtotime('2026-09-02T03:00:00Z'),'open'=>10,'high'=>8,'low'=>9,'close'=>11,'volume'=>100]];
$r=analiseNormalizarBarras($raw,'1d');verify(count($r['bars'])===1 && $r['discarded']===1,'reject inconsistent OHLC');verify($r['bars'][0]['time']==='2026-09-01','Sao Paulo trading date');
foreach([['tipo'=>'linha','titulo'=>'x','preco'=>'-1'],['tipo'=>'linha','titulo'=>'x','preco'=>'1e20'],['tipo'=>'nota','titulo'=>'x','texto'=>'']] as $input){try{analiseMarcacao($input);throw new RuntimeException('accepted invalid input');}catch(DomainException $e){verify(true,'reject invalid annotation');}}
verify(analiseMarcacao(['tipo'=>'linha','titulo'=>'x','preco'=>'12,3456'])[2]==='12.3456','decimal normalization');
$dir=sys_get_temp_dir().'/mcf-an-test-'.bin2hex(random_bytes(6));$cfg=['cache_dir'=>$dir,'brapi_token'=>'fixture'];$calls=0;
$api=new AnaliseHistorico($cfg,function()use(&$calls,$raw){$calls++;return [200,json_encode(['results'=>[['requestedSymbol'=>'TEST3','symbol'=>'TEST3','data'=>['usedInterval'=>'1d','usedRange'=>'1mo','historicalDataPrice'=>[$raw[0]]]]]])];});
verify($api->obter('TEST3','1mo','1d')['ok'],'valid provider result');$api->obter('TEST3','1mo','1d');verify($calls===1,'cache prevents duplicate provider calls');
$api2=new AnaliseHistorico($cfg,fn()=>[400,json_encode(['code'=>'INVALID_INTERVAL'])]);verify($api2->obter('TEST3','5d','5m')['code']==='plan','plan restriction is explicit');
$api3=new AnaliseHistorico($cfg,fn()=>[200,json_encode(['results'=>[['requestedSymbol'=>'OTHER3','symbol'=>'OTHER3','data'=>['usedInterval'=>'1d','historicalDataPrice'=>[$raw[0]]]]]])]);verify(!$api3->obter('OTHER4','1mo','1d')['ok'],'never shows data for another ticker');
echo 'Unit tests complete'.PHP_EOL;
