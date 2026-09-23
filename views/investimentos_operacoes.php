<?php
require_once __DIR__.'/../config.php';require_once __DIR__.'/../includes/investimentos_edicao.php';
$erro=null;
try {
    $mercado=mcfTexto($_GET,'mercado') ?: 'nacional';$tabela=mcfInvestimentoTabela($mercado);
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (mcfTexto($_POST,'mercado')!==$mercado) throw new DomainException('Mercado divergente. Reabra a página.');
        mcfInvestimentoCorrigir($conn,$_POST);
        $_SESSION['operacao_corrigida']='Operação atualizada no histórico do titular.';
        header('Location: investimentos_operacoes.php?mercado='.urlencode($mercado),true,303);exit;
    }
} catch (DomainException $e) { $erro=$e->getMessage();http_response_code(422); }
catch (Throwable $e) { $erro='Não foi possível atualizar a operação.';http_response_code(500); }
if (!isset($tabela)) mcfFalhar(422,'Mercado inválido.');
$uid=mcfDonoId();$pagina=filter_var($_GET['pagina'] ?? 1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]) ?: 1;$offset=($pagina-1)*50;
$s=$conn->prepare('SELECT id,ticker,tipo_ativo,quantidade,valor_unitario,data,tipo_operacao FROM '.$tabela.' WHERE usuario_id=? ORDER BY data DESC,id DESC LIMIT 51 OFFSET ?');
$s->bind_param('ii',$uid,$offset);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();$mais=count($rows)>50;$rows=array_slice($rows,0,50);
$aviso=$_SESSION['operacao_corrigida'] ?? null;unset($_SESSION['operacao_corrigida']);
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');$cssPagina='/MyCashFlow/assets/css/compartilhamento.css';
require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/menu.php';
?>
<main class="compartilhamento"><h2>Corrigir operações registradas</h2>
<p>Correções alteram o histórico financeiro desta conta. Uma correção ou exclusão que cause venda sem posição suficiente será rejeitada.</p>
<div class="links-modulos"><a href="investimentos.php">Voltar à carteira</a><a href="investimentos_operacoes.php?mercado=nacional">Nacionais</a><a href="investimentos_operacoes.php?mercado=internacional">Internacionais</a></div>
<?php if ($erro): ?><p class="aviso erro" role="alert"><?= $e($erro) ?></p><?php endif; ?><?php if ($aviso): ?><p class="aviso" role="status"><?= $e($aviso) ?></p><?php endif; ?>
<?php if (!$rows): ?><p>Nenhuma operação nesta página.</p><?php endif; ?>
<?php foreach ($rows as $r): ?><section><h3><?= $e($r['ticker'].' · '.$r['tipo_operacao'].' · '.date('d/m/Y',strtotime($r['data']))) ?></h3>
<p><?= $e(ltrim($r['quantidade'],'-')) ?> unidade(s) × <?= $e($mercado==='nacional' ? 'R$ ' : 'US$ ') ?><?= $e($r['valor_unitario']) ?></p>
<details><summary>Corrigir ou excluir esta operação</summary><form method="post">
<?= mcfCsrfField() ?><input type="hidden" name="mercado" value="<?= $e($mercado) ?>"><input type="hidden" name="operacao_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="revisao" value="<?= mcfInvestimentoRevisao($r) ?>">
<label>Ativo <input name="ticker" value="<?= $e($r['ticker']) ?>" required></label>
<label>Tipo <select name="tipo_ativo"><?php foreach ($mercado==='nacional' ? ['acao','fii','etf','bdr'] : ['stock','etf','reit','adr','cripto'] as $tipo): ?><option value="<?= $e($tipo) ?>"<?= $r['tipo_ativo']===$tipo ? ' selected' : '' ?>><?= $tipo==='cripto' ? 'Criptomoeda' : $e(strtoupper($tipo)) ?></option><?php endforeach; ?></select></label>
<label>Operação <select name="tipo_operacao"><option value="compra"<?= $r['tipo_operacao']==='compra' ? ' selected' : '' ?>>Compra</option><option value="venda"<?= $r['tipo_operacao']==='venda' ? ' selected' : '' ?>>Venda</option></select></label>
<label>Quantidade positiva <input name="quantidade" inputmode="decimal" value="<?= $e(ltrim($r['quantidade'],'-')) ?>" required></label>
<label>Preço unitário (ponto nos decimais) <input name="valor_unitario" inputmode="decimal" value="<?= $e($r['valor_unitario']) ?>" required></label>
<label>Data <input type="date" name="data" value="<?= $e($r['data']) ?>" required></label>
<div class="acoes"><button name="acao" value="corrigir">Salvar correção</button><button name="acao" value="excluir" class="secundario">Excluir esta operação</button></div>
</form></details></section><?php endforeach; ?>
<div class="links-modulos"><?php if ($pagina>1): ?><a href="?mercado=<?= $e($mercado) ?>&amp;pagina=<?= $pagina-1 ?>">Anterior</a><?php endif; ?><?php if ($mais): ?><a href="?mercado=<?= $e($mercado) ?>&amp;pagina=<?= $pagina+1 ?>">Próxima</a><?php endif; ?></div>
</main><?php require __DIR__.'/../includes/footer.php'; ?>
