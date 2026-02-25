<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("../config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $data_expiracao = date('Y-m-d', strtotime('+30 days'));
    $status = "ativo";

    $sql = "INSERT INTO usuario (email, senha, status_assinatura, data_expiracao) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $email, $senha, $status, $data_expiracao);

    if ($stmt->execute()) {
        $sucesso = "Usuário cadastrado com sucesso! Assinatura válida até $data_expiracao.";
    } else {
        $erro = "Erro ao cadastrar usuário.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - MyCashFlow</title>
    <!-- CSS específico para login e registro -->
    <link rel="stylesheet" href="/MyCashFlow/assets/css/style-auth.css">
</head>
<body>
    <header>
        <h1>MyCashFlow</h1>
    </header>

    <main>
        <form method="POST">
            <h2>Cadastrar Usuário</h2>
            <label>Email:</label>
            <input type="email" name="email" required>
            
            <label>Senha:</label>
            <input type="password" name="senha" required>
            
            <button type="submit">Cadastrar</button>

            <?php if(isset($sucesso)) echo "<p class='sucesso'>$sucesso</p>"; ?>
            <?php if(isset($erro)) echo "<p class='erro'>$erro</p>"; ?>
        </form>
    </main>

    <footer>
        <p>MyCashFlow © 2026 - Sistema de Finanças Pessoais</p>
    </footer>
</body>
</html>