<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/relatorios_dados.php';
if(($_SERVER['REQUEST_METHOD']??'')!=='POST')mcfFalhar(405,'Use o botão Exportar PDF do relatório.');
$token=$_POST['snapshot']??'';
if(!is_string($token)||!preg_match('/^[a-f0-9]{48}$/',$token))mcfFalhar(422,'Consulta inválida. Atualize o relatório.');
$snapshot=$_SESSION['relatorios'][$token]??null;
if(!$snapshot||$snapshot['expira']<time()||$snapshot['ator']!==mcfUsuarioId())mcfFalhar(410,'A consulta expirou. Atualize o relatório antes de exportar.');
$r=$snapshot['relatorio'];
// Revalidar mesmo que o snapshot tenha sido criado antes de uma revogação.
relPessoas($conn,array_column($r['pessoas'],'id'));
$detalhe=$_POST['detalhe']??'resumido';
if(!is_string($detalhe)||!in_array($detalhe,['resumido','detalhado'],true))mcfFalhar(422,'Formato inválido.');
session_write_close();require_once __DIR__.'/../../includes/relatorios_pdf.php';
$bytes=relPdf($r,$detalhe==='detalhado');
header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="financeiro-'.$r['filtros']['inicio'].'-'.$detalhe.'.pdf"');
header('Cache-Control: no-store, private');header('Content-Length: '.strlen($bytes));echo $bytes;
