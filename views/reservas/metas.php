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

if (empty($_SESSION['csrf_reservas'])) {
    $_SESSION['csrf_reservas'] = bin2hex(random_bytes(32));
}

$csrfReservas = $_SESSION['csrf_reservas'];

function metasNormalizarValor($valor): float {
    $valor = trim((string)$valor);

    if ($valor === '') {
        return 0;
    }

    $valor = str_replace(['R$', ' '], '', $valor);

    if (
        strpos($valor, ',') !== false &&
        strpos($valor, '.') !== false
    ) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } else {
        $valor = str_replace(',', '.', $valor);
    }

    return is_numeric($valor) ? (float)$valor : 0;
}

function metasRedirecionar(
    int $ano,
    string $mensagem,
    string $tipo = 'sucesso'
): void {
    header(
        'Location: metas.php?ano=' .
        $ano .
        '&msg=' .
        urlencode($mensagem) .
        '&tipo_msg=' .
        urlencode($tipo)
    );
    exit;
}

function metasPercentual(
    float $valor,
    float $meta
): float {
    if ($meta <= 0) {
        return 0;
    }

    return ($valor / $meta) * 100;
}

function metasPercentualBarra(
    float $valor,
    float $meta
): float {
    return min(
        100,
        max(
            0,
            metasPercentual(
                $valor,
                $meta
            )
        )
    );
}

$anoAtual = (int)date('Y');

$ano = filter_input(
    INPUT_GET,
    'ano',
    FILTER_VALIDATE_INT
);

if (!$ano) {
    $ano = filter_input(
        INPUT_POST,
        'ano',
        FILTER_VALIDATE_INT
    );
}

if (
    !$ano ||
    $ano < 2000 ||
    $ano > 2100
) {
    $ano = $anoAtual;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tokenReservas = $_POST['csrf_reservas'] ?? '';

    if (
        !is_string($tokenReservas) ||
        !hash_equals(
            $_SESSION['csrf_reservas'],
            $tokenReservas
        )
    ) {
        metasRedirecionar(
            $ano,
            'A sessão do formulário expirou. Tente novamente.',
            'erro'
        );
    }

    $acao = trim(
        $_POST['acao'] ?? ''
    );

    if ($acao === 'salvar_metas') {

        $metaMensal = metasNormalizarValor(
            $_POST['meta_mensal'] ?? 0
        );

        $referenciaPontuais = metasNormalizarValor(
            $_POST['referencia_pontuais'] ?? 0
        );

        $metaSonho = metasNormalizarValor(
            $_POST['meta_sonho'] ?? 0
        );

        $metaDesafio = metasNormalizarValor(
            $_POST['meta_desafio'] ?? 0
        );

        if ($metaMensal <= 0) {
            metasRedirecionar(
                $ano,
                'A meta mensal deve ser maior que zero.',
                'erro'
            );
        }

        if (
            $referenciaPontuais < 0 ||
            $metaSonho <= 0 ||
            $metaDesafio <= 0
        ) {
            metasRedirecionar(
                $ano,
                'Informe valores válidos para as metas.',
                'erro'
            );
        }

        $metaBasica =
            $metaMensal * 12;

        $metaProjetada =
            $metaBasica +
            $referenciaPontuais;

        if ($metaSonho < $metaProjetada) {
            metasRedirecionar(
                $ano,
                'A Meta Sonho não pode ser menor que a Meta Projetada.',
                'erro'
            );
        }

        if ($metaDesafio < $metaSonho) {
            metasRedirecionar(
                $ano,
                'A Meta Desafio não pode ser menor que a Meta Sonho.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            SELECT
                id
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

        $existente = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if ($existente) {

            $stmt = $conn->prepare("
                UPDATE metas_anuais
                SET
                    meta_mensal = ?,
                    meta_basica = ?,
                    referencia_pontuais = ?,
                    meta_sonho = ?,
                    meta_desafio = ?
                WHERE usuario_id = ?
                  AND ano = ?
            ");

            $stmt->bind_param(
                'dddddii',
                $metaMensal,
                $metaBasica,
                $referenciaPontuais,
                $metaSonho,
                $metaDesafio,
                $usuario_id,
                $ano
            );

        } else {

            $stmt = $conn->prepare("
                INSERT INTO metas_anuais
                (
                    usuario_id,
                    ano,
                    meta_mensal,
                    meta_basica,
                    referencia_pontuais,
                    meta_sonho,
                    meta_desafio
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'iiddddd',
                $usuario_id,
                $ano,
                $metaMensal,
                $metaBasica,
                $referenciaPontuais,
                $metaSonho,
                $metaDesafio
            );
        }

        if (!$stmt->execute()) {
            $stmt->close();

            metasRedirecionar(
                $ano,
                'Não foi possível salvar as metas.',
                'erro'
            );
        }

        $stmt->close();

        metasRedirecionar(
            $ano,
            'Metas atualizadas com sucesso.'
        );
    }
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

$metas = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

if (!$metas) {
    $metas = [
        'id' => null,
        'meta_mensal' => 1000.00,
        'meta_basica' => 12000.00,
        'referencia_pontuais' => 0.00,
        'meta_sonho' => 50000.00,
        'meta_desafio' => 65000.00
    ];
}

$metaMensal =
    (float)$metas['meta_mensal'];

$metaBasica =
    (float)$metas['meta_basica'];

$referenciaPontuais =
    (float)$metas['referencia_pontuais'];

$metaProjetada =
    $metaBasica +
    $referenciaPontuais;

$metaSonho =
    (float)$metas['meta_sonho'];

$metaDesafio =
    (float)$metas['meta_desafio'];

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(conseguido), 0) AS total
    FROM metas_fechamentos
    WHERE usuario_id = ?
      AND ano = ?
");

$stmt->bind_param(
    'ii',
    $usuario_id,
    $ano
);

$stmt->execute();

$resultado = $stmt
    ->get_result()
    ->fetch_assoc();

$totalFechamentos =
    (float)($resultado['total'] ?? 0);

$stmt->close();

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(valor_guardado), 0) AS total
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

$resultado = $stmt
    ->get_result()
    ->fetch_assoc();

$totalContribuicoes =
    (float)($resultado['total'] ?? 0);

$stmt->close();

$totalAcumulado =
    $totalFechamentos +
    $totalContribuicoes;

$percentualBasica =
    metasPercentual(
        $totalAcumulado,
        $metaBasica
    );

$percentualProjetada =
    metasPercentual(
        $totalAcumulado,
        $metaProjetada
    );

$percentualSonho =
    metasPercentual(
        $totalAcumulado,
        $metaSonho
    );

$percentualDesafio =
    metasPercentual(
        $totalAcumulado,
        $metaDesafio
    );

$msg = trim(
    $_GET['msg'] ?? ''
);

$tipoMsg = trim(
    $_GET['tipo_msg'] ?? 'sucesso'
);

$cssPagina = "/MyCashFlow/assets/css/reservas/style-reservas.css";

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/menu.php';

?>

<main class="reservas-layout">

    <div class="reservas-cabecalho">

        <div>

            <h2 class="mcf-page-title">
                Metas
            </h2>

            <p>
                Configure os níveis anuais e acompanhe o progresso acumulado.
            </p>

        </div>

        <div class="reservas-acoes">

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

            <button
                type="button"
                class="reservas-btn"
                id="editar-metas"
            >
                Configurar metas
            </button>

        </div>

    </div>

    <?php
    include __DIR__ .
        '/../../includes/reservas_navegacao.php';
    ?>

    <?php if ($msg !== ''): ?>

        <div
            class="reservas-mensagem <?= $tipoMsg === 'erro' ? 'erro' : 'sucesso' ?>"
        >
            <?= htmlspecialchars(
                $msg,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Progresso de <?= $ano ?>
                </h2>

                <span>
                    Todos os níveis utilizam o mesmo total acumulado.
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
                    Fechamentos + contribuições guardadas
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

            </article>

        </div>

    </section>

    <section class="reservas-secao">

        <div class="reservas-titulo">

            <div>

                <h2>
                    Níveis da meta
                </h2>

                <span>
                    O avanço ocorre sobre o mesmo valor acumulado no ano.
                </span>

            </div>

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
                            $percentualBasica,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">
                    <span
                        style="width: <?= metasPercentualBarra(
                            $totalAcumulado,
                            $metaBasica
                        ) ?>%;"
                    ></span>
                </div>

                <small class="reservas-meta-info">
                    R$ <?= number_format(
                        $metaMensal,
                        2,
                        ',',
                        '.'
                    ) ?> por mês × 12
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
                            $percentualProjetada,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">
                    <span
                        style="width: <?= metasPercentualBarra(
                            $totalAcumulado,
                            $metaProjetada
                        ) ?>%;"
                    ></span>
                </div>

                <small class="reservas-meta-info">
                    Básica + R$ <?= number_format(
                        $referenciaPontuais,
                        2,
                        ',',
                        '.'
                    ) ?> de referências pontuais
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
                            $percentualSonho,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">
                    <span
                        style="width: <?= metasPercentualBarra(
                            $totalAcumulado,
                            $metaSonho
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
                            $percentualDesafio,
                            1,
                            ',',
                            '.'
                        ) ?>%
                    </b>

                </div>

                <div class="reservas-progresso">
                    <span
                        style="width: <?= metasPercentualBarra(
                            $totalAcumulado,
                            $metaDesafio
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
                    Composição das metas
                </h2>

                <span>
                    Valores utilizados para formar os níveis do ano.
                </span>

            </div>

        </div>

        <div class="reservas-tabela-wrapper">

            <table class="reservas-tabela">

                <thead>

                    <tr>
                        <th>Componente</th>
                        <th>Valor</th>
                        <th>Regra</th>
                    </tr>

                </thead>

                <tbody>

                    <tr>

                        <td>
                            Meta mensal
                        </td>

                        <td>
                            R$
                            <?= number_format(
                                $metaMensal,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            Valor planejado para cada mês
                        </td>

                    </tr>

                    <tr>

                        <td>
                            Meta básica
                        </td>

                        <td>
                            R$
                            <?= number_format(
                                $metaBasica,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            Meta mensal × 12
                        </td>

                    </tr>

                    <tr>

                        <td>
                            Referências pontuais
                        </td>

                        <td>
                            R$
                            <?= number_format(
                                $referenciaPontuais,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            Referência para 13º, férias e outros recebimentos previstos
                        </td>

                    </tr>

                    <tr>

                        <td>
                            Meta projetada
                        </td>

                        <td>
                            R$
                            <?= number_format(
                                $metaProjetada,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            Meta básica + referências pontuais
                        </td>

                    </tr>

                    <tr>

                        <td>
                            Meta sonho
                        </td>

                        <td>
                            R$
                            <?= number_format(
                                $metaSonho,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            Nível anual ampliado
                        </td>

                    </tr>

                    <tr>

                        <td>
                            Meta desafio
                        </td>

                        <td>
                            R$
                            <?= number_format(
                                $metaDesafio,
                                2,
                                ',',
                                '.'
                            ) ?>
                        </td>

                        <td>
                            Maior nível anual
                        </td>

                    </tr>

                </tbody>

            </table>

        </div>

    </section>

</main>

<div
    class="reservas-modal"
    id="modal-metas"
    aria-hidden="true"
>

    <div class="reservas-modal-conteudo">

        <button
            type="button"
            class="reservas-modal-fechar"
            aria-label="Fechar"
        >
            &times;
        </button>

        <h2>
            Configurar metas de <?= $ano ?>
        </h2>

        <form
            method="post"
            class="reservas-form"
        >

            <input
                type="hidden"
                name="csrf_reservas"
                value="<?= htmlspecialchars(
                    $csrfReservas,
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="acao"
                value="salvar_metas"
            >

            <input
                type="hidden"
                name="ano"
                value="<?= $ano ?>"
            >

            <label for="meta-mensal">
                Meta mensal
            </label>

            <input
                type="text"
                id="meta-mensal"
                name="meta_mensal"
                inputmode="decimal"
                value="<?= number_format(
                    $metaMensal,
                    2,
                    ',',
                    ''
                ) ?>"
                required
            >

            <div class="reservas-aviso">
                A Meta Básica será calculada automaticamente multiplicando a Meta Mensal por 12.
            </div>

            <label for="referencia-pontuais">
                Referências pontuais
            </label>

            <input
                type="text"
                id="referencia-pontuais"
                name="referencia_pontuais"
                inputmode="decimal"
                value="<?= number_format(
                    $referenciaPontuais,
                    2,
                    ',',
                    ''
                ) ?>"
                required
            >

            <label for="meta-sonho">
                Meta sonho
            </label>

            <input
                type="text"
                id="meta-sonho"
                name="meta_sonho"
                inputmode="decimal"
                value="<?= number_format(
                    $metaSonho,
                    2,
                    ',',
                    ''
                ) ?>"
                required
            >

            <label for="meta-desafio">
                Meta desafio
            </label>

            <input
                type="text"
                id="meta-desafio"
                name="meta_desafio"
                inputmode="decimal"
                value="<?= number_format(
                    $metaDesafio,
                    2,
                    ',',
                    ''
                ) ?>"
                required
            >

            <div class="reservas-aviso">
                Meta Projetada = Meta Básica + Referências Pontuais. Meta Sonho e Meta Desafio são níveis superiores do mesmo total acumulado.
            </div>

            <button
                type="submit"
                class="reservas-btn"
            >
                Salvar metas
            </button>

            <?= mcfCsrfField() ?>

        </form>

    </div>

</div>

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal =
            document.getElementById(
                'modal-metas'
            );

        const abrir =
            document.getElementById(
                'editar-metas'
            );

        const fechar =
            modal.querySelector(
                '.reservas-modal-fechar'
            );

        function abrirModal() {

            modal.classList.add(
                'aberto'
            );

            modal.setAttribute(
                'aria-hidden',
                'false'
            );

            document
                .getElementById(
                    'meta-mensal'
                )
                .focus();
        }

        function fecharModal() {

            modal.classList.remove(
                'aberto'
            );

            modal.setAttribute(
                'aria-hidden',
                'true'
            );
        }

        abrir.addEventListener(
            'click',
            abrirModal
        );

        fechar.addEventListener(
            'click',
            fecharModal
        );

        modal.addEventListener(
            'click',
            function (event) {

                if (event.target === modal) {
                    fecharModal();
                }

            }
        );

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    modal.classList.contains(
                        'aberto'
                    )
                ) {
                    fecharModal();
                }

            }
        );

    }
);

</script>

<?php
include __DIR__ . '/../../includes/footer.php';
?>