<?php
date_default_timezone_set('America/Sao_Paulo');

define('BRAPI_TOKEN', 'pV9h8EEsN5xs9ESi2Uk89n');

$host = getenv("MCF_DB_HOST") ?: "127.0.0.1";
$user = getenv("MCF_DB_USER") ?: "root";
$pass = getenv("MCF_DB_PASS") ?: "";
$db = getenv("MCF_DB_NAME") ?: "financas";

require_once __DIR__.'/includes/seguranca.php';
ini_set('display_errors','0');
mcfIniciarSessao();
set_exception_handler(function (Throwable $e): void {
    // Não registrar SQL, valores, senhas, tokens ou mensagens do provedor.
    mcfFalhar(500, 'Não foi possível concluir a operação.');
});
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($host, $user, $pass, $db, (int)(getenv('MCF_DB_PORT') ?: 3306));

if ($conn->connect_error) {
    mcfFalhar(503, "Banco indisponível.");
}

$conn->set_charset("utf8mb4");
mcfProteger($conn);
?>