<?php
include("../../config.php");

$erro = null;
$sucesso = null;
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($email === '' || $senha === '' || $confirmarSenha === '') {
        $erro = "Preencha todos os campos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Informe um e-mail válido.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $erro = "As senhas não coincidem.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $erro = "Este e-mail já está cadastrado.";
            }

            $stmt->close();
        } else {
            $erro = "Não foi possível concluir o cadastro.";
        }

        if (!$erro) {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $dataExpiracao = date('Y-m-d', strtotime('+30 days'));
            $status = "ativo";

            $stmt = $conn->prepare("
                INSERT INTO usuarios (
                    email,
                    senha,
                    status_assinatura,
                    data_expiracao
                )
                VALUES (?, ?, ?, ?)
            ");

            if ($stmt) {
                $stmt->bind_param(
                    "ssss",
                    $email,
                    $senhaHash,
                    $status,
                    $dataExpiracao
                );

                if ($stmt->execute()) {
                    $sucesso = "Usuário cadastrado com sucesso. Seu acesso inicial é válido por 30 dias.";
                    $email = "";
                } else {
                    if ($stmt->errno === 1062) {
                        $erro = "Este e-mail já está cadastrado.";
                    } else {
                        $erro = "Não foi possível concluir o cadastro.";
                    }
                }

                $stmt->close();
            } else {
                $erro = "Não foi possível concluir o cadastro.";
            }
        }
    }
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

            <label for="email">Email:</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email"
                required
            >

            <label for="senha">Senha:</label>
            <input
                type="password"
                id="senha"
                name="senha"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirmar_senha">Confirmar senha:</label>
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                minlength="8"
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
        </form>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>
</body>
</html>