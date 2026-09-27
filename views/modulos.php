<?php
require_once __DIR__.'/../config.php';
$uid=mcfUsuarioId();
$modulos = mcfModulosDisponiveis();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_modulos'])) {
    $selecionados = isset($_POST['modulos']) && is_array($_POST['modulos'])
        ? $_POST['modulos']
        : [];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'INSERT INTO usuario_modulos (usuario_id, modulo, habilitado)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE habilitado = VALUES(habilitado)'
        );

        foreach ($modulos as $chave => $dados) {
            $habilitado = in_array($chave, $selecionados, true) ? 1 : 0;
            $stmt->bind_param('isi', $uid, $chave, $habilitado);
            $stmt->execute();
        }

        $stmt->close();
        $conn->commit();

        header('Location: modulos.php?modulos=salvos');
        exit;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

$estadoModulos = [];

$stmt = $conn->prepare(
    'SELECT modulo, habilitado
     FROM usuario_modulos
     WHERE usuario_id = ?'
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$resultadoModulos = $stmt->get_result();

while ($linha = $resultadoModulos->fetch_assoc()) {
    $estadoModulos[$linha['modulo']] = (int)$linha['habilitado'] === 1;
}

$stmt->close();

foreach ($modulos as $chave => $dados) {
    if (!array_key_exists($chave, $estadoModulos)) {
        $estadoModulos[$chave] = true;
    }
}


$cssPagina='/MyCashFlow/assets/css/perfil.css?v='.filemtime(__DIR__.'/../assets/css/perfil.css');
require __DIR__.'/../includes/header.php';
require __DIR__.'/../includes/menu.php';
?>
<main class="perfil conta-configuracao">
<a href="configuracao.php">← Configuração</a>
<section class="config-modulos">

<h2>Módulos do sistema</h2>

<p class="perfil-ajuda">
Escolha os módulos que deseja utilizar no MyCashFlow. Desativar um módulo não exclui seus dados.
</p>

<?php if (isset($_GET['modulos']) && $_GET['modulos'] === 'salvos'): ?>
<p class="perfil-sucesso">Preferências dos módulos salvas com sucesso.</p>
<?php endif; ?>

<form method="post">

<?= mcfCsrfField() ?>

<div class="modulos-lista">

<label class="modulo-opcao">
<input type="checkbox" name="modulos[]" value="patrimonio" <?= $estadoModulos['patrimonio'] ? 'checked' : '' ?>>
<span>
<strong>Patrimônio</strong>
<small>Contas, saldos e patrimônio financeiro.</small>
</span>
</label>

<label class="modulo-opcao">
<input type="checkbox" name="modulos[]" value="despesas" <?= $estadoModulos['despesas'] ? 'checked' : '' ?>>
<span>
<strong>Despesas</strong>
<small>Controle de contas e gastos.</small>
</span>
</label>

<label class="modulo-opcao modulo-filho">
<input type="checkbox" name="modulos[]" value="cartao" <?= $estadoModulos['cartao'] ? 'checked' : '' ?>>
<span>
<strong>Cartão de crédito</strong>
<small>Faturas, compras e parcelas.</small>
</span>
</label>

<label class="modulo-opcao">
<input type="checkbox" name="modulos[]" value="receitas" <?= $estadoModulos['receitas'] ? 'checked' : '' ?>>
<span>
<strong>Receitas</strong>
<small>Rendas e recebimentos.</small>
</span>
</label>

<label class="modulo-opcao">
<input type="checkbox" name="modulos[]" value="investimentos" <?= $estadoModulos['investimentos'] ? 'checked' : '' ?>>
<span>
<strong>Investimentos</strong>
<small>Carteiras nacionais e internacionais.</small>
</span>
</label>

<label class="modulo-opcao modulo-filho">
<input type="checkbox" name="modulos[]" value="dividendos" <?= $estadoModulos['dividendos'] ? 'checked' : '' ?>>
<span>
<strong>Dividendos</strong>
<small>Proventos e demonstrativos.</small>
</span>
</label>

<label class="modulo-opcao modulo-filho">
<input type="checkbox" name="modulos[]" value="daytrade" <?= $estadoModulos['daytrade'] ? 'checked' : '' ?>>
<span>
<strong>Day Trade</strong>
<small>Operações, corretoras e resultados.</small>
</span>
</label>

<label class="modulo-opcao">
<input type="checkbox" name="modulos[]" value="analise" <?= $estadoModulos['analise'] ? 'checked' : '' ?>>
<span>
<strong>Análise</strong>
<small>Análises e gráficos financeiros.</small>
</span>
</label>

<label class="modulo-opcao">
<input type="checkbox" name="modulos[]" value="relatorios" <?= $estadoModulos['relatorios'] ? 'checked' : '' ?>>
<span>
<strong>Relatórios</strong>
<small>Relatórios consolidados do MyCashFlow.</small>
</span>
</label>

</div>

<button type="submit" name="salvar_modulos" value="1">Salvar módulos</button>

</form>

</section>
</main>
<?php require __DIR__.'/../includes/footer.php'; ?>
