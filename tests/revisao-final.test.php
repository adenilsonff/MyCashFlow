<?php
if(PHP_SAPI!=='cli'||getenv('MCF_DB_NAME')!=='mcf_final'||getenv('MCF_DB_PORT')!=='3307')exit(1);
require __DIR__.'/../includes/valores.php';require __DIR__.'/../includes/saldos_consulta.php';require __DIR__.'/../includes/reservas.php';
$n=0;function checkFinal($ok,$label){global $n;if(!$ok)throw new RuntimeException($label);$n++;echo "PASS $label\n";}
foreach(['abc','','NaN','INF','1e3','1.234','1,234','1.23,45','1 2','-1','100000000.00',[],null] as $v){try{mcfValorMonetario($v);checkFinal(false,'Valor inválido aceito');}catch(DomainException $e){checkFinal(true,'Rejeita formato/limite inválido');}}
foreach(['0'=>0,'0.01'=>.01,'1234,56'=>1234.56,'R$ 1.234,56'=>1234.56,'99999999.99'=>99999999.99] as $v=>$expected)checkFinal(mcfValorMonetario((string)$v)===$expected||mcfValorMonetario((string)$v)==$expected,'Valor válido '.$v);
checkFinal(mcfValorMonetario('-12,34',true,'9999999999999.99')===-12.34,'Saldo inicial negativo permitido');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$c=new mysqli('127.0.0.1','root','','mcf_final',3307);$c->set_charset('utf8mb4');
$hoje=(new DateTimeImmutable('today',new DateTimeZone('America/Sao_Paulo')));$data=fn($d)=>$hoje->modify(($d>=0?'+':'').$d.' days')->format('Y-m-d');
$senha=password_hash('Teste-Conta-2026!',PASSWORD_DEFAULT);$s=$c->prepare("INSERT INTO usuarios(id,nome,email,senha,status_assinatura,data_expiracao) VALUES(?,?,?,?,'ativo','2099-12-31')");
foreach([1,2,3] as $id){$nome='Revisão '.$id;$email=['','alfa@example.test','beta@example.test','gama@example.test'][$id];$s->bind_param('isss',$id,$nome,$email,$senha);$s->execute();}$s->close();
$c->query("INSERT INTO contas_financeiras(id,usuario_id,nome,tipo,saldo_inicial,data_saldo_inicial) VALUES(1,1,'Conta disponível','corrente',100,'".$data(-1)."'),(2,1,'Conta futura','corrente',500,'".$data(1)."'),(3,2,'Privada Beta','corrente',900,'".$data(-1)."')");
foreach([[-2,'entrada',999],[-1,'entrada',20],[0,'entrada',50],[0,'saida',10],[0,'transferencia_entrada',7],[0,'transferencia_saida',3],[1,'entrada',900],[1,'saida',40],[1,'transferencia_entrada',30],[1,'transferencia_saida',15]] as [$dia,$tipo,$valor])$c->query("INSERT INTO movimentacoes_financeiras(usuario_id,conta_id,tipo,data,descricao,valor) VALUES(1,1,'$tipo','".$data($dia)."','Movimento de teste',$valor)");
$contas=array_column(saldosListarContas($c,1),null,'id');
checkFinal((float)$contas[1]['saldo_atual']===164.0,'Saldo limita entradas, saídas e transferências até hoje');
checkFinal((int)$contas[1]['quantidade_movimentacoes']===10,'Contagem preserva movimentos futuros e anteriores');
checkFinal((float)$contas[2]['saldo_atual']===0.0,'Saldo inicial futuro não é disponível hoje');
checkFinal(count($contas)===2,'Saldo não inclui conta alheia');
$entrada=['acao'=>'entrada','operacao'=>bin2hex(random_bytes(16)),'conta_id'=>'1','data'=>$data(0),'origem'=>'inicial','destino'=>'livre','descricao'=>'Reserva de teste','valor'=>'165'];
try{rmOperar($c,1,$entrada);checkFinal(false,'Reserva sem saldo');}catch(DomainException $e){checkFinal(str_contains($e->getMessage(),'saldo'),'Reserva rejeita dinheiro futuro');}
$entrada['operacao']=bin2hex(random_bytes(16));$entrada['valor']='100';rmOperar($c,1,$entrada);checkFinal((rmResumo($c,1)['contas'][1]??0)===10000,'Reserva dentro do saldo disponível aceita');
$entrada['operacao']=bin2hex(random_bytes(16));$entrada['conta_id']='2';try{rmOperar($c,1,$entrada);checkFinal(false,'Saldo inicial futuro reservado');}catch(DomainException $e){checkFinal(true,'Reserva rejeita conta com saldo inicial futuro');}
echo "TOTAL $n verificações de valores, saldos e reservas.\n";
