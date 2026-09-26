<?php
if (!isset($modulo)) { http_response_code(404); exit; }
require_once __DIR__.'/../config.php';
require_once __DIR__.'/visao_conjunta.php';
require_once __DIR__.'/relatorios_modulos.php';
$def=relModulo($modulo);
require_once __DIR__.'/relatorios_modulos_view.php';
try {
    $query=$_GET;
    if($modulo==='cartao'&&($query['natureza']??null)==='unica')$query['natureza']='todos';
    $f=relFiltros($query);
    if($modulo==='cartao')$f['natureza']=relOpcao($_GET,'natureza',['todos','pessoal','conjunta','unica'],'todos');
    $disponiveis=mcfPessoasAutorizadas($conn,$def['permissao']);
    $pessoas=mcfPessoasAutorizadas($conn,$def['permissao'],$_GET['pessoas']??(isset($_GET['selecionar'])?[]:[(string)mcfDonoId()]));
    if($modulo==='cartao'&&$f['tipo']==='recorrente')throw new DomainException('Cartões não possuem tipo recorrente.');
    $r=relModuloCarregar($conn,$f,$pessoas,$modulo);$token=bin2hex(random_bytes(24));
    $_SESSION['relatorios']??=[];
    foreach($_SESSION['relatorios'] as $k=>$v)if($v['expira']<time())unset($_SESSION['relatorios'][$k]);
    while(count($_SESSION['relatorios'])>=5)array_shift($_SESSION['relatorios']);
    $_SESSION['relatorios'][$token]=['expira'=>time()+1800,'ator'=>mcfUsuarioId(),'relatorio'=>$r];
} catch(DomainException $e) {mcfFalhar(422,$e->getMessage());}
function relSelect(string $name,string $label,array $choices,array $f): void { ?>
<label><?=relH($label)?><select name="<?=relH($name)?>"><?php foreach($choices as $v=>$l):?><option value="<?=relH($v)?>" <?=$f[$name]===(string)$v?'selected':''?>><?=relH($l)?></option><?php endforeach;?></select></label>
<?php }
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=relH($def['titulo'])?> · MyCashFlow</title><link rel="stylesheet" href="/MyCashFlow/assets/css/relatorios/central.css?v=1"></head><body>
<?php include __DIR__.'/header.php';include __DIR__.'/menu.php';?>
<main class="report-layout"><a href="relatorios.php">← Central de Relatórios</a>
<div class="report-intro"><span>MYCASHFLOW / RELATÓRIOS</span><h1><?=relH($def['titulo'])?> em perspectiva</h1><p>Escolha o período, compare resultados e leve a mesma visão para o PDF.</p></div>
<form method="get" action="<?=relH($modulo)?>.php" data-mcf-own class="report-filters">
<?php relSelect('periodo','Período',['semana'=>'Semana','mes'=>'Mês','trimestre'=>'Trimestre','semestre'=>'Semestre','ano'=>'Ano','personalizado'=>'Personalizado','36meses'=>'Últimos 36 meses até a referência','ytd'=>'Acumulado anual até a referência'],$f);?>
<label>Data de referência<input type="date" name="referencia" value="<?=relH($f['referencia'])?>" required></label>
<label>Início personalizado<input type="date" name="inicio" value="<?=relH($f['inicio'])?>"></label><label>Fim personalizado<input type="date" name="fim" value="<?=relH($f['fim'])?>"></label>
<?php
relSelect('agrupamento','Agrupar por',['dia'=>'Dia','semana'=>'Semana (segunda-feira)','mes'=>'Mês','ano'=>'Ano'],$f);
relSelect('situacao','Situação atual',['todos'=>'Todos','realizados'=>$modulo==='receitas'?'Recebidos':'Pagos','pendentes'=>'Pendentes (todos)','vencidos'=>$modulo==='cartao'?'Abertos em datas passadas':'Em atraso'],$f);
relSelect('comparacao','Comparar com',['nenhuma'=>'Sem comparação','anterior'=>'Período anterior de igual duração','ano_anterior'=>'Mesmo período do ano anterior','tres_anos'=>'Mesmo intervalo em três anos'],$f);
relSelect('visualizacao','Apresentação',['ambos'=>'Gráfico e tabela','grafico'=>'Gráfico','tabela'=>'Tabela'],$f);
relSelect('grafico','Gráfico',['barras'=>'Barras verticais','horizontal'=>'Barras horizontais','empilhadas'=>'Barras empilhadas','linhas'=>'Linhas','area'=>'Área'],$f);
if($modulo!=='receitas')relSelect('natureza','Natureza',['todos'=>'Todas','pessoal'=>'Pessoal','conjunta'=>'Conjunta']+($modulo==='cartao'?['unica'=>'Cliente / reembolsável']:[]),$f);
if($modulo==='receitas')relSelect('classificacao','Receitas',['todos'=>'Todas','regular'=>'Regular','extra'=>'Extra'],$f);
relSelect('tipo','Tipo de lançamento',['todos'=>'Todos','unica'=>'Único','parcelada'=>'Parcelado']+($modulo==='cartao'?[]:['recorrente'=>'Recorrente']),$f);
?>
<fieldset><legend>Perfis incluídos</legend><?php foreach($disponiveis as $id=>$p):?><label class="check"><input type="checkbox" name="pessoas[]" value="<?=$id?>" <?=isset($pessoas[$id])?'checked':''?>><?=relH($p['nome']?:$p['email'])?></label><?php endforeach;?><small>Outros perfis aparecem quando autorizam este módulo.</small></fieldset>
<input type="hidden" name="selecionar" value="1"><button class="primary" type="submit">Atualizar relatório</button></form>
<form method="post" action="modulo-pdf.php" data-mcf-own class="export-form"><?=mcfCsrfField()?><input type="hidden" name="snapshot" value="<?=relH($token)?>"><label>Conteúdo do PDF<select name="detalhe"><option value="resumido">Resumido</option><option value="detalhado">Detalhado com lançamentos</option></select></label><button class="primary">Exportar PDF</button><span>Exporta os valores desta consulta. Disponível por 30 minutos.</span></form>
<article class="report-document"><?=relModuloConteudo($r)?></article></main>
<?php include __DIR__.'/footer.php';?>
<script src="/MyCashFlow/assets/js/relatorios.js?v=1"></script></body></html>
