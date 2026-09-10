<?php
include __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
session_start();
}

if (!isset($_SESSION['usuario_id'])) {
header("Location: login.php");
exit;
}

$corretora_selecionada = isset($_GET['corretora_id'])
? (int)$_GET['corretora_id']
: 0;

$mes = isset($_GET['mes'])
? (int)$_GET['mes']
: (int)date('n');

$ano = isset($_GET['ano'])
? (int)$_GET['ano']
: (int)date('Y');

if ($mes < 1 || $mes > 12) {
$mes = (int)date('n');
}

if ($ano < 2000 || $ano > 2100) {
$ano = (int)date('Y');
}

$corretoras = [];

$resultCorretoras = $conn->query("
SELECT id, nome
FROM corretoras
ORDER BY nome ASC
");

if ($resultCorretoras) {
while ($row = $resultCorretoras->fetch_assoc()) {
$corretoras[] = $row;
}
}

$nome_corretora_selecionada = '';

if ($corretora_selecionada > 0) {

$stmt = $conn->prepare("
SELECT nome
FROM corretoras
WHERE id = ?
");

if ($stmt) {

$stmt->bind_param(
"i",
$corretora_selecionada
);

$stmt->execute();

$resultado = $stmt->get_result();

if (
$resultado &&
$resultado->num_rows > 0
) {

$dadosCorretora =
$resultado->fetch_assoc();

$nome_corretora_selecionada =
$dadosCorretora['nome'];

} else {

$corretora_selecionada = 0;

}

$stmt->close();

}

}

$taxas_corretora = [];

if ($corretora_selecionada > 0) {

$stmtTaxasCorretora = $conn->prepare("
SELECT
id,
nome_taxa,
percentual
FROM corretora_taxas
WHERE corretora_id = ?
ORDER BY id ASC
");

if ($stmtTaxasCorretora) {

$stmtTaxasCorretora->bind_param(
"i",
$corretora_selecionada
);

$stmtTaxasCorretora->execute();

$resultadoTaxasCorretora =
$stmtTaxasCorretora->get_result();

if ($resultadoTaxasCorretora) {

while (
$taxaCorretora =
$resultadoTaxasCorretora->fetch_assoc()
) {

$taxas_corretora[] =
$taxaCorretora;

}

}

$stmtTaxasCorretora->close();

}

}

$total_darf_mes = 0;
$total_lucro_final_mes = 0;

if ($corretora_selecionada > 0) {

$stmtResumo = $conn->prepare("
SELECT
COALESCE(SUM(darf), 0) AS total_darf,
COALESCE(SUM(lucro_final), 0) AS total_lucro_final
FROM operacoes
WHERE corretora_id = ?
AND MONTH(data) = ?
AND YEAR(data) = ?
");

if ($stmtResumo) {

$stmtResumo->bind_param(
"iii",
$corretora_selecionada,
$mes,
$ano
);

$stmtResumo->execute();

$resultadoResumo =
$stmtResumo->get_result();

if ($resultadoResumo) {

$resumo =
$resultadoResumo->fetch_assoc();

$total_darf_mes =
(float)$resumo['total_darf'];

$total_lucro_final_mes =
(float)$resumo['total_lucro_final'];

}

$stmtResumo->close();

}

}

$nomesMeses = [
1 => 'Janeiro',
2 => 'Fevereiro',
3 => 'Março',
4 => 'Abril',
5 => 'Maio',
6 => 'Junho',
7 => 'Julho',
8 => 'Agosto',
9 => 'Setembro',
10 => 'Outubro',
11 => 'Novembro',
12 => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0"
>

<title>Day Trade</title>

<link
rel="stylesheet"
href="/MyCashFlow/assets/css/style-daytrade.css?v=19"
>

</head>

<body>

<?php
include __DIR__ . '/../includes/header.php';
?>

<?php
include __DIR__ . '/../includes/menu.php';
?>

<main class="acoes-layout">

<?php if ($corretora_selecionada === 0): ?>

<div class="top-cards">

<div class="card-lista">

<h2>Corretoras cadastradas</h2>

<?php if (!empty($corretoras)): ?>

<ul>

<?php foreach ($corretoras as $corretora): ?>

<li>

<a
href="daytrade.php?corretora_id=<?= (int)$corretora['id'] ?>"
class="link-corretora"
>

<button
type="button"
class="btn-padrao btn-corretora"
>

<?= htmlspecialchars(
$corretora['nome'],
ENT_QUOTES,
'UTF-8'
) ?>

</button>

</a>

</li>

<?php endforeach; ?>

</ul>

<?php else: ?>

<p>Nenhuma corretora cadastrada.</p>

<?php endif; ?>

</div>

<div class="card-cadastro">

<h2>Cadastrar nova corretora</h2>

<form
class="form-acoes"
action="daytrade/salvar_corretora.php"
method="post"
>

<label for="corretora">
Nome da corretora
</label>

<input
type="text"
id="corretora"
name="corretora"
placeholder="Nome da Corretora"
maxlength="100"
required
>

<label for="nome_taxa">
Nome da taxa
</label>

<input
type="text"
id="nome_taxa"
name="nome_taxa"
placeholder="Nome da Taxa"
maxlength="100"
required
>

<label for="taxa_valor">
Valor da taxa (%)
</label>

<input
type="text"
id="taxa_valor"
name="taxa_valor"
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
Salvar
</button>

</form>

</div>

<div class="card-cadastro">

<h2>Editar corretora</h2>

<?php if (!empty($corretoras)): ?>

<form
class="form-acoes"
id="form-editar-corretora"
>

<label for="editar_corretora_id">
Corretora
</label>

<select id="editar_corretora_id">

<option value="">
Selecione...
</option>

<?php foreach ($corretoras as $corretora): ?>

<option
value="<?= (int)$corretora['id'] ?>"
>

<?= htmlspecialchars(
$corretora['nome'],
ENT_QUOTES,
'UTF-8'
) ?>

</option>

<?php endforeach; ?>

</select>

<button
type="button"
class="btn-padrao"
id="btn-editar-corretora"
>
Editar taxas
</button>

</form>

<div
id="aviso-edicao"
style="display:none;margin-top:10px;"
>
Selecione uma corretora.
</div>

<?php else: ?>

<p>Cadastre uma corretora primeiro.</p>

<?php endif; ?>

</div>

</div>

<?php else: ?>

<div class="topo-operacoes">

<div class="card-lista card-corretora-topo">

<div class="cabecalho-operacao">

<h2>

Day Trade -
<?= htmlspecialchars(
$nome_corretora_selecionada,
ENT_QUOTES,
'UTF-8'
) ?>

</h2>

<a href="daytrade.php">

<button
type="button"
class="btn-padrao"
>
← Voltar para corretoras
</button>

</a>

</div>

</div>

<div class="card-cadastro card-acoes-topo">

<h2>Operações</h2>

<div class="botoes-operacoes">

<button
type="button"
class="btn-padrao"
id="btn-mostrar-cadastro"
>
Cadastrar operação
</button>

<button
type="button"
class="btn-padrao"
id="btn-mostrar-pesquisa"
>
Pesquisar
</button>

</div>

</div>

</div>

<div class="bottom-card">

<div class="card-lista">

<h2>

Operações realizadas -
<?= $nomesMeses[$mes] ?>
de
<?= $ano ?>

</h2>

<div class="resumo-operacoes">

<div class="resumo-card">

<h3>Total DARF do mês</h3>

<strong>

R$
<?= number_format(
$total_darf_mes,
2,
',',
'.'
) ?>

</strong>

</div>

<div class="resumo-card">

<h3>Lucro final do mês</h3>

<strong
class="<?=
$total_lucro_final_mes > 0
? 'positivo'
: (
$total_lucro_final_mes < 0
? 'negativo'
: 'neutro'
)
?>"
>

R$
<?= number_format(
$total_lucro_final_mes,
2,
',',
'.'
) ?>

</strong>

</div>

</div>

<div class="tabela-responsiva">

<table class="tabela-acoes">

<thead>

<tr>

<th>Data</th>
<th>Ação</th>
<th>Qtd</th>
<th>Compra</th>
<th>Venda</th>
<th>%</th>
<th>Total Compra</th>
<th>Total Venda</th>
<th>Valor Op.</th>
<th>Lucro Bruto</th>
<th>Taxas</th>
<th>Dedução 1%</th>
<th>Lucro c/ desc.</th>
<th>DARF</th>
<th>Lucro Final</th>
<th>Ações</th>

</tr>

</thead>

<tbody id="operacoes-body">

<?php
$_GET['corretora_id'] =
$corretora_selecionada;

$_GET['mes'] =
$mes;

$_GET['ano'] =
$ano;

include __DIR__ .
'/daytrade/listar_operacoes.php';
?>

</tbody>

</table>

</div>

</div>

</div>

<div
class="modal"
id="modal-cadastro-operacao"
style="display:none;"
>

<div class="modal-content">

<span
class="close"
id="fechar-modal-cadastro"
>
&times;
</span>

<h2>Cadastrar operação</h2>

<form
class="form-acoes"
id="form-operacao"
action="daytrade/salvar_operacao.php"
method="post"
>

<input
type="hidden"
name="corretora_id"
value="<?= $corretora_selecionada ?>"
>

<label for="data">
Data
</label>

<input
type="date"
id="data"
name="data"
required
>

<label for="acao">
Ação
</label>

<input
type="text"
id="acao"
name="acao"
placeholder="Ex.: PETR4"
maxlength="20"
required
>

<label for="quantidade">
Quantidade
</label>

<input
type="number"
id="quantidade"
name="quantidade"
min="1"
step="1"
required
>

<label for="valor_compra">
Valor da compra
</label>

<input
type="text"
id="valor_compra"
name="valor_compra"
placeholder="Ex.: 32,45"
inputmode="decimal"
autocomplete="off"
>

<label for="valor_venda">
Valor da venda
</label>

<input
type="text"
id="valor_venda"
name="valor_venda"
placeholder="Ex.: 33,10"
inputmode="decimal"
autocomplete="off"
>

<button
type="submit"
class="btn-padrao"
>
Salvar operação
</button>

</form>

</div>

</div>

<div
class="modal"
id="modal-pesquisa-operacao"
style="display:none;"
>

<div class="modal-content">

<span
class="close"
id="fechar-modal-pesquisa"
>
&times;
</span>

<h2>Pesquisar operações</h2>

<form
class="form-acoes"
action="daytrade.php"
method="get"
>

<input
type="hidden"
name="corretora_id"
value="<?= $corretora_selecionada ?>"
>

<label for="mes">
Mês
</label>

<select
id="mes"
name="mes"
>

<?php foreach ($nomesMeses as $numero => $nome): ?>

<option
value="<?= $numero ?>"
<?= $numero === $mes ? 'selected' : '' ?>
>

<?= $nome ?>

</option>

<?php endforeach; ?>

</select>

<label for="ano">
Ano
</label>

<input
type="number"
id="ano"
name="ano"
min="2000"
max="2100"
value="<?= $ano ?>"
required
>

<button
type="submit"
class="btn-padrao"
>
Pesquisar
</button>

</form>

</div>

</div>

<div
class="modal"
id="modal-ajustar-operacao"
style="display:none;"
>

<div class="modal-content modal-ajuste-compacto">

<span
class="close"
id="fechar-modal-ajuste"
>
&times;
</span>

<h2>Ajustar operação</h2>

<form
class="form-acoes"
id="form-ajustar-operacao"
action="daytrade/ajustar_operacao.php"
method="post"
>

<input
type="hidden"
id="ajuste_id"
name="id"
>

<input
type="hidden"
name="corretora_id"
value="<?= $corretora_selecionada ?>"
>

<input
type="hidden"
name="mes"
value="<?= $mes ?>"
>

<input
type="hidden"
name="ano"
value="<?= $ano ?>"
>

<input
type="hidden"
id="ajuste_taxas_total"
name="taxas"
value="0"
>

<div class="ajuste-info-grid">

<div class="campo-ajuste">

<label for="ajuste_data">
Data
</label>

<input
type="text"
id="ajuste_data"
readonly
>

</div>

<div class="campo-ajuste">

<label for="ajuste_acao">
Ação
</label>

<input
type="text"
id="ajuste_acao"
readonly
>

</div>

<div class="campo-ajuste">

<label for="ajuste_quantidade">
Quantidade
</label>

<input
type="text"
id="ajuste_quantidade"
readonly
>

</div>

</div>

<div class="ajuste-valores-grid">

<div class="campo-ajuste">

<label for="ajuste_valor_compra">
Valor da compra
</label>

<input
type="text"
id="ajuste_valor_compra"
readonly
>

</div>

<div class="campo-ajuste">

<label for="ajuste_valor_venda">
Valor da venda
</label>

<input
type="text"
id="ajuste_valor_venda"
readonly
>

</div>

</div>

<div class="ajuste-valores-grid">

<div class="campo-ajuste">

<label for="ajuste_total_compra">
Total da compra
</label>

<input
type="text"
id="ajuste_total_compra"
name="total_compra"
inputmode="decimal"
autocomplete="off"
required
>

</div>

<div class="campo-ajuste">

<label for="ajuste_total_venda">
Total da venda
</label>

<input
type="text"
id="ajuste_total_venda"
name="total_venda"
inputmode="decimal"
autocomplete="off"
required
>

</div>

</div>

<div class="ajuste-taxas">

<h3>Taxas da operação</h3>

<?php if (!empty($taxas_corretora)): ?>

<div class="ajuste-taxas-grid">

<?php foreach ($taxas_corretora as $taxa): ?>

<div class="campo-ajuste">

<label>

<?= htmlspecialchars(
$taxa['nome_taxa'],
ENT_QUOTES,
'UTF-8'
) ?>

</label>

<input
type="text"
class="ajuste-taxa-individual"
data-percentual="<?= htmlspecialchars(
$taxa['percentual'],
ENT_QUOTES,
'UTF-8'
) ?>"
inputmode="decimal"
autocomplete="off"
required
>

<small>

Taxa cadastrada:
<?= number_format(
(float)$taxa['percentual'],
5,
',',
'.'
) ?>%

</small>

</div>

<?php endforeach; ?>

</div>

<?php else: ?>

<div class="ajuste-taxas-grid">

<div class="campo-ajuste">

<label>
Taxas
</label>

<input
type="text"
class="ajuste-taxa-individual"
data-percentual="1"
inputmode="decimal"
autocomplete="off"
required
>

</div>

</div>

<?php endif; ?>

</div>

<button
type="submit"
class="btn-padrao btn-salvar-ajuste"
>
Salvar ajustes
</button>

</form>

</div>

</div>

<?php endif; ?>

</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>

<script>
const campoTaxa =
document.getElementById(
'taxa_valor'
);

if (campoTaxa) {

campoTaxa.addEventListener(
'input',
function() {

let valor =
this.value;

valor =
valor.replace(
/[^0-9,.]/g,
''
);

if (valor.includes(',')) {

valor =
valor.replace(
/\./g,
''
);

} else {

valor =
valor.replace(
'.',
','
);

}

let partes =
valor.split(',');

if (partes.length > 2) {

valor =
partes[0] +
',' +
partes
.slice(1)
.join('');

}

partes =
valor.split(',');

if (
partes.length === 2 &&
partes[1].length > 5
) {

partes[1] =
partes[1]
.substring(
0,
5
);

valor =
partes.join(',');

}

this.value =
valor;

}
);

}

const botaoEditar =
document.getElementById(
'btn-editar-corretora'
);

if (botaoEditar) {

botaoEditar.addEventListener(
'click',
function() {

const select =
document.getElementById(
'editar_corretora_id'
);

const aviso =
document.getElementById(
'aviso-edicao'
);

const id =
select.value;

if (!id) {

aviso.style.display =
'block';

return;

}

aviso.style.display =
'none';

window.location.href =
'daytrade/editar_corretora.php?id=' +
encodeURIComponent(id);

}
);

}

const modalCadastro =
document.getElementById(
'modal-cadastro-operacao'
);

const modalPesquisa =
document.getElementById(
'modal-pesquisa-operacao'
);

const modalAjuste =
document.getElementById(
'modal-ajustar-operacao'
);

const btnMostrarCadastro =
document.getElementById(
'btn-mostrar-cadastro'
);

const btnMostrarPesquisa =
document.getElementById(
'btn-mostrar-pesquisa'
);

const fecharModalCadastro =
document.getElementById(
'fechar-modal-cadastro'
);

const fecharModalPesquisa =
document.getElementById(
'fechar-modal-pesquisa'
);

const fecharModalAjuste =
document.getElementById(
'fechar-modal-ajuste'
);

if (
btnMostrarCadastro &&
modalCadastro
) {

btnMostrarCadastro.addEventListener(
'click',
function() {

if (modalPesquisa) {
modalPesquisa.style.display =
'none';
}

if (modalAjuste) {
modalAjuste.style.display =
'none';
}

modalCadastro.style.display =
'block';

}
);

}

if (
btnMostrarPesquisa &&
modalPesquisa
) {

btnMostrarPesquisa.addEventListener(
'click',
function() {

if (modalCadastro) {
modalCadastro.style.display =
'none';
}

if (modalAjuste) {
modalAjuste.style.display =
'none';
}

modalPesquisa.style.display =
'block';

}
);

}

if (
fecharModalCadastro &&
modalCadastro
) {

fecharModalCadastro.addEventListener(
'click',
function() {

modalCadastro.style.display =
'none';

}
);

}

if (
fecharModalPesquisa &&
modalPesquisa
) {

fecharModalPesquisa.addEventListener(
'click',
function() {

modalPesquisa.style.display =
'none';

}
);

}

if (
fecharModalAjuste &&
modalAjuste
) {

fecharModalAjuste.addEventListener(
'click',
function() {

modalAjuste.style.display =
'none';

}
);

}

function converterValorAjuste(valor) {

valor =
String(valor || '')
.trim()
.replace(/R\$/g, '')
.replace(/\s/g, '');

if (
valor.includes(',') &&
valor.includes('.')
) {

valor =
valor
.replace(/\./g, '')
.replace(',', '.');

} else {

valor =
valor.replace(',', '.');

}

const numero =
parseFloat(valor);

return isNaN(numero)
? 0
: numero;

}

function formatarValorAjuste(valor) {

return Number(valor)
.toFixed(2)
.replace('.', ',');

}

const botoesAjustar =
document.querySelectorAll(
'.btn-ajustar-operacao'
);

botoesAjustar.forEach(
function(botao) {

botao.addEventListener(
'click',
function() {

if (!modalAjuste) {
return;
}

if (modalCadastro) {
modalCadastro.style.display =
'none';
}

if (modalPesquisa) {
modalPesquisa.style.display =
'none';
}

document.getElementById(
'ajuste_id'
).value =
this.dataset.id;

document.getElementById(
'ajuste_data'
).value =
this.dataset.data;

document.getElementById(
'ajuste_acao'
).value =
this.dataset.acao;

document.getElementById(
'ajuste_quantidade'
).value =
this.dataset.quantidade;

const valorCompra =
parseFloat(
this.dataset.valorCompra
) || 0;

const valorVenda =
parseFloat(
this.dataset.valorVenda
) || 0;

const totalCompra =
parseFloat(
this.dataset.totalCompra
) || 0;

const totalVenda =
parseFloat(
this.dataset.totalVenda
) || 0;

const taxas =
parseFloat(
this.dataset.taxas
) || 0;

document.getElementById(
'ajuste_valor_compra'
).value =
valorCompra > 0
? 'R$ ' +
formatarValorAjuste(
valorCompra
)
: '-';

document.getElementById(
'ajuste_valor_venda'
).value =
valorVenda > 0
? 'R$ ' +
formatarValorAjuste(
valorVenda
)
: '-';

document.getElementById(
'ajuste_total_compra'
).value =
formatarValorAjuste(
totalCompra
);

document.getElementById(
'ajuste_total_venda'
).value =
formatarValorAjuste(
totalVenda
);

const camposTaxas =
document.querySelectorAll(
'.ajuste-taxa-individual'
);

let somaPercentuais = 0;

camposTaxas.forEach(
function(campo) {

somaPercentuais +=
parseFloat(
campo.dataset.percentual
) || 0;

}
);

let taxasDistribuidas = 0;

camposTaxas.forEach(
function(campo, indice) {

const percentual =
parseFloat(
campo.dataset.percentual
) || 0;

let valorTaxa = 0;

if (
camposTaxas.length === 1
) {

valorTaxa =
taxas;

} else if (
indice ===
camposTaxas.length - 1
) {

valorTaxa =
taxas -
taxasDistribuidas;

} else if (
somaPercentuais > 0
) {

valorTaxa =
taxas *
(
percentual /
somaPercentuais
);

valorTaxa =
Math.round(
valorTaxa * 100
) / 100;

taxasDistribuidas +=
valorTaxa;

}

if (valorTaxa < 0) {
valorTaxa = 0;
}

campo.value =
formatarValorAjuste(
valorTaxa
);

}
);

document.getElementById(
'ajuste_taxas_total'
).value =
taxas.toFixed(2);

modalAjuste.style.display =
'block';

}
);

}
);

const formAjustarOperacao =
document.getElementById(
'form-ajustar-operacao'
);

if (formAjustarOperacao) {

formAjustarOperacao.addEventListener(
'submit',
function() {

const camposTaxas =
document.querySelectorAll(
'.ajuste-taxa-individual'
);

let totalTaxas = 0;

camposTaxas.forEach(
function(campo) {

totalTaxas +=
converterValorAjuste(
campo.value
);

}
);

totalTaxas =
Math.round(
totalTaxas * 100
) / 100;

document.getElementById(
'ajuste_taxas_total'
).value =
totalTaxas.toFixed(2);

}
);

}

window.addEventListener(
'click',
function(event) {

if (
modalCadastro &&
event.target === modalCadastro
) {

modalCadastro.style.display =
'none';

}

if (
modalPesquisa &&
event.target === modalPesquisa
) {

modalPesquisa.style.display =
'none';

}

if (
modalAjuste &&
event.target === modalAjuste
) {

modalAjuste.style.display =
'none';

}

}
);

document.addEventListener(
'keydown',
function(event) {

if (event.key === 'Escape') {

if (modalCadastro) {
modalCadastro.style.display =
'none';
}

if (modalPesquisa) {
modalPesquisa.style.display =
'none';
}

if (modalAjuste) {
modalAjuste.style.display =
'none';
}

}

}
);

const formOperacao =
document.getElementById(
'form-operacao'
);

if (formOperacao) {

formOperacao.addEventListener(
'submit',
function(event) {

const compra =
document.getElementById(
'valor_compra'
).value.trim();

const venda =
document.getElementById(
'valor_venda'
).value.trim();

if (
compra === '' &&
venda === ''
) {

event.preventDefault();

alert(
'Informe pelo menos o valor da compra ou o valor da venda.'
);

}

}
);

}
</script>

</body>
</html>