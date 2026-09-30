<?php

require_once __DIR__ . '/../../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login/login.php");
    exit;
}

$usuario_id = mcfDonoId();

$anoAtual = (int)date('Y');

$ano = filter_input(
    INPUT_GET,
    'ano',
    FILTER_VALIDATE_INT
);

if (
    !$ano ||
    $ano < 2000 ||
    $ano > 2100
) {
    $ano = $anoAtual;
}

$meses = [
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

$tipos = [
    'decimo_terceiro' => '13º salário',
    'ferias' => 'Férias',
    'restituicao_ir' => 'Restituição de IR',
    'servico_extra' => 'Serviço extra',
    'dividendo' => 'Dividendos',
    'day_trade' => 'Day Trade',
    'outro' => 'Outro'
];

$stmt = $conn->prepare("
    SELECT
        mes,
        meta,
        conseguido,
        observacao,
        fechado_em,
        criado_em
    FROM metas_fechamentos
    WHERE usuario_id = ?
      AND ano = ?
    ORDER BY mes ASC
");

$stmt->bind_param(
    'ii',
    $usuario_id,
    $ano
);

$stmt->execute();

$fechamentos = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$stmt = $conn->prepare("
    SELECT
        id,
        data,
        tipo,
        descricao,
        valor_recebido,
        valor_guardado,
        percentual_guardado,
        observacao,
        criado_em
    FROM metas_contribuicoes
    WHERE usuario_id = ?
      AND YEAR(data) = ?
    ORDER BY data ASC, id ASC
");

$stmt->bind_param(
    'ii',
    $usuario_id,
    $ano
);

$stmt->execute();

$contribuicoes = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$totalFechamentos = 0;
$totalContribuicoes = 0;

$evolucaoMeses = [];

for ($mes = 1; $mes <= 12; $mes++) {
    $evolucaoMeses[$mes] = [
        'fechamentos' => 0,
        'contribuicoes' => 0,
        'total' => 0,
        'acumulado' => 0
    ];
}

$historico = [];

foreach ($fechamentos as $fechamento) {

    $mes =
        (int)$fechamento['mes'];

    $valor =
        (float)$fechamento['conseguido'];

    $totalFechamentos +=
        $valor;

    $evolucaoMeses[$mes]['fechamentos'] +=
        $valor;

    $dataHistorico = sprintf(
        '%04d-%02d-01',
        $ano,
        $mes
    );

    $historico[] = [
        'data' => $dataHistorico,
        'ordem' => 1,
        'tipo' => 'Fechamento mensal',
        'descricao' => 'Fechamento de ' . $meses[$mes],
        'valor' => $valor,
        'observacao' => (string)($fechamento['observacao'] ?? '')
    ];
}

foreach ($contribuicoes as $contribuicao) {

    $valor =
        (float)$contribuicao['valor_guardado'];

    $mes =
        (int)date(
            'n',
            strtotime(
                $contribuicao['data']
            )
        );

    $totalContribuicoes +=
        $valor;

    $evolucaoMeses[$mes]['contribuicoes'] +=
        $valor;

    $rotuloTipo =
        $tipos[$contribuicao['tipo']]
        ?? $contribuicao['tipo'];

    $historico[] = [
        'data' => $contribuicao['data'],
        'ordem' => 2,
        'tipo' => $rotuloTipo,
        'descricao' => $contribuicao['descricao'],
        'valor' => $valor,
        'observacao' => (string)($contribuicao['observacao'] ?? '')
    ];
}

usort(
    $historico,
    function ($a, $b) {

        $comparacao =
            strcmp(
                $a['data'],
                $b['data']
            );

        if ($comparacao !== 0) {
            return $comparacao;
        }

        return $a['ordem']
            <=> $b['ordem'];
    }
);

$totalAcumulado =
    $totalFechamentos +
    $totalContribuicoes;

$acumuladoMes = 0;

foreach ($evolucaoMeses as $mes => &$dadosMes) {

    $dadosMes['total'] =
        $dadosMes['fechamentos'] +
        $dadosMes['contribuicoes'];

    $acumuladoMes +=
        $dadosMes['total'];

    $dadosMes['acumulado'] =
        $acumuladoMes;
}

unset($dadosMes);

$acumuladoHistorico = 0;

foreach ($historico as &$movimento) {

    $acumuladoHistorico +=
        $movimento['valor'];

    $movimento['acumulado'] =
        $acumuladoHistorico;
}

unset($movimento);

$quantidadeMovimentos =
    count($historico);

$mesesComMovimento = 0;

foreach ($evolucaoMeses as $dadosMes) {
    if ($dadosMes['total'] > 0) {
        $mesesComMovimento++;
    }
}

$cssPagina = "/MyCashFlow/assets/css/reservas/style-reservas.css";

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/menu.php';

?>

<main class="reservas-layout">

    <div class="reservas-cabecalho">

        <div>

            <h2 class="mcf-page-title">
                Histórico
            </h2>

            <p>
                Acompanhe como a reserva evoluiu ao longo do ano.
            </p>

        </div>

        <form
            method="get"
            class="reservas-filtro-ano"
        >

            <label for="ano">
                Ano
            </label>

            <select
                id="ano"
                name="ano"
                onchange="this.form.submit()"
            >

                <?php
                for (
                    $a = $anoAtual + 1;
                    $a >= $anoAtual - 5;
                    $a--
                ):
                ?>

                    <option
                        value="<?= $a ?>"
                        <?= $a === $ano ? 'selected' : '' ?>
                    >
                        <?= $a ?>
                    </option>

                <?php endfor; ?>

            </select>

        </form>

    </div>

    <?php
    include __DIR__ .
        '/../../includes/reservas_navegacao.php';
    ?>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Resumo de <?= $ano ?>
                </h2>

                <span>
                    Composição do total acumulado no período.
                </span>

            </div>

        </div>

        <div class="reservas-resumo">

            <article
                class="reservas-card reservas-card-principal"
            >

                <span>
                    Total acumulado
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalAcumulado,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    <?= $quantidadeMovimentos ?>
                    movimento<?= $quantidadeMovimentos === 1 ? '' : 's' ?>
                    em <?= $mesesComMovimento ?>
                    <?= $mesesComMovimento === 1 ? 'mês' : 'meses' ?>
                </small>

            </article>

            <article class="reservas-card">

                <span>
                    Fechamentos mensais
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalFechamentos,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Valores efetivamente conseguidos nos fechamentos
                </small>

            </article>

            <article class="reservas-card">

                <span>
                    Contribuições adicionais
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalContribuicoes,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Somente valores efetivamente guardados
                </small>

            </article>

        </div>

    </section>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Evolução mensal
                </h2>

                <span>
                    Fechamentos, contribuições e acumulado por mês.
                </span>

            </div>

        </div>

        <div class="reservas-tabela-wrapper">

            <table class="reservas-tabela">

                <thead>

                    <tr>
                        <th>Mês</th>
                        <th>Fechamentos</th>
                        <th>Contribuições</th>
                        <th>Total do mês</th>
                        <th>Acumulado</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($evolucaoMeses as $mes => $dadosMes): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= $meses[$mes] ?>
                                </strong>
                            </td>

                            <td>
                                R$
                                <?= number_format(
                                    $dadosMes['fechamentos'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                R$
                                <?= number_format(
                                    $dadosMes['contribuicoes'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td class="<?= $dadosMes['total'] > 0 ? 'positivo' : '' ?>">
                                <strong>
                                    R$
                                    <?= number_format(
                                        $dadosMes['total'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </strong>
                            </td>

                            <td>
                                <strong>
                                    R$
                                    <?= number_format(
                                        $dadosMes['acumulado'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </strong>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Movimentações
                </h2>

                <span>
                    Origem cronológica da evolução da reserva.
                </span>

            </div>

        </div>

        <div class="reservas-tabela-wrapper">

            <table class="reservas-tabela">

                <thead>

                    <tr>
                        <th>Data</th>
                        <th>Origem</th>
                        <th>Descrição</th>
                        <th>Valor acrescentado</th>
                        <th>Acumulado</th>
                        <th>Observação</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!$historico): ?>

                        <tr>

                            <td colspan="6">
                                Nenhuma movimentação registrada em <?= $ano ?>.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($historico as $movimento): ?>

                            <tr>

                                <td>
                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $movimento['data']
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $movimento['tipo'],
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $movimento['descricao'],
                                            ENT_QUOTES | ENT_SUBSTITUTE,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td class="positivo">
                                    <strong>
                                        + R$
                                        <?= number_format(
                                            $movimento['valor'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    R$
                                    <?= number_format(
                                        $movimento['acumulado'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $movimento['observacao'] !== ''
                                        ? htmlspecialchars(
                                            $movimento['observacao'],
                                            ENT_QUOTES | ENT_SUBSTITUTE,
                                            'UTF-8'
                                        )
                                        : '—'
                                    ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php
include __DIR__ . '/../../includes/footer.php';
?>