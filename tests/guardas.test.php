<?php
if(getenv('MCF_DB_NAME')!=='mcf_test_20260921')exit(1);
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$c=new mysqli('127.0.0.1','root','','mcf_test_20260921');$c->set_charset('utf8mb4');$n=0;
function verify(bool $v,string $m):void{global $n;if(!$v)throw new RuntimeException($m);$n++;}
$c->begin_transaction();
try {
 $share=$c->query('SELECT id FROM compartilhamentos WHERE proprietario_id=1 AND leitor_id=2')->fetch_assoc()['id'];
 $c->query("UPDATE compartilhamentos SET estado='ativo' WHERE id=$share");$c->query("UPDATE compartilhamento_modulos SET nivel='edicao',cadastrar=1,editar=0,excluir=0 WHERE compartilhamento_id=$share");
 $version=$c->query("SELECT versao FROM compartilhamentos WHERE id=$share")->fetch_assoc()['versao'];
 $c->query("SET @mcf_ator_id=2,@mcf_usuario_id=1,@mcf_convite_id=$share,@mcf_convite_versao=$version");
 foreach(['contas'=>'valor','rendas'=>'valor','cartoes'=>'valor','contas_financeiras'=>'saldo_inicial','investimentos_nacionais'=>'valor_unitario','corretora_taxas'=>'percentual','analise_marcacoes'=>'preco'] as $table=>$field){
  $row=$c->query("SELECT id FROM `$table` WHERE usuario_id=1 LIMIT 1")->fetch_assoc();verify((bool)$row,'fixture '.$table);$id=$row['id'];
  foreach(["UPDATE `$table` SET `$field`=COALESCE(`$field`,0)+1 WHERE id=$id AND usuario_id=1","DELETE FROM `$table` WHERE id=$id AND usuario_id=1"] as $sql){$denied=false;try{$c->query($sql);}catch(mysqli_sql_exception $e){$denied=$e->getCode()===1644;}verify($denied,'guarda SQL '.$table);}
 }
 // Uma mutação e seu histórico são revertidos juntos.
 $c->query('SET @mcf_ator_id=1,@mcf_usuario_id=1,@mcf_convite_id=0,@mcf_convite_versao=0');
 $before=(int)$c->query('SELECT COUNT(*) n FROM historico_alteracoes')->fetch_assoc()['n'];$c->query('SAVEPOINT audit_check');
 $c->query("UPDATE contas SET valor=valor+1 WHERE usuario_id=1 LIMIT 1");verify((int)$c->query('SELECT COUNT(*) n FROM historico_alteracoes')->fetch_assoc()['n']===$before+1,'auditoria transacional');
 $c->query('ROLLBACK TO SAVEPOINT audit_check');verify((int)$c->query('SELECT COUNT(*) n FROM historico_alteracoes')->fetch_assoc()['n']===$before,'rollback remove auditoria da alteração abortada');
 $c->query("INSERT INTO corretoras(usuario_id,nome) VALUES(1,'CASCADE-QA')");$parent=$c->insert_id;
 $c->query("INSERT INTO corretora_taxas(usuario_id,corretora_id,nome_taxa,percentual) VALUES(1,$parent,'QA',1)");$child=$c->insert_id;
 $c->query("DELETE FROM corretoras WHERE id=$parent AND usuario_id=1");
 verify((int)$c->query("SELECT COUNT(*) n FROM historico_alteracoes WHERE tabela='corretora_taxas' AND registro='$child' AND acao='excluir'")->fetch_assoc()['n']===1,'exclusão em cascata tem histórico');
 echo "PASS: $n verificações de guardas SQL, rollback e cascata\n";
}finally{$c->rollback();}
