<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!getenv('MCF_DB_NAME') || !in_array($argv[1] ?? '', ['--check','--apply'],true)) exit("Defina MCF_DB_NAME e --check ou --apply.\n");
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$c=new mysqli(getenv('MCF_DB_HOST') ?: '127.0.0.1',getenv('MCF_DB_USER') ?: 'root',getenv('MCF_DB_PASS') ?: '',getenv('MCF_DB_NAME'));
$c->set_charset('utf8mb4');
$map=['contas'=>'despesas','rendas'=>'receitas','compras'=>'cartao','cartoes'=>'cartao','cartao_nomes_recorrentes'=>'cartao','contas_financeiras'=>'saldos','movimentacoes_financeiras'=>'saldos','investimentos_nacionais'=>'investimentos','investimentos_internacionais'=>'investimentos','div_datacom'=>'investimentos','corretoras'=>'daytrade','corretora_taxas'=>'daytrade','operacoes'=>'daytrade','analise_acompanhamento'=>'analise','analise_marcacoes'=>'analise'];
try {
 $tables=array_column($c->query('SHOW TABLES')->fetch_all(),0);
 if (array_intersect(['historico_alteracoes','compartilhamento_vistos'],$tables) || $c->query("SHOW COLUMNS FROM compartilhamento_modulos LIKE 'cadastrar'")->num_rows) throw new RuntimeException('Migração existente ou parcial. Inspecione antes de continuar.');
 $columns=[];foreach($map as $t=>$m) { $columns[$t]=array_column($c->query("SHOW COLUMNS FROM `$t`")->fetch_all(MYSQLI_ASSOC),'Field'); if(!in_array('usuario_id',$columns[$t])) throw new RuntimeException('Escopo ausente.'); }
 if($argv[1]==='--check') exit("Pré-verificação aprovada, sem alterações.\n");
 $c->query('ALTER TABLE compartilhamento_modulos ADD cadastrar TINYINT NOT NULL DEFAULT 1, ADD editar TINYINT NOT NULL DEFAULT 1, ADD excluir TINYINT NOT NULL DEFAULT 1');
 $c->query("CREATE TABLE historico_alteracoes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, proprietario_id INT NOT NULL, ator_id INT NOT NULL, modulo VARCHAR(30) NOT NULL, tabela VARCHAR(64) NOT NULL, registro VARCHAR(255) NOT NULL, acao VARCHAR(12) NOT NULL, antes LONGTEXT NULL, depois LONGTEXT NULL, criado_em DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), INDEX dono_data(proprietario_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 $c->query('CREATE TABLE compartilhamento_vistos (usuario_id INT NOT NULL, compartilhamento_id INT NOT NULL, versao INT NOT NULL, PRIMARY KEY(usuario_id,compartilhamento_id)) ENGINE=InnoDB');
 foreach($map as $t=>$m) foreach(['INSERT'=>'cadastrar','UPDATE'=>'editar','DELETE'=>'excluir'] as $sqlAction=>$action) {
   $ref=$sqlAction==='DELETE'?'OLD':'NEW';$pk=$t==='cartao_nomes_recorrentes'?'chave_descricao':'id';
   $json=static fn($prefix)=>'JSON_OBJECT('.implode(',',array_map(static fn($f)=>"'$f',$prefix.`$f`",$columns[$t])).')';
   $old=$sqlAction==='INSERT'?'NULL':$json('OLD');$new=$sqlAction==='DELETE'?'NULL':$json('NEW');
   $changed=$sqlAction==='UPDATE'?"NOT ($old <=> $new)":'1';
   $immutable=$sqlAction==='UPDATE'?"IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF;":'';
   // MariaDB não dispara triggers dos filhos excluídos por FK: registra-os antes da cascata.
   $cascade='';
   if($sqlAction==='DELETE')foreach(match($t){'compras'=>['cartoes'=>'compra_id'],'corretoras'=>['corretora_taxas'=>'corretora_id','operacoes'=>'corretora_id'],default=>[]} as $child=>$fk){
     $childJson='JSON_OBJECT('.implode(',',array_map(static fn($f)=>"'$f',x.`$f`",$columns[$child])).')';
     $cascade.="INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) SELECT x.usuario_id,@mcf_ator_id,'$m','$child',x.id,'excluir',$childJson,NULL FROM `$child` x WHERE x.`$fk`=OLD.id AND x.usuario_id=OLD.usuario_id; ";
   }
   $c->query("CREATE TRIGGER mcf_guard_{$t}_{$sqlAction} BEFORE $sqlAction ON `$t` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN $immutable IF $ref.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> $ref.usuario_id AND $changed AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=$ref.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='$m' AND m.nivel='edicao' AND m.$action=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF; $cascade END IF; END");
   $c->query("CREATE TRIGGER mcf_audit_{$t}_{$sqlAction} AFTER $sqlAction ON `$t` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND $changed THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES($ref.usuario_id,@mcf_ator_id,'$m','$t',$ref.`$pk`,'$action',$old,$new); END IF; END");
 }
 echo "Melhorias aplicadas: permissões granulares, histórico e avisos.\n";
} catch(Throwable $e) { fwrite(STDERR,$e->getMessage()."\nDDL parcial exige inspeção; não reaplique automaticamente.\n");exit(1); }
