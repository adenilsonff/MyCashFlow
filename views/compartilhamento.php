<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/compartilhamento.php';
require_once __DIR__.'/../includes/compartilhamento_melhorias.php';
$erro=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try { $_SESSION['compartilhamento_aviso']=mcfCompartilhamentoAlterar($conn,$_POST); header('Location: /MyCashFlow/views/compartilhamento.php',true,303); exit; }
    catch (DomainException $e) { $erro=$e->getMessage(); http_response_code(422); }
    catch (Throwable $e) { $erro='Não foi possível atualizar o compartilhamento. Tente novamente.'; http_response_code(500); }
}
$ator=mcfUsuarioId(); $lista=mcfCompartilhamentoListar($conn); $modulos=mcfModulosCompartilhaveis();
$aviso=$_SESSION['compartilhamento_aviso'] ?? null; unset($_SESSION['compartilhamento_aviso']);
$e=static fn($v)=>htmlspecialchars((string)($v ?? ''),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
$estados=['pendente'=>'Aguardando aceitação','ativo'=>'Ativo','revogado'=>'Revogado','recusado'=>'Recusado'];
$cssPagina='/MyCashFlow/assets/css/compartilhamento.css';
require __DIR__.'/../includes/header.php'; require __DIR__.'/../includes/menu.php';
?>
<main class="compartilhamento">
<h2>Compartilhamento</h2><p><a href="visao_conjunta.php">Visão conjunta: meus dados e módulos autorizados</a></p>
<p>Cada pessoa entra com seu próprio login. Os acessos autorizados podem permitir consulta ou edição e podem ser revogados a qualquer momento.</p>
<?php if ($erro): ?><p role="alert" class="aviso erro"><?= $e($erro) ?></p><?php endif; ?>
<?php if ($aviso): ?><p role="status" class="aviso"><?= $e($aviso) ?></p><?php endif; ?>
<section><h3>Compartilhar meus dados</h3>
<p>Informe o e-mail da outra conta no MyCashFlow e escolha os módulos que ela poderá consultar, incluindo seu histórico. Escolha consulta ou edição para cada módulo marcado. Para edição, marque também cadastrar, editar e/ou excluir. Nenhum módulo é compartilhado automaticamente.</p>
<form method="post">
<?= mcfCsrfField() ?><input type="hidden" name="acao" value="convidar">
<label for="destinatario">E-mail de quem poderá consultar</label>
<input id="destinatario" type="email" name="email" maxlength="100" autocomplete="off" required>
<input type="hidden" name="granular" value="1"><fieldset><legend>Módulos autorizados</legend>
<?php foreach ($modulos as $chave=>$rotulo): ?><label class="opcao"><input type="checkbox" name="modulos[]" value="<?= $e($chave) ?>"> <?= $e($rotulo) ?></label><label>Nível de acesso a <?= $e($rotulo) ?><select name="nivel[<?= $e($chave) ?>]"><option value="leitura">Somente visualizar</option><option value="edicao">Visualizar e alterar</option></select></label><?= mcfAcoesCampos($chave) ?><?php endforeach; ?>
</fieldset>
<button type="submit">Enviar convite no sistema</button>
</form><p class="ajuda">O convite aparece na conta da outra pessoa, nesta mesma página. Não é enviado e-mail. Nenhum acesso é liberado antes da aceitação.</p>
</section>
<section><h3>Compartilhados comigo</h3>
<?php $recebidos=array_filter($lista,fn($v)=>(int)$v['leitor_id']===$ator); if (!$recebidos): ?><p>Nenhum convite recebido.</p><?php endif; ?>
<?php foreach ($recebidos as $v): ?>
<article>
<h4><?= $e($v['proprietario_nome'] ?: $v['proprietario_email']) ?></h4>
<p><?= $e($v['proprietario_email']) ?> · <?= $e($estados[$v['estado']]) ?></p>
<p>Módulos: <?= $e(implode(', ',array_map(fn($m)=>$modulos[$m].' ('.mcfAcoesRotulo($v,$m).')',$v['modulos']))) ?>.</p>
<?php if ($v['estado']==='ativo'): ?>
<div class="links-modulos"><?php foreach ($v['modulos'] as $m): ?><a href="compartilhado.php?id=<?= (int)$v['id'] ?>&amp;modulo=<?= $e($m) ?>">Consultar <?= $e($modulos[$m]) ?></a><?php if (($v['niveis'][$m] ?? '')==='edicao'): ?><a href="<?= $e(mcfEntradasEdicao()[$m]) ?>?compartilhamento=<?= (int)$v['id'] ?>">Gerenciar <?= $e($modulos[$m]) ?></a><?php endif; ?><?php endforeach; ?></div>
<?php endif; ?>
<?php if (in_array($v['estado'],['pendente','ativo'],true)): ?>
<form method="post" class="acoes"><?= mcfCsrfField() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="versao" value="<?= (int)$v['versao'] ?>">
<?php if ($v['estado']==='pendente'): ?><button name="acao" value="aceitar">Aceitar permissões</button><button name="acao" value="recusar" class="secundario">Recusar</button>
<?php else: ?><button name="acao" value="sair" class="secundario">Deixar de acessar</button><?php endif; ?>
</form><?php endif; ?>
</article><?php endforeach; ?>
</section>
<section><h3>Quem pode consultar meus dados</h3>
<?php $enviados=array_filter($lista,fn($v)=>(int)$v['proprietario_id']===$ator); if (!$enviados): ?><p>Você ainda não compartilhou seus dados.</p><?php endif; ?>
<?php foreach ($enviados as $v): ?><article>
<h4><?= $e($v['leitor_nome'] ?: $v['leitor_email']) ?></h4><p><?= $e($v['leitor_email']) ?> · <?= $e($estados[$v['estado']]) ?></p>
<p>Módulos: <?= $e(implode(', ',array_map(fn($m)=>$modulos[$m].' ('.mcfAcoesRotulo($v,$m).')',$v['modulos']))) ?>.</p>
<?php if (in_array($v['estado'],['pendente','ativo'],true)): ?>
<form method="post" class="acoes"><?= mcfCsrfField() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="versao" value="<?= (int)$v['versao'] ?>"><button name="acao" value="revogar" class="secundario">Revogar todo o acesso</button></form>
<?php endif; ?>
<details><summary>Revisar módulos e enviar convite</summary>
<p>Salvar suspende o acesso anterior e exige uma nova aceitação, mesmo quando você reduz os módulos.</p>
<form method="post"><?= mcfCsrfField() ?><input type="hidden" name="acao" value="revisar"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="versao" value="<?= (int)$v['versao'] ?>">
<input type="hidden" name="granular" value="1"><fieldset><legend>Módulos autorizados</legend><?php foreach ($modulos as $m=>$rotulo): ?><label class="opcao"><input type="checkbox" name="modulos[]" value="<?= $e($m) ?>"<?= in_array($m,$v['modulos'],true) ? ' checked' : '' ?>> <?= $e($rotulo) ?></label><label>Nível de acesso a <?= $e($rotulo) ?><select name="nivel[<?= $e($m) ?>]"><option value="leitura">Somente visualizar</option><option value="edicao"<?= ($v['niveis'][$m] ?? '')==='edicao' ? ' selected' : '' ?>>Visualizar e alterar</option></select></label><?= mcfAcoesCampos($m,$v['acoes'][$m] ?? []) ?><?php endforeach; ?></fieldset>
<button type="submit">Salvar módulos e reenviar convite</button></form>
</details></article><?php endforeach; ?>
</section>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
