<?php
require_once __DIR__.'/../config.php';
$uid=mcfUsuarioId();
$s=$conn->prepare('SELECT email,status_assinatura,data_expiracao FROM usuarios WHERE id=?');
$s->bind_param('i',$uid);$s->execute();$conta=$s->get_result()->fetch_assoc();$s->close();
$cssPagina='/MyCashFlow/assets/css/perfil.css?v='.filemtime(__DIR__.'/../assets/css/perfil.css');
require __DIR__.'/../includes/header.php';
require __DIR__.'/../includes/menu.php';
?>
<main class="perfil conta-configuracao">
<h2>Minha conta</h2>
<p class="perfil-intro">Gerencie seus dados, permissões e as contas que você acompanha.</p>
<div class="conta-atalhos">
<a class="conta-atalho" href="perfil.php"><strong>Meu perfil</strong><span>Edite seu nome, e-mail e senha.</span><span class="conta-atalho-acao">Editar perfil →</span></a>
<a class="conta-atalho" href="compartilhamento.php"><strong>Compartilhamento</strong><span>Escolha quem pode acessar seus módulos e consulte os convites recebidos.</span><span class="conta-atalho-acao">Gerenciar permissões →</span></a>
<a class="conta-atalho" href="visao_conjunta.php"><strong>Visão conjunta</strong><span>Acompanhe sua conta e os módulos compartilhados com você.</span><span class="conta-atalho-acao">Consultar contas →</span></a>
</div>
<section><h3>Dados da conta</h3>
<p>E-mail: <?= htmlspecialchars($conta['email'],ENT_QUOTES,'UTF-8') ?></p>
<p>Acesso válido até <?= htmlspecialchars(date('d/m/Y',strtotime($conta['data_expiracao'])),ENT_QUOTES,'UTF-8') ?>.</p>
</section>
<section><h3>Dados e preferências</h3>
<p>Seus dados são privados por padrão. Você escolhe os módulos e se permite somente consulta ou também alterações. O acesso depende de convite aceito e pode ser revogado. A categoria “Conjunta” é apenas uma classificação e não concede acesso.</p>
<p>As preferências dos gráficos são salvas neste navegador separadamente para cada usuário. Cotações públicas de mercado podem utilizar um cache comum.</p>
<p>Para uma segunda conta, saia e use “Cadastre-se aqui” na tela de login. Cada conta começa sem dados financeiros.</p>
<p>A recuperação por e-mail ainda não está configurada. Para redefinir a senha, solicite ao responsável pela instalação.</p>
</section>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
