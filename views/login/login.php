<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/perfil.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: ../dashboard.php");
exit;
}

$erro = null;
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
    $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';

    if ($email === '' || $senha === '') {
        $erro = "Preencha o e-mail e a senha.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Informe um e-mail válido.";
    } else {
        $sql = "
            SELECT
                id,
                nome,
                email,
                senha,
                status_assinatura,
                data_expiracao
            FROM usuarios
            WHERE email_normalizado = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $usuario = $result->fetch_assoc();

                if (password_verify($senha, $usuario['senha'])) {
                    if ($usuario['status_assinatura'] !== 'ativo') {
                        $erro = "Seu acesso está inativo.";
                    } elseif ($usuario['data_expiracao'] < date('Y-m-d')) {
                        $erro = "Seu período de acesso expirou.";
                    } else {
                        session_regenerate_id(true);
                        $_SESSION = [];
                        $_SESSION['mcf_csrf'] = bin2hex(random_bytes(32));
                        $_SESSION['auth_version'] = hash('sha256', $usuario['senha']);

                        $_SESSION['usuario_id'] = (int)$usuario['id'];
                        $_SESSION['usuario_email'] = $usuario['email'];
                        $_SESSION['usuario_nome'] = $usuario['nome'];
                        $_SESSION['status_assinatura'] = $usuario['status_assinatura'];
                        $_SESSION['data_expiracao'] = $usuario['data_expiracao'];

                        $stmt->close();

                        header("Location: ../dashboard.php");
                        exit;
                    }
                } else {
                    $erro = "E-mail ou senha incorretos.";
                }
            } else {
                $erro = "E-mail ou senha incorretos.";
            }

            $stmt->close();
        } else {
            $erro = "Não foi possível realizar o login.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MyCashFlow</title>
    <link rel="stylesheet" href="/MyCashFlow/assets/css/style-auth.css">

    <script>
        function atualizarRelogio() {
            const agora = new Date();
            const data = agora.toLocaleDateString('pt-BR');
            const hora = agora.toLocaleTimeString('pt-BR');

            document.getElementById("relogio").textContent = data + " " + hora;
        }

        setInterval(atualizarRelogio, 1000);
    </script>
</head>

<body onload="atualizarRelogio()">

<header>
    <h1>MyCashFlow</h1>
    <p id="relogio"></p>
</header>

<main>
    <form method="POST" autocomplete="on">
        <h2>Login</h2>

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
            autocomplete="current-password"
            required
        >

        <button type="submit">Entrar</button>

        <?php if ($erro): ?>
            <p class="erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <p class="cadastro">
            <a href="esqueci_senha.php">Esqueci minha senha</a>
        </p>

        <p class="cadastro">
            Ainda não tem conta?
            <a href="register.php">Cadastre-se aqui</a>
        </p>
    <?= mcfCsrfField() ?></form>
</main>

<footer>
    <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
</footer>

</body>
</html>
