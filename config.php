<?php
date_default_timezone_set('America/Sao_Paulo');

define('BRAPI_TOKEN', 'pV9h8EEsN5xs9ESi2Uk89n');

$host = "localhost";
$user = "root";
$pass = "";
$db = "financas";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>