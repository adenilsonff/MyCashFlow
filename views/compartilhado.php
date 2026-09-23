<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/compartilhamento_consulta.php';
if (!in_array($_SERVER['REQUEST_METHOD'],['GET','HEAD'],true)) mcfFalhar(405,'A consulta compartilhada não permite alterações.');
try {
    $id=mcfCompartilhamentoInt($_GET,'id'); $modulo=mcfTexto($_GET,'modulo');
    $secoes=mcfCompartilhamentoSecoes()[$modulo] ?? [];
    if (!$secoes) mcfFalhar(404,'Consulta não disponível.');
    $secao=mcfTexto($_GET,'secao') ?: array_key_first($secoes);
    $inicio=mcfCompartilhamentoData(mcfTexto($_GET,'inicio') ?: date('Y-m-01'));
    $fim=mcfCompartilhamentoData(mcfTexto($_GET,'fim') ?: date('Y-m-t'));
    if ($inicio>$fim) throw new DomainException('A data inicial deve ser anterior ou igual à final.');
    $pagina=isset($_GET['pagina']) ? mcfCompartilhamentoInt($_GET,'pagina') : 1;
    if ($pagina>1000000) throw new DomainException('Página inválida.');
    $consulta=mcfCompartilhamentoConsultar($conn,$id,$modulo,$secao,$inicio,$fim,$pagina);
} catch (DomainException $error) { mcfFalhar(422,$error->getMessage()); }
$e=static fn($v)=>htmlspecialchars((string)($v ?? ''),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
$url=static fn($s,$p=1)=>'compartilhado.php?'.http_build_query(['id'=>$id,'modulo'=>$modulo,'secao'=>$s,'inicio'=>$inicio,'fim'=>$fim,'pagina'=>$p]);
$pessoa=$consulta['pessoa'];
$cssPagina='/MyCashFlow/assets/css/compartilhamento.css';
require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/menu.php';
?>
<main class="compartilhamento">
<p><a href="compartilhamento.php">← Compartilhados comigo</a></p>
<h2><?= $e(mcfModulosCompartilhaveis()[$modulo]) ?></h2>
<?php if ($modulo==='investimentos'): ?><p><a href="visao_conjunta.php?modulo=investimentos&amp;pessoas[]=<?= (int)$pessoa['proprietario_id'] ?>">Ver valor de mercado, lucro/prejuízo e compor total conjunto</a></p><?php endif; ?>
<?php if (($pessoa['nivel'] ?? '')==='edicao'): ?><p><a href="<?= $e(mcfEntradasEdicao()[$modulo]) ?>?compartilhamento=<?= $id ?>">Gerenciar este módulo do titular</a></p><?php endif; ?>
<div class="aviso"><strong>Consultando dados de <?= $e($pessoa['nome'] ?: $pessoa['email']) ?></strong><br><?= $e($pessoa['email']) ?> · Somente leitura. Você continua conectado à sua própria conta.</div>
<div class="links-modulos"><?php foreach ($secoes as $chave=>$def): ?><a href="<?= $e($url($chave)) ?>"<?= $chave===$secao ? ' aria-current="page"' : '' ?>><?= $e($def[0]) ?></a><?php endforeach; ?></div>
<section><h3><?= $e($consulta['titulo']) ?></h3>
<?php if ($consulta['data']!==null): ?>
<form method="get" class="filtros"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="modulo" value="<?= $e($modulo) ?>"><input type="hidden" name="secao" value="<?= $e($secao) ?>">
<label>De <input type="date" name="inicio" value="<?= $e($inicio) ?>" required></label><label>Até <input type="date" name="fim" value="<?= $e($fim) ?>" required></label><button>Consultar período</button></form>
<p>Período: <?= $e(mcfCompartilhamentoFormatar($inicio,'data')) ?> a <?= $e(mcfCompartilhamentoFormatar($fim,'data')) ?>.</p>
<?php else: ?><p>Todos os registros atuais. Posições e saldos usam o histórico completo do titular.</p><?php endif; ?>
<?php if (str_starts_with($secao,'posicoes_')): ?><p class="ajuda">Posições calculadas pelas mesmas regras da carteira individual. Valores ao custo, sem cotação de mercado e sem somar contas ou moedas diferentes.</p><?php endif; ?>
<?php if ($modulo==='analise'): ?><p class="ajuda">Consulta das linhas, notas e ativos acompanhados. O gráfico interativo e as preferências do navegador permanecem na área individual do titular.</p><?php endif; ?>
<?php if ($consulta['totalValor']!==null): ?><p><strong><?= $e($consulta['colunas'][$consulta['somar']][0]) ?> total do período: <?= $e(mcfCompartilhamentoFormatar($consulta['totalValor'],'brl')) ?></strong></p><?php endif; ?>
<p><?= (int)$consulta['quantidade'] ?> registro(s). Página <?= $pagina ?> de <?= max(1,(int)ceil($consulta['quantidade']/50)) ?>; até 50 registros por página.</p>
<?php if (!$consulta['rows']): ?><p>Nenhum registro nesta página.</p><?php else: ?>
<div class="tabela-scroll"><table><thead><tr><?php foreach ($consulta['colunas'] as [$rotulo,$tipo]): ?><th scope="col"><?= $e($rotulo) ?></th><?php endforeach; ?></tr></thead>
<tbody><?php foreach ($consulta['rows'] as $row): ?><tr><?php foreach ($consulta['colunas'] as $campo=>[$rotulo,$tipo]): ?><td><?= $e(mcfCompartilhamentoFormatar($row[$campo] ?? null,$tipo)) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<div class="links-modulos"><?php if ($pagina>1): ?><a href="<?= $e($url($secao,$pagina-1)) ?>">Página anterior</a><?php endif; ?><?php if ($pagina*50<$consulta['quantidade']): ?><a href="<?= $e($url($secao,$pagina+1)) ?>">Próxima página</a><?php endif; ?></div>
</section>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
