<main class="acoes-layout">
    <h1><?= $relatorio['titulo'] ?></h1>
    <button onclick="window.print()" class="btn-imprimir">🖨️ Imprimir</button>

    <!-- KPIs -->
    <section class="kpis">
        <div class="kpi-card">
            <h3>Total Investido</h3>
            <p>R$ <?= number_format($relatorio['total_investido'], 2, ',', '.') ?></p>
        </div>
        <div class="kpi-card">
            <h3>Resultado Consolidado</h3>
            <p>
                <?php if ($relatorio['total_resultado'] >= 0) { ?>
                    <span class="positivo">+ R$ <?= number_format($relatorio['total_resultado'], 2, ',', '.') ?></span>
                <?php } else { ?>
                    <span class="negativo">- R$ <?= number_format(abs($relatorio['total_resultado']), 2, ',', '.') ?></span>
                <?php } ?>
            </p>
        </div>
    </section>

    <!-- Cards -->
    <div class="cards-container">
        <?php foreach($relatorio['dados'] as $acao) { ?>
            <div class="card-acao">
                <h3><?= $acao['ticker'] ?></h3>
                <p class="valor">Investido: R$ <?= number_format($acao['investido'], 2, ',', '.') ?></p>
                <p class="valor">Mercado: R$ <?= number_format($acao['mercado'], 2, ',', '.') ?></p>
                <p class="valor">
                    <?php if ($acao['resultado'] >= 0) { ?>
                        <span class="positivo">+ R$ <?= number_format($acao['resultado'], 2, ',', '.') ?></span>
                    <?php } else { ?>
                        <span class="negativo">- R$ <?= number_format(abs($acao['resultado']), 2, ',', '.') ?></span>
                    <?php } ?>
                </p>
            </div>
        <?php } ?>
    </div>

    <!-- Tabela detalhada -->
    <section class="tabela-detalhes">
        <h2>Histórico de Compras</h2>
        <table>
            <thead>
                <tr>
                    <th>Ticker</th>
                    <th>Data</th>
                    <th>Quantidade</th>
                    <th>Valor Unitário</th>
                    <th>Valor Mercado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($relatorio['dados'] as $acao) { ?>
                    <tr>
                        <td><?= $acao['ticker'] ?></td>
                        <td><?= date('d/m/Y', strtotime($acao['data'])) ?></td>
                        <td><?= $acao['quantidade'] ?></td>
                        <td>R$ <?= number_format($acao['valor_unitario'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($acao['valor_mercado'], 2, ',', '.') ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>
</main>
