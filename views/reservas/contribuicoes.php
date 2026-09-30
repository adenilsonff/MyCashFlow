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

function contribuicoesNormalizarValor($valor): float {
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

function contribuicoesRedirecionar(
    int $ano,
    string $mensagem,
    string $tipo = 'sucesso'
): void {
    header(
        'Location: contribuicoes.php?ano=' .
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

$tipos = [
    'decimo_terceiro' => '13º salário',
    'ferias' => 'Férias',
    'restituicao_ir' => 'Restituição de IR',
    'servico_extra' => 'Serviço extra',
    'dividendo' => 'Dividendos',
    'day_trade' => 'Day Trade',
    'outro' => 'Outro'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tokenReservas = $_POST['csrf_reservas'] ?? '';

    if (
        !is_string($tokenReservas) ||
        !hash_equals(
            $_SESSION['csrf_reservas'],
            $tokenReservas
        )
    ) {
        contribuicoesRedirecionar(
            $ano,
            'A sessão do formulário expirou. Tente novamente.',
            'erro'
        );
    }

    $acao = trim($_POST['acao'] ?? '');

    if ($acao === 'salvar_contribuicao') {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        $data = trim(
            $_POST['data'] ?? ''
        );

        $tipo = trim(
            $_POST['tipo'] ?? ''
        );

        $descricao = trim(
            $_POST['descricao'] ?? ''
        );

        $valorRecebido = contribuicoesNormalizarValor(
            $_POST['valor_recebido'] ?? 0
        );

        $valorGuardado = contribuicoesNormalizarValor(
            $_POST['valor_guardado'] ?? 0
        );

        $observacao = trim(
            $_POST['observacao'] ?? ''
        );

        $dataObjeto = DateTime::createFromFormat(
            'Y-m-d',
            $data
        );

        $dataValida =
            $dataObjeto &&
            $dataObjeto->format('Y-m-d') === $data;

        if (!$dataValida) {
            contribuicoesRedirecionar(
                $ano,
                'Informe uma data válida.',
                'erro'
            );
        }

        if ((int)$dataObjeto->format('Y') !== $ano) {
            contribuicoesRedirecionar(
                $ano,
                'A data da contribuição deve pertencer ao ano selecionado.',
                'erro'
            );
        }

        if (!array_key_exists($tipo, $tipos)) {
            contribuicoesRedirecionar(
                $ano,
                'Tipo de contribuição inválido.',
                'erro'
            );
        }

        if ($descricao === '') {
            contribuicoesRedirecionar(
                $ano,
                'Informe a descrição da contribuição.',
                'erro'
            );
        }

        if (
            mb_strlen(
                $descricao,
                'UTF-8'
            ) > 150
        ) {
            contribuicoesRedirecionar(
                $ano,
                'A descrição deve ter no máximo 150 caracteres.',
                'erro'
            );
        }

        if (
            $valorRecebido < 0 ||
            $valorGuardado < 0
        ) {
            contribuicoesRedirecionar(
                $ano,
                'Os valores não podem ser negativos.',
                'erro'
            );
        }

        if ($valorRecebido <= 0) {
            contribuicoesRedirecionar(
                $ano,
                'O valor recebido deve ser maior que zero.',
                'erro'
            );
        }

        if ($valorGuardado > $valorRecebido) {
            contribuicoesRedirecionar(
                $ano,
                'O valor guardado não pode ser maior que o valor recebido.',
                'erro'
            );
        }

        if (
            mb_strlen(
                $observacao,
                'UTF-8'
            ) > 255
        ) {
            contribuicoesRedirecionar(
                $ano,
                'A observação deve ter no máximo 255 caracteres.',
                'erro'
            );
        }

        $percentualGuardado =
            ($valorGuardado / $valorRecebido) * 100;

        if ($id) {

            $stmt = $conn->prepare("
                SELECT
                    id
                FROM metas_contribuicoes
                WHERE id = ?
                  AND usuario_id = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                'ii',
                $id,
                $usuario_id
            );

            $stmt->execute();

            $existente = $stmt
                ->get_result()
                ->fetch_assoc();

            $stmt->close();

            if (!$existente) {
                contribuicoesRedirecionar(
                    $ano,
                    'Contribuição não encontrada.',
                    'erro'
                );
            }

            $stmt = $conn->prepare("
                UPDATE metas_contribuicoes
                SET
                    data = ?,
                    tipo = ?,
                    descricao = ?,
                    valor_recebido = ?,
                    valor_guardado = ?,
                    percentual_guardado = ?,
                    observacao = ?
                WHERE id = ?
                  AND usuario_id = ?
            ");

            $stmt->bind_param(
                'sssdddsii',
                $data,
                $tipo,
                $descricao,
                $valorRecebido,
                $valorGuardado,
                $percentualGuardado,
                $observacao,
                $id,
                $usuario_id
            );

            if (!$stmt->execute()) {
                $stmt->close();

                contribuicoesRedirecionar(
                    $ano,
                    'Não foi possível atualizar a contribuição.',
                    'erro'
                );
            }

            $stmt->close();

            contribuicoesRedirecionar(
                $ano,
                'Contribuição atualizada com sucesso.'
            );

        } else {

            $stmt = $conn->prepare("
                INSERT INTO metas_contribuicoes
                (
                    usuario_id,
                    data,
                    tipo,
                    descricao,
                    valor_recebido,
                    valor_guardado,
                    percentual_guardado,
                    observacao
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'isssddds',
                $usuario_id,
                $data,
                $tipo,
                $descricao,
                $valorRecebido,
                $valorGuardado,
                $percentualGuardado,
                $observacao
            );

            if (!$stmt->execute()) {
                $stmt->close();

                contribuicoesRedirecionar(
                    $ano,
                    'Não foi possível registrar a contribuição.',
                    'erro'
                );
            }

            $stmt->close();

            contribuicoesRedirecionar(
                $ano,
                'Contribuição registrada com sucesso.'
            );
        }
    }

    if ($acao === 'excluir_contribuicao') {

        $id = filter_input(
            INPUT_POST,
            'id',
            FILTER_VALIDATE_INT
        );

        if (!$id) {
            contribuicoesRedirecionar(
                $ano,
                'Contribuição inválida.',
                'erro'
            );
        }

        $stmt = $conn->prepare("
            DELETE FROM metas_contribuicoes
            WHERE id = ?
              AND usuario_id = ?
        ");

        $stmt->bind_param(
            'ii',
            $id,
            $usuario_id
        );

        if (!$stmt->execute()) {
            $stmt->close();

            contribuicoesRedirecionar(
                $ano,
                'Não foi possível excluir a contribuição.',
                'erro'
            );
        }

        $stmt->close();

        contribuicoesRedirecionar(
            $ano,
            'Contribuição excluída com sucesso.'
        );
    }
}

$stmt = $conn->prepare("
    SELECT
        id,
        data,
        tipo,
        descricao,
        valor_recebido,
        valor_guardado,
        percentual_guardado,
        origem_tabela,
        origem_id,
        observacao
    FROM metas_contribuicoes
    WHERE usuario_id = ?
      AND YEAR(data) = ?
    ORDER BY data DESC, id DESC
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

$totalRecebido = 0;
$totalGuardado = 0;

foreach ($contribuicoes as $contribuicao) {

    $totalRecebido +=
        (float)$contribuicao['valor_recebido'];

    $totalGuardado +=
        (float)$contribuicao['valor_guardado'];
}

$totalDisponivel =
    $totalRecebido -
    $totalGuardado;

$percentualTotal = $totalRecebido > 0
    ? ($totalGuardado / $totalRecebido) * 100
    : 0;

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
                Contribuições
            </h2>

            <p>
                Registre valores adicionais destinados às suas metas.
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
                id="nova-contribuicao"
            >
                Nova contribuição
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
                    Resumo de <?= $ano ?>
                </h2>

                <span>
                    Somente o valor guardado entra no progresso das metas.
                </span>

            </div>

        </div>

        <div class="reservas-resumo">

            <article class="reservas-card">

                <span>
                    Valores recebidos
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalRecebido,
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
                    Valores guardados
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
                    <?= number_format(
                        $percentualTotal,
                        1,
                        ',',
                        '.'
                    ) ?>% do recebido
                </small>

            </article>

            <article class="reservas-card">

                <span>
                    Valor não destinado
                </span>

                <strong>
                    R$
                    <?= number_format(
                        $totalDisponivel,
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
                    Contribuições registradas
                </h2>

                <span>
                    <?= count($contribuicoes) ?>
                    registro<?= count($contribuicoes) === 1 ? '' : 's' ?>
                    em <?= $ano ?>.
                </span>

            </div>

        </div>

        <div class="reservas-tabela-wrapper">

            <table class="reservas-tabela">

                <thead>

                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Recebido</th>
                        <th>Guardado</th>
                        <th>% guardado</th>
                        <th>Ações</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (!$contribuicoes): ?>

                        <tr>

                            <td colspan="7">
                                Nenhuma contribuição registrada em <?= $ano ?>.
                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($contribuicoes as $contribuicao): ?>

                            <?php

                            $tipoRegistro =
                                $contribuicao['tipo'];

                            $rotuloTipo =
                                $tipos[$tipoRegistro]
                                ?? $tipoRegistro;

                            ?>

                            <tr>

                                <td>
                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $contribuicao['data']
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $rotuloTipo,
                                        ENT_QUOTES | ENT_SUBSTITUTE,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $contribuicao['descricao'],
                                            ENT_QUOTES | ENT_SUBSTITUTE,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    R$
                                    <?= number_format(
                                        (float)$contribuicao['valor_recebido'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td class="positivo">
                                    R$
                                    <?= number_format(
                                        (float)$contribuicao['valor_guardado'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$contribuicao['percentual_guardado'],
                                        1,
                                        ',',
                                        '.'
                                    ) ?>%
                                </td>

                                <td>

                                    <div class="reservas-acoes">

                                        <button
                                            type="button"
                                            class="reservas-btn-secundario js-editar-contribuicao"
                                            data-id="<?= (int)$contribuicao['id'] ?>"
                                            data-data="<?= htmlspecialchars(
                                                $contribuicao['data'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-tipo="<?= htmlspecialchars(
                                                $tipoRegistro,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-descricao="<?= htmlspecialchars(
                                                $contribuicao['descricao'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            data-recebido="<?= number_format(
                                                (float)$contribuicao['valor_recebido'],
                                                2,
                                                ',',
                                                ''
                                            ) ?>"
                                            data-guardado="<?= number_format(
                                                (float)$contribuicao['valor_guardado'],
                                                2,
                                                ',',
                                                ''
                                            ) ?>"
                                            data-observacao="<?= htmlspecialchars(
                                                (string)$contribuicao['observacao'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >
                                            Editar
                                        </button>

                                        <form
                                            method="post"
                                            onsubmit="return confirm('Excluir esta contribuição?');"
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
                                                value="excluir_contribuicao"
                                            >

                                            <input
                                                type="hidden"
                                                name="ano"
                                                value="<?= $ano ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$contribuicao['id'] ?>"
                                            >

                                            <?= mcfCsrfField() ?>

                                            <button
                                                type="submit"
                                                class="reservas-btn-secundario"
                                            >
                                                Excluir
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<div
    class="reservas-modal"
    id="modal-contribuicao"
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

        <h2 id="titulo-modal-contribuicao">
            Nova contribuição
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
                value="salvar_contribuicao"
            >

            <input
                type="hidden"
                name="ano"
                value="<?= $ano ?>"
            >

            <input
                type="hidden"
                name="id"
                id="contribuicao-id"
                value=""
            >

            <label for="contribuicao-data">
                Data
            </label>

            <input
                type="date"
                id="contribuicao-data"
                name="data"
                required
            >

            <label for="contribuicao-tipo">
                Tipo
            </label>

            <select
                id="contribuicao-tipo"
                name="tipo"
                required
            >

                <?php foreach ($tipos as $valor => $rotulo): ?>

                    <option
                        value="<?= htmlspecialchars(
                            $valor,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                        <?= htmlspecialchars(
                            $rotulo,
                            ENT_QUOTES | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label for="contribuicao-descricao">
                Descrição
            </label>

            <input
                type="text"
                id="contribuicao-descricao"
                name="descricao"
                maxlength="150"
                required
            >

            <label for="contribuicao-recebido">
                Valor recebido
            </label>

            <input
                type="text"
                id="contribuicao-recebido"
                name="valor_recebido"
                inputmode="decimal"
                required
            >

            <label for="contribuicao-guardado">
                Valor guardado
            </label>

            <input
                type="text"
                id="contribuicao-guardado"
                name="valor_guardado"
                inputmode="decimal"
                required
            >

            <div
                id="aviso-servico-extra"
                class="reservas-aviso"
                hidden
            >
                Para serviço extra, a referência é guardar 70% do valor recebido.
                O valor pode ser ajustado caso uma parcela maior seja efetivamente
                destinada à reserva.
            </div>

            <label for="contribuicao-observacao">
                Observação
            </label>

            <textarea
                id="contribuicao-observacao"
                name="observacao"
                maxlength="255"
            ></textarea>

            <button
                type="submit"
                class="reservas-btn"
            >
                Salvar contribuição
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
                'modal-contribuicao'
            );

        const titulo =
            document.getElementById(
                'titulo-modal-contribuicao'
            );

        const campoId =
            document.getElementById(
                'contribuicao-id'
            );

        const campoData =
            document.getElementById(
                'contribuicao-data'
            );

        const campoTipo =
            document.getElementById(
                'contribuicao-tipo'
            );

        const campoDescricao =
            document.getElementById(
                'contribuicao-descricao'
            );

        const campoRecebido =
            document.getElementById(
                'contribuicao-recebido'
            );

        const campoGuardado =
            document.getElementById(
                'contribuicao-guardado'
            );

        const campoObservacao =
            document.getElementById(
                'contribuicao-observacao'
            );

        const avisoServico =
            document.getElementById(
                'aviso-servico-extra'
            );

        let preenchimentoAutomatico = true;

        function numero(valor) {

            valor = String(valor || '')
                .replace(/\./g, '')
                .replace(',', '.');

            const resultado =
                parseFloat(valor);

            return Number.isFinite(resultado)
                ? resultado
                : 0;
        }

        function moedaCampo(valor) {

            return Number(valor)
                .toLocaleString(
                    'pt-BR',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );
        }

        function atualizarServicoExtra() {

            const servico =
                campoTipo.value ===
                'servico_extra';

            avisoServico.hidden =
                !servico;

            if (
                servico &&
                preenchimentoAutomatico
            ) {

                const recebido =
                    numero(
                        campoRecebido.value
                    );

                campoGuardado.value =
                    moedaCampo(
                        recebido * 0.70
                    );
            }
        }

        function abrirNovo() {

            titulo.textContent =
                'Nova contribuição';

            campoId.value = '';

            const hoje = new Date();

            const anoSelecionado =
                <?= $ano ?>;

            const anoData =
                hoje.getFullYear() === anoSelecionado
                    ? hoje.getFullYear()
                    : anoSelecionado;

            const mesData =
                hoje.getFullYear() === anoSelecionado
                    ? hoje.getMonth() + 1
                    : 1;

            const diaData =
                hoje.getFullYear() === anoSelecionado
                    ? hoje.getDate()
                    : 1;

            campoData.value =
                String(anoData) +
                '-' +
                String(mesData).padStart(2, '0') +
                '-' +
                String(diaData).padStart(2, '0');

            campoTipo.value =
                'decimo_terceiro';

            campoDescricao.value = '';
            campoRecebido.value = '';
            campoGuardado.value = '';
            campoObservacao.value = '';

            preenchimentoAutomatico = true;

            atualizarServicoExtra();

            modal.classList.add(
                'aberto'
            );

            modal.setAttribute(
                'aria-hidden',
                'false'
            );

            campoData.focus();
        }

        function abrirEdicao(botao) {

            titulo.textContent =
                'Editar contribuição';

            campoId.value =
                botao.dataset.id || '';

            campoData.value =
                botao.dataset.data || '';

            campoTipo.value =
                botao.dataset.tipo || '';

            campoDescricao.value =
                botao.dataset.descricao || '';

            campoRecebido.value =
                botao.dataset.recebido || '';

            campoGuardado.value =
                botao.dataset.guardado || '';

            campoObservacao.value =
                botao.dataset.observacao || '';

            preenchimentoAutomatico = false;

            atualizarServicoExtra();

            modal.classList.add(
                'aberto'
            );

            modal.setAttribute(
                'aria-hidden',
                'false'
            );

            campoDescricao.focus();
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
            .getElementById(
                'nova-contribuicao'
            )
            .addEventListener(
                'click',
                abrirNovo
            );

        document
            .querySelectorAll(
                '.js-editar-contribuicao'
            )
            .forEach(
                function (botao) {

                    botao.addEventListener(
                        'click',
                        function () {
                            abrirEdicao(botao);
                        }
                    );

                }
            );

        campoTipo.addEventListener(
            'change',
            function () {

                preenchimentoAutomatico =
                    campoTipo.value ===
                    'servico_extra';

                atualizarServicoExtra();
            }
        );

        campoRecebido.addEventListener(
            'input',
            function () {

                if (
                    campoTipo.value ===
                    'servico_extra' &&
                    preenchimentoAutomatico
                ) {
                    atualizarServicoExtra();
                }

            }
        );

        campoGuardado.addEventListener(
            'input',
            function () {
                preenchimentoAutomatico = false;
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