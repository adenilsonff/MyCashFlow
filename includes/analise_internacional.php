<?php
// Histórico público internacional: moeda conferida antes de exibir qualquer preço.
final class AnaliseHistoricoInternacional {
    public function __construct(private array $cfg, private $transport=null) {}
    public function obter(string $ticker,string $tipo,string $range,string $interval): array {
        if(!preg_match('/^[A-Z0-9][A-Z0-9.-]{0,19}$/D',$ticker) || !analiseInternacional($tipo)
            || !in_array($range,['5d','1mo','3mo'],true) || !in_array($interval,['1d','1m','5m','15m','60m'],true))throw new DomainException('Ativo ou período inválido.');
        $symbol=$tipo==='cripto'?$ticker.'-USD':str_replace('.','-',$ticker);
        $dir=$this->cfg['cache_dir'].'/analise-internacional-v1';if(!is_dir($dir))@mkdir($dir,0700,true);
        $file=@fopen($dir.'/'.hash('sha256',$symbol.'|'.$tipo.'|'.$range.'|'.$interval).'.json','c+b');
        if($file && !flock($file,LOCK_EX|LOCK_NB)){fclose($file);return ['ok'=>false,'message'=>'Consulta em andamento. Tente novamente em alguns segundos.'];}
        try {
            $cached=$file?json_decode(stream_get_contents($file),true):null;
            if(is_array($cached) && ($cached['next']??0)>time())return $cached['payload'];
            $url='https://query1.finance.yahoo.com/v8/finance/chart/'.rawurlencode($symbol).'?'.http_build_query(['range'=>$range,'interval'=>$interval,'includePrePost'=>'false']);
            if($this->transport)[$status,$body]=($this->transport)($url);
            else{$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>12,CURLOPT_USERAGENT=>'Mozilla/5.0',CURLOPT_HTTPHEADER=>['Accept: application/json']]);$body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);}
            $json=json_decode((string)$body,true);$r=$json['chart']['result'][0]??[];$meta=$r['meta']??[];
            $payload=['ok'=>false,'code'=>'unavailable','message'=>'A fonte internacional não retornou histórico válido. Tente novamente mais tarde.'];
            if($status===429)$payload['message']='Limite da fonte internacional atingido. Aguarde antes de tentar novamente.';
            elseif($status===404)$payload['message']='Histórico internacional não encontrado para esse código. Confira o ticker.';
            elseif($status===200 && strtoupper($meta['symbol']??'')===$symbol && ($meta['currency']??'')==='USD') {
                $quotes=$r['indicators']['quote'][0]??[];$rows=[];
                foreach($r['timestamp']??[] as $i=>$time){$row=['date'=>$time];foreach(['open','high','low','close','volume'] as $f)$row[$f]=$quotes[$f][$i]??null;$rows[]=$row;}
                $normal=analiseNormalizarBarras($rows,$interval,$meta['exchangeTimezoneName']??'UTC');
                if($normal['bars'])$payload=$normal+['ok'=>true,'fetched_at'=>time(),'stale'=>false,'source'=>'Yahoo Finance','currency'=>'USD','interval'=>$interval,'range'=>$range];
            }elseif($status===200 && ($meta['currency']??'')!=='USD')$payload['message']='A fonte não confirmou preços em dólar. O gráfico não será misturado com os valores da carteira.';
            $good=$payload['ok']?$payload:($cached['good']??null);
            if(!$payload['ok'] && $good){$payload=$good+['message'=>$payload['message']];$payload['stale']=true;}
            $next=time()+($payload['ok']&&!$payload['stale']?($interval==='1d'?1800:300):60);
            if($file){ftruncate($file,0);rewind($file);fwrite($file,json_encode(['next'=>$next,'payload'=>$payload,'good'=>$good],JSON_INVALID_UTF8_SUBSTITUTE));fflush($file);}
            return $payload;
        }finally{if($file){flock($file,LOCK_UN);fclose($file);}}
    }
}
