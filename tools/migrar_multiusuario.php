<?php
// DDL do MySQL faz commit implícito: executar apenas após backup e em manutenção.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$db=getenv('MCF_DB_NAME');
if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/D',$db)) exit("Defina MCF_DB_NAME explicitamente.\n");
$owner=filter_var($argv[1]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$owner) exit("Informe o proprietário confirmado dos dados legados.\n");
$backup=realpath($argv[2]??'');
if ($db!=='mcf_test_20260921' && (!$backup || !is_file($backup) || filesize($backup)<100)) {
    fwrite(STDERR,"Informe como segundo argumento o backup SQL completo e verificado.\n"); exit(1);
}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$c=new mysqli(getenv('MCF_DB_HOST')?:'127.0.0.1',getenv('MCF_DB_USER')?:'root',getenv('MCF_DB_PASS')?:'',$db,(int)(getenv('MCF_DB_PORT')?:3306));
$s=$c->prepare('SELECT id FROM usuarios WHERE id=?'); $s->bind_param('i',$owner);$s->execute();
if (!$s->get_result()->fetch_assoc()) exit("Proprietário inexistente.\n");
if ($c->query("SHOW COLUMNS FROM contas LIKE 'usuario_id'")->num_rows) { fwrite(STDERR,"Migração já iniciada/aplicada. Confira o estado antes de retomar.\n"); exit(1); }
$checks=[
 'SELECT COUNT(*) n FROM movimentacoes_financeiras m LEFT JOIN contas_financeiras p ON p.id=m.conta_id AND p.usuario_id=m.usuario_id WHERE p.id IS NULL',
 'SELECT COUNT(*) n FROM cartoes c LEFT JOIN compras p ON p.id=c.compra_id WHERE p.id IS NULL',
 'SELECT COUNT(*) n FROM corretora_taxas c LEFT JOIN corretoras p ON p.id=c.corretora_id WHERE p.id IS NULL',
 'SELECT COUNT(*) n FROM operacoes c LEFT JOIN corretoras p ON p.id=c.corretora_id WHERE p.id IS NULL'
];
foreach(['clientes','contas_financeiras','movimentacoes_financeiras','investimentos_nacionais','investimentos_internacionais','div_datacom','analise_acompanhamento','analise_marcacoes'] as $t) $checks[]="SELECT COUNT(*) n FROM `$t` t LEFT JOIN usuarios u ON u.id=t.usuario_id WHERE u.id IS NULL";
foreach($checks as $sql) if ((int)$c->query($sql)->fetch_assoc()['n']>0) { fwrite(STDERR,"Há proprietários/vínculos inconsistentes. Migração cancelada antes do DDL.\n"); exit(1); }
$tables=['contas','rendas','compras','cartoes','corretoras','corretora_taxas','operacoes','ofx_importacoes','cartao_nomes_recorrentes'];
foreach($tables as $t) {
    $c->query("ALTER TABLE `$t` ADD usuario_id INT NULL");
    $s=$c->prepare("UPDATE `$t` SET usuario_id=?");$s->bind_param('i',$owner);$s->execute();$s->close();
    $c->query("ALTER TABLE `$t` MODIFY usuario_id INT NOT NULL, ADD INDEX idx_mcf_owner (usuario_id), ADD CONSTRAINT fk_mcf_{$t}_owner FOREIGN KEY (usuario_id) REFERENCES usuarios(id)");
}
$c->query('ALTER TABLE cartao_nomes_recorrentes DROP PRIMARY KEY, ADD PRIMARY KEY (usuario_id,chave_descricao)');
// A deduplicação de arquivos pertence à conta, não ao sistema inteiro.
$indexes=$c->query('SHOW INDEX FROM ofx_importacoes'); $drop=[];
while($i=$indexes->fetch_assoc()) if (!$i['Non_unique'] && $i['Key_name']!=='PRIMARY' && $i['Column_name']==='hash_arquivo') $drop[$i['Key_name']]=true;
foreach(array_keys($drop) as $i) $c->query('ALTER TABLE ofx_importacoes DROP INDEX `'.str_replace('`','``',$i).'`');
$c->query('ALTER TABLE ofx_importacoes ADD UNIQUE KEY uq_mcf_ofx_owner (usuario_id,hash_arquivo)');
foreach(['compras','corretoras','contas_financeiras'] as $t) $c->query("ALTER TABLE `$t` ADD UNIQUE KEY uq_mcf_id_owner (id,usuario_id)");
foreach([
 ['cartoes','compra_id','compras','CASCADE'],
 ['corretora_taxas','corretora_id','corretoras','CASCADE'],
 ['operacoes','corretora_id','corretoras','CASCADE'],
 ['movimentacoes_financeiras','conta_id','contas_financeiras','RESTRICT']
] as [$child,$column,$parent,$onDelete]) {
 $c->query("ALTER TABLE `$child` ADD CONSTRAINT fk_mcf_{$child}_relation FOREIGN KEY (`$column`,usuario_id) REFERENCES `$parent`(id,usuario_id) ON DELETE $onDelete");
}
foreach(['analise_acompanhamento','analise_marcacoes'] as $t) $c->query("ALTER TABLE `$t` ADD CONSTRAINT fk_mcf_{$t}_owner FOREIGN KEY (usuario_id) REFERENCES usuarios(id)");
echo "Migração 20260921_multiusuario aplicada.\n";
