<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/visao_conjunta.php';
require_once __DIR__.'/../includes/mercado_api.php';
$n=0;
function cryptoCheck(bool $v,string $label):void{global $n;if(!$v)throw new RuntimeException($label);$n++;}
function cryptoOp(int $id,string $q,string $price,string $kind='compra',string $type='cripto'):array{return ['id'=>$id,'ticker'=>'BTC','tipo_ativo'=>$type,'quantidade'=>$q,'valor_unitario'=>$price,'data'=>'2026-09-01','tipo_operacao'=>$kind];}
$ops=[cryptoOp(1,'0.12500000','40000.12345678'),cryptoOp(2,'0.12500000','60000.12345678'),cryptoOp(3,'-0.05000000','70000','venda')];
$p=array_values(internacionalConsolidar($ops))[0];
cryptoCheck(bccomp($p['quantidade_total'],'0.20',8)===0,'posição após venda parcial');
cryptoCheck(bccomp($p['valor_medio_ponderado'],'50000.12345678',8)===0,'média mantém oito casas');
cryptoCheck(bccomp($p['total_investido_usd'],'10000.024691356',10)===0,'custo remanescente');
cryptoCheck(!internacionalConsolidar([...$ops,cryptoOp(4,'-0.2','70000','venda')]),'venda integral zera posição');
$denied=false;try{internacionalConsolidar([...$ops,cryptoOp(4,'-0.20000001','70000','venda')]);}catch(DomainException $e){$denied=true;}cryptoCheck($denied,'rejeita venda excedente por uma fração mínima');
$tiny=array_values(internacionalConsolidar([cryptoOp(1,'1000000','0.00000001')]))[0];cryptoCheck(bccomp($tiny['total_investido_usd'],'0.01',8)===0,'preço de um centésimo de milionésimo');
$mixed=internacionalConsolidar([...$ops,cryptoOp(4,'2','30','compra','etf')]);cryptoCheck(count($mixed)===2,'ETF BTC e cripto BTC são posições distintas');
$fake=new class {public array $calls=[];function stocks($s,$c){$this->calls[]=['stocks',$s,$c];return ['BTC'=>['price'=>'30','currency'=>'USD']];}function crypto($s,$c){$this->calls[]=['crypto',$s,$c];return ['BTC'=>['price'=>'70000','currency'=>'USD']];}};
$quotes=mercadoCotacoesPosicoes(array_values($mixed),'USD',$fake);cryptoCheck(count($fake->calls)===2,'separa provedores por tipo');cryptoCheck($quotes['BTC|etf']['price']==='30'&&$quotes['BTC|cripto']['price']==='70000','sem colisão de ticker');
$wallets=[['pessoa'=>['id'=>1],'moeda'=>'USD','posicoes'=>array_values($mixed)],['pessoa'=>['id'=>2],'moeda'=>'USD','posicoes'=>array_values(internacionalConsolidar([cryptoOp(5,'0.1','60000')]))]];
$r=mcfCalcularConjunto($wallets,['USD'=>$quotes]);cryptoCheck(count($r['agrupadas'])===2,'consolidação separa ETF/cripto');cryptoCheck(count($r['individuais'])===3,'titulares preservados');cryptoCheck(bccomp($r['totais']['USD']['mercado'],'21060',8)===0,'valor conjunto em USD');
foreach([null,0,-1,'NaN'] as $bad){$q=$quotes;$q['BTC|cripto']['price']=$bad;$r=mcfCalcularConjunto($wallets,['USD'=>$q]);cryptoCheck($r['totais']['USD']['mercado']===null&&$r['totais']['USD']['faltam']===1,'cotação inválida não vira total completo');}
$q=$quotes;$q['BTC|cripto']['currency']='BRL';cryptoCheck(mcfCalcularConjunto($wallets,['USD'=>$q])['totais']['USD']['mercado']===null,'não mistura moeda de cotação');
cryptoCheck(mcfCalcularConjunto($wallets,['USD'=>['BTC'=>['price'=>'30','currency'=>'USD']]])['totais']['USD']['mercado']===null,'cripto não herda cotação stock legada');
$dir=sys_get_temp_dir().'/mcf-crypto-unit-'.bin2hex(random_bytes(6));
$cfg=['cache_dir'=>$dir,'brapi_token'=>'fixture','ttl'=>180,'retry_after'=>60,'stale_max'=>604800,'batch_size'=>10];
$calls=0;$now=1800000000;
$transport=function($requests)use(&$calls){$calls++;return array_map(static fn($r)=>['status'=>200,'data'=>['coins'=>[['coin'=>'BTC','currency'=>'USD','regularMarketPrice'=>'0.00000001','regularMarketTime'=>1800000000]]]],$requests);};
try{
 $api=new MercadoApi($cfg,$transport,fn()=>$now);$q=$api->crypto(['BTC']);cryptoCheck(abs($q['BTC']['price']-0.00000001)<1e-16,'parser preserva preço pequeno');$api->crypto(['BTC']);cryptoCheck($calls===1,'cache evita consulta repetida');
 $other=new MercadoApi($cfg,fn($r)=>throw new RuntimeException('cache deveria atender'),fn()=>$now);cryptoCheck($other->crypto(['BTC'])['BTC']['price']===$q['BTC']['price'],'cache compartilhado preserva precisão');
 foreach([['coin'=>'OTHER','currency'=>'USD','regularMarketPrice'=>10],['coin'=>'BTC','currency'=>'BRL','regularMarketPrice'=>10],['coin'=>'BTC','currency'=>'USD','regularMarketPrice'=>-1]] as $i=>$item){$api=new MercadoApi([...$cfg,'brapi_token'=>'invalid'.$i],fn($req)=>array_map(fn($r)=>['status'=>200,'data'=>['coins'=>[$item]]],$req),fn()=>$now);cryptoCheck($api->crypto(['BTC'])['BTC']['price']===null,'resposta inválida não gera cotação');}
 $api=new MercadoApi([...$cfg,'brapi_token'=>'unavailable'],fn($req)=>array_map(fn($r)=>['status'=>503,'data'=>[]],$req),fn()=>$now);cryptoCheck($api->crypto(['BTC'])['BTC']['price']===null,'indisponibilidade explícita');
}finally{foreach(glob($dir.'/*.json')?:[] as $f)unlink($f);if(is_dir($dir))rmdir($dir);}
echo "PASS: $n verificações de cripto (precisão, posições, cotações, cache e consolidação)\n";
