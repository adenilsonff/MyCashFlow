<?php
require_once __DIR__.'/../config.php';
if($_SERVER['REQUEST_METHOD']!=='GET') mcfFalhar(405,'Método não permitido.');
$m=$_GET['modulo']??'';$choice=$_GET['conta']??'';
if(!is_string($m)||!isset(mcfEntradasEdicao()[$m])||!is_string($choice)) mcfFalhar(422,'Seleção inválida.');
$base='/MyCashFlow/views/';
if($choice==='minha') $url=$base.mcfEntradasEdicao()[$m];
elseif($choice==='conjunta') $url=$base.'visao_conjunta.php?modulo='.urlencode($m);
else {
 $id=filter_var($choice,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id) mcfFalhar(422,'Conta inválida.');
 $v=mcfCompartilhamentoExigir($conn,$id,$m);
 $url=$v['nivel']==='edicao'?$base.mcfEntradasEdicao()[$m].'?compartilhamento='.$id:$base.'compartilhado.php?id='.$id.'&modulo='.urlencode($m);
}
header('Location: '.$url,true,303);exit;
