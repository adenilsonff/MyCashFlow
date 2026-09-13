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

if ($valor === '') {
return 0;
}

$valor = str_replace(['R$', ' '], '', $valor);

if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
$valor = str_replace('.', '', $valor);
$valor = str_replace(',', '.', $valor);
} else {
$valor = str_replace(',', '.', $valor);
}

if (!is_numeric($valor)) {
return 0;
}

return (float)$valor;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
header("Location: ../daytrade.php");
exit;
}

$corretora_id = isset($_POST['corretora_id'])
? (int)$_POST['corretora_id']
: 0;

$data = isset($_POST['data'])
? trim($_POST['data'])
: '';

$acao = isset($_POST['acao'])
? strtoupper(trim($_POST['acao']))
: '';

$quantidade = isset($_POST['quantidade'])
? (int)$_POST['quantidade']
: 0;

$valor_compra = isset($_POST['valor_compra'])
? normalizarValor($_POST['valor_compra'])
: 0;

$valor_venda = isset($_POST['valor_venda'])
? normalizarValor($_POST['valor_venda'])
: 0;

if ($corretora_id <= 0) {
die("Corretora inválida.");
}

if ($data === '') {
die("Informe a data da operação.");
}

if ($acao === '') {
die("Informe a ação.");
}

if ($quantidade <= 0) {
die("Informe uma quantidade válida.");
}

if ($valor_compra <= 0 && $valor_venda <= 0) {
die("Informe o valor da compra ou da venda.");
}

/*
Verifica se a corretora existe
*/
$stmt = $conn->prepare("
SELECT id
FROM corretoras
WHERE id = ?
LIMIT 1
");

if (!$stmt) {
die("Erro ao verificar corretora: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $corretora_id);
$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
$stmt->close();
die("Corretora não encontrada.");
}

$stmt->close();

/*
Calcula os valores da operação
*/
$total_compra = 0;
$total_venda = 0;

if ($valor_compra > 0) {
$total_compra = $quantidade * $valor_compra;
}

if ($valor_venda > 0) {
$total_venda = $quantidade * $valor_venda;
}

$total_compra = round($total_compra, 2);
$total_venda = round($total_venda, 2);

$valor_operacao = $total_compra + $total_venda;
$valor_operacao = round($valor_operacao, 2);

/*
Busca todas as taxas cadastradas para a corretora
*/
$stmtTaxas = $conn->prepare("
SELECT
nome_taxa,
percentual
FROM corretora_taxas
WHERE corretora_id = ?
ORDER BY id ASC
");

if (!$stmtTaxas) {
die("Erro ao consultar taxas: " . htmlspecialchars($conn->error));
}

$stmtTaxas->bind_param("i", $corretora_id);
$stmtTaxas->execute();

$resultadoTaxas = $stmtTaxas->get_result();

$taxas = 0;

if ($resultadoTaxas) {

while ($taxa = $resultadoTaxas->fetch_assoc()) {

$percentual = (float)$taxa['percentual'];

if ($percentual > 0) {

$valor_taxa =
$valor_operacao *
($percentual / 100);

$taxas += $valor_taxa;

}

}

}

$stmtTaxas->close();

$taxas = round($taxas, 2);

/*
Valores derivados
*/
$lucro_bruto = 0;
$deducao_1 = 0;
$imposto_20 = 0;
$lucro_desc = 0;
$darf = 0;
$lucro_final = 0;

/*
Só calcula lucro e impostos quando existem
compra e venda na mesma operação
*/
if ($total_compra > 0 && $total_venda > 0) {

$lucro_bruto =
$total_venda -
$total_compra;

$lucro_bruto =
round(
$lucro_bruto,
2
);

/*
Lucro após as taxas da corretora
*/
$lucro_desc =
$lucro_bruto -
$taxas;

$lucro_desc =
round(
$lucro_desc,
2
);

if ($lucro_desc > 0) {

/*
IRRF / dedo-duro de 1%

Usamos truncamento em 2 casas para reproduzir
o comportamento observado na nota:
416,81 x 1% = 4,1681 -> 4,16
*/
$deducao_1 =
floor(
($lucro_desc * 0.01) * 100
) / 100;

/*
Imposto total de 20%
*/
$imposto_20 =
round(
$lucro_desc * 0.20,
2
);

/*
DARF estimada:
imposto de 20% menos o IRRF já retido
*/
$darf =
$imposto_20 -
$deducao_1;

$darf =
round(
$darf,
2
);

if ($darf < 0) {
$darf = 0;
}

/*
Resultado após o imposto.

Não descontamos novamente o IRRF,
pois ele já foi compensado na DARF.
*/
$lucro_final =
$lucro_desc -
$darf;

$lucro_final =
round(
$lucro_final,
2
);

} else {

/*
Em caso de prejuízo:
não há IRRF, imposto ou DARF neste cálculo.
*/
$deducao_1 = 0;
$imposto_20 = 0;
$darf = 0;

$lucro_final =
$lucro_desc;

}

}

/*
Grava a operação
*/
$stmt = $conn->prepare("
INSERT INTO operacoes
(
corretora_id,
data,
acao,
quantidade,
valor_compra,
valor_venda,
total_compra,
total_venda,
valor_operacao,
lucro_bruto,
taxas,
deducao_1,
imposto_20,
lucro_desc,
darf,
lucro_final
)
VALUES
(
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?,
?
)
");

if (!$stmt) {
die("Erro ao preparar operação: " . htmlspecialchars($conn->error));
}

$tipos = "issi" . str_repeat("d", 12);

$stmt->bind_param(
$tipos,
$corretora_id,
$data,
$acao,
$quantidade,
$valor_compra,
$valor_venda,
$total_compra,
$total_venda,
$valor_operacao,
$lucro_bruto,
$taxas,
$deducao_1,
$imposto_20,
$lucro_desc,
$darf,
$lucro_final
);

if (!$stmt->execute()) {

$erro = $stmt->error;

$stmt->close();

die(
"Erro ao salvar operação: " .
htmlspecialchars(
$erro,
ENT_QUOTES,
'UTF-8'
)
);

}

$stmt->close();

/*
Retorna para o mês da operação cadastrada
*/
$dataOperacao = new DateTime($data);

$mes = (int)$dataOperacao->format('n');
$ano = (int)$dataOperacao->format('Y');

header(
"Location: ../daytrade.php?corretora_id=" .
$corretora_id .
"&mes=" .
$mes .
"&ano=" .
$ano
);

exit;
?>