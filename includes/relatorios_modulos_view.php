<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/relatorios_view.php';
require_once __DIR__.'/relatorios_modulos.php';
function relModuloValor(array $b,string $key): ?int {return $b['n']?$b[$key]:null;}
function relModuloSvg(array $grupos,string $tipo,string $modulo): string {
    $def=relModulo($modulo);$cards=$modulo==='cartao';
    $mapped=array_map(fn($b)=>['chave'=>$b['chave'],'receitas'=>$b[$cards?'compras':'efetivado'],'despesas'=>$b[$cards?'creditos':'pendente'],'nr'=>$b['n'],'nd'=>$b['n']],$grupos);
    $svg=$tipo==='horizontal'?relHorizontal($mapped):relSvg($mapped,$tipo);
    return strtr($svg,['Receitas e despesas por período'=>relH(implode(' e ',$def['series']).' por período'),'R - Receitas previstas'=>'A - '.relH($def['series'][0]),'D - Despesas previstas'=>'B - '.relH($def['series'][1]),'receitas:'=>relH($def['series'][0]).':','despesas:'=>relH($def['series'][1]).':','>R</text>'=>'>A</text>','>D</text>'=>'>B</text>']);
}
function relModuloConteudo(array $r,bool $pdf=false,bool $detalhado=false): string {
    $f=$r['filtros'];$def=relModulo($r['modulo']);$labels=$def['labels'];ob_start(); ?>
    <h1><?=relH($def['titulo'])?></h1>
    <p class="subtitle"><?=relH($f['inicio'])?> a <?=relH($f['fim'])?> · BRL · Gerado em <?=relH($r['gerado'])?></p>
    <p><strong>Perfis:</strong> <?=relH(implode(', ',array_map(fn($p)=>$p['nome']?:$p['email'],$r['pessoas'])))?></p>
    <p class="meta">Agrupamento: <?=relH($f['agrupamento'])?> · Situação: <?=relH($f['situacao'])?> · <?= $r['modulo']==='receitas'?'Classificação: '.relH($f['classificacao']):'Natureza: '.relH($f['natureza'])?> · Tipo: <?=relH($f['tipo'])?> · Comparação: <?=relH($f['comparacao'])?> · Apresentação: <?=relH($f['visualizacao'].' / '.$f['grafico'])?></p>
    <div class="notice"><?=relH($def['aviso'])?> Os indicadores recorrente/parcelado e atraso são recortes do total; não devem ser somados a ele.</div>
    <?php foreach($r['periodos'] as $pi=>$p): ?>
    <section><h2><?=relH($p['nome'])?> <small><?=relH($p['inicio'].' a '.$p['fim'])?></small></h2>
    <?php if($p['fim']>$r['hoje']):?><p class="notice">Este intervalo inclui datas futuras. Use o acumulado anual para comparar até a mesma data.</p><?php endif;?>
    <div class="indicators"><?php foreach($labels as $key=>$label):?><div class="indicator"><span><?=relH($label)?></span><strong><?=relMoeda(relModuloValor($p['total'],$key))?></strong></div><?php endforeach;?></div>
    <?php if($pi>0):?><h3>Diferenças: selecionado menos este período</h3><table><thead><tr><th>Indicador</th><th>Diferença em reais</th><th>Diferença percentual</th></tr></thead><tbody><?php foreach($labels as $key=>$label):$delta=relDiferenca(relModuloValor($r['periodos'][0]['total'],$key),relModuloValor($p['total'],$key));?><tr><td><?=relH($label)?></td><td><?=relMoeda($delta['valor'])?></td><td><?=$delta['percentual']===null?'Indisponível (base zero ou sem dados)':number_format($delta['percentual'],2,',','.').'%'?></td></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php if(count($r['pessoas'])>1):?><h3>Participação individual no consolidado</h3><table><thead><tr><th>Titular</th><th><?=relH($labels['total'])?></th><th><?=relH($labels['efetivado'])?></th><th><?=relH($labels['pendente'])?></th></tr></thead><tbody><?php foreach($r['pessoas'] as $person):$pt=$p['titulares'][$person['id']]??null;?><tr><td><?=relH($person['nome']?:$person['email'])?></td><?php foreach(['total','efetivado','pendente'] as $key):?><td><?=relMoeda($pt[$key]??null)?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php if($f['visualizacao']!=='tabela'):?>
    <?php foreach(array_chunk($p['grupos'],$f['grafico']==='horizontal'?10:24) as $chunk):$svg=relModuloSvg($chunk,$f['grafico'],$r['modulo']);?><div class="chart"><h3><?=relH(implode(' × ',$def['series']))?></h3><?php if($pdf):?><img style="width:100%" src="data:image/svg+xml;base64,<?=base64_encode($svg)?>"><?php else:?><?=$svg?><?php endif;?></div><?php endforeach;?>
    <p class="meta">Lacunas indicam ausência de registros. Zero com registros é preservado. Barras horizontais mostram magnitude, com o sinal no rótulo.</p><?php endif;?>
    <?php if($f['visualizacao']!=='grafico'):?>
    <div class="table-wrap"><table><thead><tr><th>Período</th><?php foreach($labels as $label):?><th><?=relH($label)?></th><?php endforeach;?></tr></thead><tbody>
    <?php foreach($p['grupos'] as $b):?><tr><td><?php if(!$pdf):?><a href="#detail-<?=$pi?>" data-group="<?=relH($b['chave'])?>"><?=relH($b['chave'])?></a><?php else:?><?=relH($b['chave'])?><?php endif;?></td><?php foreach($labels as $key=>$_):?><td><?=relMoeda(relModuloValor($b,$key))?></td><?php endforeach;?></tr><?php endforeach;?>
    <tr class="total"><td>Total</td><?php foreach($labels as $key=>$_):?><td><?=relMoeda(relModuloValor($p['total'],$key))?></td><?php endforeach;?></tr></tbody></table></div>
    <h3><?=$r['modulo']==='receitas'?'Classificação':'Natureza'?> · valor líquido</h3><table><thead><tr><th>Grupo</th><th>Valor</th></tr></thead><tbody><?php foreach($p['naturezas'] as $categoria=>$valor):?><tr><td><?=relH($categoria?:'Não informado')?></td><td><?=relMoeda($valor)?></td></tr><?php endforeach;?><?php if(!$p['naturezas']):?><tr><td colspan="2">Sem dados</td></tr><?php endif;?></tbody></table><?php endif;?>
    <?php if(!$pdf||$detalhado):?><<?=$pdf?'div':'details'?> id="detail-<?=$pi?>" class="details"><?php if($pdf):?><h3>Lançamentos · <?=count($p['detalhes'])?> registros</h3><?php else:?><summary>Ver lançamentos · <?=count($p['detalhes'])?> registros</summary><p>Selecione um período na tabela para filtrar. <button type="button" class="reset-detail">Mostrar todos</button></p><?php endif;?>
    <div class="table-wrap"><table><thead><tr><th>Data</th><th>Titular</th><th>Descrição / parcela</th><th>Tipo / grupo</th><th>Situação atual</th><th>Valor</th></tr></thead><tbody><?php foreach($p['detalhes'] as $d):?><tr data-row-group="<?=relH($d['grupo'])?>"><td><?=relH($d['data'])?></td><td><?=relH($d['titular'])?></td><td><?=relH($d['nome'])?><?=isset($d['parcela'])?' · '.(int)$d['parcela'].'/'.(int)$d['total_parcelas']:''?></td><td><?=relH($d['tipo'].' / '.$d['categoria'])?></td><td><?=$d['realizado']?($r['modulo']==='receitas'?'Recebido':'Pago'):($d['data']<$r['hoje']?($r['modulo']==='cartao'?'Aberto / data passada':'Em atraso'):'Pendente')?></td><td><?=relMoeda($d['centavos'])?></td></tr><?php endforeach;?></tbody></table></div></<?=$pdf?'div':'details'?>><?php endif;?></section><?php endforeach;
    return ob_get_clean();
}
