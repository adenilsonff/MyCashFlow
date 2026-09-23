<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/compartilhamento_melhorias.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
 try {$id=mcfCompartilhamentoInt($_POST,'id');$version=mcfCompartilhamentoInt($_POST,'versao');}catch(DomainException $e){mcfFalhar(422,$e->getMessage());}
 $uid=mcfUsuarioId();$s=$conn->prepare('INSERT INTO compartilhamento_vistos(usuario_id,compartilhamento_id,versao) SELECT ?,id,? FROM compartilhamentos WHERE id=? AND (proprietario_id=? OR leitor_id=?) AND versao>=? ON DUPLICATE KEY UPDATE versao=GREATEST(compartilhamento_vistos.versao,VALUES(versao))');
 $s->bind_param('iiiiii',$uid,$version,$id,$uid,$uid,$version);$s->execute();$s->close();header('Location: avisos.php',true,303);exit;
}
$avisos=mcfAvisos($conn);$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$cssPagina='/MyCashFlow/assets/css/compartilhamento.css';require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/menu.php';
?>
<main class="compartilhamento"><h2>Avisos de compartilhamento</h2><p>Convites e mudanças de permissões. Marcar como lido não aceita nem altera um acesso.</p>
<?php if(!$avisos): ?><p>Nenhum aviso novo.</p><?php endif; ?>
<?php foreach($avisos as $av): $other=(int)$av['proprietario_id']===mcfUsuarioId()?'leitor':'proprietario'; ?>
<section><h3><?= $e($av[$other.'_nome']?:$av[$other.'_email']) ?></h3><p><?= $e($av[$other.'_email']) ?> — <?= $e(['pendente'=>'Convite aguardando aceitação','ativo'=>'Permissões aceitas e ativas','revogado'=>'Acesso encerrado','recusado'=>'Convite recusado'][$av['estado']]) ?></p><a href="compartilhamento.php">Consultar convite e permissões</a><form method="post"><?= mcfCsrfField() ?><input type="hidden" name="id" value="<?= (int)$av['id'] ?>"><input type="hidden" name="versao" value="<?= (int)$av['versao'] ?>"><button>Marcar como lido</button></form></section>
<?php endforeach; ?></main><?php require __DIR__.'/../includes/footer.php'; ?>
