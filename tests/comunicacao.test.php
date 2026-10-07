<?php
require __DIR__.'/../includes/comunicacao.php';
$n=0;
function verificar($v,$nome){global $n;if(!$v)throw new RuntimeException($nome);$n++;}
$q=['requested'=>'PETR4','currency'=>'BRL','price'=>'40.00','stale'=>false,'error'=>'','fetched_at'=>172800];
verificar(mcfAvisoMercado([])==='', 'Sem consultas não gera alerta');
verificar(mcfAvisoMercado([$q])==='', 'Cotação válida não gera alerta');
$q['stale']=true;$html=mcfAvisoMercado([$q,$q]);
verificar(str_contains($html,'pendentes (1)'), 'Deduplicação por código e moeda');
verificar(str_contains($html,'Última consulta bem-sucedida:')&&str_contains($html,'Brasília'), 'Cache identifica horário');
$q['price']=null;$html=mcfAvisoMercado([$q]);
verificar(str_contains($html,'Cotação indisponível.')&&!str_contains($html,'Última consulta bem-sucedida:'), 'Preço ausente não recebe aparência de preço salvo');
$q['requested']='<script>alert(1)</script>';$q['error']='TOKEN_PRIVADO';$html=mcfAvisoMercado([$q]);
verificar(!str_contains($html,'<script>')&&str_contains($html,'&lt;script&gt;'), 'Código externo escapado');
verificar(!str_contains($html,'TOKEN_PRIVADO'),'Não expõe erros internos ou credenciais');
$_SERVER['REQUEST_URI']='//example.invalid';
verificar(!str_contains(mcfAvisoMercado([$q]),'example.invalid'),'Botão não direciona para outro domínio');
$_SERVER['REQUEST_URI']='/views/contas.php?compartilhamento=4&mes=10';
verificar(str_contains(mcfAvisoMercado([$q]),'compartilhamento=4&amp;mes=10'),'Preserva contexto e filtros em GET');
verificar(!str_contains(mcfEtiqueta('" onclick="x','<b>Teste</b>'),'<b>')&&!str_contains(mcfEtiqueta('" onclick="x','Teste'),'onclick'),'Etiqueta escapa conteúdo e restringe classes');
echo "PASS: $n verificações de comunicação\n";
