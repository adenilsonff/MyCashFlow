<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/relatorios_core.php';
function relH(mixed $s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function relRotulos(): array {return ['receitas'=>'Receitas previstas','despesas'=>'Despesas previstas','recebido'=>'Recebido','pago'=>'Pago','saldo'=>'Saldo previsto','realizado'=>'Saldo realizado'];}
function relValor(array $b,string $k): ?int { $count=match($k){'receitas','recebido'=>'nr','despesas','pago'=>'nd',default=>'n'};return $b[$count] ? $b[$k] : null;}
function relSvg(array $grupos,string $tipo): string {
    $w=1000;$h=330;$left=110;$top=30;$bottom=265;$plot=840;$n=count($grupos);
    $max=1;$min=0;foreach($grupos as $g){$max=max($max,$g['receitas'],$g['despesas'],$tipo==='empilhadas'?max(0,$g['receitas'])+max(0,$g['despesas']):0);$min=min($min,$g['receitas'],$g['despesas'],$tipo==='empilhadas'?min(0,$g['receitas'])+min(0,$g['despesas']):0);}
    $y=fn($v)=>$bottom-($v-$min)/($max-$min)*($bottom-$top);
    $s='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 330" width="1000" height="330" role="img" aria-label="Receitas e despesas por período"><rect width="1000" height="330" fill="white"/>';
    for($i=0;$i<=4;$i++){ $v=$min+($max-$min)*$i/4;$yy=$y($v);$s.='<line x1="110" y1="'.$yy.'" x2="950" y2="'.$yy.'" stroke="#dde3ea"/><text x="100" y="'.($yy+4).'" text-anchor="end" font-size="11">'.relH(relMoeda((int)$v)).'</text>'; }
    foreach(['receitas'=>'#16729b','despesas'=>'#ae4e18'] as $key=>$color) {
        $points=[];
        $segment=static function(array $points)use($tipo,$color,$key,$y):string {if(count($points)<2)return ''; $shape='';if($tipo==='area'){$first=explode(',',$points[0])[0];$last=explode(',',$points[count($points)-1])[0];$shape='<polygon points="'.$first.','.$y(0).' '.implode(' ',$points).' '.$last.','.$y(0).'" fill="'.$color.'" fill-opacity="0.15"/>'; }return $shape.'<polyline points="'.implode(' ',$points).'" fill="none" stroke="'.$color.'" stroke-width="3"'.($key==='despesas'?' stroke-dasharray="7 4"':'').'/>';};
        foreach($grupos as $i=>$g){$x=$left+($i+.5)*$plot/max(1,$n);$v=$g[$key];$yy=$y($v);$title=relH($g['chave'].' • '.$key.': '.($g[$key==='receitas'?'nr':'nd']?relMoeda($v):'Sem dados'));
            if(!$g[$key==='receitas'?'nr':'nd']) {$s.=$segment($points);$points=[];continue;}
            if(in_array($tipo,['linhas','area'],true)) {
                $points[]="$x,$yy";
                $s.='<circle cx="'.$x.'" cy="'.$yy.'" r="3" fill="'.$color.'"><title>'.$title.'</title></circle>';
            }else{
                $bw=max(1,min(25,$plot/$n*.33));$xx=$x+($key==='receitas'?-$bw:0);$base=0;
                if($tipo==='empilhadas'){$xx=$x-$bw/2;if($key==='despesas'&&(($v>=0&&$g['receitas']>=0)||($v<0&&$g['receitas']<0)))$base=$g['receitas'];}
                $ya=$y($base+$v);$yb=$y($base);
                $labelX=$tipo==='empilhadas'?($key==='receitas'?$xx-6:$xx+$bw+6):$xx+$bw/2;
                $s.='<rect x="'.$xx.'" y="'.min($ya,$yb).'" width="'.$bw.'" height="'.max(1,abs($yb-$ya)).'" fill="'.$color.'"><title>'.$title.'</title></rect><text x="'.$labelX.'" y="'.(min($ya,$yb)-5).'" text-anchor="middle" font-size="9">'.($key==='receitas'?'R':'D').'</text>';
            }
        }
        $s.=$segment($points);
    }
    foreach($grupos as $i=>$g)if($i%max(1,(int)ceil($n/10))===0){$x=$left+($i+.5)*$plot/max(1,$n);$s.='<text x="'.$x.'" y="285" text-anchor="middle" font-size="11">'.relH($g['chave']).'</text>';}
    return $s.'<rect x="300" y="310" width="12" height="12" fill="#16729b"/><text x="318" y="321" font-size="12">R - Receitas previstas</text><rect x="550" y="310" width="12" height="12" fill="#ae4e18"/><text x="568" y="321" font-size="12">D - Despesas previstas</text></svg>';
}
function relHorizontal(array $grupos): string {
    $max=1;foreach($grupos as $g)$max=max($max,abs($g['receitas']),abs($g['despesas']));
    $h=70+count($grupos)*44;$s='<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="'.$h.'" viewBox="0 0 1000 '.$h.'" role="img" aria-label="Barras horizontais"><rect width="1000" height="'.$h.'" fill="white"/>';
    foreach($grupos as $i=>$g){$y=30+$i*44;$s.='<text x="10" y="'.($y+14).'" font-size="12">'.relH($g['chave']).'</text>';
        foreach(['receitas'=>'#16729b','despesas'=>'#ae4e18'] as $k=>$cor){$v=relMoeda($g[$k==='receitas'?'nr':'nd']?$g[$k]:null);$width=abs($g[$k])/$max*580;$s.='<rect x="140" y="'.$y.'" width="'.$width.'" height="13" fill="'.$cor.'"><title>'.relH($k.': '.$v).'</title></rect><text x="'.(150+$width).'" y="'.($y+11).'" font-size="11">'.relH($k.': '.$v).'</text>';$y+=17;}}
    return $s.'</svg>';
}
function relAnosSvg(array $periodos): string {
    $series=[];$min=0;$max=1;
    foreach($periodos as $p){$values=array_fill(1,12,null);foreach($p['detalhes'] as $d){$m=(int)substr($d['data'],5,2);$values[$m]??=0;$values[$m]+=$d['modulo']==='receitas'?$d['centavos']:-$d['centavos'];}foreach($values as $v)if($v!==null){$min=min($min,$v);$max=max($max,$v);}$series[]=['ano'=>substr($p['inicio'],0,4),'values'=>$values];}
    $y=fn($v)=>255-($v-$min)/($max-$min)*215;
    $s='<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="330" viewBox="0 0 1000 330" role="img" aria-label="Saldo previsto mensal, uma linha por ano"><rect width="1000" height="330" fill="white"/>';
    for($i=0;$i<=4;$i++){$v=$min+($max-$min)*$i/4;$yy=$y($v);$s.='<line x1="110" x2="950" y1="'.$yy.'" y2="'.$yy.'" stroke="#dde3ea"/><text x="100" y="'.($yy+4).'" text-anchor="end" font-size="11">'.relH(relMoeda((int)$v)).'</text>';}
    foreach($series as $i=>$serie){$color=['#16729b','#ae4e18','#6652a0'][$i];$dash=['','7 4','2 4'][$i];$prev=null;
        foreach($serie['values'] as $m=>$v){if($v===null){$prev=null;continue;}$x=110+($m-.5)*70;$yy=$y($v);if($prev)$s.='<line x1="'.$prev[0].'" y1="'.$prev[1].'" x2="'.$x.'" y2="'.$yy.'" stroke="'.$color.'" stroke-width="3" stroke-dasharray="'.$dash.'"/>';$s.='<circle cx="'.$x.'" cy="'.$yy.'" r="4" fill="'.$color.'"><title>'.relH($serie['ano'].'-'.$m.': '.relMoeda($v)).'</title></circle>';$prev=[$x,$yy];}
        $x=260+$i*220;$s.='<line x1="'.$x.'" x2="'.($x+40).'" y1="310" y2="310" stroke="'.$color.'" stroke-width="3" stroke-dasharray="'.$dash.'"/><text x="'.($x+50).'" y="314" font-size="12">'.$serie['ano'].'</text>';
    }
    foreach(['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'] as $i=>$mes)$s.='<text x="'.(110+($i+.5)*70).'" y="280" text-anchor="middle" font-size="12">'.$mes.'</text>';
    return $s.'</svg>';
}
function relConteudo(array $r,bool $pdf=false,bool $detalhado=false): string {
    $f=$r['filtros'];$labels=relRotulos();ob_start(); ?>
    <h1>Visão Financeira</h1>
    <p class="subtitle"><?=relH($f['inicio'])?> a <?=relH($f['fim'])?> · BRL · Gerado em <?=relH($r['gerado'])?></p>
    <p><strong>Perfis:</strong> <?=relH(implode(', ',array_map(fn($p)=>$p['nome']?:$p['email'],$r['pessoas'])))?></p>
    <p class="meta">Agrupamento: <?=relH($f['agrupamento'])?> · Situação: <?=relH($f['situacao'])?> · Natureza das despesas: <?=relH($f['natureza'])?> · Receitas: <?=relH($f['classificacao'])?> · Tipo: <?=relH($f['tipo'])?> · Comparação: <?=relH($f['comparacao'])?> · Apresentação: <?=relH($f['visualizacao'])?> / <?=relH($f['grafico'])?></p>
    <div class="notice">Base de datas: vencimento das despesas e data cadastrada das receitas. “Realizado” considera a situação atual, sem data efetiva registrada. Não representa o caixa histórico. Cartões, transferências e investimentos não são somados nesta visão. Pessoal/conjunta é natureza da despesa, não categoria de finalidade. Não há vínculo para identificar duplicatas lançadas em perfis diferentes; o consolidado soma os registros de cada titular.</div>
    <?php if(count($r['periodos'])===3 && substr($f['inicio'],0,4)===substr($f['fim'],0,4) && $f['visualizacao']!=='tabela' && in_array($f['grafico'],['linhas','area'],true)): $svg=relAnosSvg($r['periodos']);?>
    <div class="chart"><h2>Saldo previsto por mês · comparação entre anos</h2><p class="meta">Mesma janela de datas em cada ano; meses fora do intervalo e meses sem registros ficam em branco. O último mês pode ser parcial.</p><?php if($pdf):?><img style="width:100%" src="data:image/svg+xml;base64,<?=base64_encode($svg)?>"><?php else:?><?=$svg?><?php endif;?></div>
    <?php endif;?>
    <?php foreach($r['periodos'] as $pi=>$p): ?>
    <section><h2><?=relH($p['nome'])?> <small><?=relH($p['inicio'])?> a <?=relH($p['fim'])?></small></h2>
    <?php if($p['fim']>$r['hoje']):?><p class="notice">Este intervalo inclui datas futuras e valores previstos. Para comparar acumulados até a mesma data, selecione “Acumulado anual”.</p><?php endif;?>
    <div class="indicators"><?php foreach($labels as $k=>$label):?><div class="indicator"><span><?=relH($label)?></span><strong><?=relMoeda(relValor($p['total'],$k))?></strong></div><?php endforeach;?></div>
    <?php if($pi>0):?><h3>Diferenças: selecionado menos este período</h3><table><thead><tr><th>Indicador</th><th>Diferença em reais</th><th>Diferença percentual</th></tr></thead><tbody><?php foreach($labels as $k=>$label):$delta=relDiferenca(relValor($r['periodos'][0]['total'],$k),relValor($p['total'],$k));?><tr><td><?=relH($label)?></td><td><?=relMoeda($delta['valor'])?></td><td><?=$delta['percentual']===null?'Indisponível (base zero ou sem dados)':number_format($delta['percentual'],2,',','.').'%'?></td></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php if(count($r['pessoas'])>1):$participacao=[];foreach($r['pessoas'] as $pessoa)$participacao[$pessoa['id']]=['nome'=>$pessoa['nome']?:$pessoa['email'],'receitas'=>0,'despesas'=>0,'n'=>0];foreach($p['detalhes'] as $d){$participacao[$d['usuario_id']][$d['modulo']]+=$d['centavos'];$participacao[$d['usuario_id']]['n']++;}?>
    <h3>Participação individual no consolidado</h3><table><thead><tr><th>Titular</th><th>Receitas previstas</th><th>Despesas previstas</th><th>Saldo previsto</th></tr></thead><tbody><?php foreach($participacao as $pt):?><tr><td><?=relH($pt['nome'])?></td><td><?=relMoeda($pt['n']?$pt['receitas']:null)?></td><td><?=relMoeda($pt['n']?$pt['despesas']:null)?></td><td><?=relMoeda($pt['n']?$pt['receitas']-$pt['despesas']:null)?></td></tr><?php endforeach;?></tbody></table><?php endif;?>
    <?php if($f['visualizacao']!=='tabela'):?>
    <?php foreach(array_chunk($p['grupos'],$f['grafico']==='horizontal'?10:24) as $chunk):$svg=$f['grafico']==='horizontal'?relHorizontal($chunk):relSvg($chunk,$f['grafico']);?>
    <div class="chart"><h3>Receitas previstas × despesas previstas</h3><?php if($pdf):?><img style="width:100%" src="data:image/svg+xml;base64,<?=base64_encode($svg)?>"><?php else:?><?=$svg?><?php endif;?></div>
    <?php endforeach;?><p class="meta">Lacunas indicam ausência de registros. Valores zero com registros são mantidos. Consulte os valores exatos nos totais e na tabela. Barras horizontais mostram magnitude; o rótulo preserva o sinal.</p>
    <?php endif;?>
    <?php if($f['visualizacao']!=='grafico'):?>
    <div class="table-wrap"><table><thead><tr><th>Período</th><?php foreach($labels as $label):?><th><?=relH($label)?></th><?php endforeach;?></tr></thead><tbody>
    <?php foreach($p['grupos'] as $b):?><tr><td><?php if(!$pdf):?><a href="#detail-<?=$pi?>" data-group="<?=relH($b['chave'])?>"><?=relH($b['chave'])?></a><?php else:?><?=relH($b['chave'])?><?php endif;?></td><?php foreach($labels as $k=>$_):?><td><?=relMoeda(relValor($b,$k))?></td><?php endforeach;?></tr><?php endforeach;?>
    <tr class="total"><td>Total</td><?php foreach($labels as $k=>$_):?><td><?=relMoeda(relValor($p['total'],$k))?></td><?php endforeach;?></tr></tbody></table></div>
    <?php endif;?>
    <?php if(!$pdf||$detalhado):?>
    <<?=$pdf?'div':'details'?> id="detail-<?=$pi?>" class="details"><?php if($pdf):?><h3>Lançamentos · <?=count($p['detalhes'])?> registros</h3><?php else:?><summary>Ver lançamentos · <?=count($p['detalhes'])?> registros</summary><?php endif;?>
    <?php if(!$pdf):?><p>Selecione um período na tabela para filtrar os lançamentos. <button type="button" class="reset-detail">Mostrar todos</button></p><?php endif;?>
    <table><thead><tr><th>Data</th><th>Titular</th><th>Descrição</th><th>Módulo / natureza</th><th>Situação atual</th><th>Valor</th></tr></thead><tbody>
    <?php foreach($p['detalhes'] as $d):?><tr data-row-group="<?=relH($d['grupo'])?>"><td><?=relH($d['data'])?></td><td><?=relH($d['titular'])?></td><td><?=relH($d['nome'])?></td><td><?=relH($d['modulo'].' / '.$d['categoria'])?></td><td><?=$d['realizado']?'Pago/recebido':($d['data']<$r['hoje']?'Vencido':'Pendente')?></td><td><?=relMoeda($d['centavos'])?></td></tr><?php endforeach;?>
    </tbody></table></<?=$pdf?'div':'details'?>><?php endif;?></section><?php endforeach;
    return ob_get_clean();
}
