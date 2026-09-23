<?php
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

$menuAtual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$menuEhExtra = $menuAtual === '/MyCashFlow/views/rendas.php' && ($_GET['classificacao'] ?? '') === 'extra';
$menuBase = '/MyCashFlow/views/';
$menuItens = [
    ['Início', 'dashboard.php', [], ['index.php', '']],
    ['Patrimônio', 'patrimonio.php', [
        ['Contas e saldos', 'saldos.php']
    ], []],
    ['Despesas', 'contas.php', [
        ['Cartão de crédito', 'cartao.php'],
        ['Relatório de despesas', 'relatorios/gastos.php']
    ], ['relatorios/cartao.php']],
    ['Receitas', 'rendas.php', [
        ['Rendas extras', 'rendas.php?classificacao=extra'],
        ['Relatório de receitas', 'relatorios/receitas.php']
    ], []],
    ['Investimentos', 'investimentos.php', [
        ['Dividendos', 'dividendos.php'],
        ['Day trade', 'daytrade.php']
    ], ['investimentos_nacionais.php', 'investimentos_internacionais.php',
        'div_datacom.php', 'div_valor.php', 'div_compra.php',
        'daytrade/editar_corretora.php']],
    ['Análise', 'analise.php', [], []],
    ['Relatórios', 'relatorios/relatorios.php', [], [
        'relatorios/financeiro.php', 'relatorios/investimentos.php',
        'relatorios/proventos.php', 'relatorios/daytrade.php', 'relatorios/rel-acoes.php'
    ]],
    ['Configuração', 'configuracao.php', [['Meu perfil', 'perfil.php'], ['Compartilhamento', 'compartilhamento.php'], ['Histórico dos meus dados', 'historico.php'], ['Avisos', 'avisos.php']], ['compartilhado.php']],
    ['Sair', 'login/logout.php', [], []]
];
$menuEscape = static function ($valor) {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
};
?>
<button id="mcf-mobile-toggle" type="button" aria-expanded="false" aria-controls="mcf-menu" hidden>☰ Menu</button>
<nav id="mcf-menu" aria-label="Navegação principal">
<?php foreach ($menuItens as $menuIndice => [$menuRotulo, $menuDestino, $menuFilhos, $menuRelacionadas]):
    $menuPaginaAtiva = $menuAtual === $menuBase . $menuDestino && !($menuDestino === 'rendas.php' && $menuEhExtra);
    if ($menuDestino === 'dashboard.php' && in_array($menuAtual, ['/MyCashFlow/', '/MyCashFlow/index.php'], true)) {
        $menuPaginaAtiva = true;
    }
    $menuGrupoAtivo = $menuPaginaAtiva || ($menuDestino === 'rendas.php' && $menuEhExtra);
    foreach (array_merge(array_column($menuFilhos, 1), $menuRelacionadas) as $menuRelacionada) {
        $menuGrupoAtivo = $menuGrupoAtivo || $menuAtual === $menuBase . $menuRelacionada;
    }
?>
    <div class="mcf-menu-item<?= $menuGrupoAtivo ? ' mcf-menu-active' : '' ?>">
        <div class="mcf-menu-row">
            <a data-mcf-own href="<?= $menuEscape($menuBase . $menuDestino) ?>"<?= $menuPaginaAtiva ? ' aria-current="page"' : '' ?>><?= $menuEscape($menuRotulo) ?></a>
            <?php if ($menuFilhos): ?>
            <button type="button" class="mcf-menu-toggle" aria-expanded="false" aria-controls="mcf-submenu-<?= $menuIndice ?>" aria-label="Submenus de <?= $menuEscape($menuRotulo) ?>"><span aria-hidden="true">▾</span></button>
            <?php endif; ?>
        </div>
        <?php if ($menuFilhos): ?>
        <div class="mcf-submenu" id="mcf-submenu-<?= $menuIndice ?>" hidden>
            <?php foreach ($menuFilhos as [$menuFilhoRotulo, $menuFilhoDestino]): ?>
            <a data-mcf-own href="<?= $menuEscape($menuBase . $menuFilhoDestino) ?>"<?= ($menuFilhoDestino === 'rendas.php?classificacao=extra' ? $menuEhExtra : $menuAtual === $menuBase . $menuFilhoDestino) ? ' aria-current="page"' : '' ?>><?= $menuEscape($menuFilhoRotulo) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</nav>
<script>
(() => {
    const menu = document.getElementById('mcf-menu');
    const mobileButton = document.getElementById('mcf-mobile-toggle');
    const mobile = window.matchMedia('(max-width: 768px)');
    mobileButton.hidden = false;
    menu.classList.add('mcf-enhanced');
    function closeMobile() {
        menu.classList.remove('mcf-mobile-open');
        mobileButton.setAttribute('aria-expanded', 'false');
    }
    mobileButton.addEventListener('click', () => {
        const opening = mobileButton.getAttribute('aria-expanded') !== 'true';
        menu.classList.toggle('mcf-mobile-open', opening);
        mobileButton.setAttribute('aria-expanded', String(opening));
        if (!opening) closeMenus();
    });
    mobile.addEventListener('change', () => { closeMobile(); closeMenus(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !event.defaultPrevented && mobile.matches && menu.classList.contains('mcf-mobile-open')) {
            closeMobile();
            closeMenus();
            mobileButton.focus();
        }
    });
    const toggles = Array.from(menu.querySelectorAll('.mcf-menu-toggle'));
    function closeMenus() {
        toggles.forEach(button => {
            button.setAttribute('aria-expanded', 'false');
            document.getElementById(button.getAttribute('aria-controls')).hidden = true;
        });
    }
    toggles.forEach(button => {
        button.addEventListener('click', () => {
            const opening = button.getAttribute('aria-expanded') !== 'true';
            closeMenus();
            if (opening) {
                button.setAttribute('aria-expanded', 'true');
                document.getElementById(button.getAttribute('aria-controls')).hidden = false;
            }
        });
    });
    document.addEventListener('click', event => {
        if (!menu.contains(event.target)) closeMenus();
    });
    menu.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            const expanded = toggles.find(button => button.getAttribute('aria-expanded') === 'true');
            if (expanded) {
                closeMenus();
                expanded.focus();
                event.preventDefault();
            }
        }
    });
    menu.addEventListener('focusout', event => {
        if (!menu.contains(event.relatedTarget)) closeMenus();
    });
})();
</script>
