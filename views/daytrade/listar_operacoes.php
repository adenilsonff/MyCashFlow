<?php
if (!isset($conn)) {
include __DIR__ . '/../../config.php';
}

$corretora_id = isset($_GET['corretora_id'])
? (int)$_GET['corretora_id']
: 0;

$mes = isset($_GET['mes'])
? (int)$_GET['mes']
: (int)date('n');

$ano = isset($_GET['ano'])
? (int)$_GET['ano']
: (int)date('Y');

if ($corretora_id <= 0) {
echo '<tr><td colspan="16">Corretora inválida.</td></tr>';
return;
}

if ($mes < 1 || $mes > 12) {
$mes = (int)date('n');
}

if ($ano < 2000 || $ano > 2100) {
$ano = (int)date('Y');
}

$stmt = $conn->prepare("
SELECT
id,
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
lucro_desc,
darf,
lucro_final
FROM operacoes
WHERE corretora_id = ?
AND MONTH(data) = ?
AND YEAR(data) = ?
ORDER BY data DESC, id DESC
");

if (!$stmt) {
echo '<tr><td colspan="16">Erro ao consultar operações.</td></tr>';
return;
}

$stmt->bind_param(
"iii",
$corretora_id,
$mes,
$ano
);

$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
echo '<tr><td colspan="16">Nenhuma operação encontrada para este mês.</td></tr>';
$stmt->close();
return;
}

while ($operacao = $resultado->fetch_assoc()) {

$valor_compra = (float)$operacao['valor_compra'];
$valor_venda = (float)$operacao['valor_venda'];

$total_compra = (float)$operacao['total_compra'];
$total_venda = (float)$operacao['total_venda'];
$valor_operacao = (float)$operacao['valor_operacao'];

$lucro_bruto = (float)$operacao['lucro_bruto'];
$taxas = (float)$operacao['taxas'];
$deducao_1 = (float)$operacao['deducao_1'];
$lucro_desc = (float)$operacao['lucro_desc'];
$darf = (float)$operacao['darf'];
$lucro_final = (float)$operacao['lucro_final'];

$percentual = 0;

if ($valor_compra > 0 && $valor_venda > 0) {
$percentual =
(($valor_venda - $valor_compra) / $valor_compra) * 100;
}

$classe_lucro_bruto =
$lucro_bruto > 0
? 'positivo'
: (
$lucro_bruto < 0
? 'negativo'
: 'neutro'
);

$classe_lucro_desc =
$lucro_desc > 0
? 'positivo'
: (
$lucro_desc < 0
? 'negativo'
: 'neutro'
);

$classe_lucro_final =
$lucro_final > 0
? 'positivo'
: (
$lucro_final < 0
? 'negativo'
: 'neutro'
);
?>

<tr>

<td>
<?= date('d/m/Y', strtotime($operacao['data'])) ?>
</td>

<td>
<?= htmlspecialchars($operacao['acao'], ENT_QUOTES, 'UTF-8') ?>
</td>

<td>
<?= number_format((int)$operacao['quantidade'], 0, ',', '.') ?>
</td>

<td>
<?= $valor_compra > 0
? 'R$ ' . number_format($valor_compra, 2, ',', '.')
: '-'
?>
</td>

<td>
<?= $valor_venda > 0
? 'R$ ' . number_format($valor_venda, 2, ',', '.')
: '-'
?>
</td>

<td class="<?=
$percentual > 0
? 'positivo'
: (
$percentual < 0
? 'negativo'
: 'neutro'
)
?>">
<?= number_format($percentual, 2, ',', '.') ?>%
</td>

<td>
R$ <?= number_format($total_compra, 2, ',', '.') ?>
</td>

<td>
R$ <?= number_format($total_venda, 2, ',', '.') ?>
</td>

<td>
R$ <?= number_format($valor_operacao, 2, ',', '.') ?>
</td>

<td class="<?= $classe_lucro_bruto ?>">
R$ <?= number_format($lucro_bruto, 2, ',', '.') ?>
</td>

<td>
R$ <?= number_format($taxas, 2, ',', '.') ?>
</td>

<td>
R$ <?= number_format($deducao_1, 2, ',', '.') ?>
</td>

<td class="<?= $classe_lucro_desc ?>">
R$ <?= number_format($lucro_desc, 2, ',', '.') ?>
</td>

<td>
R$ <?= number_format($darf, 2, ',', '.') ?>
</td>

<td class="<?= $classe_lucro_final ?>">
R$ <?= number_format($lucro_final, 2, ',', '.') ?>
</td>

<td>

<button
type="button"
class="btn-padrao btn-ajustar-operacao"
data-id="<?= (int)$operacao['id'] ?>"
data-data="<?= htmlspecialchars(date('d/m/Y', strtotime($operacao['data'])), ENT_QUOTES, 'UTF-8') ?>"
data-acao="<?= htmlspecialchars($operacao['acao'], ENT_QUOTES, 'UTF-8') ?>"
data-quantidade="<?= (int)$operacao['quantidade'] ?>"
data-valor-compra="<?= number_format($valor_compra, 2, '.', '') ?>"
data-valor-venda="<?= number_format($valor_venda, 2, '.', '') ?>"
data-total-compra="<?= number_format($total_compra, 2, '.', '') ?>"
data-total-venda="<?= number_format($total_venda, 2, '.', '') ?>"
data-taxas="<?= number_format($taxas, 2, '.', '') ?>"
>
Ajustar
</button>

</td>

</tr>

<?php
}

$stmt->close();
?>