<?php
include __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
session_start();
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

$corretora_id = isset($_GET['id'])
? (int)$_GET['id']
: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$corretora_id = isset($_POST['corretora_id'])
? (int)$_POST['corretora_id']
: 0;

$acao = isset($_POST['acao'])
? trim($_POST['acao'])
: '';

if ($corretora_id <= 0) {
die("Corretora inválida.");
}

if ($acao === 'salvar_nome') {

$nome_corretora = isset($_POST['nome_corretora'])
? trim($_POST['nome_corretora'])
: '';

if ($nome_corretora === '') {
die("Informe o nome da corretora.");
}

$stmt = $conn->prepare("
SELECT id
FROM corretoras
WHERE nome = ?
AND id <> ?
LIMIT 1
");

if (!$stmt) {
die(
"Erro ao consultar corretora: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"si",
$nome_corretora,
$corretora_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
$stmt->close();
die("Já existe outra corretora com esse nome.");
}

$stmt->close();

$stmt = $conn->prepare("
UPDATE corretoras
SET nome = ?
WHERE id = ?
");

if (!$stmt) {
die(
"Erro ao atualizar corretora: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"si",
$nome_corretora,
$corretora_id
);

if (!$stmt->execute()) {

$erro = $stmt->error;

$stmt->close();

die(
"Erro ao atualizar corretora: " .
htmlspecialchars(
$erro,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->close();

header(
"Location: editar_corretora.php?id=" .
$corretora_id
);

exit;
}

if ($acao === 'adicionar_taxa') {

$nome_taxa = isset($_POST['nome_taxa'])
? trim($_POST['nome_taxa'])
: '';

$percentual = isset($_POST['percentual'])
? normalizarPercentual($_POST['percentual'])
: null;

if ($nome_taxa === '') {
die("Informe o nome da taxa.");
}

if ($percentual === null || $percentual < 0) {
die("Informe um percentual válido.");
}

$stmt = $conn->prepare("
INSERT INTO corretora_taxas
(
corretora_id,
nome_taxa,
percentual
)
VALUES (?, ?, ?)
");

if (!$stmt) {
die(
"Erro ao adicionar taxa: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"isd",
$corretora_id,
$nome_taxa,
$percentual
);

if (!$stmt->execute()) {

$erro = $stmt->error;

$stmt->close();

die(
"Erro ao adicionar taxa: " .
htmlspecialchars(
$erro,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->close();

header(
"Location: editar_corretora.php?id=" .
$corretora_id
);

exit;
}

if ($acao === 'editar_taxa') {

$taxa_id = isset($_POST['taxa_id'])
? (int)$_POST['taxa_id']
: 0;

$nome_taxa = isset($_POST['nome_taxa'])
? trim($_POST['nome_taxa'])
: '';

$percentual = isset($_POST['percentual'])
? normalizarPercentual($_POST['percentual'])
: null;

if ($taxa_id <= 0) {
die("Taxa inválida.");
}

if ($nome_taxa === '') {
die("Informe o nome da taxa.");
}

if ($percentual === null || $percentual < 0) {
die("Informe um percentual válido.");
}

$stmt = $conn->prepare("
UPDATE corretora_taxas
SET
nome_taxa = ?,
percentual = ?
WHERE id = ?
AND corretora_id = ?
");

if (!$stmt) {
die(
"Erro ao atualizar taxa: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"sdii",
$nome_taxa,
$percentual,
$taxa_id,
$corretora_id
);

if (!$stmt->execute()) {

$erro = $stmt->error;

$stmt->close();

die(
"Erro ao atualizar taxa: " .
htmlspecialchars(
$erro,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->close();

header(
"Location: editar_corretora.php?id=" .
$corretora_id
);

exit;
}

if ($acao === 'excluir_taxa') {

$taxa_id = isset($_POST['taxa_id'])
? (int)$_POST['taxa_id']
: 0;

if ($taxa_id <= 0) {
die("Taxa inválida.");
}

$stmt = $conn->prepare("
DELETE FROM corretora_taxas
WHERE id = ?
AND corretora_id = ?
");

if (!$stmt) {
die(
"Erro ao excluir taxa: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"ii",
$taxa_id,
$corretora_id
);

if (!$stmt->execute()) {

$erro = $stmt->error;

$stmt->close();

die(
"Erro ao excluir taxa: " .
htmlspecialchars(
$erro,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->close();

header(
"Location: editar_corretora.php?id=" .
$corretora_id
);

exit;
}

die("Ação inválida.");
}

if ($corretora_id <= 0) {
die("Corretora inválida.");
}

$stmt = $conn->prepare("
SELECT
id,
nome
FROM corretoras
WHERE id = ?
");

if (!$stmt) {
die(
"Erro ao consultar corretora: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"i",
$corretora_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
$stmt->close();
die("Corretora não encontrada.");
}

$corretora = $resultado->fetch_assoc();

$stmt->close();

$stmt = $conn->prepare("
SELECT
id,
nome_taxa,
percentual
FROM corretora_taxas
WHERE corretora_id = ?
ORDER BY id ASC
");

if (!$stmt) {
die(
"Erro ao consultar taxas: " .
htmlspecialchars(
$conn->error,
ENT_QUOTES,
'UTF-8'
)
);
}

$stmt->bind_param(
"i",
$corretora_id
);

$stmt->execute();

$resultadoTaxas = $stmt->get_result();

$taxas = [];

if ($resultadoTaxas) {

while ($row = $resultadoTaxas->fetch_assoc()) {
$taxas[] = $row;
}

}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Editar corretora</title>

<link rel="stylesheet" href="/MyCashFlow/assets/css/style-daytrade.css?v=10">

</head>

<body>

<?php include __DIR__ . '/../../includes/header.php'; ?>

<?php include __DIR__ . '/../../includes/menu.php'; ?>

<main class="acoes-layout">

<div class="card-lista editar-corretora-topo">

<div class="cabecalho-operacao">

<h2>
Editar corretora -
<?= htmlspecialchars(
$corretora['nome'],
ENT_QUOTES,
'UTF-8'
) ?>
</h2>

<a href="../daytrade.php">

<button
type="button"
class="btn-padrao"
>
← Voltar
</button>

</a>

</div>

</div>

<div class="top-cards editar-corretora-cards">

<div class="card-cadastro">

<h2>Alterar nome da corretora</h2>

<form
class="form-acoes"
method="post"
action="editar_corretora.php?id=<?= $corretora_id ?>"
>

<input
type="hidden"
name="acao"
value="salvar_nome"
>

<input
type="hidden"
name="corretora_id"
value="<?= $corretora_id ?>"
>

<label for="nome_corretora">
Nome da corretora
</label>

<input
type="text"
id="nome_corretora"
name="nome_corretora"
value="<?= htmlspecialchars(
$corretora['nome'],
ENT_QUOTES,
'UTF-8'
) ?>"
maxlength="100"
required
>

<button
type="submit"
class="btn-padrao"
>
Salvar nome
</button>

</form>

</div>

<div class="card-cadastro">

<h2>Adicionar nova taxa</h2>

<form
class="form-acoes"
method="post"
action="editar_corretora.php?id=<?= $corretora_id ?>"
>

<input
type="hidden"
name="acao"
value="adicionar_taxa"
>

<input
type="hidden"
name="corretora_id"
value="<?= $corretora_id ?>"
>

<label for="nova_taxa_nome">
Nome da taxa
</label>

<input
type="text"
id="nova_taxa_nome"
name="nome_taxa"
placeholder="Nome da taxa"
maxlength="100"
required
>

<label for="nova_taxa_percentual">
Percentual (%)
</label>

<input
type="text"
id="nova_taxa_percentual"
name="percentual"
placeholder="Ex.: 0,03645"
inputmode="decimal"
maxlength="15"
autocomplete="off"
required
>

<button
type="submit"
class="btn-padrao"
>
Adicionar taxa
</button>

</form>

</div>

<div class="card-lista">

<h2>Taxas cadastradas</h2>

<?php if (!empty($taxas)): ?>

<div class="lista-taxas">

<?php foreach ($taxas as $taxa): ?>

<div class="taxa-item">

<form
class="form-acoes"
method="post"
action="editar_corretora.php?id=<?= $corretora_id ?>"
>

<input
type="hidden"
name="corretora_id"
value="<?= $corretora_id ?>"
>

<input
type="hidden"
name="taxa_id"
value="<?= (int)$taxa['id'] ?>"
>

<label>Nome da taxa</label>

<input
type="text"
name="nome_taxa"
value="<?= htmlspecialchars(
$taxa['nome_taxa'],
ENT_QUOTES,
'UTF-8'
) ?>"
maxlength="100"
required
>

<label>Percentual (%)</label>

<input
type="text"
name="percentual"
value="<?= number_format(
(float)$taxa['percentual'],
5,
',',
''
) ?>"
inputmode="decimal"
maxlength="15"
autocomplete="off"
required
>

<div class="acoes-taxa">

<button
type="submit"
name="acao"
value="editar_taxa"
class="btn-padrao"
>
Salvar alteração
</button>

<button
type="submit"
name="acao"
value="excluir_taxa"
class="btn-padrao btn-excluir"
onclick="return confirm('Deseja realmente excluir esta taxa?');"
>
Excluir
</button>

</div>

</form>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<p class="texto-vazio">
Nenhuma taxa cadastrada.
</p>

<?php endif; ?>

</div>

</div>

</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<script>
const camposPercentuais =
document.querySelectorAll(
'input[name="percentual"]'
);

camposPercentuais.forEach(function(campo) {

campo.addEventListener('input', function() {

let valor = this.value;

valor = valor.replace(
/[^0-9,.]/g,
''
);

if (valor.includes(',')) {

valor = valor.replace(
/\./g,
''
);

} else {

valor = valor.replace(
'.',
','
);

}

let partes = valor.split(',');

if (partes.length > 2) {

valor =
partes[0] +
',' +
partes.slice(1).join('');

}

partes = valor.split(',');

if (
partes.length === 2 &&
partes[1].length > 5
) {

partes[1] =
partes[1].substring(
0,
5
);

valor =
partes.join(',');

}

this.value = valor;

});

});
</script>

</body>

</html>