<?php
// Executar apenas no banco sintético indicado; nunca na instalação real.
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
if(getenv('MCF_DB_NAME')!=='mcf_notifications'||getenv('MCF_DB_PORT')!=='3307') {fwrite(STDERR,"Use somente mcf_notifications na porta 3307.\n");exit(1);}
require_once __DIR__.'/../includes/notificacoes.php';
require_once __DIR__.'/../includes/backup_conta.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$c=new mysqli('127.0.0.1','root','','mcf_notifications',3307);$c->set_charset('utf8mb4');
$pass=0;function verificar(bool $ok,string $label): void {global $pass;if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label."\n";$pass++;}
foreach([['2026-02-26',31,'2026-02-28'],['2028-02-28',30,'2028-02-29'],['2026-12-31',1,'2027-01-01'],['2026-10-06',6,'2026-10-06'],['2026-10-07',6,'2026-11-06']] as [$hoje,$dia,$esperado]) verificar(mcfProximoFechamento($dia,mcfNotificacaoData($hoje))===$esperado,'Calendário '.$hoje.' / '.$dia);
try {mcfProximoFechamento(32,mcfNotificacaoHoje());verificar(false,'Dia inválido');}catch(DomainException $e){verificar(true,'Dia fora do mês rejeitado');}
$hoje=mcfNotificacaoHoje();$data=fn($dias)=>$hoje->modify(($dias>=0?'+':'').$dias.' days')->format('Y-m-d');
$senha=password_hash('Teste-Conta-2026!',PASSWORD_DEFAULT);
$s=$c->prepare("INSERT INTO usuarios(id,nome,email,senha,status_assinatura,data_expiracao) VALUES(?,?,?,?,'ativo',?)");
foreach([1,2,3] as $id){$nome='Teste '.$id;$email=['','alfa@example.test','beta@example.test','backup@example.test'][$id];$exp=$id===1?$data(10):'2099-12-31';$s->bind_param('issss',$id,$nome,$email,$senha,$exp);$s->execute();}$s->close();
$conta=function($nome,$dias,$uid=1,$paga=0)use($c,$data){$d=$data($dias);$s=$c->prepare("INSERT INTO contas(usuario_id,nome,tipo,categoria,vencimento,valor,paga) VALUES(?,?,'unica','pessoal',?,100,?)");$s->bind_param('issi',$uid,$nome,$d,$paga);$s->execute();return $c->insert_id;};
$atraso=$conta('Conta atrasada',-2);$vencendo=$conta('Conta hoje',0);$limite=$conta('Conta no limite',7);$conta('Fora da janela',8);$conta('Conta paga',1,1,1);$conta('SEGREDO BETA',0,2);
$c->query("INSERT INTO investimentos_nacionais(usuario_id,ticker,tipo_ativo,quantidade,valor_unitario,data,tipo_operacao) VALUES(1,'PETR4','acao',10,35,'".$data(-30)."','compra'),(1,'PETR4','acao',2,40,'".$data(-10)."','venda'),(1,'PETR4','acao',20,35,'".$data(-2)."','compra'),(2,'VALE3','acao',50,35,'".$data(-30)."','compra')");
$c->query("INSERT INTO div_datacom(usuario_id,ticker,tipo_ativo,datacom,datapag,valor,tipo) VALUES(1,'PETR4','acao','".$data(-5)."','".$data(7)."',1.25,'DIV'),(1,'VALE3','acao','".$data(-5)."','".$data(2)."',1,'JCP'),(1,'PETR4','fii','".$data(-5)."','".$data(2)."',1,'REND'),(1,'PETR4','acao','".$data(-5)."','".$data(8)."',1,'DIV'),(1,'PETR4','acao','".$data(-5)."','".$data(-1)."',1,'DIV')");
mcfLembreteCartaoSalvar($c,1,['nome'=>'Cartão principal','dia_fechamento'=>$hoje->format('j')]);
mcfLembreteCartaoSalvar($c,2,['nome'=>'Cartão privado Beta','dia_fechamento'=>$hoje->format('j')]);
$avs=mcfNotificacoes($c,1);$tipos=array_count_values(array_column($avs,'tipo'));
verificar(count($avs)===6,'Seis avisos: três contas, um cartão, um provento e assinatura');
verificar(($tipos['dividendo']??0)===1,'Provento exige posição do titular, tipo e período corretos');
$provento=current(array_filter($avs,fn($a)=>$a['tipo']==='dividendo'));verificar(str_contains($provento['texto'],'R$ 10,00'),'Posição na data com considera vendas e exclui compras posteriores');
verificar(!str_contains(json_encode($avs),'SEGREDO')&&!str_contains(json_encode($avs),'Beta'),'Isolamento entre titulares');
verificar($avs===mcfNotificacoes($c,1),'Visitas não duplicam avisos');
$ler=current(array_filter($avs,fn($a)=>$a['tipo']==='conta'));mcfNotificacaoMarcarLida($c,1,$ler['chave']);mcfNotificacaoMarcarLida($c,1,$ler['chave']);
verificar((int)$c->query('SELECT COUNT(*) FROM notificacao_lidos WHERE usuario_id=1')->fetch_row()[0]===1,'Leitura idempotente');
verificar(count(array_filter(mcfNotificacoes($c,1),fn($a)=>$a['lido']))===1,'Leitura persiste');
verificar(count(array_filter(mcfNotificacoes($c,2),fn($a)=>$a['lido']))===0,'Leitura não afeta outro usuário');
try{mcfNotificacaoMarcarLida($c,2,$ler['chave']);verificar(false,'Aviso estrangeiro');}catch(DomainException $e){verificar(true,'Chave de outro titular rejeitada');}
$antes=current(array_filter(mcfNotificacoes($c,1),fn($a)=>str_ends_with($a['titulo'],'Conta hoje')));
$depois=current(array_filter(mcfNotificacoes($c,1,$hoje->modify('+1 day')),fn($a)=>str_ends_with($a['titulo'],'Conta hoje')));
verificar($antes['chave']!==$depois['chave']&&$depois['tipo']==='atraso','Passagem para atraso gera nova ocorrência');
$c->query('UPDATE contas SET paga=1 WHERE id='.$atraso);verificar(count(mcfNotificacoes($c,1))===5,'Pagamento remove aviso');$c->query('UPDATE contas SET paga=0 WHERE id='.$atraso);
$c->query("INSERT INTO usuario_modulos(usuario_id,modulo,habilitado) VALUES(1,'despesas',0),(1,'investimentos',0)");
verificar(array_column(mcfNotificacoes($c,1),'tipo')===['assinatura'],'Módulos desativados e seus filhos não geram avisos');$c->query('DELETE FROM usuario_modulos WHERE usuario_id=1');
$foreign=mcfLembretesCartao($c,2)[0];
try {mcfLembreteCartaoSalvar($c,1,['id'=>(string)$foreign['id'],'nome'=>'Invasão','dia_fechamento'=>'1']);verificar(false,'Edição estrangeira');}catch(DomainException $e){verificar(true,'Cartão de outro titular não pode ser editado');}
try {mcfLembreteCartaoSalvar($c,1,['nome'=>'Cartão principal','dia_fechamento'=>'1']);verificar(false,'Duplicata');}catch(DomainException $e){verificar(true,'Nome duplicado rejeitado');}
$backup=mcfBackupGerar($c,1);$valid=mcfBackupValidar($c,$backup);verificar(count($valid['tabelas']['notificacao_cartoes'])===1,'Backup inclui configuração de cartão');
foreach([[],['rm_fontes','rm_partes','rm_objetivos','rm_reposicoes','rm_metas','rm_eventos'],['rm_fontes','rm_partes','rm_objetivos','rm_reposicoes','rm_metas','rm_eventos','cartao_exclusoes'],['rm_fontes','rm_partes','rm_objetivos','rm_reposicoes','rm_metas','rm_eventos','cartao_exclusoes','cartao_categorias_recorrentes']] as $retirar){$antigo=$valid;foreach(array_merge(['notificacao_cartoes'],$retirar) as $t)unset($antigo['tabelas'][$t]);verificar(mcfBackupValidar($c,json_encode($antigo))['tabelas']['notificacao_cartoes']===[],'Compatibilidade de backup anterior '.count($retirar));}
mcfBackupRestaurar($c,3,$valid);verificar(mcfLembretesCartao($c,3)[0]['nome']==='Cartão principal','Backup restaura lembrete com novo titular');
verificar(mcfLembretesCartao($c,3)[0]['id']!==mcfLembretesCartao($c,1)[0]['id'],'Restauração remapeia ID');
verificar((int)$c->query('SELECT COUNT(*) FROM notificacao_lidos WHERE usuario_id=3')->fetch_row()[0]===0,'Estado de leitura permanece pessoal e fora do backup');
echo "TOTAL: $pass verificações aprovadas.\n";
