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

function reservasNormalizarValor($valor): float {
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

function reservasRedirecionarFechamento(
    int $ano,
    string $mensagem,
    string $tipo = 'sucesso'
): void {
    header(
        'Location: fechamento.php?ano=' .
        $ano .
        '&msg=' .
        urlencode($mensagem) .
        '&tipo_msg=' .
        urlencode($tipo)
    );
    exit;
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

$stmt = $conn->prepare("
    SELECT
        meta_mensal
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

$configuracao = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

$metaMensalPadrao = $configuracao
    ? (float)$configuracao['meta_mensal']
    : 1000.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tokenReservas = $_POST['csrf_reservas'] ?? '';

    if (
        !is_string($tokenReservas) ||
        !hash_equals(
            $_SESSION['csrf_reservas'],
            $tokenReservas
        )
    ) {
        reservasRedirecionarFechamento(
            $ano,
            'A sessão do formulário expirou. Tente novamente.',
            'erro'
        );
    }

    $acao = trim($_POST['acao'] ?? '');

    if ($acao === 'salvar_fechamento') {

        $mes = filter_input(
            INPUT_POST,
            'mes',
            FILTER_VALIDATE_INT
        );

        $meta = reservasNormalizarValor(
            $_POST['meta'] ?? $metaMensalPadrao
        );

        $conseguido = reservasNormalizarValor(
            $_POST['conseguido'] ?? 0
        );

        $observacao = trim(
            $_POST['observacao'] ?? ''
        );

        if (
            !$mes ||
            $mes < 1 ||
            $mes > 12
        ) {
            reservasRedirecionarFechamento(
                $ano,
                'Mês inválido.',
                'erro'
            );
        }

        if (
            $meta < 0 ||
            $conseguido < 0
        ) {
            reservasRedirecionarFechamento(
                $ano,
                'Os valores não podem ser negativos.',
                'erro'
            );
        }

        if (
            mb_strlen(
                $observacao,
                'UTF-8'
            ) > 255
        ) {
            reservasRedirecionarFechamento(
                $ano,
                'A observação deve ter no máximo 255 caracteres.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            SELECT
                id
            FROM metas_fechamentos
            WHERE usuario_id = ?
              AND ano = ?
              AND mes = ?
            LIMIT 1
        ");

        $stmt->bind_param(
            'iii',
            $usuario_id,
            $ano,
            $mes
        );

        $stmt->execute();

        $existente = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        $fechadoEm = date('Y-m-d H:i:s');

        if ($existente) {

            $id = (int)$existente['id'];

            $stmt = $conn->prepare("
                UPDATE metas_fechamentos
                SET
                    meta = ?,
                    conseguido = ?,
                    observacao = ?,
                    fechado_em = ?
                WHERE id = ?
                  AND usuario_id = ?
            ");

            $stmt->bind_param(
                'ddssii',
                $meta,
                $conseguido,
                $observacao,
                $fechadoEm,
                $id,
                $usuario_id
            );

            if (!$stmt->execute()) {
                $stmt->close();

                reservasRedirecionarFechamento(
                    $ano,
                    'Não foi possível atualizar o fechamento.',
                    'erro'
                );
            }

            $stmt->close();

            reservasRedirecionarFechamento(
                $ano,
                'Fechamento atualizado com sucesso.'
            );

        } else {

            $stmt = $conn->prepare("
                INSERT INTO metas_fechamentos
                (
                    usuario_id,
                    ano,
                    mes,
                    meta,
                    conseguido,
                    observacao,
                    fechado_em
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'iiiddss',
                $usuario_id,
                $ano,
                $mes,
                $meta,
                $conseguido,
                $observacao,
                $fechadoEm
            );

            if (!$stmt->execute()) {
                $stmt->close();

                reservasRedirecionarFechamento(
                    $ano,
                    'Não foi possível registrar o fechamento.',
                    'erro'
                );
            }

            $stmt->close();

            reservasRedirecionarFechamento(
                $ano,
                'Fechamento registrado com sucesso.'
            );
        }
    }
}

$fechamentos = [];

$stmt = $conn->prepare("
    SELECT
        id,
        mes,
        meta,
        conseguido,
        observacao,
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

$resultado = $stmt->get_result();

while ($row = $resultado->fetch_assoc()) {
    $fechamentos[(int)$row['mes']] = $row;
}

$stmt->close();

$totalMeta = 0;
$totalConseguido = 0;
$mesesFechados = count($fechamentos);

for ($mes = 1; $mes <= 12; $mes++) {

    if (isset($fechamentos[$mes])) {

        $totalMeta +=
            (float)$fechamentos[$mes]['meta'];

        $totalConseguido +=
            (float)$fechamentos[$mes]['conseguido'];

    } else {

        $totalMeta += $metaMensalPadrao;
    }
}

$diferencaAnual =
    $totalConseguido -
    $totalMeta;

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
                Fechamento Mensal
            </h2>

            <p>
                Registre quanto foi efetivamente separado
                para guardar em cada mês.
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
                    Resumo de <?= $ano ?>
                </h2>

                <span>
                    Resultado considerando os
                    12 meses do ano.
                </span>

            </div>

        </div>

        <div class="reservas-resumo">

            <article class="reservas-card">

                <span>
                    Meta anual mensal
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalMeta,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

            </article>

            <article
                class="reservas-card reservas-card-principal"
            >

                <span>
                    Conseguido
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalConseguido,
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>

                <small>
                    <?= $mesesFechados ?>
                    de 12 meses registrados
                </small>

            </article>

            <article class="reservas-card">

                <span>
                    Diferença anual
                </span>

                <strong
                    class="<?= $diferencaAnual >= 0 ? 'positivo' : 'negativo' ?>"
                >

                    <?= $diferencaAnual >= 0 ? '+' : '-' ?>

                    R$
                    <?= number_format(
                        abs($diferencaAnual),
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
                    Fechamentos
                </h2>

                <span>
                    Um único fechamento por mês.
                </span>

            </div>

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
                        <th>Ação</th>
                    </tr>

                </thead>

                <tbody>

                    <?php
                    foreach (
                        $meses as
                        $numeroMes => $nomeMes
                    ):
                    ?>

                        <?php

                        $registro =
                            $fechamentos[$numeroMes]
                            ?? null;

                        $metaMes = $registro
                            ? (float)$registro['meta']
                            : $metaMensalPadrao;

                        $conseguido = $registro
                            ? (float)$registro['conseguido']
                            : 0;

                        $diferenca =
                            $conseguido -
                            $metaMes;

                        $observacao = $registro
                            ? (string)$registro['observacao']
                            : '';

                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?= $nomeMes ?>
                                </strong>
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

                            <td
                                class="<?= $diferenca >= 0 ? 'positivo' : 'negativo' ?>"
                            >

                                <?= $diferenca >= 0 ? '+' : '-' ?>

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
                                    $conseguido >= $metaMes
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

                            <td>

                                <button
                                    type="button"
                                    class="reservas-btn-secundario js-fechamento"
                                    data-mes="<?= $numeroMes ?>"
                                    data-nome-mes="<?= htmlspecialchars(
                                        $nomeMes,
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?>"
                                    data-meta="<?= number_format(
                                        $metaMes,
                                        2,
                                        ',',
                                        ''
                                    ) ?>"
                                    data-conseguido="<?= number_format(
                                        $conseguido,
                                        2,
                                        ',',
                                        ''
                                    ) ?>"
                                    data-observacao="<?= htmlspecialchars(
                                        $observacao,
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?>"
                                >
                                    <?= $registro
                                        ? 'Editar'
                                        : 'Registrar'
                                    ?>
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<div
    class="reservas-modal"
    id="modal-fechamento"
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
            Fechamento mensal
        </h2>

        <div
            class="reservas-modal-mes"
            id="modal-nome-mes"
        ></div>

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
                value="salvar_fechamento"
            >

            <input
                type="hidden"
                name="ano"
                value="<?= $ano ?>"
            >

            <input
                type="hidden"
                name="mes"
                id="fechamento-mes"
            >

            <label for="fechamento-meta">
                Meta do mês
            </label>

            <input
                type="text"
                id="fechamento-meta"
                name="meta"
                inputmode="decimal"
                required
            >

            <label for="fechamento-conseguido">
                Conseguido
            </label>

            <input
                type="text"
                id="fechamento-conseguido"
                name="conseguido"
                inputmode="decimal"
                required
            >

            <label for="fechamento-observacao">
                Observação
            </label>

            <textarea
                id="fechamento-observacao"
                name="observacao"
                maxlength="255"
                placeholder="Ex.: R$ 1.000 planejados + sobra do mês."
            ></textarea>

            <button
                type="submit"
                class="reservas-btn"
            >
                Salvar fechamento
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
                'modal-fechamento'
            );

        const campoMes =
            document.getElementById(
                'fechamento-mes'
            );

        const campoMeta =
            document.getElementById(
                'fechamento-meta'
            );

        const campoConseguido =
            document.getElementById(
                'fechamento-conseguido'
            );

        const campoObservacao =
            document.getElementById(
                'fechamento-observacao'
            );

        const nomeMes =
            document.getElementById(
                'modal-nome-mes'
            );

        function abrirModal(botao) {

            campoMes.value =
                botao.dataset.mes || '';

            campoMeta.value =
                botao.dataset.meta || '0,00';

            campoConseguido.value =
                botao.dataset.conseguido || '0,00';

            campoObservacao.value =
                botao.dataset.observacao || '';

            nomeMes.textContent =
                (botao.dataset.nomeMes || '') +
                ' de <?= $ano ?>';

            modal.classList.add(
                'aberto'
            );

            modal.setAttribute(
                'aria-hidden',
                'false'
            );

            campoConseguido.focus();
            campoConseguido.select();
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

        document
            .querySelectorAll(
                '.js-fechamento'
            )
            .forEach(
                function (botao) {

                    botao.addEventListener(
                        'click',
                        function () {
                            abrirModal(botao);
                        }
                    );

                }
            );

        modal
            .querySelector(
                '.reservas-modal-fechar'
            )
            .addEventListener(
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