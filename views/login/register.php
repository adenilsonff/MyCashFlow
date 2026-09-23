<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/perfil.php';
$erro = null; $sucesso = null; $email = ''; $nome = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = mcfNome(mcfTexto($_POST, 'nome'));
        $email = mcfEmail(mcfTexto($_POST, 'email'));
        $senha = mcfTexto($_POST, 'senha');
        mcfValidarSenha($senha, mcfTexto($_POST, 'confirmar_senha'));
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $validade = date('Y-m-d', strtotime('+30 days'));
        $s = $conn->prepare("INSERT INTO usuarios (nome,email,senha,status_assinatura,data_expiracao) VALUES (?,?,?,'ativo',?)");
        $s->bind_param('ssss', $nome, $email, $hash, $validade); $s->execute(); $s->close();
        $sucesso = 'Usuário cadastrado com sucesso. Seu acesso inicial é válido por 30 dias.';
        $email = ''; $nome = '';
    } catch (DomainException $e) { $erro = $e->getMessage(); http_response_code(422); }
    catch (mysqli_sql_exception $e) { $erro = $e->getCode() === 1062 ? 'Este e-mail já está cadastrado.' : 'Não foi possível concluir o cadastro.'; http_response_code($e->getCode() === 1062 ? 422 : 500); }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - MyCashFlow</title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/style-auth.css">
</head>
<body>
    <header>
        <h1>MyCashFlow</h1>
    </header>

    <main>
        <form method="POST" autocomplete="on">
            <h2>Cadastrar Usuário</h2>

            <label for="nome">Nome de exibição:</label>
            <input id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" minlength="2" maxlength="100" autocomplete="name" required>
            <label for="email">Email:</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email"
                required
            >

            <p>Senha de 12 a 72 bytes; letras acentuadas podem ocupar mais de um byte.</p>
            <label for="senha">Senha:</label>
            <input
                type="password"
                id="senha"
                name="senha"
                minlength="12"
                autocomplete="new-password"
                required
            >

            <label for="confirmar_senha">Confirmar senha:</label>
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                minlength="12"
                autocomplete="new-password"
                required
            >

            <button type="submit">Cadastrar</button>

            <?php if ($sucesso): ?>
                <p class="sucesso">
                    <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <?php if ($erro): ?>
                <p class="erro">
                    <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <p class="cadastro">
                Já tem uma conta?
                <a href="login.php">Entrar</a>
            </p>
        <?= mcfCsrfField() ?></form>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>
</body>
</html>