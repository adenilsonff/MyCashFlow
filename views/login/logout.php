<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<!doctype html><html lang="pt-br"><meta charset="utf-8"><title>Sair</title><form method="post"><p>Encerrar esta sessão?</p>'.mcfCsrfField().'<button type="submit">Sair</button></form></html>';
    exit;
}
$_SESSION=[];
$p=session_get_cookie_params();
setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>true,'samesite'=>'Lax']);
session_destroy(); header('Location: login.php'); exit;
