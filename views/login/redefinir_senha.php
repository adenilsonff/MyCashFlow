<?php
include __DIR__ . '/../../config.php';

$erro = null;
$sucesso = null;
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenValido = false;
$recuperacaoId = null;
$usuarioId = null;

if ($token !== '') {
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare("
        SELECT
            id,
            usuario_id,
            data_expiracao,
            utilizado
        FROM recuperacao_senha
        WHERE token_hash = ?
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("s", $tokenHash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $recuperacao = $result->fetch_assoc();

            if ((int)$recuperacao['utilizado'] === 1) {
                $erro = "Este link de recuperação já foi utilizado.";
            } elseif ($recuperacao['data_expiracao'] < date('Y-m-d H:i:s')) {
                $erro = "Este link de recuperação expirou.";
            } else {
                $tokenValido = true;
                $recuperacaoId = (int)$recuperacao['id'];
                $usuarioId = (int)$recuperacao['usuario_id'];
            }
        } else {
            $erro = "Link de recuperação inválido.";
        }

        $stmt->close();
    } else {
        $erro = "Não foi possível validar o link de recuperação.";
    }
} else {
    $erro = "Link de recuperação inválido.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $tokenValido) {
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($senha === '' || $confirmarSenha === '') {
        $erro = "Preencha os dois campos de senha.";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter pelo menos 8 caracteres.";
    } elseif ($senha !== $confirmarSenha) {
        $erro = "As senhas não coincidem.";
    } else {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                UPDATE usuarios
                SET senha = ?
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception();
            }

            $stmt->bind_param("si", $senhaHash, $usuarioId);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception();
            }

            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE recuperacao_senha
                SET utilizado = 1
                WHERE id = ?
            ");

            if (!$stmt) {
                throw new Exception();
            }

            $stmt->bind_param("i", $recuperacaoId);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception();
            }

            $stmt->close();

            $stmt = $conn->prepare("
                UPDATE recuperacao_senha
                SET utilizado = 1
                WHERE usuario_id = ?
                AND utilizado = 0
            ");

            if (!$stmt) {
                throw new Exception();
            }

            $stmt->bind_param("i", $usuarioId);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception();
            }

            $stmt->close();

            $conn->commit();

            $sucesso = "Senha redefinida com sucesso. Você já pode entrar com a nova senha.";
            $tokenValido = false;
        } catch (Throwable $e) {
            $conn->rollback();
            $erro = "Não foi possível redefinir sua senha.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha - MyCashFlow</title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/style-auth.css">
</head>

<body>

<header>
    <h1>MyCashFlow</h1>
</header>

<main>
    <form method="POST" autocomplete="off">
        <h2>Redefinir senha</h2>

        <?php if ($tokenValido): ?>

            <input
                type="hidden"
                name="token"
                value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="senha">Nova senha:</label>

            <input
                type="password"
                id="senha"
                name="senha"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirmar_senha">Confirmar nova senha:</label>

            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit">Redefinir senha</button>

        <?php endif; ?>

        <?php if ($sucesso): ?>
            <p class="sucesso">
                <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p class="cadastro">
                <a href="/MyCashFlow/views/login/login.php">Ir para o login</a>
            </p>
        <?php endif; ?>

        <?php if ($erro): ?>
            <p class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php if (!$tokenValido): ?>
                <p class="cadastro">
                    <a href="/MyCashFlow/views/login/esqueci_senha.php">Solicitar novo link</a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$sucesso && $tokenValido): ?>
            <p class="cadastro">
                <a href="/MyCashFlow/views/login/login.php">Voltar para o login</a>
            </p>
        <?php endif; ?>
    </form>
</main>

<footer>
    <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
</footer>

</body>
</html>