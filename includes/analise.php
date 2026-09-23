<?php
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

require_once __DIR__ . '/carteiras_posicoes.php';

function analiseSql(mysqli $conn, string $sql, string $types = '', array $args = []): mysqli_stmt {
    $stmt = $conn->prepare($sql);
    if ($types !== '') $stmt->bind_param($types, ...$args);
    $stmt->execute();
    return $stmt;
}
function analiseRows(mysqli $conn, string $sql, string $types = '', array $args = []): array {
    $stmt = analiseSql($conn, $sql, $types, $args);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
function analiseAtivo(array $input): array {
    if (!is_string($input['ticker'] ?? null) || !is_string($input['tipo_ativo'] ?? null)) {
        throw new DomainException('Informe o código e a categoria do ativo.');
    }
    $ticker = acoesTicker($input['ticker']);
    $tipo = $input['tipo_ativo'];
    if (!preg_match('/^[A-Z0-9]{1,10}$/D', $ticker) || !in_array($tipo, ['acao','fii','etf','bdr'], true)) {
        throw new DomainException('Código ou categoria inválidos.');
    }
    return [$ticker, $tipo];
}
function analiseTexto($value, int $max, bool $required = true): string {
    if (!is_string($value)) throw new DomainException('Texto inválido.');
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) {
        throw new DomainException("Preencha o texto com até $max caracteres.");
    }
    return $value;
}
function analiseMarcacao(array $input): array {
    $tipo = $input['tipo'] ?? '';
    if (!in_array($tipo, ['linha','nota'], true)) throw new DomainException('Marcação inválida.');
    $titulo = analiseTexto($input['titulo'] ?? '', 80);
    $texto = analiseTexto($input['texto'] ?? '', 2000, $tipo === 'nota');
    $cor = $input['cor'] ?? '#6366f1';
    if (!is_string($cor) || !preg_match('/^#[0-9a-fA-F]{6}$/D', $cor)) throw new DomainException('Cor inválida.');
    $preco = null;
    if ($tipo === 'linha') {
        $preco = $input['preco'] ?? '';
        if (!is_string($preco)) throw new DomainException('Informe um preço válido.');
        $preco = str_replace(',', '.', trim($preco));
        if (!preg_match('/^[0-9]{1,12}(\.[0-9]{1,4})?$/D', $preco) || (float)$preco <= 0) {
            throw new DomainException('Informe um preço positivo com até quatro casas decimais.');
        }
    }
    return [$tipo,$titulo,$preco,$cor,$texto];
}
function analiseCarteira(mysqli $conn, int $uid, bool $incluirInvestimentos=true): array {
    $ops = $incluirInvestimentos ? analiseRows($conn, 'SELECT id,ticker,tipo_ativo,quantidade,valor_unitario,data,tipo_operacao,logo
        FROM investimentos_nacionais WHERE usuario_id = ? ORDER BY data,id', 'i', [$uid]) : [];
    $groups = [];
    foreach ($ops as $op) {
        $op['ticker'] = acoesTicker($op['ticker']);
        $groups[$op['ticker'].'|'.$op['tipo_ativo']][] = $op;
    }
    $assets = [];
    foreach ($groups as $key => $history) {
        $op = $history[0];
        $asset = ['ticker'=>$op['ticker'],'tipo_ativo'=>$op['tipo_ativo'],
            'quantidade'=>0,'preco_medio'=>null,'carteira'=>false,'acompanhando'=>false,'erro'=>null];
        try {
            $p = array_values(acoesConsolidar($history));
            if ($p) {
                $asset['quantidade'] = $p[0]['quantidade_total'];
                $asset['preco_medio'] = $p[0]['valor_medio_ponderado'];
                $asset['carteira'] = true;
            }
        } catch (DomainException $e) {
            $asset['erro'] = 'Histórico inconsistente: revise as operações na carteira.';
        }
        // Posições encerradas continuam acessíveis para consultar o histórico.
        $assets[$key] = $asset;
    }
    foreach (analiseRows($conn,'SELECT ticker,tipo_ativo FROM analise_acompanhamento WHERE usuario_id = ?', 'i',[$uid]) as $a) {
        $key = $a['ticker'].'|'.$a['tipo_ativo'];
        $assets[$key] = ($assets[$key] ?? ($a + ['quantidade'=>0,'preco_medio'=>null,'carteira'=>false,'erro'=>null]));
        $assets[$key]['acompanhando'] = true;
    }
    uasort($assets, fn($a,$b) => ($b['carteira'] <=> $a['carteira']) ?: strcmp($a['ticker'],$b['ticker']));
    return array_values($assets);
}
function analiseResumoPosicao(array $ops, string $ticker, string $tipo): array {
    $summary=['quantidade'=>0,'preco_medio'=>null,'carteira'=>false,'erro'=>null];
    try {
        $history=array_map(fn($op)=>$op+['ticker'=>$ticker,'tipo_ativo'=>$tipo,'logo'=>null],$ops);
        $positions=array_values(acoesConsolidar($history));
        if ($positions) {
            $summary['quantidade']=$positions[0]['quantidade_total'];
            $summary['preco_medio']=$positions[0]['valor_medio_ponderado'];
            $summary['carteira']=true;
        }
    } catch (DomainException $e) { $summary['erro']='Histórico inconsistente: revise as operações na carteira.'; }
    return $summary;
}

// Normaliza somente OHLC do provedor. Nunca mistura adjustedClose com OHLC.
function analiseNormalizarBarras(array $rows, string $interval): array {
    $bars = []; $discarded = 0;
    $zone = new DateTimeZone('America/Sao_Paulo');
    foreach ($rows as $r) {
        $ok = is_array($r) && isset($r['date']) && is_numeric($r['date'])
            && $r['date'] > 0 && $r['date'] <= time()+86400;
        foreach (['open','high','low','close','volume'] as $f) {
            $ok = $ok && isset($r[$f]) && is_numeric($r[$f]) && is_finite((float)$r[$f])
                && ($f === 'volume' ? $r[$f] >= 0 : $r[$f] > 0);
        }
        if (!$ok || $r['high'] < max($r['open'],$r['close']) || $r['low'] > min($r['open'],$r['close'])) {
            $discarded++; continue;
        }
        $time = $interval === '1d' ? (new DateTimeImmutable('@'.(int)$r['date']))->setTimezone($zone)->format('Y-m-d') : (int)$r['date'];
        $bars[$time] = ['time'=>$time,'open'=>(float)$r['open'],'high'=>(float)$r['high'],
            'low'=>(float)$r['low'],'close'=>(float)$r['close'],'volume'=>(float)$r['volume']];
    }
    ksort($bars);
    return ['bars'=>array_values($bars),'discarded'=>$discarded];
}

final class AnaliseHistorico {
    private array $cfg;
    private $transport;
    public function __construct(array $cfg, $transport = null) { $this->cfg=$cfg; $this->transport=$transport; }
    public function obter(string $ticker, string $range, string $interval): array {
        if (!in_array($range,['5d','1mo','3mo'],true) || !in_array($interval,['1d','1m','5m','15m','60m'],true)
            || !preg_match('/^[A-Z0-9]{1,10}$/D',$ticker)) throw new DomainException('Período inválido.');
        $dir=$this->cfg['cache_dir'].'/analise-v1';
        if (!is_dir($dir)) @mkdir($dir,0700,true);
        $path=$dir.'/'.hash('sha256',$this->cfg['brapi_token'].'|'.$ticker.'|'.$range.'|'.$interval).'.json';
        $file=@fopen($path,'c+b');
        // Uma chamada por chave: evita chamadas duplicadas por várias abas.
        if ($file && !flock($file,LOCK_EX|LOCK_NB)) { fclose($file); return ['ok'=>false,'message'=>'Consulta em andamento. Tente novamente em alguns segundos.']; }
        try {
            $cached=$file?json_decode(stream_get_contents($file),true):null;
            if (is_array($cached) && ($cached['next']??0)>time()) return $cached['payload'];
            $url='https://brapi.dev/api/v2/stocks/historical?'.http_build_query([
                'symbols'=>$ticker,'range'=>$range,'interval'=>$interval,'sortOrder'=>'asc']);
            if ($this->transport) { [$status,$body]=($this->transport)($url); }
            else {
                $ch=curl_init($url);
                curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>12,
                    CURLOPT_HTTPHEADER=>['Accept: application/json','Authorization: Bearer '.$this->cfg['brapi_token']],
                    CURLOPT_USERAGENT=>'MyCashFlow/Analise']);
                $body=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
            }
            $json=json_decode((string)$body,true);
            $item=$json['results'][0]??[];
            $data=$item['data']??[];
            $payload=['ok'=>false,'message'=>'Histórico indisponível. Tente novamente mais tarde.','code'=>'unavailable'];
            if (($json['code']??'')==='INVALID_INTERVAL' || ($json['code']??'')==='INVALID_RANGE' || $status===403) {
                $payload['code']='plan'; $payload['message']='Este período não está disponível para este ativo no acesso atual. Use Diário e até 3 meses.';
            } elseif ($status===404) { $payload['code']='empty'; $payload['message']='Não há histórico para este ativo e período. Confira o código ou amplie a janela.'; }
            elseif ($status===429) $payload['message']='Limite de consultas atingido. Aguarde antes de tentar novamente.';
            elseif ($status===401) $payload['message']='A fonte não autorizou a consulta. Confira a configuração da API.';
            elseif ($status===200 && ($item['requestedSymbol']??'')===$ticker && ($item['symbol']??'')===$ticker
                && ($data['usedInterval']??'')===$interval && is_array($data['historicalDataPrice']??null)) {
                $normal=analiseNormalizarBarras($data['historicalDataPrice'],$interval);
                if ($normal['bars']) $payload=$normal+['ok'=>true,'fetched_at'=>time(),'stale'=>false,'source'=>'BRAPI',
                    'interval'=>$interval,'range'=>$data['usedRange']??$range];
                else $payload['message']='A fonte não retornou candles válidos para este período.';
            } elseif ($status===200 && ($item['changed']??false)) {
                $payload['message']='A fonte informou mudança de código do ativo. Revise o ticker antes de comparar com sua carteira.';
            }
            $good=$payload['ok']?$payload:($cached['good']??null);
            if (!$payload['ok'] && $payload['code']==='unavailable' && $good && time()-$good['fetched_at']<172800) {
                $payload=$good+['message'=>$payload['message']]; $payload['stale']=true;
            }
            $next=time()+($payload['ok'] && !$payload['stale']?($interval==='1d'?1800:300):60);
            if ($file) { ftruncate($file,0); rewind($file); fwrite($file,json_encode(['next'=>$next,'payload'=>$payload,'good'=>$good],JSON_INVALID_UTF8_SUBSTITUTE)); fflush($file); }
            return $payload;
        } finally { if ($file) { flock($file,LOCK_UN); fclose($file); } }
    }
}
