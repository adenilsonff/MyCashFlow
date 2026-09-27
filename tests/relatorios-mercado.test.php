<?php
require __DIR__.'/../includes/relatorios_modulos_view.php';
$n=0;function check($v,$label){global $n;if(!$v)throw new RuntimeException($label);$n++;}
foreach(['0.004'=>0,'0.005'=>1,'-0.005'=>-1,'12.3456789'=>1235] as $v=>$expected)check(relDecimalCentavos($v)===$expected,'arredondamento');
$base=['id'=>1,'data'=>'2026-03-01','usuario_id'=>1,'titular'=>'Alfa','ticker'=>'TEST3','tipo_ativo'=>'acao'];
$buy=relMercadoNormalizar($base+['tipo_operacao'=>'compra','quantidade'=>'10','valor_unitario'=>'20.00'],'investimentos');
$sell=relMercadoNormalizar($base+['tipo_operacao'=>'compra','quantidade'=>'-3','valor_unitario'=>'25.00'],'investimentos');
$crypto=relMercadoNormalizar($base+['tipo_operacao'=>'compra','quantidade'=>'0.00000001','valor_unitario'=>'123456.78912345'],'investimentos');
check($buy['centavos']===20000,'compra');check($sell['centavos']===-7500,'venda legada negativa');check($crypto['centavos']===0,'fração cripto');
$f=relMercadoFiltros(['ano'=>2026],'investimentos');$p=relPeriodos($f)[0];$a=relMercadoAgregar([$buy,$sell],$p,$f,'investimentos');
check($a['total']['total']===12500,'movimentação');check($a['total']['efetivado']===20000&&$a['total']['pendente']===7500,'compras vendas');check(relModuloValor($a['grupos'][1],'total')===null,'lacuna');
check(count(relMercadoAgregar([$buy,$sell],$p,array_replace($f,['operacao'=>'venda']),'investimentos')['detalhes'])===1,'filtro venda');
$div=relMercadoNormalizar($base+['tipo'=>'DIV','quantidade_elegivel'=>'7','valor'=>'0.12345678','datacom'=>'2026-03-01','datapag'=>null],'proventos');check($div['centavos']===86&&$div['metrics']['pendente']===86,'provento estimado');
$f=relMercadoFiltros(['ano'=>2026],'daytrade');$gain=relMercadoNormalizar($base+['lucro_final'=>'50.00','lucro_bruto'=>'80.00','taxas'=>'10.00','darf'=>'20.00','total_compra'=>'100','total_venda'=>'180','corretora_id'=>1,'corretora_nome'=>'QA'],'daytrade');$loss=array_replace($gain,['centavos'=>-2000,'metrics'=>array_replace($gain['metrics'],['total'=>-2000,'efetivado'=>0,'pendente'=>-2000])]);
$a=relMercadoAgregar([$gain,$loss],$p,$f,'daytrade');check($a['total']['total']===3000,'lucro prejuízo');check($a['total']['efetivado']+$a['total']['pendente']===$a['total']['total'],'reconciliação');check($gain['tipo']==='concluidas','situação');
foreach([['moeda'=>'EUR'],['ticker'=>[]],['corretora_id'=>'-1'],['data_base'=>'hack']] as $q){try{relMercadoFiltros($q,'investimentos');check(false,'deveria rejeitar');}catch(DomainException $e){check(true,'inválido');}}
$r=['modulo'=>'investimentos','moeda'=>'USD','filtros'=>relMercadoFiltros(['ano'=>2026,'moeda'=>'USD'],'investimentos'),'pessoas'=>[['id'=>1,'nome'=>'Alfa','email'=>'qa@example.test']],'gerado'=>'26/09/2026','hoje'=>'2026-09-26','periodos'=>[relMercadoAgregar([$buy],$p,relMercadoFiltros(['ano'=>2026],'investimentos'),'investimentos')]];
$html=relModuloConteudo($r);check(str_contains($html,'US$ 200,00')&&!str_contains($html,'R$ 200,00'),'USD em todos os valores');
echo "PASS: $n verificações de mercado\n";
