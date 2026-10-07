<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

function mcfEtiqueta(string $tipo, string $rotulo): string {
    $tipos=['unica','parcelada','recorrente','pessoal','conjunta','regular','extra'];
    $classe=in_array($tipo,$tipos,true)?$tipo:'unica';
    return '<span class="mcf-etiqueta mcf-etiqueta--'.$classe.'">'.htmlspecialchars($rotulo,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</span>';
}

// Recebe somente as cotações utilizadas nesta tela; não consulta o provedor.
function mcfAvisoMercado(array $cotacoes): string {
    $pendentes=[];
    foreach($cotacoes as $q){
        if(!is_array($q))continue;
        if(($q['price']??null)!==null && empty($q['stale']) && empty($q['error']))continue;
        $codigo=(string)($q['requested']??$q['symbol']??'Cotação');
        $moeda=(string)($q['currency']??'');
        $pendentes[$codigo.'|'.$moeda]=$q;
    }
    if(!$pendentes)return '';
    $e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $uri=$_SERVER['REQUEST_URI']??'';
    $url=str_starts_with($uri,'/')&&!str_starts_with($uri,'//')&&!str_contains($uri,'\\')?$uri:'/MyCashFlow/views/dashboard.php';
    $html='<aside class="mcf-api-aviso" role="status" aria-label="Atualização das cotações"><strong>Falha ao atualizar as cotações pela API</strong><p>O serviço de cotações não retornou todos os dados válidos. Quando disponível, mantemos a última cotação salva. Os valores calculados com ela são estimativas e podem estar desatualizados. Seus lançamentos não foram alterados.</p><details open><summary>Ver dados pendentes ('.count($pendentes).')</summary><ul>';
    foreach($pendentes as $q){
        $nome=($q['requested']??$q['symbol']??'Cotação').' · '.($q['currency']??'');
        $texto=($q['price']??null)===null?'Cotação indisponível. Ainda não há um preço válido salvo para exibir.':'Preço salvo, aguardando atualização.';
        if(($q['price']??null)!==null) {
            $casas=(float)$q['price']<1?8:(in_array($q['requested']??'',['USD','EUR'],true)?4:2);
            $texto.=' Última cotação: '.($q['currency']??'').' '.number_format((float)$q['price'],$casas,',','.').'.';
            if(!empty($q['very_old'])) $texto.=' Atenção: cotação salva há mais de 7 dias.';
        }
        if(($q['price']??null)!==null && !empty($q['fetched_at'])){
            $data=(new DateTimeImmutable('@'.(int)$q['fetched_at']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');
            $texto.=' Última consulta bem-sucedida: '.$data.' (Brasília).';
        }
        $html.='<li><strong>'.$e($nome).'</strong> — '.$e($texto).'</li>';
    }
    return $html.'</ul></details><a class="mcf-api-tentar" href="'.$e($url).'">Tentar novamente</a><small>Se o aviso continuar, aguarde alguns instantes e tente novamente.</small></aside>';
}
