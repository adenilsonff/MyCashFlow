<?php
include __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login/login.php");
    exit;
}

$filtroClassificacao = in_array($_GET['classificacao'] ?? '', ['regular', 'extra'], true)
    ? $_GET['classificacao'] : '';
$rotulosClassificacao = ['' => 'Todas as receitas', 'regular' => 'Rendas regulares', 'extra' => 'Rendas extras'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date("n");
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date("Y");

if ($mes < 1 || $mes > 12) {
    $mes = (int)date("n");
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date("Y");
}

function voltarRendas($mes, $ano, $classificacao)
{
    header("Location: rendas.php?mes=" . (int)$mes . "&ano=" . (int)$ano . "&classificacao=" . urlencode($classificacao));
    exit;
}

function criarDataRecorrente(DateTime $dataInicial, $mesesAdicionar)
{
    $diaOriginal = (int)$dataInicial->format("d");

    $data = new DateTime(
        $dataInicial->format("Y-m-01")
    );

    if ($mesesAdicionar > 0) {
        $data->modify("+{$mesesAdicionar} month");
    }

    $ultimoDiaMes = (int)$data->format("t");
    $dia = min($diaOriginal, $ultimoDiaMes);

    $data->setDate(
        (int)$data->format("Y"),
        (int)$data->format("m"),
        $dia
    );

    return $data;
}

function normalizarValor($valor)
{
    $valor = trim((string)$valor);
    $valor = str_replace(['R$', ' '], '', $valor);

    if (strpos($valor, ',') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    }

    return (float)$valor;
}

function gerarGrupoRecorrencia()
{
    try {
        return bin2hex(random_bytes(16));
    } catch (Exception $e) {
        return uniqid('rec_', true);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['nova_renda'])) {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = $_POST['tipoRenda'] ?? '';
        $classificacao = $_POST['classificacao'] ?? '';
        $dataInformado = $_POST['data'] ?? '';
        $valor = normalizarValor($_POST['valor'] ?? 0);

        $totalParcelas = $tipo === 'parcelada'
            ? filter_var($_POST['total_parcelas'] ?? '', FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 2147483647]
            ])
            : null;
        $data = is_string($dataInformado)
            ? DateTime::createFromFormat('!Y-m-d', $dataInformado)
            : false;

        if (
            $nome !== '' && $descricao !== '' &&
            in_array($tipo, ['unica', 'parcelada', 'recorrente'], true) &&
            in_array($classificacao, ['regular', 'extra'], true) &&
            $data && $data->format('Y-m-d') === $dataInformado &&
            (int)$data->format('Y') >= 1000 &&
            is_finite($valor) && $valor >= 0 &&
            ($tipo !== 'parcelada' || $totalParcelas !== false)
        ) {
            $quantidade = $tipo === 'parcelada' ? $totalParcelas : ($tipo === 'recorrente' ? 12 : 1);
            $diaOriginal = (int)$data->format('d');
            $mesOriginal = (int)$data->format('m');
            $anoOriginal = (int)$data->format('Y');

            if ($quantidade > (9999 - $anoOriginal) * 12 + (12 - $mesOriginal) + 1) {
                voltarRendas($mes, $ano, $filtroClassificacao);
            }

            $grupoRecorrencia = $tipo === 'unica' ? null : gerarGrupoRecorrencia();
            if (!$conn->begin_transaction()) {
                throw new RuntimeException('Não foi possível iniciar o cadastro.');
            }
            try {
                $stmt = $conn->prepare("
                    INSERT INTO rendas
                    (nome, descricao, tipo, classificacao, data, recebido, valor, grupo_recorrencia, parcela_atual, total_parcelas)
                    VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
                ");
                if (!$stmt) {
                    throw new RuntimeException('Não foi possível preparar o cadastro.');
                }
                $dataFormatada = '';
                $parcelaAtual = null;
                $stmt->bind_param("sssssdsii", $nome, $descricao, $tipo, $classificacao, $dataFormatada,
                    $valor, $grupoRecorrencia, $parcelaAtual, $totalParcelas);

                for ($i = 0; $i < $quantidade; $i++) {
                    $primeiroDia = new DateTime(sprintf('%04d-%02d-01', $anoOriginal, $mesOriginal));
                    if ($i > 0) {
                        $primeiroDia->modify("+$i month");
                    }
                    $dia = min($diaOriginal, (int)$primeiroDia->format('t'));
                    $dataFormatada = sprintf('%04d-%02d-%02d',
                        (int)$primeiroDia->format('Y'), (int)$primeiroDia->format('m'), $dia);
                    $parcelaAtual = $tipo === 'parcelada' ? $i + 1 : null;
                    if (!$stmt->execute()) {
                        throw new RuntimeException('Não foi possível cadastrar os lançamentos.');
                    }
                }
                $stmt->close();
                if (!$conn->commit()) {
                    throw new RuntimeException('Não foi possível concluir o cadastro.');
                }
            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }
        }

        voltarRendas($mes, $ano, $filtroClassificacao);
    }

    if (isset($_POST['atualizar_recebido'])) {
        $id = (int)($_POST['id'] ?? 0);
        $recebido = isset($_POST['recebido']) && (int)$_POST['recebido'] === 1 ? 1 : 0;

        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE rendas
                SET recebido = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ii", $recebido, $id);
            $stmt->execute();
            $stmt->close();
        }

        voltarRendas($mes, $ano, $filtroClassificacao);
    }

    if (isset($_POST['ajustar_valor']) || isset($_POST['deletar_renda'])) {
        $id = (int)($_POST['id'] ?? 0);
        $ajustar = isset($_POST['ajustar_valor']);
        $novoValor = $ajustar ? normalizarValor($_POST['novo_valor'] ?? 0) : 0;
        $classificacao = $_POST['classificacao'] ?? '';
        $alcance = $_POST['alcance'] ?? 'somente';

        if ((!$ajustar || in_array($classificacao, ['regular', 'extra'], true)) && $id > 0 && is_finite($novoValor) && $novoValor >= 0 &&
            in_array($alcance, ['somente', 'proximos'], true)) {
            $stmt = $conn->prepare("
                SELECT tipo, data, grupo_recorrencia
                FROM rendas WHERE id = ? LIMIT 1
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $rendaSelecionada = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($rendaSelecionada) {
                $aplicarProximos = $alcance === 'proximos' &&
                    in_array($rendaSelecionada['tipo'], ['parcelada', 'recorrente'], true) &&
                    !empty($rendaSelecionada['grupo_recorrencia']);

                if ($aplicarProximos) {
                    $grupoRecorrencia = $rendaSelecionada['grupo_recorrencia'];
                    $data = $rendaSelecionada['data'];
                    $tipoSelecionado = $rendaSelecionada['tipo'];
                    if ($ajustar) {
                        $stmt = $conn->prepare("
                            UPDATE rendas SET valor = ?
                            WHERE grupo_recorrencia = ? AND data >= ? AND tipo = ?
                        ");
                        $stmt->bind_param("dsss", $novoValor, $grupoRecorrencia, $data, $tipoSelecionado);
                    } else {
                        $stmt = $conn->prepare("
                            DELETE FROM rendas
                            WHERE grupo_recorrencia = ? AND data >= ? AND tipo = ?
                        ");
                        $stmt->bind_param("sss", $grupoRecorrencia, $data, $tipoSelecionado);
                    }
                } elseif ($ajustar) {
                    $stmt = $conn->prepare("UPDATE rendas SET valor = ? WHERE id = ?");
                    $stmt->bind_param("di", $novoValor, $id);
                } else {
                    $stmt = $conn->prepare("DELETE FROM rendas WHERE id = ?");
                    $stmt->bind_param("i", $id);
                }
                $stmt->execute();
                $stmt->close();
                if ($ajustar) {
                    $stmt = $conn->prepare("UPDATE rendas SET classificacao = ? WHERE id = ?");
                    $stmt->bind_param("si", $classificacao, $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        voltarRendas($mes, $ano, $filtroClassificacao);
    }

}

$sql = "
    SELECT
        id,
        nome,
        descricao,
        data,
        valor,
        tipo,
        grupo_recorrencia,
        parcela_atual,
        total_parcelas,
        recebido,
        porcentagem,
        classificacao
    FROM rendas
    WHERE MONTH(data) = ?
    AND YEAR(data) = ?
    ORDER BY data ASC, id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $mes, $ano);
$stmt->execute();
$result = $stmt->get_result();

$rendas = [];
$total = 0;
$totalRecebido = 0;

while ($row = $result->fetch_assoc()) {
    if ($filtroClassificacao !== '' && $row['classificacao'] !== $filtroClassificacao) {
        continue;
    }
    $rendas[] = $row;
    $total += (float)$row['valor'];

    if ((int)$row['recebido'] === 1) {
        $totalRecebido += (float)$row['valor'];
    }
}

$stmt->close();

$totalAReceber = $total - $totalRecebido;

$meses = [
    "Janeiro",
    "Fevereiro",
    "Março",
    "Abril",
    "Maio",
    "Junho",
    "Julho",
    "Agosto",
    "Setembro",
    "Outubro",
    "Novembro",
    "Dezembro"
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receitas</title>
    <link rel="stylesheet" href="../assets/css/style-rendas.css?v=<?= filemtime(__DIR__ . "/../assets/css/style-rendas.css") ?>">
</head>
<body>

<?php include("../includes/header.php"); ?>
<?php include("../includes/menu.php"); ?>

<main class="rendas-layout">

    <div class="cabecalho-rendas">
        <div>
            <h1>Receitas</h1>
            <p><?= htmlspecialchars($meses[$mes - 1]) ?>/<?= $ano ?> · <?= $rotulosClassificacao[$filtroClassificacao] ?></p>
        </div>

        <div class="acoes-rendas">
            <button type="button" class="btn-padrao" data-modal="modalNovaRenda">
                Nova Receita
            </button>

            <button type="button" class="btn-padrao" data-modal="modalPesquisa">
                Pesquisar
            </button>
        </div>
    </div>

    <form method="GET" class="filtro-classificacao">
        <input type="hidden" name="mes" value="<?= $mes ?>">
        <input type="hidden" name="ano" value="<?= $ano ?>">
        <label for="filtro-classificacao">Mostrar</label>
        <select id="filtro-classificacao" name="classificacao">
            <?php foreach ($rotulosClassificacao as $chave => $rotulo): ?>
            <option value="<?= $chave ?>" <?= $filtroClassificacao === $chave ? 'selected' : '' ?>><?= $rotulo ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-padrao">Filtrar</button>
    </form>
    <?php if ($filtroClassificacao !== ''): ?>
    <p class="aviso-filtro">Os totais e percentuais abaixo consideram apenas <?= strtolower($rotulosClassificacao[$filtroClassificacao]) ?> do período.</p>
    <?php endif; ?>

    <div class="resumo-rendas">

        <div class="card-resumo">
            <span>Total previsto</span>
            <strong>
                R$ <?= number_format($total, 2, ',', '.') ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>Total recebido</span>
            <strong>
                R$ <?= number_format($totalRecebido, 2, ',', '.') ?>
            </strong>
        </div>

        <div class="card-resumo">
            <span>A receber</span>
            <strong>
                R$ <?= number_format($totalAReceber, 2, ',', '.') ?>
            </strong>
        </div>

    </div>

    <div class="card-lista-renda">

        <div class="titulo-tabela">
            <h2>
                Lista de Receitas - <?= htmlspecialchars($meses[$mes - 1]) ?>/<?= $ano ?>
            </h2>
        </div>

        <div class="tabela-wrapper">

            <table class="tabela-rendas">

                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Data</th>
                        <th>Valor</th>
                        <th>Recebido</th>
                        <th>%</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($rendas)) { ?>

                    <tr>
                        <td colspan="8" class="sem-registros">
                            Nenhuma receita encontrada neste período.
                        </td>
                    </tr>

                <?php } else { ?>

                    <?php foreach ($rendas as $r) {
                        $temSerie = in_array($r['tipo'], ['parcelada', 'recorrente'], true);
                        $rotuloTipo = $r['tipo'] === 'recorrente' ? 'Recorrente' : 'Única';
                        if ($r['tipo'] === 'parcelada') {
                            $rotuloTipo = 'Parcelada ' . (int)$r['parcela_atual'] . '/' . (int)$r['total_parcelas'];
                        }
                    ?>

                        <tr class="<?= $r['recebido'] ? 'linha-recebida' : 'linha-nao-recebida' ?>">

                            <td>
                                <?= htmlspecialchars($r['nome']) ?>
                                <span class="renda-classificacao"><?= $r['classificacao'] === 'extra' ? 'Extra' : 'Regular' ?></span>
                            </td>

                            <td><?= htmlspecialchars($rotuloTipo) ?></td>

                            <td>
                                <?= htmlspecialchars($r['descricao']) ?>
                            </td>

                            <td>
                                <?= date("d/m/Y", strtotime($r['data'])) ?>
                            </td>

                            <td>
                                R$ <?= number_format($r['valor'], 2, ',', '.') ?>
                            </td>

                            <td>

                                <form method="POST" class="form-status">

                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                    <input type="hidden" name="mes" value="<?= $mes ?>">
                                    <input type="hidden" name="ano" value="<?= $ano ?>">

                                    <input
                                        type="hidden"
                                        name="atualizar_recebido"
                                        value="1"
                                    >

                                    <select
                                        name="recebido"
                                        onchange="this.form.submit()"
                                    >
                                        <option
                                            value="0"
                                            <?= !$r['recebido'] ? 'selected' : '' ?>
                                        >
                                            Não
                                        </option>

                                        <option
                                            value="1"
                                            <?= $r['recebido'] ? 'selected' : '' ?>
                                        >
                                            Sim
                                        </option>
                                    </select>

                                </form>

                            </td>

                            <td>
                                <?= $total > 0
                                    ? number_format(
                                        ((float)$r['valor'] / $total) * 100,
                                        2,
                                        ',',
                                        '.'
                                    )
                                    : '0,00'
                                ?>%
                            </td>

                            <td>

                                <div class="acoes-linha">

                                    <button
                                        type="button"
                                        class="btn-acao btn-ajustar"
                                        data-serie="<?= $temSerie ? '1' : '0' ?>"
                                        data-id="<?= (int)$r['id'] ?>"
                                        data-nome="<?= htmlspecialchars($r['nome'], ENT_QUOTES) ?>"
                                        data-valor="<?= number_format($r['valor'], 2, '.', '') ?>" data-classificacao="<?= htmlspecialchars($r['classificacao'], ENT_QUOTES) ?>"
                                    >
                                        Ajustar
                                    </button>

                                    <button type="button" class="btn-acao btn-excluir"
                                        data-excluir data-id="<?= (int)$r['id'] ?>"
                                        data-nome="<?= htmlspecialchars($r['nome'], ENT_QUOTES) ?>"
                                        data-serie="<?= $temSerie ? '1' : '0' ?>">
                                        Excluir
                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="4">
                            Total
                        </td>

                        <td>
                            R$ <?= number_format($total, 2, ',', '.') ?>
                        </td>

                        <td colspan="3"></td>
                    </tr>
                </tfoot>

            </table>

        </div>

    </div>

</main>

<div id="modalNovaRenda" class="modal-rendas">

    <div class="modal-conteudo">

        <button
            type="button"
            class="modal-fechar"
            data-fechar
        >
            ×
        </button>

        <h2>Nova Receita</h2>

        <form method="POST" class="form-rendas">

            <input
                type="hidden"
                name="nova_renda"
                value="1"
            >

            <input
                type="text"
                name="nome"
                placeholder="Nome da receita"
                required
            >

            <input
                type="text"
                name="descricao"
                placeholder="Descrição"
                required
            >

            <label for="nova-classificacao">Classificação</label>
            <select id="nova-classificacao" name="classificacao" required>
                <option value="regular" <?= $filtroClassificacao !== 'extra' ? 'selected' : '' ?>>Regular</option>
                <option value="extra" <?= $filtroClassificacao === 'extra' ? 'selected' : '' ?>>Extra</option>
            </select>

            <label for="nova-frequencia">Tipo</label>
            <select id="nova-frequencia" name="tipoRenda" required>
                <option value="unica">
                    Única
                </option>

                <option value="parcelada">Parcelada</option>
                <option value="recorrente">Recorrente</option>
            </select>

            <label id="parcelas-campo" hidden>
                Quantidade de parcelas
                <input type="number" name="total_parcelas" id="total-parcelas" min="1" step="1" disabled>
            </label>
            <p id="aviso-tipo" class="aviso-filtro" hidden></p>

            <input
                type="date"
                name="data"
                required
            >

            <input
                type="number"
                step="0.01"
                min="0"
                name="valor"
                placeholder="Valor"
                required
            >

            <button type="submit" class="btn-padrao">
                Registrar
            </button>

        </form>

    </div>

</div>

<div id="modalPesquisa" class="modal-rendas">

    <div class="modal-conteudo">

        <button
            type="button"
            class="modal-fechar"
            data-fechar
        >
            ×
        </button>

        <h2>Pesquisar Receitas</h2>

        <form method="GET" class="form-rendas">
            <input type="hidden" name="classificacao" value="<?= $filtroClassificacao ?>">

            <select name="mes">

                <?php for ($i = 1; $i <= 12; $i++) { ?>

                    <option
                        value="<?= $i ?>"
                        <?= $i === $mes ? 'selected' : '' ?>
                    >
                        <?= htmlspecialchars($meses[$i - 1]) ?>
                    </option>

                <?php } ?>

            </select>

            <select name="ano">

                <?php
                $anoInicial = min((int)date("Y") - 5, $ano);
                $anoFinal = max((int)date("Y") + 5, $ano);

                for ($y = $anoInicial; $y <= $anoFinal; $y++) {
                ?>

                    <option
                        value="<?= $y ?>"
                        <?= $y === $ano ? 'selected' : '' ?>
                    >
                        <?= $y ?>
                    </option>

                <?php } ?>

            </select>

            <button type="submit" class="btn-padrao">
                Pesquisar
            </button>

        </form>

    </div>

</div>

<div id="modalAjustar" class="modal-rendas">

    <div class="modal-conteudo">

        <button
            type="button"
            class="modal-fechar"
            data-fechar
        >
            ×
        </button>

        <h2>Ajustar Receita</h2>

        <form method="POST" class="form-rendas">

            <input
                type="hidden"
                name="ajustar_valor"
                value="1"
            >

            <input
                type="hidden"
                name="id"
                id="ajuste-id"
            >

            <div id="ajuste-nome" class="nome-renda-ajuste"></div>
            <label for="ajuste-classificacao">Classificação</label>
            <select id="ajuste-classificacao" name="classificacao" required>
                <option value="regular">Regular</option>
                <option value="extra">Extra</option>
            </select>
            <p class="aviso-filtro">A classificação se aplica somente a este lançamento.</p>

            <input
                type="number"
                step="0.01"
                min="0"
                name="novo_valor"
                id="ajuste-valor"
                required
            >

            <label id="ajuste-alcance-campo" hidden>
                Aplicar o valor a
                <select name="alcance" id="ajuste-alcance" disabled>
                    <option value="somente">Somente este lançamento</option>
                    <option value="proximos">Este lançamento e os próximos</option>
                </select>
            </label>

            <button type="submit" class="btn-padrao">
                Atualizar
            </button>

        </form>

    </div>

</div>

<div id="modalExcluir" class="modal-rendas">
    <div class="modal-conteudo">
        <button type="button" class="modal-fechar" data-fechar>×</button>
        <h2>Excluir Receita</h2>
        <form method="POST" class="form-rendas">
            <input type="hidden" name="deletar_renda" value="1">
            <input type="hidden" name="id" id="exclusao-id">
            <div id="exclusao-nome" class="nome-renda-ajuste"></div>
            <p>Tem certeza que deseja excluir esta receita?</p>
            <label id="exclusao-alcance-campo" hidden>
                Excluir
                <select name="alcance" id="exclusao-alcance" disabled>
                    <option value="somente">Somente este lançamento</option>
                    <option value="proximos">Este lançamento e os próximos</option>
                </select>
            </label>

            <button type="submit" class="btn-padrao btn-excluir">Excluir</button>
            <button type="button" class="btn-padrao" data-fechar>Cancelar</button>
        </form>
    </div>
</div>

<?php include("../includes/footer.php"); ?>

<script>
function configurarAlcance(prefixo, temSerie) {
    document.getElementById(prefixo + '-alcance-campo').hidden = !temSerie;
    const campo = document.getElementById(prefixo + '-alcance');
    campo.value = 'somente';
    campo.disabled = !temSerie;
}

function atualizarTipo() {
    const tipo = document.getElementById('nova-frequencia').value;
    const parcelada = tipo === 'parcelada';
    document.getElementById('parcelas-campo').hidden = !parcelada;
    const parcelas = document.getElementById('total-parcelas');
    parcelas.required = parcelada;
    parcelas.disabled = !parcelada;
    const aviso = document.getElementById('aviso-tipo');
    aviso.hidden = tipo === 'unica';
    aviso.textContent = parcelada
        ? 'O valor informado será aplicado a cada parcela mensal.'
        : 'Serão criados 12 lançamentos mensais a partir da data informada.';
}
document.getElementById('nova-frequencia').addEventListener('change', atualizarTipo);
atualizarTipo();

document.querySelectorAll('[data-excluir]').forEach(function(botao) {
    botao.addEventListener('click', function() {
        document.getElementById('exclusao-id').value = this.dataset.id;
        document.getElementById('exclusao-nome').textContent = this.dataset.nome;
        configurarAlcance('exclusao', this.dataset.serie === '1');
        document.getElementById('modalExcluir').classList.add('ativo');
    });
});

document.querySelectorAll('[data-modal]').forEach(function(botao) {
    botao.addEventListener('click', function() {
        const modal = document.getElementById(this.dataset.modal);

        if (modal) {
            modal.classList.add('ativo');
        }
    });
});

document.querySelectorAll('[data-fechar]').forEach(function(botao) {
    botao.addEventListener('click', function() {
        this.closest('.modal-rendas').classList.remove('ativo');
    });
});

document.querySelectorAll('.modal-rendas').forEach(function(modal) {
    modal.addEventListener('click', function(evento) {
        if (evento.target === modal) {
            modal.classList.remove('ativo');
        }
    });
});

document.querySelectorAll('.btn-ajustar').forEach(function(botao) {
    botao.addEventListener('click', function() {
        document.getElementById('ajuste-id').value = this.dataset.id;
        document.getElementById('ajuste-classificacao').value = this.dataset.classificacao;
        document.getElementById('ajuste-valor').value = this.dataset.valor;
        document.getElementById('ajuste-nome').textContent = this.dataset.nome;

        configurarAlcance('ajuste', this.dataset.serie === '1');
        document.getElementById('modalAjustar').classList.add('ativo');
    });
});

document.addEventListener('keydown', function(evento) {
    if (evento.key === 'Escape') {
        document.querySelectorAll('.modal-rendas.ativo').forEach(function(modal) {
            modal.classList.remove('ativo');
        });
    }
});
</script>

</body>
</html>