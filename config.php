<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "financas"; // nome do banco que você já criou

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}
?>