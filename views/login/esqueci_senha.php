<?php
include("../../config.php");

$erro = null;
$sucesso = null;
$email = "";
$linkTeste = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $erro = "Informe seu e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Informe um e-mail válido.";
    } else {
        $stmt = $conn->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();
                $usuarioId = (int)$usuario['id'];

                $stmt->close();

                $stmt = $conn->prepare("
                    UPDATE recuperacao_senha
                    SET utilizado = 1
                    WHERE usuario_id = ?
                    AND utilizado = 0
                ");

                if ($stmt) {
                    $stmt->bind_param("i", $usuarioId);
                    $stmt->execute();
                    $stmt->close();
                }

                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $dataExpiracao = date('Y-m-d H:i:s', strtotime('+60 minutes'));

                $stmt = $conn->prepare("
                    INSERT INTO recuperacao_senha (
                        usuario_id,
                        token_hash,
                        data_expiracao,
                        utilizado
                    )
                    VALUES (?, ?, ?, 0)
                ");

                if ($stmt) {
                    $stmt->bind_param(
                        "iss",
                        $usuarioId,
                        $tokenHash,
                        $dataExpiracao
                    );

                    if ($stmt->execute()) {
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

                        $linkTeste =
                            "http://" .
                            $host .
                            "/MyCashFlow/views/login/redefinir_senha.php?token=" .
                            urlencode($token);
                    }

                    $stmt->close();
                }
            } else {
                $stmt->close();
            }

            $sucesso = "Se o e-mail informado estiver cadastrado, você receberá as instruções para redefinir sua senha.";
            $email = "";
        } else {
            $erro = "Não foi possível processar sua solicitação.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar senha - MyCashFlow</title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/style-auth.css">
</head>

<body>

<header>
    <h1>MyCashFlow</h1>
</header>

<main>
    <form method="POST" autocomplete="on">
        <h2>Recuperar senha</h2>

        <p>
            Informe o e-mail da sua conta para receber as instruções de redefinição.
        </p>

        <label for="email">Email:</label>

        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="email"
            required
        >

        <button type="submit">
            Recuperar senha
        </button>

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

        <?php if ($linkTeste && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)): ?>
            <p class="cadastro">
                Teste local:
                <a href="<?= htmlspecialchars($linkTeste, ENT_QUOTES, 'UTF-8') ?>">
                    redefinir senha
                </a>
            </p>
        <?php endif; ?>

        <p class="cadastro">
            <a href="login.php">Voltar para o login</a>
        </p>
    </form>
</main>

<footer>
    <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
</footer>

</body>
</html>