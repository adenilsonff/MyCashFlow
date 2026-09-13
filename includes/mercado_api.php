<?php
// Consultas e cache compartilhados; nunca altera o histórico das carteiras.
final class MercadoApi {
    private $cfg, $transport, $clock;
    private $memory = [];
    public function __construct(array $cfg, $transport = null, $clock = null) {
        $this->cfg=$cfg; $this->transport=$transport;
        $this->clock=$clock ?? function(){return time();};
    }
    private function now() { return ($this->clock)(); }
    private function path($key) {
        return $this->cfg['cache_dir'].'/'.hash('sha256',$this->cfg['brapi_token'].':'.$key).'.json';
    }
    private function read($key) {
        if (array_key_exists($key,$this->memory)) return $this->memory[$key];
        $h=@fopen($this->path($key),'rb');
        if (!$h) return null;
        $value=null;
        if (flock($h,LOCK_SH)) {
            $value=json_decode(stream_get_contents($h),true);
            flock($h,LOCK_UN);
        }
        fclose($h);
        return $this->memory[$key]=is_array($value)?$value:null;
    }
    private function write($key,array $value) {
        $this->memory[$key]=$value;
        $dir=$this->cfg['cache_dir'];
        if (!is_dir($dir) && !@mkdir($dir,0700,true) && !is_dir($dir)) return;
        $h=@fopen($this->path($key),'c+b');
        if (!$h) return;
        if (flock($h,LOCK_EX)) {
            ftruncate($h,0);
            fwrite($h,json_encode($value,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE));
            fflush($h); flock($h,LOCK_UN);
        }
        fclose($h);
    }
    private function due($e) { return !$e || ($e['next'] ?? 0)<=$this->now(); }
    private function blank($s,$currency) {
        return ['requested'=>$s,'symbol'=>$s,'currency'=>$currency,'price'=>null,'logo'=>null,
            'source'=>'BRAPI','market_time'=>null,'fetched_at'=>null,'stale'=>false,
            'error'=>'Cotação indisponível.','changed'=>false];
    }
    private function expose($entry,$s,$currency) {
        $q=$entry['quote'] ?? $this->blank($s,$currency);
        $q['error']=$entry['error'] ?? '';
        $age=isset($q['fetched_at'])?$this->now()-$q['fetched_at']:0;
        $q['stale']=$q['price']!==null && $age>=$this->cfg['ttl'];
        if ($q['price']!==null && $age>$this->cfg['stale_max']) {
            $q['price']=null; $q['stale']=false;
            $q['error']='A última consulta expirou e não foi utilizada no resultado.';
        }
        return $q;
    }
    private function save($key,$quote,$error,$s,$currency) {
        $old=$this->read($key);
        if ($quote && $quote['price']!==null) {
            $entry=['quote'=>$quote,'next'=>$this->now()+$this->cfg['ttl'],'error'=>''];
        } else {
            $previous=$old['quote'] ?? $quote ?? $this->blank($s,$currency);
            if ($quote && !empty($quote['logo'])) $previous['logo']=$quote['logo'];
            // Não renova fetched_at quando só resta um preço anterior.
            $entry=['quote'=>$previous,'next'=>$this->now()+$this->cfg['retry_after'],
                'error'=>$error ?: 'Preço indisponível na moeda da carteira.'];
        }
        $this->write($key,$entry);
        return $this->expose($entry,$s,$currency);
    }
    private function stamp($v) {
        if ($v===null || $v==='') return null;
        $t=is_numeric($v)?(int)$v:strtotime((string)$v);
        return $t && $t>0 && $t<=$this->now()+300?$t:null;
    }
    private function price($v) {
        if (!is_numeric($v) || !is_finite((float)$v) || $v<=0) return null;
        return number_format((float)$v,8,'.','');
    }
    private function logo($v) {
        return is_string($v) && strlen($v)<=500 && filter_var($v,FILTER_VALIDATE_URL)
            && strtolower(parse_url($v,PHP_URL_SCHEME) ?? '')==='https'?$v:null;
    }
    private function reason($r) {
        $s=$r['status'] ?? 0;
        if ($s===429) return 'Limite de consultas atingido; nova tentativa em breve.';
        if ($s===401 || $s===403) return 'Token inválido ou recurso fora do plano.';
        if ($s===404) return 'Ativo ou recurso sem cobertura nesta fonte.';
        if ($s===0) return 'Não foi possível conectar ao serviço de cotações.';
        return $s===200?'Resposta sem cotação válida.':'Falha na fonte de dados (HTTP '.$s.').';
    }
    private function request($url,$provider) {
        $headers=['Accept: application/json'];
        if ($provider==='brapi' && $this->cfg['brapi_token']!=='') $headers[]='Authorization: Bearer '.$this->cfg['brapi_token'];
        if ($provider==='awesome' && $this->cfg['awesome_token']!=='') $headers[]='x-api-key: '.$this->cfg['awesome_token'];
        return ['url'=>$url,'headers'=>$headers];
    }
    private function fetch(array $requests) {
        if (!$requests) return [];
        if ($this->transport) return ($this->transport)($requests);
        $out=[];
        if (function_exists('curl_multi_init')) {
            // Até três consultas simultâneas; sem repetição automática em 429.
            foreach (array_chunk($requests,3,true) as $group) {
                $multi=curl_multi_init(); $handles=[];
                foreach ($group as $id=>$r) {
                    $ch=curl_init($r['url']);
                    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,
                        CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>$r['headers'],CURLOPT_USERAGENT=>'MyCashFlow/2.0']);
                    curl_multi_add_handle($multi,$ch); $handles[$id]=$ch;
                }
                do {
                    $status=curl_multi_exec($multi,$running);
                    if ($running && curl_multi_select($multi,0.2)===-1) usleep(10000);
                } while ($running && $status===CURLM_OK);
                foreach ($handles as $id=>$ch) {
                    $d=json_decode((string)curl_multi_getcontent($ch),true);
                    $out[$id]=['status'=>(int)curl_getinfo($ch,CURLINFO_HTTP_CODE),'data'=>is_array($d)?$d:[]];
                    curl_multi_remove_handle($multi,$ch); curl_close($ch);
                }
                curl_multi_close($multi);
            }
        } else {
            foreach ($requests as $id=>$r) {
                $ctx=stream_context_create(['http'=>['timeout'=>8,'follow_location'=>0,'ignore_errors'=>true,
                    'header'=>implode("\r\n",$r['headers'])]]);
                $http_response_header=[];
                $raw=@file_get_contents($r['url'],false,$ctx);
                preg_match('~\s(\d{3})\s~',$http_response_header[0] ?? '',$m);
                $d=json_decode((string)$raw,true);
                $out[$id]=['status'=>(int)($m[1] ?? 0),'data'=>is_array($d)?$d:[]];
            }
        }
        return $out;
    }
    private function stockItem(array $item,$requested,$currency) {
        if (($item['requestedSymbol'] ?? '')!==$requested) return null;
        $symbol=$item['symbol'] ?? '';
        if (!is_string($symbol) || !preg_match('/^[A-Z0-9.-]{1,20}$/D',$symbol)) return null;
        if ($symbol!==$requested && ($item['changed'] ?? false)!==true) return null;
        $d=$item['data'] ?? [];
        $q=$this->blank($symbol,$currency);
        $q['requested']=$requested; $q['changed']=$symbol!==$requested;
        $q['price']=($d['currency'] ?? '')===$currency?$this->price($d['regularMarketPrice'] ?? null):null;
        $q['logo']=$this->logo($d['logourl'] ?? null);
        $q['market_time']=$this->stamp($d['regularMarketTime'] ?? null);
        $q['fetched_at']=$this->now(); $q['error']='';
        return $q;
    }
    public function stocks(array $symbols,$currency) {
        if (!in_array($currency,['BRL','USD'],true)) throw new InvalidArgumentException('Moeda inválida.');
        $symbols=array_values(array_unique(array_map(function($s)use($currency){
            $s=strtoupper(trim((string)$s));
            return $currency==='BRL'?preg_replace('/\.SA$/','',$s):$s;
        },$symbols)));
        $result=[]; $pending=[];
        foreach ($symbols as $s) {
            if (!preg_match('/^[A-Z0-9][A-Z0-9.-]{0,19}$/D',$s)) { $result[$s]=$this->blank($s,$currency); continue; }
            $e=$this->read('stock:'.$currency.':'.$s);
            if ($this->due($e)) $pending[]=$s;
            else $result[$s]=$this->expose($e,$s,$currency);
        }
        if (!$pending) return $result;
        $plan=$this->read('batch-limit');
        $size=max(1,min(10,(int)(($plan && ($plan['until'] ?? 0)>$this->now())?$plan['size']:$this->cfg['batch_size'])));
        for ($round=0;$round<2 && $pending;$round++) {
            $groups=array_chunk($pending,$size); $requests=[];
            foreach ($groups as $id=>$group) $requests[$id]=$this->request(
                'https://brapi.dev/api/v2/stocks/quote?symbols='.rawurlencode(implode(',',$group)),'brapi');
            $responses=$this->fetch($requests); $retry=[];
            foreach ($groups as $id=>$group) {
                $r=$responses[$id] ?? ['status'=>0,'data'=>[]];
                if ($round===0 && count($group)>1 && ($r['data']['code'] ?? '')==='QUOTES_PER_REQUEST_EXCEEDED') {
                    $size=max(1,min($size,(int)($r['data']['details']['limit']['current'] ?? 1)));
                    $this->write('batch-limit',['size'=>$size,'until'=>$this->now()+86400]);
                    $retry=array_merge($retry,$group); continue;
                }
                if ($round===0 && count($group)>1 && in_array($r['status'],[400,404],true)) {
                    $size=1;
                    $retry=array_merge($retry,$group); continue;
                }
                $items=[];
                if ($r['status']===200) foreach (($r['data']['results'] ?? []) as $item) {
                    if (is_array($item)) $items[$item['requestedSymbol'] ?? '']=$item;
                }
                foreach ($group as $s) {
                    $q=isset($items[$s])?$this->stockItem($items[$s],$s,$currency):null;
                    $result[$s]=$this->save('stock:'.$currency.':'.$s,$q,$this->reason($r),$s,$currency);
                }
            }
            $pending=$retry;
        }
        return $result;
    }
    public function fx() {
        $result=[]; $pending=[];
        foreach (['USD','EUR'] as $s) {
            $e=$this->read('fx:'.$s);
            if ($this->due($e)) $pending[]=$s;
            else $result[$s]=$this->expose($e,$s,'BRL');
        }
        if (!$pending) return $result;
        $pairs=implode(',',array_map(function($s){return $s.'-BRL';},$pending));
        $rs=$this->fetch([$this->request('https://economia.awesomeapi.com.br/json/last/'.$pairs,'awesome')]);
        $r=$rs[0] ?? ['status'=>0,'data'=>[]];
        $quotes=[]; $missing=[];
        foreach ($pending as $s) {
            $d=$r['data'][$s.'BRL'] ?? [];
            if ($r['status']===200 && ($d['code'] ?? '')===$s && ($d['codein'] ?? '')==='BRL') {
                $q=$this->blank($s,'BRL');
                $q['price']=$this->price($d['bid'] ?? null); $q['market_time']=$this->stamp($d['timestamp'] ?? null);
                $q['fetched_at']=$this->now(); $q['source']='AwesomeAPI';
                if ($q['price']!==null) $quotes[$s]=$q;
            }
            if (!isset($quotes[$s])) $missing[]=$s;
        }
        $access=$this->read('fx-brapi-access');
        if ($missing && (!$access || ($access['until'] ?? 0)<=$this->now())) {
            $pairs=implode(',',array_map(function($s){return $s.'-BRL';},$missing));
            $rs=$this->fetch([$this->request('https://brapi.dev/api/v2/currency?currency='.$pairs,'brapi')]);
            $f=$rs[0] ?? ['status'=>0,'data'=>[]];
            if (in_array($f['status'],[401,403],true)) $this->write('fx-brapi-access',['until'=>$this->now()+86400]);
            if ($f['status']===200) foreach (($f['data']['currency'] ?? []) as $d) {
                $s=$d['fromCurrency'] ?? '';
                if (!in_array($s,$missing,true) || ($d['toCurrency'] ?? '')!=='BRL') continue;
                $q=$this->blank($s,'BRL');
                $q['price']=$this->price($d['bidPrice'] ?? null); $q['market_time']=$this->stamp($d['updatedAtTimestamp'] ?? null);
                $q['fetched_at']=$this->now(); $q['source']='BRAPI';
                if ($q['price']!==null) $quotes[$s]=$q;
            }
        }
        foreach ($pending as $s) $result[$s]=$this->save('fx:'.$s,$quotes[$s] ?? null,
            'Câmbio indisponível na consulta. '.$this->reason($r),$s,'BRL');
        return $result;
    }
}
function mercadoApi() {
    static $api;
    if (!$api) $api=new MercadoApi(require __DIR__.'/mercado_config.php');
    return $api;
}
function mercadoLegenda($q) {
    if (!$q) return 'Cotação indisponível.';
    $parts=[$q['source']];
    if ($q['market_time']) $parts[]='Preço de '.(new DateTimeImmutable('@'.$q['market_time']))
        ->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i').' (Brasília)';
    else $parts[]='Horário do preço não informado pela fonte';
    if (!empty($q['fetched_at'])) $parts[]='Consultado em '.(new DateTimeImmutable('@'.$q['fetched_at']))
        ->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
    if ($q['price']===null) $parts[]=$q['error'] ?: 'Preço indisponível';
    elseif ($q['stale']) $parts[]='Última consulta disponível; atualização pendente';
    if (!empty($q['changed'])) $parts[]='Código atual informado pela API: '.$q['symbol'];
    return implode(' · ',$parts);
}
