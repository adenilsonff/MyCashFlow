<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("../config.php");

// Lógica de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();

        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_email'] = $usuario['email'];
            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - MyCashFlow</title>
    <link rel="stylesheet" href="../includes/style-auth.css">
    <script>
        function atualizarRelogio() {
            const agora = new Date();
            const data = agora.toLocaleDateString('pt-BR');
            const hora = agora.toLocaleTimeString('pt-BR');
            document.getElementById("relogio").innerHTML = data + " " + hora;
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
        <form method="POST">
            <h2>Login</h2>
            <label>Email:</label>
            <input type="email" name="email" required>
            
            <label>Senha:</label>
            <input type="password" name="senha" required>
            
            <button type="submit">Entrar</button>

            <?php if(isset($erro)) echo "<p class='erro'>$erro</p>"; ?>

            <p class="cadastro">Ainda não tem conta? <a href="register.php">Cadastre-se aqui</a></p>
        </form>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>
</body>
</html>