<?php
require_once __DIR__.'/../config.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!filter_var($_SESSION['usuario_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])) {
    header('Location: login/login.php'); exit;
}
$_SESSION['csrf_analise']=$_SESSION['csrf_analise']??bin2hex(random_bytes(32));
$csrf=$_SESSION['csrf_analise'];
session_write_close();
header('Cache-Control: no-store, private');
$cssPagina='/MyCashFlow/assets/css/style-analise.css?v=4';
include __DIR__.'/../includes/header.php';
include __DIR__.'/../includes/menu.php';
?>
<main class="analise" id="analise-app" data-user="<?= mcfUsuarioId() ?><?= !empty($GLOBALS['mcf_contexto']) ? ':dono:'.mcfDonoId() : '' ?>" data-compartilhamento="<?= (int)($GLOBALS['mcf_contexto']['id'] ?? 0) ?>" data-context-version="<?= (int)($GLOBALS['mcf_contexto']['versao'] ?? 0) ?>" data-csrf="<?= htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>">
<?php if (!mcfContextoPode($conn,'investimentos')): ?><p>Operações e preço médio estão ocultos: o titular ainda não autorizou consulta aos investimentos.</p><?php endif; ?>
  <div class="an-heading"><div><p class="an-eyebrow">SUA CARTEIRA EM PERSPECTIVA</p><h1>Análise</h1><p>Observe os preços, acompanhe suas operações e registre suas ideias.</p></div><span class="an-badge">Mercado brasileiro · BRL</span></div>
  <p id="an-feedback" class="an-feedback" role="status" aria-live="polite" hidden></p>
  <div class="an-layout">
    <aside class="an-panel an-assets"><h2>Meus ativos</h2>
      <label for="an-search">Buscar na lista</label><input id="an-search" type="search" placeholder="Código do ativo" maxlength="10">
      <label for="an-filter">Categoria</label><select id="an-filter"><option value="">Todos os ativos</option value="acao">Ações</option><option value="fii">FIIs</option><option value="etf">ETFs</option><option value="bdr">BDRs</option></select>
      <div id="an-assets-list" class="an-assets-list" aria-label="Ativos da carteira e acompanhamento"><p>Carregando seus ativos…</p></div>
      <details class="an-add"><summary>Acompanhar outro ativo</summary><form id="an-watch-form">
        <label for="an-watch-ticker">Código</label><input id="an-watch-ticker" name="ticker" placeholder="Ex.: PETR4" pattern="[A-Za-z0-9]{1,10}" maxlength="10" required>
        <label for="an-watch-type">Categoria</label><select id="an-watch-type" name="tipo_ativo"><option value="acao">Ação</option><option value="fii">FII</option><option value="etf">ETF</option><option value="bdr">BDR</option></select>
        <button class="an-primary" type="submit">Adicionar à lista</button><small>O histórico depende da cobertura da fonte. Isso não registra uma compra.</small>
      </form></details>
    </aside>
    <div class="an-main">
      <section class="an-panel an-chart-panel" aria-label="Gráfico do ativo">
        <div class="an-chart-heading"><div><p class="an-eyebrow" id="an-category">SELECIONE UM ATIVO</p><h2 id="an-symbol">Seu próximo olhar</h2></div><button id="an-unwatch" type="button" hidden>Retirar da lista</button></div>
        <div class="an-metrics"><div><span>Último fechamento exibido</span><strong id="an-close">—</strong></div><div><span>Preço médio atual</span><strong id="an-average">—</strong></div><div><span>Quantidade em carteira</span><strong id="an-quantity">—</strong></div></div>
        <div class="an-toolbar">
          <label>Intervalo<select id="an-interval"><option value="1d">Diário</option><option value="1wk">Semanal</option><option value="1mo">Mensal</option><option value="1m">1 minuto · consultar acesso</option><option value="5m">5 minutos · consultar acesso</option><option value="15m">15 minutos · consultar acesso</option><option value="60m">60 minutos · consultar acesso</option></select></label>
          <label>Histórico<select id="an-range"><option value="1mo">1 mês</option><option value="3mo" selected>3 meses</option><option value="5d">5 dias</option></select></label>
          <label>Exibição<select id="an-style"><option value="candles">Candles</option><option value="line">Linha</option></select></label>
          <button id="an-fit" type="button">Ajustar visão</button><button id="an-refresh" type="button">Atualizar</button>
        </div>
        <div class="an-toggles"><label><input id="an-show-average" type="checkbox" checked> Preço médio</label><label><input id="an-show-operations" type="checkbox" checked> Compras e vendas</label><label><input id="an-show-marks" type="checkbox" checked> Minhas linhas</label><span>Role para ampliar · arraste para navegar</span></div>
        <details class="an-ma-panel"><summary>Médias móveis</summary><p class="an-muted">Calculadas pelos fechamentos do intervalo selecionado. A MME começa pela média simples dos primeiros períodos disponíveis; pode diferir de fontes com mais histórico.</p><div id="an-averages"></div></details>
        <div class="an-drawing-toolbar"><button id="an-draw-line" type="button" aria-pressed="false" disabled>Desenhar linha</button><span id="an-drawing-hint">Desenhe uma linha ou arraste o botão ↕ de uma marcação. Esc cancela.</span></div>
        <p id="an-preferences-status" class="an-muted" role="status">Preferências, bloqueios e visibilidade são lembrados neste navegador, por usuário.</p>
        <div id="an-ohlc" class="an-ohlc" aria-live="off">Selecione um candle para consultar seus preços.</div>
        <div class="an-chart-wrap"><div id="an-chart" aria-label="Gráfico interativo de preços e volume"></div><div id="an-chart-empty" class="an-chart-empty">Escolha um ativo na lista ou adicione um código para começar.</div></div>
        <p id="an-source" class="an-source">Histórico fornecido pela BRAPI. Não é um feed de execução em tempo real.</p>
        <p class="an-disclaimer">Preço médio é o custo atual da sua posição. Eventos corporativos e ajustes da fonte podem afetar a comparação com compras antigas. Visões semanais e mensais usam somente os dias disponíveis; as extremidades podem ser parciais.</p>
      </section>
      <div class="an-bottom">
        <section class="an-panel"><div class="an-section-title"><h2>Minhas marcações</h2><button id="an-new" type="button">Nova marcação</button></div><p class="an-muted">Linhas e notas privadas. Diferenças calculadas pelo último fechamento exibido, não por uma cotação em tempo real.</p><div id="an-annotations"><p class="an-muted">Selecione um ativo.</p></div></section>
        <section class="an-panel"><h2>Operações registradas</h2><p class="an-muted" id="an-operations-help">Histórico do ativo na sua carteira.</p><div class="an-table-wrap"><table><thead><tr><th>Data</th><th>Operação</th><th>Qtd.</th><th>Preço</th></tr></thead><tbody id="an-operations"></tbody></table></div></section>
      </div>
      <p class="an-credit">Gráficos com <a href="https://www.tradingview.com/" target="_blank" rel="noopener noreferrer">TradingView Lightweight Charts™</a>. Copyright © 2025 TradingView, Inc. <a href="../assets/js/vendor/lightweight-charts-5.2.0/NOTICE">Avisos</a> · <a href="../assets/js/vendor/lightweight-charts-5.2.0/LICENSE">Licença</a>.</p>
    </div>
  </div>
  <dialog id="an-dialog"><form id="an-mark-form"><h2 id="an-dialog-title">Nova marcação</h2><p id="an-mark-target" class="an-muted"></p><input type="hidden" name="id" value="0"><input type="hidden" name="versao" value="0">
    <label for="an-kind">Tipo</label><select id="an-kind" name="tipo"><option value="linha">Linha horizontal</option><option value="nota">Anotação</option></select>
    <label for="an-title">Título</label><input id="an-title" name="titulo" maxlength="80" required placeholder="Ex.: Meu ponto de atenção">
    <div id="an-price-field"><label for="an-price">Preço (R$)</label><input id="an-price" name="preco" type="text" inputmode="decimal" maxlength="17" placeholder="Ex.: 32,50" required></div>
    <label for="an-color">Cor</label><input id="an-color" name="cor" type="color" value="#6366f1">
    <label for="an-note">Comentário</label><textarea id="an-note" name="texto" maxlength="2000" rows="4" placeholder="O que você deseja acompanhar?"></textarea>
    <p id="an-form-error" role="alert"></p><div class="an-dialog-actions"><button id="an-cancel" type="button">Cancelar</button><button class="an-primary" type="submit">Salvar marcação</button></div>
  </form></dialog>
</main>
<script src="../assets/js/vendor/lightweight-charts-5.2.0/lightweight-charts.standalone.production.js"></script>
<script src="../assets/js/analise-core.js?v=4"></script>
<script src="../assets/js/analise-drawing.js?v=4"></script>
<script src="../assets/js/analise-preferences.js?v=4"></script>
<script src="../assets/js/analise-averages.js?v=4"></script>
<script src="../assets/js/analise.js?v=<?= filemtime(__DIR__.'/../assets/js/analise.js') ?>"></script>
<?php include __DIR__.'/../includes/footer.php'; ?>
