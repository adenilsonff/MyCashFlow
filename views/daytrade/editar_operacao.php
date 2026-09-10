<?php
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
session_start();
}

if (!isset($_SESSION['usuario_id'])) {
header("Location: ../login.php");
exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
exit("Operação inválida.");
}

$stmt = $conn->prepare("
SELECT
id,
corretora_id,
data,
acao,
quantidade,
valor_compra,
valor_venda,
total_compra,
total_venda,
taxas
FROM operacoes
WHERE id = ?
");

if (!$stmt) {
exit(
"Erro ao consultar operação: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
$stmt->close();
exit("Operação não encontrada.");
}

$row = $result->fetch_assoc();

$stmt->close();

function formatarValorCampo($valor) {
if ($valor === null || $valor === '') {
return '';
}

return number_format(
(float)$valor,
2,
',',
'.'
);
}

function formatarValorOriginal($valor) {
if ($valor === null || $valor === '' || (float)$valor <= 0) {
return '-';
}

return number_format(
(float)$valor,
2,
',',
'.'
);
}
?>

<h3>Ajustar operação</h3>

<form
method="post"
action="ajustar_operacao.php"
class="form-acoes"
>

<input
type="hidden"
name="operacao_id"
value="<?= (int)$row['id'] ?>"
>

<input
type="hidden"
name="corretora_id"
value="<?= (int)$row['corretora_id'] ?>"
>

<label>Data</label>

<input
type="date"
value="<?= htmlspecialchars($row['data'], ENT_QUOTES, 'UTF-8') ?>"
readonly
>

<label>Ação</label>

<input
type="text"
value="<?= htmlspecialchars($row['acao'], ENT_QUOTES, 'UTF-8') ?>"
readonly
>

<label>Quantidade</label>

<input
type="number"
value="<?= (int)$row['quantidade'] ?>"
readonly
>

<label>Valor da compra</label>

<input
type="text"
value="<?= formatarValorOriginal($row['valor_compra']) ?>"
readonly
>

<label>Valor da venda</label>

<input
type="text"
value="<?= formatarValorOriginal($row['valor_venda']) ?>"
readonly
>

<label>Total da compra</label>

<input
type="text"
name="total_compra"
value="<?= formatarValorCampo($row['total_compra']) ?>"
inputmode="decimal"
autocomplete="off"
required
>

<label>Total da venda</label>

<input
type="text"
name="total_venda"
value="<?= formatarValorCampo($row['total_venda']) ?>"
inputmode="decimal"
autocomplete="off"
required
>

<label>Taxas</label>

<input
type="text"
name="taxas"
value="<?= formatarValorCampo($row['taxas']) ?>"
inputmode="decimal"
autocomplete="off"
required
>

<button
type="submit"
class="btn-padrao"
>
Salvar ajuste
</button>

</form>