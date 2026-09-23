<?php if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; } ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MyCashFlow</title>
<link rel="stylesheet" href="/MyCashFlow/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
<?php if (!empty($cssPagina)): ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($cssPagina); if ($cssPagina === '/MyCashFlow/assets/css/compartilhamento.css') echo '?v='.filemtime(__DIR__.'/../assets/css/compartilhamento.css'); ?>">
<?php endif; ?>
<link rel="stylesheet" href="/MyCashFlow/assets/css/padrao.css?v=<?= filemtime(__DIR__.'/../assets/css/padrao.css') ?>">
</head>
<body>

<header>
<h1>MyCashFlow</h1>
<?php if (isset($_SESSION['usuario_id'])): require_once __DIR__.'/perfil.php'; ?>
<p><a data-mcf-own href="/MyCashFlow/views/perfil.php" class="mcf-profile-link"><?= htmlspecialchars(mcfRotuloUsuario(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a></p>
<?php endif; ?>
</header>
<?php if (isset($_SESSION['usuario_id'], $conn)):
require_once __DIR__.'/compartilhamento_melhorias.php'; $novosAvisos=count(mcfAvisos($conn)); ?>
<div class="mcf-account-links"><a data-mcf-own href="/MyCashFlow/views/avisos.php">Avisos de compartilhamento<?= $novosAvisos ? ' ('.$novosAvisos.($novosAvisos===1?' novo)':' novos)') : '' ?></a><a data-mcf-own href="/MyCashFlow/views/historico.php">Histórico dos meus dados</a></div>
<?php mcfSeletorConta($conn); endif; ?>
<?php if (!empty($GLOBALS['mcf_contexto'])): $contexto=$GLOBALS['mcf_contexto']; ?>
<aside class="mcf-owner-notice" role="status">
<strong>Gerenciando dados de <?= htmlspecialchars($contexto['nome'] ?: $contexto['email'],ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8') ?></strong>
<p>As alterações nesta página pertencem a <?= htmlspecialchars($contexto['email'],ENT_QUOTES,'UTF-8') ?>. Você continua conectado como <?= htmlspecialchars(mcfRotuloUsuario(),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8') ?>.</p>
<p>Permitido neste módulo: visualizar<?php foreach(['cadastrar','editar','excluir'] as $acaoPermitida) if(!empty($contexto[$acaoPermitida])) echo ', '.$acaoPermitida; ?>.</p>
<a data-mcf-own href="/MyCashFlow/views/compartilhamento.php">Voltar aos compartilhamentos</a> · <a data-mcf-own href="/MyCashFlow/views/dashboard.php">Voltar aos meus dados</a>
</aside>
<?php endif; ?>
