<?php
require_once __DIR__.'/../../config.php';
// Ator permanece na sessão; proprietário vem do contexto autorizado pelo bootstrap.
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function analiseResposta(array $data, int $status=200): never {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
require_once __DIR__.'/../../config.php';
$uid=mcfDonoId();
if (!$uid) analiseResposta(['ok'=>false,'message'=>'Entre novamente para acessar a Análise.'],401);
$method=$_SERVER['REQUEST_METHOD'];
if (!in_array($method,['GET','POST'],true)) { header('Allow: GET, POST'); analiseResposta(['ok'=>false,'message'=>'Método não permitido.'],405); }
if ($method==='POST') {
    $token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
    if (!is_string($token) || empty($_SESSION['csrf_analise']) || !hash_equals($_SESSION['csrf_analise'],$token)) {
        analiseResposta(['ok'=>false,'message'=>'Formulário expirado. Recarregue a página.'],403);
    }
    if ((int)($_SERVER['CONTENT_LENGTH']??0)>12000) analiseResposta(['ok'=>false,'message'=>'Solicitação muito grande.'],413);
    $input=json_decode(file_get_contents('php://input',false,null,0,12001),true);
    if (!is_array($input)) analiseResposta(['ok'=>false,'message'=>'Solicitação inválida.'],400);
} else $input=$_GET;
session_write_close(); // Não bloqueia as outras páginas durante a consulta externa.
try {
    require_once __DIR__.'/../../config.php';
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    require_once __DIR__.'/../../includes/analise.php';
    $user=analiseRows($conn,'SELECT id FROM usuarios WHERE id=? AND status_assinatura=? AND data_expiracao>=CURRENT_DATE()', 'is',[$uid,'ativo']);
    if (!$user) analiseResposta(['ok'=>false,'message'=>'Seu acesso está inativo ou expirado.'],403);
    $action=$input['action']??'';
    $incluirInvestimentos=mcfContextoPode($conn,'investimentos');
    if ($method==='GET' && $action==='assets') analiseResposta(['ok'=>true,'assets'=>analiseCarteira($conn,$uid,$incluirInvestimentos)]);
    [$ticker,$tipoAtivo]=analiseAtivo($input);
    if ($method==='GET' && $action==='state') {
        $ops=$incluirInvestimentos ? analiseRows($conn,'SELECT id,data,quantidade,valor_unitario,tipo_operacao FROM investimentos_nacionais
            WHERE usuario_id=? AND (UPPER(ticker)=? OR UPPER(ticker)=CONCAT(?,\'.SA\')) AND tipo_ativo=? ORDER BY data,id',
            'isss',[$uid,$ticker,$ticker,$tipoAtivo]) : [];
        $marks=analiseRows($conn,'SELECT id,tipo,titulo,preco,cor,texto,versao FROM analise_marcacoes
            WHERE usuario_id=? AND ticker=? AND tipo_ativo=? ORDER BY id','iss',[$uid,$ticker,$tipoAtivo]);
        analiseResposta(['ok'=>true,'operations'=>$ops,'annotations'=>$marks,'position'=>analiseResumoPosicao($ops,$ticker,$tipoAtivo)]);
    }
    if ($method==='GET' && $action==='history') {
        if (!is_string($input['range']??null) || !is_string($input['interval']??null)) throw new DomainException('Período inválido.');
        $api=new AnaliseHistorico(require __DIR__.'/../../includes/mercado_config.php');
        analiseResposta($api->obter($ticker,$input['range'],$input['interval']));
    }
    if ($method==='POST' && $action==='watch') {
        analiseSql($conn,'INSERT INTO analise_acompanhamento (usuario_id,ticker,tipo_ativo) VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE ticker=VALUES(ticker)','iss',[$uid,$ticker,$tipoAtivo])->close();
        analiseResposta(['ok'=>true]);
    }
    if ($method==='POST' && $action==='unwatch') {
        analiseSql($conn,'DELETE FROM analise_acompanhamento WHERE usuario_id=? AND ticker=? AND tipo_ativo=?',
            'iss',[$uid,$ticker,$tipoAtivo])->close();
        analiseResposta(['ok'=>true]);
    }
    if ($method==='POST' && in_array($action,['save','delete'],true)) {
        $id=filter_var($input['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        $version=filter_var($input['versao']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if ($id===false || $version===false || ($id>0 && $version<1) || ($action==='delete' && !$id)) throw new DomainException('Marcação inválida.');
        if ($action==='save') {
            [$kind,$title,$price,$color,$text]=analiseMarcacao($input);
            if (!$id) {
                $stmt=analiseSql($conn,'INSERT INTO analise_marcacoes (usuario_id,ticker,tipo_ativo,tipo,titulo,preco,cor,texto) VALUES (?,?,?,?,?,?,?,?)',
                    'isssssss',[$uid,$ticker,$tipoAtivo,$kind,$title,$price,$color,$text]);
            } else {
                $stmt=analiseSql($conn,'UPDATE analise_marcacoes SET tipo=?,titulo=?,preco=?,cor=?,texto=?,versao=versao+1
                    WHERE id=? AND usuario_id=? AND ticker=? AND tipo_ativo=? AND versao=?',
                    'sssssiissi',[$kind,$title,$price,$color,$text,$id,$uid,$ticker,$tipoAtivo,$version]);
            }
        } else $stmt=analiseSql($conn,'DELETE FROM analise_marcacoes WHERE id=? AND usuario_id=? AND ticker=? AND tipo_ativo=? AND versao=?',
            'iissi',[$id,$uid,$ticker,$tipoAtivo,$version]);
        $affected=$stmt->affected_rows; $stmt->close();
        if (!$affected) analiseResposta(['ok'=>false,'message'=>'A marcação foi alterada ou não está disponível. Atualize a lista antes de tentar novamente.'],409);
        analiseResposta(['ok'=>true]);
    }
    analiseResposta(['ok'=>false,'message'=>'Ação não encontrada.'],404);
} catch (DomainException $e) {
    analiseResposta(['ok'=>false,'message'=>$e->getMessage()],422);
} catch (Throwable $e) {
    // Não envia SQL, dados privados ou credenciais para o navegador.
    error_log('MyCashFlow Análise: '.get_class($e).' em '.basename($e->getFile()).':'.$e->getLine());
    analiseResposta(['ok'=>false,'message'=>'Não foi possível concluir a operação. Tente novamente.'],500);
}
