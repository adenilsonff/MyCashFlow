<?php
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
session_start();
}

if (!isset($_SESSION['usuario_id'])) {
header("Location: ../login.php");
exit;
}

function normalizarValor($valor) {
$valor = trim((string)$valor);
$valor = str_replace(['R$', ' '], '', $valor);

if ($valor === '') {
return 0;
}

if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);
} else {
$valor = str_replace(',', '.', $valor);
}

return is_numeric($valor) ? (float)$valor : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header("Location: ../daytrade.php");
exit;
}

$operacao_id = isset($_POST['operacao_id'])
? (int)$_POST['operacao_id']
: 0;

$corretora_id = isset($_POST['corretora_id'])
? (int)$_POST['corretora_id']
: 0;

$total_compra = isset($_POST['total_compra'])
? normalizarValor($_POST['total_compra'])
: 0;

$total_venda = isset($_POST['total_venda'])
? normalizarValor($_POST['total_venda'])
: 0;

$taxas = isset($_POST['taxas'])
? normalizarValor($_POST['taxas'])
: 0;

if ($operacao_id <= 0 || $corretora_id <= 0) {
die("Operação ou corretora inválida.");
}

if ($total_compra < 0 || $total_venda < 0 || $taxas < 0) {
die("Os valores informados não podem ser negativos.");
}

$stmt = $conn->prepare("
SELECT id
FROM operacoes
WHERE id = ?
AND corretora_id = ?
");

if (!$stmt) {
die(
"Erro ao consultar operação: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"ii",
$operacao_id,
$corretora_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
$stmt->close();
die("Operação não encontrada.");
}

$stmt->close();

$valor_operacao = $total_compra + $total_venda;

$lucro_bruto = 0;
$deducao_1 = 0;
$lucro_desc = 0;
$darf = 0;
$lucro_final = 0;

if ($total_compra > 0 && $total_venda > 0) {

$lucro_bruto =
$total_venda -
$total_compra;

$lucro_desc =
$lucro_bruto -
$taxas;

if ($lucro_desc > 0) {

$deducao_1 =
$lucro_desc *
0.01;

$darf =
$lucro_desc *
0.19;

}

$lucro_final =
$lucro_desc -
$deducao_1 -
$darf;

}

$stmt = $conn->prepare("
UPDATE operacoes
SET
total_compra = ?,
total_venda = ?,
valor_operacao = ?,
lucro_bruto = ?,
taxas = ?,
deducao_1 = ?,
lucro_desc = ?,
darf = ?,
lucro_final = ?
WHERE id = ?
AND corretora_id = ?
");

if (!$stmt) {
die(
"Erro ao preparar atualização: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"dddddddddii",
$total_compra,
$total_venda,
$valor_operacao,
$lucro_bruto,
$taxas,
$deducao_1,
$lucro_desc,
$darf,
$lucro_final,
$operacao_id,
$corretora_id
);

if ($stmt->execute()) {

$stmt->close();

header(
"Location: ../daytrade.php?corretora_id=" .
$corretora_id
);

exit;

}

$erro = $stmt->error;

$stmt->close();

die(
"Erro ao ajustar operação: " .
htmlspecialchars(
$erro,
ENT_QUOTES,
'UTF-8'
)
);
?>