<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/visao_conjunta.php';
require_once __DIR__.'/../includes/comparativo.php';
if (!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)) mcfFalhar(405,'A visão conjunta é somente de consulta.');
try {
    $modulo=mcfTexto($_GET,'modulo') ?: 'investimentos';
    $disponiveis=mcfPessoasAutorizadas($conn,$modulo);
    $pessoas=mcfPessoasAutorizadas($conn,$modulo,$_GET['pessoas'] ?? (isset($_GET['selecionar']) ? [] : null));
    $secoes=mcfCompartilhamentoSecoes()[$modulo];
    $secao=mcfTexto($_GET,'secao') ?: array_key_first($secoes);
    if (!isset($secoes[$secao])) mcfFalhar(404,'Seção inválida.');
    $inicio=mcfCompartilhamentoData(mcfTexto($_GET,'inicio') ?: date('Y-m-01'));
    $fim=mcfCompartilhamentoData(mcfTexto($_GET,'fim') ?: date('Y-m-t'));
    if ($inicio>$fim) throw new DomainException('Período inválido.');
    $pagina=isset($_GET['pagina']) ? mcfCompartilhamentoInt($_GET,'pagina') : 1;
    if ($pagina>1000000) throw new DomainException('Página inválida.');
    $consultas=[];$soma='0';$somar=false;
    if ($modulo==='investimentos') $carteira=mcfAvaliarCarteiras(mcfPosicoesPessoas($conn,$pessoas));
    else foreach ($pessoas as $p) {
        $consulta=mcfCompartilhamentoConsultar($conn,(int)$p['convite'],$modulo,$secao,$inicio,$fim,$pagina);
        $consultas[]=$consulta;
        if ($consulta['totalValor']!==null) { $soma=bcadd($soma,(string)$consulta['totalValor'],2);$somar=true; }
        if ($modulo==='saldos' && $secao==='contas') {
            require_once __DIR__.'/../includes/saldos_consulta.php';
            foreach (saldosListarContas($conn,(int)$p['id']) as $conta) if ((int)$conta['ativa']===1) $soma=bcadd($soma,(string)$conta['saldo_atual'],2);
            $somar=true;
        }
    }
} catch (DomainException $error) { mcfFalhar(422,$error->getMessage()); }
$e=static fn($v)=>htmlspecialchars((string)($v ?? ''),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
$money=static fn($v,$moeda)=>$v===null ? 'Indisponível' : ($moeda==='BRL' ? 'R$ ' : 'US$ ').number_format((float)$v,2,',','.');
$cssPagina='/MyCashFlow/assets/css/compartilhamento.css';
require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/menu.php';
?>
<main class="compartilhamento">
<h2>Visão conjunta</h2>
<p>Consulte uma pessoa ou componha um total. Cada lançamento e operação permanece na conta do titular.</p>
<p><a href="compartilhamento.php">Gerenciar permissões</a></p>
<div class="links-modulos"><?php foreach (mcfModulosCompartilhaveis() as $m=>$rotulo): ?><a href="visao_conjunta.php?modulo=<?= $e($m) ?>"<?= $m===$modulo ? ' aria-current="page"' : '' ?>><?= $e($rotulo) ?></a><?php endforeach; ?></div>
<section><h3><?= $e(mcfModulosCompartilhaveis()[$modulo]) ?></h3>
<form method="get"><input type="hidden" name="modulo" value="<?= $e($modulo) ?>"><input type="hidden" name="selecionar" value="1">
<fieldset><legend>Pessoas incluídas neste total</legend>
<?php foreach ($disponiveis as $id=>$p): ?><label class="opcao"><input type="checkbox" name="pessoas[]" value="<?= (int)$id ?>"<?= isset($pessoas[$id]) ? ' checked' : '' ?>> <?= $e($p['nome'] ?: $p['email']) ?> (<?= $e($p['email']) ?>)<?= $id===mcfUsuarioId() ? ' — minha conta' : '' ?></label><?php endforeach; ?>
</fieldset>
<?php if ($modulo!=='investimentos'): ?><label>Consulta <select name="secao"><?php foreach ($secoes as $key=>$def): ?><option value="<?= $e($key) ?>"<?= $key===$secao ? ' selected' : '' ?>><?= $e($def[0]) ?></option><?php endforeach; ?></select></label>
<div class="filtros"><label>De <input type="date" name="inicio" value="<?= $e($inicio) ?>" required></label><label>Até <input type="date" name="fim" value="<?= $e($fim) ?>" required></label></div><?php endif; ?>
<button>Atualizar visão conjunta</button></form>
<p class="ajuda">A lista mostra apenas sua conta e quem autorizou este módulo. Para incluir alguém, essa pessoa precisa enviar um convite e você precisa aceitá-lo.</p>
</section>
<?php if ($modulo==='investimentos'): ?>
<p class="aviso">Resultado não realizado das posições abertas: valor de mercado menos custo. Não inclui proventos, impostos nem resultado de posições encerradas. Valores em reais e dólares permanecem separados.</p>
<?php foreach ($carteira['totais'] as $moeda=>$t): ?><section><h3>Total <?= $moeda==='BRL' ? 'nacional (BRL)' : 'internacional (USD)' ?></h3>
<p>Custo: <strong><?= $e($money($t['custo'],$moeda)) ?></strong> · Valor de mercado: <strong><?= $e($money($t['mercado'],$moeda)) ?></strong> · Lucro/prejuízo não realizado: <strong><?= $e($money($t['resultado'],$moeda)) ?></strong></p>
<?php if ($t['faltam']): ?><p role="status"><?= (int)$t['faltam'] ?> ativo(s) sem cotação válida. O valor de mercado e o resultado total não foram calculados.</p><?php endif; ?>
<?php if ($t['stale']): ?><p>Há cotações da última consulta disponível, com atualização pendente.</p><?php endif; ?>
</section><?php endforeach; ?>
<?php require __DIR__.'/../includes/comparativo_view.php'; ?>
<?php foreach (['agrupadas'=>'Posições somadas por ativo','individuais'=>'Posições por titular'] as $key=>$titulo): ?><section><h3><?= $e($titulo) ?></h3>
<?php if (!$carteira[$key]): ?><p>Nenhuma posição aberta nas contas selecionadas.</p><?php else: ?>
<div class="tabela-scroll"><table><thead><tr><?php if ($key==='individuais'): ?><th>Titular</th><?php endif; ?><th>Ativo</th><th>Quantidade</th><th>Preço médio</th><th>Custo</th><th>Cotação</th><th>Mercado</th><th>Lucro/prejuízo</th><th>Variação</th><th>Fonte / horário</th></tr></thead><tbody>
<?php foreach ($carteira[$key] as $r): ?><tr><?php if ($key==='individuais'): ?><td><?= $e($r['titular']['nome'] ?: $r['titular']['email']) ?><br><?= $e($r['titular']['email']) ?></td><?php endif; ?>
<td><?= $e($r['ticker']) ?><br><?= $e($r['tipo'].' · '.$r['moeda']) ?></td><td><?= $e(mcfCompartilhamentoFormatar($r['quantidade'],'numero')) ?></td>
<?php foreach (['medio','custo','cotacao','mercado','resultado'] as $campo): ?><td><?= $e($money($r[$campo],$r['moeda'])) ?></td><?php endforeach; ?>
<td><?= $r['percentual']===null ? 'Indisponível' : $e(number_format((float)$r['percentual'],2,',','.').'%') ?></td><td><?= $e($r['fonte']) ?><br><?= $r['hora'] ? $e(date('d/m/Y H:i',(int)$r['hora'])) : 'Sem horário disponível' ?><?= $r['stale'] ? ' · atualização pendente' : '' ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section><?php endforeach; ?>
<?php else: ?>
<?php if ($somar): ?><section><h3>Total das pessoas selecionadas</h3><p><?= $e($money($soma,'BRL')) ?><?= $modulo==='saldos' ? ' — somente contas ativas, histórico completo' : ' — período selecionado, incluindo todas as páginas' ?></p></section><?php endif; ?>
<?php foreach ($consultas as $c): ?><section><h3><?= $e($c['pessoa']['nome'] ?: $c['pessoa']['email']) ?> · <?= $e($c['titulo']) ?></h3><p><?= $e($c['pessoa']['email']) ?> · <?= (int)$c['quantidade'] ?> registro(s) · página <?= $pagina ?>.</p>
<?php if (!$c['data']): ?><p>Registros atuais, sem filtro de período.</p><?php endif; ?>
<?php if (!$c['rows']): ?><p>Nenhum registro nesta página.</p><?php else: ?><div class="tabela-scroll"><table><thead><tr><?php foreach ($c['colunas'] as [$rotulo,$tipo]): ?><th><?= $e($rotulo) ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($c['rows'] as $r): ?><tr><?php foreach ($c['colunas'] as $campo=>[$rotulo,$tipo]): ?><td><?= $e(mcfCompartilhamentoFormatar($r[$campo] ?? null,$tipo)) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section><?php endforeach; ?>
<div class="links-modulos"><?php foreach ([$pagina-1=>'Anterior',$pagina+1=>'Próxima'] as $pg=>$rotulo): ?><?php if ($pg>=1 && ($pg<$pagina || max(array_column($consultas,'quantidade') ?: [0])>$pagina*50)): ?><a href="visao_conjunta.php?<?= $e(http_build_query(['modulo'=>$modulo,'secao'=>$secao,'inicio'=>$inicio,'fim'=>$fim,'pessoas'=>array_keys($pessoas),'pagina'=>$pg])) ?>"><?= $e($rotulo) ?></a><?php endif; ?><?php endforeach; ?></div>
<?php endif; ?>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
