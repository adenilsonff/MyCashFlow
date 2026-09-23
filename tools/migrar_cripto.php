<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(!getenv('MCF_DB_NAME')||!in_array($argv[1]??'',['--check','--apply'],true)){fwrite(STDERR,"Defina MCF_DB_NAME e --check ou --apply.\n");exit(1);}
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{
 $c=new mysqli(getenv('MCF_DB_HOST')?:'127.0.0.1',getenv('MCF_DB_USER')?:'root',getenv('MCF_DB_PASS')?:'',getenv('MCF_DB_NAME'));$c->set_charset('utf8mb4');
 $cols=array_column($c->query('SHOW COLUMNS FROM investimentos_internacionais')->fetch_all(MYSQLI_ASSOC),'Type','Field');
 if($cols['tipo_ativo']!=="enum('stock','etf','reit','adr')"||$cols['valor_unitario']!=='decimal(18,4)')throw new RuntimeException('Estrutura diferente ou migração já aplicada. Inspecione antes de prosseguir.');
 if($argv[1]==='--check')exit("Pré-verificação aprovada; nenhuma alteração.\n");
 $c->query("ALTER TABLE investimentos_internacionais MODIFY tipo_ativo ENUM('stock','etf','reit','adr','cripto') NOT NULL, MODIFY valor_unitario DECIMAL(22,8) NOT NULL, MODIFY valor_mercado DECIMAL(22,8) NULL");
 echo "Criptomoeda adicionada à carteira USD; precisão de preço ampliada sem modificar valores.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
