<?php
$paginaReservas = basename($_SERVER['PHP_SELF']);
?>

<nav class="reservas-nav">
    <a href="index.php" class="<?= $paginaReservas === 'index.php' ? 'ativo' : '' ?>">
        Visão Geral
    </a>

    <a href="fechamento.php" class="<?= $paginaReservas === 'fechamento.php' ? 'ativo' : '' ?>">
        Fechamento
    </a>

    <a href="contribuicoes.php" class="<?= $paginaReservas === 'contribuicoes.php' ? 'ativo' : '' ?>">
        Contribuições
    </a>

    <a href="metas.php" class="<?= $paginaReservas === 'metas.php' ? 'ativo' : '' ?>">
        Metas
    </a>

    <a href="historico.php" class="<?= $paginaReservas === 'historico.php' ? 'ativo' : '' ?>">
        Histórico
    </a>
</nav>