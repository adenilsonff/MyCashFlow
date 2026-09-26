<?php
require_once __DIR__.'/../config.php';

$uid = mcfUsuarioId();
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

        header('Location: configuracao.php?modulos=salvos');
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

$s = $conn->prepare('SELECT email,status_assinatura,data_expiracao FROM usuarios WHERE id=?');
$s->bind_param('i', $uid);
$s->execute();
$conta = $s->get_result()->fetch_assoc();
$s->close();

$cssPagina = '/MyCashFlow/assets/css/perfil.css?v='.filemtime(__DIR__.'/../assets/css/perfil.css');

require __DIR__.'/../includes/header.php';
require __DIR__.'/../includes/menu.php';
?>

<main class="perfil conta-configuracao">

<h2>Minha conta</h2>
<p class="perfil-intro">Gerencie seus dados, permissões e as contas que você acompanha.</p>

<div class="conta-atalhos">

<a class="conta-atalho" href="perfil.php">
<strong>Meu perfil</strong>
<span>Edite seu nome, e-mail e senha.</span>
<span class="conta-atalho-acao">Editar perfil →</span>
</a>

<a class="conta-atalho" href="compartilhamento.php">
<strong>Compartilhamento</strong>
<span>Escolha quem pode acessar seus módulos e consulte os convites recebidos.</span>
<span class="conta-atalho-acao">Gerenciar permissões →</span>
</a>

<a class="conta-atalho" href="visao_conjunta.php">
<strong>Visão conjunta</strong>
<span>Acompanhe sua conta e os módulos compartilhados com você.</span>
<span class="conta-atalho-acao">Consultar contas →</span>
</a>

</div>

<section class="config-modulos">

<h3>Módulos do sistema</h3>

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

<section>

<h3>Dados da conta</h3>

<p>E-mail: <?= htmlspecialchars($conta['email'], ENT_QUOTES, 'UTF-8') ?></p>

<p>
Acesso válido até
<?= htmlspecialchars(date('d/m/Y', strtotime($conta['data_expiracao'])), ENT_QUOTES, 'UTF-8') ?>.
</p>

</section>

<section>

<h3>Dados e preferências</h3>

<p>Seus dados são privados por padrão. Você escolhe os módulos e se permite somente consulta ou também alterações. O acesso depende de convite aceito e pode ser revogado. A categoria “Conjunta” é apenas uma classificação e não concede acesso.</p>

<p>As preferências dos gráficos são salvas neste navegador separadamente para cada usuário. Cotações públicas de mercado podem utilizar um cache comum.</p>

<p>Para uma segunda conta, saia e use “Cadastre-se aqui” na tela de login. Cada conta começa sem dados financeiros.</p>

<p>A recuperação por e-mail ainda não está configurada. Para redefinir a senha, solicite ao responsável pela instalação.</p>

</section>

</main>

<?php require __DIR__.'/../includes/footer.php'; ?>