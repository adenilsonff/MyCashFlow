<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

if (!isset($_SESSION['usuario_id'])) {
header("Location: ../login.php");
exit;
}

function normalizarPercentual($valor) {
$valor = trim((string)$valor);

if ($valor === '') {
return null;
}

$valor = str_replace(['%', ' '], '', $valor);

if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);
} else {
$valor = str_replace(',', '.', $valor);
}

if (!is_numeric($valor)) {
return null;
}

return (float)$valor;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header("Location: ../daytrade.php");
exit;
}

$corretora = isset($_POST['corretora'])
? trim($_POST['corretora'])
: '';

$nome_taxa = isset($_POST['nome_taxa'])
? trim($_POST['nome_taxa'])
: '';

$taxa_valor = isset($_POST['taxa_valor'])
? normalizarPercentual($_POST['taxa_valor'])
: null;

if ($corretora === '') {
die("Informe o nome da corretora.");
}

if ($nome_taxa === '') {
die("Informe o nome da taxa.");
}

if ($taxa_valor === null || $taxa_valor < 0) {
die("Informe um percentual válido.");
}

$stmt = $conn->prepare("
SELECT id
FROM (SELECT * FROM corretoras WHERE usuario_id = @mcf_usuario_id) AS corretoras
WHERE nome = ?
LIMIT 1
");

if (!$stmt) {
die(
"Erro ao consultar corretora: " .
htmlspecialchars('Falha de banco de dados.', ENT_QUOTES, 'UTF-8')
);
}

$stmt->bind_param("s", $corretora);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
$stmt->close();
die("Já existe uma corretora cadastrada com esse nome.");
}

$stmt->close();

$conn->begin_transaction();

try {

$stmt = $conn->prepare("
INSERT INTO corretoras
(usuario_id, nome)
VALUES (@mcf_usuario_id, ?)
");

if (!$stmt) {
throw new Exception('Falha de banco de dados.');
}

$stmt->bind_param(
"s",
$corretora
);

if (!$stmt->execute()) {
throw new Exception('Falha de banco de dados.');
}

$corretora_id = $conn->insert_id;

$stmt->close();

$stmt = $conn->prepare("
INSERT INTO corretora_taxas
(usuario_id, 
corretora_id,
nome_taxa,
percentual
)
VALUES (@mcf_usuario_id, ?, ?, ?)
");

if (!$stmt) {
throw new Exception('Falha de banco de dados.');
}

$stmt->bind_param(
"isd",
$corretora_id,
$nome_taxa,
$taxa_valor
);

if (!$stmt->execute()) {
throw new Exception('Falha de banco de dados.');
}

$stmt->close();

$conn->commit();

header("Location: ../daytrade.php");
exit;

} catch (Throwable $e) {

$conn->rollback();

die(
"Erro ao cadastrar corretora: " .
htmlspecialchars(
mcfMensagemErro($e),
ENT_QUOTES,
'UTF-8'
)
);

}
?>