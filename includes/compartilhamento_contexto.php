<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/compartilhamento.php';

function mcfRotasCompartilhadas(): array {
    return [
        '/views/contas.php'=>'despesas', '/views/rendas.php'=>'receitas','/views/extras.php'=>'receitas',
        '/views/cartao.php'=>'cartao', '/views/saldos.php'=>'saldos',
        '/views/investimentos.php'=>'investimentos','/views/investimentos_nacionais.php'=>'investimentos',
        '/views/investimentos_internacionais.php'=>'investimentos','/views/dividendos.php'=>'investimentos',
        '/views/investimentos_operacoes.php'=>'investimentos',
        '/views/div_datacom.php'=>'investimentos','/views/div_compra.php'=>'investimentos','/views/div_valor.php'=>'investimentos',
        '/views/daytrade.php'=>'daytrade','/views/taxas.php'=>'daytrade',
        '/views/daytrade/editar_corretora.php'=>'daytrade','/views/daytrade/editar_operacao.php'=>'daytrade',
        '/views/daytrade/listar_operacoes.php'=>'daytrade','/views/daytrade/salvar_corretora.php'=>'daytrade',
        '/views/daytrade/salvar_operacao.php'=>'daytrade','/views/daytrade/ajustar_operacao.php'=>'daytrade',
        '/views/analise.php'=>'analise','/views/analise/api.php'=>'analise',
        '/views/relatorios/gastos.php'=>'despesas','/views/relatorios/receitas.php'=>'receitas',
        '/views/relatorios/cartao.php'=>'cartao','/views/relatorios/daytrade.php'=>'daytrade',
        '/views/relatorios/investimentos.php'=>'investimentos','/views/relatorios/proventos.php'=>'investimentos',
        '/views/relatorios/rel-acoes.php'=>'investimentos',
    ];
}
function mcfEntradasEdicao(): array {
    return ['despesas'=>'contas.php','receitas'=>'rendas.php','cartao'=>'cartao.php','saldos'=>'saldos.php',
        'investimentos'=>'investimentos.php','daytrade'=>'daytrade.php','analise'=>'analise.php'];
}
function mcfContextoPode(mysqli $c, string $modulo): bool {
    if (empty($GLOBALS['mcf_contexto'])) return true;
    $id=(int)$GLOBALS['mcf_contexto']['id']; $ator=mcfUsuarioId();
    $s=$c->prepare("SELECT c.id FROM compartilhamentos c JOIN compartilhamento_modulos m ON m.compartilhamento_id=c.id WHERE c.id=? AND c.leitor_id=? AND c.estado='ativo' AND m.modulo=?");
    $s->bind_param('iis',$id,$ator,$modulo); $s->execute(); $ok=(bool)$s->get_result()->fetch_assoc(); $s->close(); return $ok;
}
function mcfContextoUrl(string $url): string {
    $ctx=$GLOBALS['mcf_contexto'] ?? null;
    if (!$ctx || $url==='' || $url[0]==='#') return $url;
    $parts=parse_url($url);
    if ($parts===false || isset($parts['scheme']) || isset($parts['host'])) return $url;
    $base=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
    $p=$parts['path'] ?? '';
    $p=$p==='' ? $base : ($p[0]==='/' ? $p : dirname($base).'/'.$p);
    $segments=[];
    foreach (explode('/',$p) as $s) { if ($s==='..') array_pop($segments); elseif ($s!=='' && $s!=='.') $segments[]=$s; }
    $path='/'.implode('/',$segments);
    $route=preg_replace('#^/MyCashFlow(?=/)#','',$path);
    if (!isset(mcfRotasCompartilhadas()[$route])) return $url;
    parse_str($parts['query'] ?? '',$query); $query['compartilhamento']=$ctx['id'];
    return $path.'?'.http_build_query($query).(isset($parts['fragment']) ? '#'.$parts['fragment'] : '');
}
function mcfContextoHtml(string $html): string {
    $ctx=$GLOBALS['mcf_contexto'];
    // Reescreve apenas atributos de navegação HTML. SQL, scripts e respostas JSON não são reescritos.
    return preg_replace_callback('/<(a|form)\b[^>]*>/i', static function($match) use($ctx) {
        $tag=$match[0]; if (stripos($tag,'data-mcf-own')!==false) return $tag;
        $attr=strtolower($match[1])==='a' ? 'href' : 'action';
        if (preg_match('/\b'.$attr.'\s*=\s*(["\'])(.*?)\1/is',$tag,$found)) {
            $old=html_entity_decode($found[2],ENT_QUOTES|ENT_HTML5,'UTF-8'); $new=mcfContextoUrl($old);
            $tag=str_replace($found[0],$attr.'="'.htmlspecialchars($new,ENT_QUOTES,'UTF-8').'"',$tag);
        } elseif ($attr==='action') {
            $tag=substr($tag,0,-1).' action="'.htmlspecialchars(mcfContextoUrl($_SERVER['REQUEST_URI']),ENT_QUOTES,'UTF-8').'">';
        }
        if ($attr==='action') {
            $tag.='<input type="hidden" name="compartilhamento" value="'.(int)$ctx['id'].'"><input type="hidden" name="mcf_contexto" value="'.(int)$ctx['id'].'"><input type="hidden" name="mcf_contexto_versao" value="'.(int)$ctx['versao'].'">';
        }
        return $tag;
    },$html);
}
function mcfContextoIniciar(mysqli $c, string $route): void {
    if (!isset($_GET['compartilhamento']) && !isset($_POST['mcf_contexto']) && !isset($_POST['compartilhamento']) && ($_SERVER['HTTP_X_MCF_CONTEXT_VERSION'] ?? '0')==='0') return;
    $modulo=mcfRotasCompartilhadas()[$route] ?? null;
    if (!$modulo) mcfFalhar(403,'Esta página não permite acesso compartilhado.');
    try { $id=mcfCompartilhamentoInt($_GET,'compartilhamento'); } catch (DomainException $e) { mcfFalhar(403,'Contexto de compartilhamento ausente ou inválido.'); }
    // As telas operacionais só abrem para quem pode editar. Consulta tem rotas próprias.
    $v=mcfCompartilhamentoExigir($c,$id,$modulo,'edicao');
    if (($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
        $dados=$_POST;
        if ($route==='/views/analise/api.php') $dados=json_decode(file_get_contents('php://input',false,null,0,12001),true) ?: [];
        $acoes=mcfAcoesRequisicao($route,is_array($dados)?$dados:[]);
        foreach($acoes as $acao) if(empty($v[$acao])) mcfFalhar(403,'O titular não autorizou a ação: '.$acao.'. Volte ao módulo para continuar.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
        $version=$route==='/views/analise/api.php' ? ($_SERVER['HTTP_X_MCF_CONTEXT_VERSION'] ?? '') : ($_POST['mcf_contexto_versao'] ?? '');
        $context=$route==='/views/analise/api.php' ? (string)$id : ($_POST['mcf_contexto'] ?? '');
        if (!is_scalar($version) || !is_scalar($context) || (string)$version!==(string)$v['versao'] || (string)$context!==(string)$id) mcfFalhar(403,'Permissão atualizada ou formulário sem contexto. Reabra o módulo compartilhado.');
    }
    $GLOBALS['mcf_contexto']=$v+['id'=>$id,'modulo'=>$modulo];
    $dono=(int)$v['proprietario_id'];
    $version=(int)$v['versao']; $s=$c->prepare('SET @mcf_usuario_id = ?, @mcf_convite_id = ?, @mcf_convite_versao = ?'); $s->bind_param('iii',$dono,$id,$version); $s->execute(); $s->close();
    header_register_callback(static function() {
        foreach (headers_list() as $h) if (stripos($h,'Location:')===0) { $old=trim(substr($h,9)); $new=mcfContextoUrl($old); if ($new!==$old) header('Location: '.$new,true); }
    });
    if ($route!=='/views/analise/api.php' && $route!=='/views/daytrade/listar_operacoes.php') ob_start('mcfContextoHtml');
}

function mcfAcoesRequisicao(string $route,array $dados): array {
    if($route==='/views/analise/api.php') return match($dados['action']??'') {'watch'=>['cadastrar'],'unwatch','delete'=>['excluir'],'save'=>[(int)($dados['id']??0)>0?'editar':'cadastrar'],default=>[]};
    if(str_ends_with($route,'/salvar_corretora.php')||str_ends_with($route,'/salvar_operacao.php')) return ['cadastrar'];
    if(str_ends_with($route,'/ajustar_operacao.php')) return ['editar'];
    $out=[];$keys=array_keys($dados);if(is_string($dados['acao']??null))$keys[]=$dados['acao'];
    foreach($keys as $key) {
        if(preg_match('/^(deletar|excluir|remover)/',$key))$out[]='excluir';
        elseif(preg_match('/^(ajustar|atualizar|editar|renomear|inativar|reativar|corrigir)/',$key))$out[]='editar';
        elseif(preg_match('/^(nova_|cadastrar|adicionar|transferir|registrar_)/',$key))$out[]='cadastrar';
        elseif(in_array($key,['upload_fatura','resolver_ofx'],true))$out=array_merge($out,['cadastrar','editar']);
    }
    return array_values(array_unique($out));
}
