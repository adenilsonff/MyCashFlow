<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/compartilhamento_consulta.php';
require_once __DIR__.'/carteiras_posicoes.php';

function mcfPessoasAutorizadas(mysqli $c, string $modulo, mixed $selecionados=null): array {
    if (!isset(mcfModulosCompartilhaveis()[$modulo])) mcfFalhar(404,'Módulo não disponível.');
    $uid=mcfUsuarioId();
    $pessoas=[$uid=>['id'=>$uid,'nome'=>$_SESSION['usuario_nome'] ?? null,'email'=>$_SESSION['usuario_email'],'convite'=>0]];
    $s=$c->prepare("SELECT u.id,u.nome,u.email,c.id AS convite FROM compartilhamentos c JOIN compartilhamento_modulos m ON m.compartilhamento_id=c.id JOIN usuarios u ON u.id=c.proprietario_id WHERE c.leitor_id=? AND c.estado='ativo' AND m.modulo=? AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE() ORDER BY u.id");
    $s->bind_param('is',$uid,$modulo);$s->execute();foreach ($s->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $pessoas[(int)$r['id']]=$r;$s->close();
    if ($selecionados===null) return $pessoas;
    if (!is_array($selecionados) || !$selecionados || count($selecionados)>100) mcfFalhar(422,'Selecione ao menos uma pessoa.');
    $out=[];
    foreach ($selecionados as $value) {
        $id=is_scalar($value) ? filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) : false;
        if (!$id || !isset($pessoas[$id])) mcfFalhar(403,'Uma das pessoas selecionadas não autorizou este módulo ou revogou o acesso.');
        $out[$id]=$pessoas[$id];
    }
    return $out;
}
function mcfPosicoesPessoas(mysqli $c, array $pessoas): array {
    $carteiras=[];
    foreach ($pessoas as $pessoa) {
        foreach (['BRL'=>'investimentos_nacionais','USD'=>'investimentos_internacionais'] as $moeda=>$tabela) {
            $uid=(int)$pessoa['id'];
            $s=$c->prepare('SELECT id,ticker,tipo_ativo,data,tipo_operacao,quantidade,valor_unitario FROM '.$tabela.' WHERE usuario_id=? ORDER BY data,id');
            $s->bind_param('i',$uid);$s->execute();$ops=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
            $posicoes=$moeda==='BRL' ? acoesConsolidar($ops) : internacionalConsolidar($ops);
            $carteiras[]=['pessoa'=>$pessoa,'moeda'=>$moeda,'posicoes'=>array_values($posicoes)];
        }
    }
    return $carteiras;
}
function mcfDecimalConjunto(mixed $value): string {
    return is_float($value) ? number_format($value,12,'.','') : (string)$value;
}
function mcfCalcularConjunto(array $carteiras, array $cotacoes): array {
    $individuais=[]; $agrupadas=[];
    foreach ($carteiras as $carteira) {
        $moeda=$carteira['moeda'];
        foreach ($carteira['posicoes'] as $p) {
            $q=mcfDecimalConjunto($p['quantidade_total']);
            $custo=mcfDecimalConjunto($p[$moeda==='BRL' ? 'total_investido' : 'total_investido_usd']);
            $key=$moeda.'|'.$p['tipo_ativo'].'|'.$p['ticker'];
            $row=['titular'=>$carteira['pessoa'],'ticker'=>$p['ticker'],'tipo'=>$p['tipo_ativo'],'moeda'=>$moeda,'quantidade'=>$q,'custo'=>$custo];
            $individuais[]=$row;
            if (!isset($agrupadas[$key])) $agrupadas[$key]=['ticker'=>$p['ticker'],'tipo'=>$p['tipo_ativo'],'moeda'=>$moeda,'quantidade'=>'0','custo'=>'0'];
            $agrupadas[$key]['quantidade']=bcadd($agrupadas[$key]['quantidade'],$q,12);
            $agrupadas[$key]['custo']=bcadd($agrupadas[$key]['custo'],$custo,12);
        }
    }
    $avaliar=static function(array $row) use($cotacoes): array {
        $quote=$cotacoes[$row['moeda']][$row['ticker']] ?? [];
        $preco=$quote['price'] ?? null;
        if (!is_numeric($preco) || !is_finite((float)$preco) || (float)$preco<=0 || ($quote['currency'] ?? $row['moeda'])!==$row['moeda']) $preco=null;
        $row['cotacao']=$preco;
        $row['medio']=bcdiv($row['custo'],$row['quantidade'],12);
        $row['mercado']=$preco===null ? null : bcmul($row['quantidade'],mcfDecimalConjunto($preco),12);
        $row['resultado']=$row['mercado']===null ? null : bcsub($row['mercado'],$row['custo'],12);
        $row['percentual']=$row['resultado']===null || bccomp($row['custo'],'0',12)<=0 ? null : bcmul(bcdiv($row['resultado'],$row['custo'],12),'100',8);
        $row['fonte']=$quote['source'] ?? 'BRAPI';$row['hora']=$quote['market_time'] ?? null;$row['stale']=!empty($quote['stale']);
        return $row;
    };
    $individuais=array_map($avaliar,$individuais);ksort($agrupadas);$agrupadas=array_map($avaliar,array_values($agrupadas));
    $totais=[];
    foreach (['BRL','USD'] as $moeda) {
        $t=['custo'=>'0','mercado'=>'0','resultado'=>null,'faltam'=>0,'stale'=>false];
        foreach ($agrupadas as $r) if ($r['moeda']===$moeda) {
            $t['custo']=bcadd($t['custo'],$r['custo'],12);
            if ($r['mercado']===null) $t['faltam']++; else $t['mercado']=bcadd($t['mercado'],$r['mercado'],12);
            $t['stale']=$t['stale'] || $r['stale'];
        }
        if ($t['faltam']) $t['mercado']=null;
        $t['resultado']=$t['mercado']===null ? null : bcsub($t['mercado'],$t['custo'],12);$totais[$moeda]=$t;
    }
    return compact('individuais','agrupadas','totais');
}
function mcfAvaliarCarteiras(array $carteiras): array {
    require_once __DIR__.'/mercado_api.php';$quotes=[];
    foreach (['BRL','USD'] as $moeda) {
        $tickers=[];foreach ($carteiras as $c) if ($c['moeda']===$moeda) foreach ($c['posicoes'] as $p) $tickers[]=$p['ticker'];
        $quotes[$moeda]=$tickers ? mercadoApi()->stocks(array_values(array_unique($tickers)),$moeda) : [];
    }
    return mcfCalcularConjunto($carteiras,$quotes);
}
