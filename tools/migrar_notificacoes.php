<?php
if (PHP_SAPI !== 'cli') {http_response_code(404);exit;}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try {
    if (!getenv('MCF_DB_NAME')) throw new RuntimeException('Defina MCF_DB_NAME explicitamente.');
    $modo=$argv[1]??'';
    if (!in_array($modo,['--check','--apply'],true)) throw new RuntimeException('Use --check ou --apply após backup recuperável.');
    $c=new mysqli(getenv('MCF_DB_HOST')?:'127.0.0.1',getenv('MCF_DB_USER')?:'root',getenv('MCF_DB_PASS')?:'',getenv('MCF_DB_NAME'),(int)(getenv('MCF_DB_PORT')?:3306));
    $c->set_charset('utf8mb4');
    $existentes=array_column($c->query('SHOW TABLES')->fetch_all(),0);
    if (array_intersect(['notificacao_cartoes','notificacao_lidos'],$existentes)) throw new RuntimeException('Estrutura já aplicada ou parcial. Inspecione antes de continuar.');
    if (!in_array('usuarios',$existentes,true)) throw new RuntimeException('Instalação multiusuário não encontrada.');
    if ($modo==='--check') {echo "Pré-verificação aprovada; nenhuma alteração.\n";exit;}
    $sql=file_get_contents(__DIR__.'/../migrations/20261006_notificacoes.sql');
    foreach(explode(';',$sql) as $q) if(trim($q)!=='') $c->query($q);
    echo "Duas tabelas de notificações criadas. Dados financeiros preservados.\n";
} catch(Throwable $e) {fwrite(STDERR,($e instanceof mysqli_sql_exception?'Falha na migração. Inspecione a estrutura; DDL não é revertido automaticamente.':$e->getMessage())."\n");exit(1);}
