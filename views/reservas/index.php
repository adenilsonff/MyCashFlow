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

function reservasPercentualMeta(
    float $valor,
    float $meta
): float {
    if ($meta <= 0) {
        return 0;
    }

    return ($valor / $meta) * 100;
}

function reservasLimitarPercentual(
    float $percentual
): float {
    return min(
        max(
            $percentual,
            0
        ),
        100
    );
}

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

$stmt = $conn->prepare("
    SELECT
        id,
        meta_mensal,
        meta_basica,
        referencia_pontuais,
        meta_sonho,
        meta_desafio
    FROM metas_anuais
    WHERE usuario_id = ?
      AND ano = ?
    LIMIT 1
");

$stmt->bind_param(
    'ii',
    $usuario_id,
    $ano
);

$stmt->execute();

$metaAnual = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$metaAnual) {
    $metaAnual = [
        'id' => null,
        'meta_mensal' => 1000.00,
        'meta_basica' => 12000.00,
        'referencia_pontuais' => 0.00,
        'meta_sonho' => 50000.00,
        'meta_desafio' => 65000.00
    ];
}

$metaMensal =
    (float)$metaAnual['meta_mensal'];

$metaBasica =
    (float)$metaAnual['meta_basica'];

$referenciaPontuais =
    (float)$metaAnual['referencia_pontuais'];

$metaProjetada =
    $metaBasica +
    $referenciaPontuais;

$metaSonho =
    (float)$metaAnual['meta_sonho'];

$metaDesafio =
    (float)$metaAnual['meta_desafio'];

$fechamentos = [];

$stmt = $conn->prepare("
    SELECT
        mes,
        meta,
        conseguido,
        fechado_em
    FROM metas_fechamentos
    WHERE usuario_id = ?
      AND ano = ?
    ORDER BY mes
");

$stmt->bind_param(
    'ii',
    $usuario_id,
    $ano
);

$stmt->execute();

$resultado =
    $stmt->get_result();

while (
    $row =
    $resultado->fetch_assoc()
) {
    $fechamentos[
        (int)$row['mes']
    ] = $row;
}

$stmt->close();

$totalFechamentos = 0;

foreach (
    $fechamentos as $fechamento
) {
    $totalFechamentos +=
        (float)$fechamento['conseguido'];
}

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            SUM(valor_guardado),
            0
        ) AS total_guardado,
        COUNT(*) AS quantidade
    FROM metas_contribuicoes
    WHERE usuario_id = ?
      AND YEAR(data) = ?
");

$stmt->bind_param(
    'ii',
    $usuario_id,
    $ano
);

$stmt->execute();

$resumoContribuicoes = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

$totalContribuicoes =
    (float)(
        $resumoContribuicoes['total_guardado']
        ?? 0
    );

$quantidadeContribuicoes =
    (int)(
        $resumoContribuicoes['quantidade']
        ?? 0
    );

$totalGuardado =
    $totalFechamentos +
    $totalContribuicoes;

$progressoBasica =
    reservasPercentualMeta(
        $totalGuardado,
        $metaBasica
    );

$progressoProjetada =
    reservasPercentualMeta(
        $totalGuardado,
        $metaProjetada
    );

$progressoSonho =
    reservasPercentualMeta(
        $totalGuardado,
        $metaSonho
    );

$progressoDesafio =
    reservasPercentualMeta(
        $totalGuardado,
        $metaDesafio
    );

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

$cssPagina =
    "/MyCashFlow/assets/css/reservas/style-reservas.css";

include __DIR__ .
    '/../../includes/header.php';

include __DIR__ .
    '/../../includes/menu.php';

?>

<main class="reservas-layout">

    <div class="reservas-cabecalho">

        <div>

            <h2 class="mcf-page-title">
                Reservas e Metas
            </h2>

            <p>
                Acompanhe quanto foi efetivamente guardado e a evolução dos seus objetivos anuais.
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
                    Resultado de <?= $ano ?>
                </h2>

                <span>
                    Valores efetivamente destinados às suas reservas e metas.
                </span>

            </div>

        </div>

        <div class="reservas-resumo">

            <article
                class="reservas-card reservas-card-principal"
            >

                <span>
                    Total guardado
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalGuardado,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    Fechamentos + contribuições adicionais
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
                    Valores efetivamente conseguidos
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
                    <?= $quantidadeContribuicoes ?>
                    <?= $quantidadeContribuicoes === 1
                        ? 'registro'
                        : 'registros'
                    ?>
                </small>

            </article>

        </div>

    </section>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Metas do ano
                </h2>

                <span>
                    Os quatro patamares utilizam o mesmo total guardado.
                </span>

            </div>

            <a
                href="metas.php?ano=<?= $ano ?>"
                class="reservas-link"
            >
                Ver metas
            </a>

        </div>

        <div class="reservas-metas-grid">

            <article class="reservas-meta">

                <div class="reservas-meta-topo">

                    <div>

                        <span>
                            Meta básica
                        </span>

                        <strong>
                            R$
                            <?= number_format(
                                $metaBasica,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                    </div>

                    <b>
                        <?= number_format(
                            $progressoBasica,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">

                    <span
                        style="width: <?= reservasLimitarPercentual(
                            $progressoBasica
                        ) ?>%;"
                    ></span>

                </div>

                <small class="reservas-meta-info">
                    R$
                    <?= number_format(
                        $metaMensal,
                        2,
                        ',',
                        '.'
                    ) ?>
                    por mês × 12
                </small>

            </article>

            <article class="reservas-meta">

                <div class="reservas-meta-topo">

                    <div>

                        <span>
                            Meta projetada
                        </span>

                        <strong>
                            R$
                            <?= number_format(
                                $metaProjetada,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                    </div>

                    <b>
                        <?= number_format(
                            $progressoProjetada,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">

                    <span
                        style="width: <?= reservasLimitarPercentual(
                            $progressoProjetada
                        ) ?>%;"
                    ></span>

                </div>

                <small class="reservas-meta-info">
                    Básica + R$
                    <?= number_format(
                        $referenciaPontuais,
                        2,
                        ',',
                        '.'
                    ) ?>
                    de referências pontuais
                </small>

            </article>

            <article class="reservas-meta">

                <div class="reservas-meta-topo">

                    <div>

                        <span>
                            Meta sonho
                        </span>

                        <strong>
                            R$
                            <?= number_format(
                                $metaSonho,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                    </div>

                    <b>
                        <?= number_format(
                            $progressoSonho,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">

                    <span
                        style="width: <?= reservasLimitarPercentual(
                            $progressoSonho
                        ) ?>%;"
                    ></span>

                </div>

                <small class="reservas-meta-info">
                    Nível ampliado de acumulação anual
                </small>

            </article>

            <article class="reservas-meta">

                <div class="reservas-meta-topo">

                    <div>

                        <span>
                            Meta desafio
                        </span>

                        <strong>
                            R$
                            <?= number_format(
                                $metaDesafio,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </strong>

                    </div>

                    <b>
                        <?= number_format(
                            $progressoDesafio,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">

                    <span
                        style="width: <?= reservasLimitarPercentual(
                            $progressoDesafio
                        ) ?>%;"
                    ></span>

                </div>

                <small class="reservas-meta-info">
                    Maior nível definido para <?= $ano ?>
                </small>

            </article>

        </div>

    </section>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Fechamento mensal
                </h2>

                <span>
                    Meta mensal versus valor efetivamente guardado.
                </span>

            </div>

            <a
                href="fechamento.php?ano=<?= $ano ?>"
                class="reservas-link"
            >
                Ver fechamento
            </a>

        </div>

        <div class="reservas-tabela-wrapper">

            <table class="reservas-tabela">

                <thead>

                    <tr>
                        <th>Mês</th>
                        <th>Meta</th>
                        <th>Conseguido</th>
                        <th>Diferença</th>
                        <th>Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach (
                        $meses as
                        $numeroMes => $nomeMes
                    ): ?>

                        <?php

                        $registro =
                            $fechamentos[$numeroMes]
                            ?? null;

                        $metaMes =
                            $registro
                                ? (float)$registro['meta']
                                : $metaMensal;

                        $conseguido =
                            $registro
                                ? (float)$registro['conseguido']
                                : 0;

                        $diferenca =
                            $conseguido -
                            $metaMes;

                        ?>

                        <tr>

                            <td>
                                <?= $nomeMes ?>
                            </td>

                            <td>
                                R$
                                <?= number_format(
                                    $metaMes,
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                R$
                                <?= number_format(
                                    $conseguido,
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td class="<?= $diferenca >= 0
                                ? 'positivo'
                                : 'negativo'
                            ?>">

                                <?= $diferenca >= 0
                                    ? '+'
                                    : '-'
                                ?>

                                R$
                                <?= number_format(
                                    abs($diferenca),
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </td>

                            <td>

                                <?php if (!$registro): ?>

                                    <span
                                        class="reservas-status pendente"
                                    >
                                        Pendente
                                    </span>

                                <?php elseif (
                                    $conseguido >=
                                    $metaMes
                                ): ?>

                                    <span
                                        class="reservas-status atingida"
                                    >
                                        Meta atingida
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="reservas-status parcial"
                                    >
                                        Abaixo da meta
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php
include __DIR__ . '/../../includes/footer.php';
?>