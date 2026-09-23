<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/perfil.php';
$erro = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $trocouSenha = mcfAtualizarPerfil($conn, $_POST);
        $_SESSION['perfil_sucesso'] = $trocouSenha ? 'Senha alterada. Os outros acessos foram encerrados; esta sessão foi renovada.' : 'Perfil atualizado.';
        header('Location: /MyCashFlow/views/perfil.php', true, 303); exit;
    } catch (DomainException $e) { $erro = $e->getMessage(); http_response_code(422); }
    catch (Throwable $e) { $erro = 'Não foi possível atualizar o perfil. Tente novamente.'; http_response_code(500); }
}
$uid = mcfUsuarioId();
$s = $conn->prepare('SELECT nome,email FROM usuarios WHERE id=?');
$s->bind_param('i', $uid); $s->execute(); $perfil = $s->get_result()->fetch_assoc(); $s->close();
$sucesso = $_SESSION['perfil_sucesso'] ?? null; unset($_SESSION['perfil_sucesso']);
$escape = static fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cssPagina = '/MyCashFlow/assets/css/perfil.css?v='.filemtime(__DIR__.'/../assets/css/perfil.css');
require __DIR__.'/../includes/header.php';
require __DIR__.'/../includes/menu.php';
?>
<main class="perfil">
<h2>Meu perfil</h2>
<p>Atualize seus dados de identificação e sua senha.</p>
<?php if ($erro): ?><p class="perfil-erro" role="alert"><?= $escape($erro) ?></p><?php endif; ?>
<?php if ($sucesso): ?><p class="perfil-sucesso" role="status"><?= $escape($sucesso) ?></p><?php endif; ?>
<section><h3>Dados pessoais</h3>
<form method="post">
<?= mcfCsrfField() ?><input type="hidden" name="acao" value="dados">
<label for="nome">Nome de exibição</label>
<input id="nome" name="nome" value="<?= $escape($perfil['nome']) ?>" minlength="2" maxlength="100" autocomplete="name" required>
<p class="perfil-ajuda">De 2 a 100 caracteres. Esse nome identifica você na interface.</p>
<label for="email">E-mail de acesso</label>
<input id="email" name="email" type="email" value="<?= $escape($perfil['email']) ?>" maxlength="100" autocomplete="email" required>
<p class="perfil-ajuda">Maiúsculas e minúsculas são equivalentes; espaços nas extremidades são removidos. Ao alterar, use o novo e-mail no próximo login.</p>
<label for="senha-email">Senha atual para alterar o e-mail</label>
<input id="senha-email" name="senha_atual" type="password" autocomplete="current-password">
<button type="submit">Salvar dados</button>
</form></section>
<section><h3>Alterar senha</h3>
<p class="perfil-ajuda">Use de 12 a 72 bytes; letras acentuadas podem ocupar mais de um byte. Os outros acessos serão encerrados.</p>
<form method="post">
<?= mcfCsrfField() ?><input type="hidden" name="acao" value="senha">
<label for="senha-atual">Senha atual</label><input id="senha-atual" name="senha_atual" type="password" autocomplete="current-password" required>
<label for="nova-senha">Nova senha</label><input id="nova-senha" name="nova_senha" type="password" autocomplete="new-password" required>
<label for="confirmar-senha">Confirmar nova senha</label><input id="confirmar-senha" name="confirmar_senha" type="password" autocomplete="new-password" required>
<button type="submit">Alterar senha</button>
</form></section>
<p><a href="configuracao.php">Configurações da conta</a></p>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
