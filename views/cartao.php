<?php

include __DIR__ . '/../config.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_cartao'])) {
    $_SESSION['csrf_cartao'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_cartao'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_cartao'], $token)) {
        http_response_code(403);
        exit('Sessão do formulário expirada. Recarregue a página e tente novamente.');
    }
}
$paginaAtual = basename($_SERVER['PHP_SELF']);
$mes = isset($_GET['mes']) ? (int) $_GET['mes'] : (int) date('n');
$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : (int) date('Y');
$filtroCategoria = is_string($_GET['filtro_categoria'] ?? null)
    ? $_GET['filtro_categoria']
    : '';

if (!in_array($filtroCategoria, ['', 'pessoal', 'conjunta', 'unica'], true)) {
    $filtroCategoria = '';
}

if ($mes < 1 || $mes > 12) {
    $mes = (int) date('n');
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int) date('Y');
}

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function rotuloCategoria($categoria)
{
    $rotulos = [
        'pessoal' => 'Particular',
        'conjunta' => 'Conjunta',
        'unica' => 'Cliente/Reembolsável'
    ];

    return $rotulos[$categoria] ?? 'Particular';
}

function parametrosPaginaCartao($mes, $ano, array $extras = [])
{
    global $filtroCategoria;

    $parametros = ['mes' => $mes, 'ano' => $ano];

    if ($filtroCategoria !== '') {
        $parametros['filtro_categoria'] = $filtroCategoria;
    }

    return array_merge($parametros, $extras);
}

function voltarPagina($paginaAtual, $mes, $ano, array $extras = [])
{
    $parametros = parametrosPaginaCartao($mes, $ano, $extras);

    header(
        'Location: ' . $paginaAtual . '?' . http_build_query($parametros)
    );

    exit;
}

function normalizarTexto($texto)
{
    $texto = trim($texto);

    $convertido = @iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        $texto
    );

    if ($convertido !== false) {
        $texto = $convertido;
    }

    return trim(
        preg_replace('/\s+/', ' ', strtoupper($texto))
    );
}

function extrairParcela($texto)
{
    $padrao = '/\b(?:PARC(?:ELA)?|P)\s*0*(\d{1,3})\s*(?:\/|DE)\s*0*(\d{1,3})\b/i';

    if (preg_match($padrao, $texto, $resultado)) {
        $parcela = (int) $resultado[1];
        $total = (int) $resultado[2];

        if ($parcela >= 1 && $total >= $parcela && $total <= 120) {
            return [
                'parcela' => $parcela,
                'total' => $total,
                'nome' => trim(
                    preg_replace($padrao, '', $texto)
                )
            ];
        }
    }

    return [
        'parcela' => 1,
        'total' => 1,
        'nome' => trim($texto)
    ];
}

function obterReferenciaFatura($nomeArquivo, $mesPadrao, $anoPadrao)
{
    $meses = [
        'JAN' => 1,
        'FEV' => 2,
        'MAR' => 3,
        'ABR' => 4,
        'MAI' => 5,
        'JUN' => 6,
        'JUL' => 7,
        'AGO' => 8,
        'SET' => 9,
        'OUT' => 10,
        'NOV' => 11,
        'DEZ' => 12
    ];

    $nomeSemExtensao = strtoupper(
        pathinfo($nomeArquivo, PATHINFO_FILENAME)
    );

    $padrao = '/(?:^|[^A-Z])(JAN|FEV|MAR|ABR|MAI|JUN|JUL|AGO|SET|OUT|NOV|DEZ)[_\- ]?(\d{2,4})(?:$|[^0-9])/i';

    if (preg_match($padrao, $nomeSemExtensao, $resultado)) {
        $mesArquivo = $meses[strtoupper($resultado[1])];
        $anoArquivo = (int) $resultado[2];

        if ($anoArquivo < 100) {
            $anoArquivo += 2000;
        }

        return [$mesArquivo, $anoArquivo];
    }

    return [$mesPadrao, $anoPadrao];
}

function dataNoMes($ano, $mes, $dia)
{
    $data = new DateTime(
        sprintf('%04d-%02d-01', $ano, $mes)
    );

    $data->setDate(
        $ano,
        $mes,
        min((int) $dia, (int) $data->format('t'))
    );

    return $data;
}

function adicionarMeses(DateTime $dataBase, $quantidade)
{
    $diaOriginal = (int) $dataBase->format('d');

    $data = new DateTime(
        $dataBase->format('Y-m-01')
    );

    if ($quantidade !== 0) {
        $data->modify(
            ($quantidade > 0 ? '+' : '') .
            $quantidade .
            ' month'
        );
    }

    $data->setDate(
        (int) $data->format('Y'),
        (int) $data->format('m'),
        min($diaOriginal, (int) $data->format('t'))
    );

    return $data;
}

/*
|--------------------------------------------------------------------------
| Marcar fatura como paga
|--------------------------------------------------------------------------
*/
if (isset($_POST['atualizar_fatura_paga'])) {
    $mesSelecionado = (int) ($_POST['mes'] ?? 0);
    $anoSelecionado = (int) ($_POST['ano'] ?? 0);

    if ($mesSelecionado >= 1 && $mesSelecionado <= 12 && $anoSelecionado >= 2000 && $anoSelecionado <= 2100) {
        $stmt = $conn->prepare(
            'UPDATE cartoes 
             SET paga = 1 
             WHERE MONTH(data) = ? AND YEAR(data) = ?'
        );

        if (!$stmt) {
            die('Erro ao preparar atualização da fatura: ' . $conn->error);
        }

        $stmt->bind_param('ii', $mesSelecionado, $anoSelecionado);

        if (!$stmt->execute()) {
            die('Erro ao marcar fatura como paga: ' . $stmt->error);
        }

        $stmt->close();
    }

    voltarPagina($paginaAtual, $mesSelecionado, $anoSelecionado);
}

/*
|--------------------------------------------------------------------------
| Atualizar categoria em lote
|--------------------------------------------------------------------------
*/
if (isset($_POST['atualizar_categoria_lote'])) {
    $comprasSelecionadas = is_array($_POST['compras'] ?? null) ? $_POST['compras'] : [];
    $categoria = $_POST['categoria'] ?? 'pessoal';

    if (!in_array($categoria, ['pessoal', 'conjunta', 'unica'], true)) {
        $categoria = 'pessoal';
    }

    if (!empty($comprasSelecionadas)) {
        $stmt = $conn->prepare('UPDATE compras SET categoria = ? WHERE id = ?');
        foreach ($comprasSelecionadas as $compraId) {
            $id = (int)$compraId;
            $stmt->bind_param('si', $categoria, $id);
            $stmt->execute();
        }
        $stmt->close();
    }

    voltarPagina($paginaAtual, $mes, $ano);
}

/*
|--------------------------------------------------------------------------
| Excluir compra e parcelas
|--------------------------------------------------------------------------
*/
if (isset($_POST['deletar_cartao'])) {
    $compraId = (int) ($_POST['compra_id'] ?? 0);
    $inicio = sprintf('%04d-%02d-01', $ano, $mes);
    $fim = (new DateTime($inicio))->modify('+1 month')->format('Y-m-d');
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare('SELECT id FROM compras WHERE id = ? FOR UPDATE');
        if (!$stmt) {
            throw new Exception('Falha ao localizar compra.');
        }
        $stmt->bind_param('i', $compraId);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao localizar compra.');
        }
        $stmt->close();
        $stmt = $conn->prepare('SELECT id FROM cartoes WHERE compra_id = ? AND data >= ? AND data < ? LIMIT 1');
        if (!$stmt) {
            throw new Exception('Falha ao verificar competência.');
        }
        $stmt->bind_param('iss', $compraId, $inicio, $fim);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao verificar competência.');
        }
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$existe) {
            throw new Exception('A compra não possui parcela no mês selecionado.');
        }
        $stmt = $conn->prepare('DELETE FROM cartoes WHERE compra_id = ? AND data >= ?');
        if (!$stmt) {
            throw new Exception('Falha ao preparar exclusão.');
        }
        $stmt->bind_param('is', $compraId, $inicio);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao excluir parcelas.');
        }
        $stmt->close();
        $stmt = $conn->prepare('DELETE FROM compras WHERE id = ? AND NOT EXISTS (SELECT 1 FROM cartoes WHERE compra_id = ?)');
        if (!$stmt) {
            throw new Exception('Falha ao preparar exclusão da compra.');
        }
        $stmt->bind_param('ii', $compraId, $compraId);
        if (!$stmt->execute()) {
            throw new Exception('Falha ao excluir compra.');
        }
        $stmt->close();
        $conn->commit();
        $_SESSION['cartao_aviso'] = 'Parcelas excluídas do mês selecionado em diante. Histórico anterior preservado.';
    }
    catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['cartao_aviso'] = 'Não foi possível excluir. Nenhuma alteração foi confirmada.';
    }
    voltarPagina($paginaAtual, $mes, $ano);
}

/*
|--------------------------------------------------------------------------
| Atualizar pagamento
|--------------------------------------------------------------------------
*/
if (isset($_POST['atualizar_paga'])) {
    $id = (int) ($_POST['id'] ?? 0);

    $paga = isset($_POST['paga']) && $_POST['paga'] === '1'
    ? 1
    : 0;

    if ($id > 0) {
        $stmt = $conn->prepare(
            'UPDATE cartoes SET paga = ? WHERE id = ?'
        );

        if (!$stmt) {
            die('Erro ao preparar pagamento: ' . $conn->error);
        }

        $stmt->bind_param('ii', $paga, $id);

        if (!$stmt->execute()) {
            die('Erro ao atualizar pagamento: ' . $stmt->error);
        }

        $stmt->close();
    }

    voltarPagina($paginaAtual, $mes, $ano);
}

/*
|--------------------------------------------------------------------------
| Ajustar valor de uma parcela
|--------------------------------------------------------------------------
*/
if (isset($_POST['ajustar_valor'])) {
    $id = (int) ($_POST['id'] ?? 0);

    $entradaValor = $_POST['novo_valor'] ?? '';
    if (
        !is_string($entradaValor) || !preg_match('/^-?\d{1,8}(?:[.,]\d{1,2})?$/D', trim($entradaValor))
    ) {
        $_SESSION['cartao_aviso'] = 'Informe um valor válido para a parcela.';
        voltarPagina($paginaAtual, $mes, $ano);
    }
    $novoValor = (float) str_replace(',', '.', trim($entradaValor));

    if ($id > 0) {
        $stmt = $conn->prepare(
            'UPDATE cartoes SET valor = ? WHERE id = ?'
        );

        if (!$stmt) {
            die('Erro ao preparar ajuste: ' . $conn->error);
        }

        $stmt->bind_param('di', $novoValor, $id);

        if (!$stmt->execute()) {
            die('Erro ao ajustar valor: ' . $stmt->error);
        }

        $stmt->close();
    }

    voltarPagina($paginaAtual, $mes, $ano);
}

/*
|--------------------------------------------------------------------------
| Atualizar categoria da compra
|--------------------------------------------------------------------------
*/
if (isset($_POST['atualizar_categoria'])) {
    $compraId = (int) ($_POST['compra_id'] ?? 0);
    $categoria = $_POST['categoria'] ?? 'pessoal';

    if (
        !in_array(
            $categoria,
            ['pessoal', 'conjunta', 'unica'],
            true
        )
    ) {
        $categoria = 'pessoal';
    }

    if ($compraId > 0) {
        $stmt = $conn->prepare(
            'UPDATE compras SET categoria = ? WHERE id = ?'
        );

        if (!$stmt) {
            die('Erro ao preparar categoria: ' . $conn->error);
        }

        $stmt->bind_param('si', $categoria, $compraId);

        if (!$stmt->execute()) {
            die('Erro ao atualizar categoria: ' . $stmt->error);
        }

        $stmt->close();
    }

    voltarPagina($paginaAtual, $mes, $ano);
}

/*
|--------------------------------------------------------------------------
| Importação OFX
|--------------------------------------------------------------------------
*/
function ccSql($conn, $sql, $types = '', array $args = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Não foi possível preparar a operação.');
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$args);
    }
    if (!$stmt->execute()) {
        throw new RuntimeException('Não foi possível concluir a operação.');
    }
    return $stmt;
}
function ccRows($conn, $sql, $types = '', array $args = [])
{
    $stmt = ccSql($conn, $sql, $types, $args);
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
function ccExec($conn, $sql, $types = '', array $args = [])
{
    ccSql($conn, $sql, $types, $args)->close();
}
class CcRevisao extends RuntimeException {
    public $indice;
    public $opcoes;
    public function __construct($indice, $descricao, $opcoes)
    {
        parent::__construct($descricao);
        $this->indice = $indice;
        $this->opcoes = $opcoes;
    }
}
function ccLerOfx($conteudo)
{
    if (!preg_match('//u', $conteudo)) {
        $conteudo = iconv('Windows-1252', 'UTF-8', $conteudo);
    }
    $posicao = stripos($conteudo, '<OFX>');
    $xmlTexto = $posicao !== false ? substr($conteudo, $posicao) : $conteudo;
    $anterior = libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlTexto, 'SimpleXMLElement', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);
    $transacoes = null;
    if ($xml !== false) {
        if (isset($xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->BANKTRANLIST->STMTTRN)) {
            $transacoes = $xml->CREDITCARDMSGSRSV1->CCSTMTTRNRS->CCSTMTRS->BANKTRANLIST->STMTTRN;
        }
        elseif (isset($xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN)) {
            $transacoes = $xml->BANKMSGSRSV1->STMTTRNRS->STMTRS->BANKTRANLIST->STMTTRN;
        }
    }
    $lista = [];
    $campos = ['FITID', 'DTPOSTED', 'TRNAMT', 'NAME', 'MEMO', 'TRNTYPE'];
    if ($transacoes !== null) {
        foreach ($transacoes as $item) {
            $row = [];
            foreach ($campos as $campo) {
                $row[$campo] = trim((string) $item->$campo);
            }
            $lista[] = $row;
        }
    }
    else {
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $conteudo, $blocos);
        foreach ($blocos[1] as $bloco) {
            $row = [];
            foreach ($campos as $campo) {
                preg_match('/<' . $campo . '>\s*([^<\r\n]+)/i', $bloco, $m);
                $row[$campo] = html_entity_decode(trim($m[1] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            $lista[] = $row;
        }
    }
    if (!$lista) {
        throw new RuntimeException('Nenhuma transação encontrada no OFX.');
    }
    return $lista;
}
function ccPagamentoFatura($nome)
{
    return preg_match(
        '/^(?:PGTO\.?\s*CASH\b|(?:PGTO\.?|PAGTO\.?|PAGAMENTO)\s+(?:(?:DA|DE)\s+)?FATURA\b)/i',
        normalizarTexto($nome)
    ) === 1;
}
function ccChaveNomeRecorrente($descricao)
{
    return hash('sha256', normalizarTexto($descricao));
}

function ccPermiteNomeRecorrente(array $compra)
{
    return $compra['origem'] === 'ofx'
        && (int) $compra['total_parcelas'] === 1
        && (float) $compra['valor_total'] > 0;
}

function ccRenomearCompra($conn, $compraId, $nome, $repetir)
{
    if ($nome === '' || mb_strlen($nome, 'UTF-8') > 255) {
        throw new RuntimeException('Informe um nome de até 255 caracteres.');
    }

    $lock = ccRows(
        $conn,
        "SELECT GET_LOCK('mycashflow_cartao_importacao', 10) AS adquirido"
    );

    if ((int) $lock[0]['adquirido'] !== 1) {
        throw new RuntimeException('Uma importação está em andamento. Tente novamente.');
    }

    $emTransacao = false;

    try {
        $conn->begin_transaction();
        $emTransacao = true;

        $compras = ccRows(
            $conn,
            'SELECT * FROM compras WHERE id = ? FOR UPDATE',
            'i',
            [$compraId]
        );

        if (!$compras) {
            throw new RuntimeException('A compra não está mais disponível.');
        }

        $compra = $compras[0];
        $descricao = $compra['nome_original'] ?? $compra['nome'];
        $permite = ccPermiteNomeRecorrente($compra);

        if ($repetir && (!$permite || trim($descricao) === '')) {
            throw new RuntimeException('Esta opção é exclusiva para cobranças OFX positivas sem parcelamento.');
        }

        $quantidade = 1;

        if ($repetir) {
            $chave = ccChaveNomeRecorrente($descricao);

            ccExec(
                $conn,
                'INSERT INTO cartao_nomes_recorrentes
                    (chave_descricao, descricao_original, nome_personalizado)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    descricao_original = VALUES(descricao_original),
                    nome_personalizado = VALUES(nome_personalizado)',
                'sss',
                [$chave, $descricao, $nome]
            );

            $cobrancas = ccRows(
                $conn,
                "SELECT id, nome, nome_original FROM compras
                 WHERE origem = 'ofx' AND total_parcelas = 1 AND valor_total > 0"
            );

            $quantidade = 0;

            foreach ($cobrancas as $cobranca) {
                $original = $cobranca['nome_original'] ?? $cobranca['nome'];

                if (ccChaveNomeRecorrente($original) !== $chave) {
                    continue;
                }

                ccExec(
                    $conn,
                    'UPDATE compras
                     SET nome_original = COALESCE(nome_original, nome), nome = ?
                     WHERE id = ?',
                    'si',
                    [$nome, (int) $cobranca['id']]
                );

                $quantidade++;
            }
        } else {
            ccExec(
                $conn,
                'UPDATE compras
                 SET nome_original = COALESCE(nome_original, nome), nome = ?
                 WHERE id = ?',
                'si',
                [$nome, $compraId]
            );

            if ($permite) {
                ccExec(
                    $conn,
                    'DELETE FROM cartao_nomes_recorrentes WHERE chave_descricao = ?',
                    's',
                    [ccChaveNomeRecorrente($descricao)]
                );
            }
        }

        $conn->commit();
        $emTransacao = false;

        return $repetir
            ? 'Nome aplicado a ' . $quantidade . ' cobrança(s). As próximas com a mesma descrição receberão este nome. Categorias e valores foram mantidos.'
            : 'Nome atualizado nesta compra e suas parcelas. A repetição automática desta descrição está desativada; nomes de outras compras foram mantidos.';
    } catch (Throwable $e) {
        if ($emTransacao) {
            $conn->rollback();
        }

        throw $e;
    } finally {
        ccRows($conn, "SELECT RELEASE_LOCK('mycashflow_cartao_importacao')");
    }
}

function ccImportar(
    $conn,
    $conteudo,
    $arquivo,
    $mesPadrao,
    $anoPadrao,
    $reprocessar = false,
    array $escolhas = []
) {
    $hash = hash('sha256', $conteudo);
    [$mf, $af] = obterReferenciaFatura($arquivo, $mesPadrao, $anoPadrao);
    if ($mf < 1 || $mf > 12 || $af < 2000 || $af > 2100) {
        throw new RuntimeException('Competência inválida no nome do arquivo.');
    }
    $inicio = sprintf('%04d-%02d-01', $af, $mf);
    $fim = (new DateTime($inicio))->modify('+1 month')->format('Y-m-d');
    $lista = ccLerOfx($conteudo);
    $lock = ccRows($conn, "SELECT GET_LOCK('mycashflow_cartao_importacao', 10) AS adquirido");
    if ((int) $lock[0]['adquirido'] !== 1) {
        throw new RuntimeException('Outra importação está em andamento. Tente novamente.');
    }
    $emTransacao = false;
    try {
        $conn->begin_transaction();
        $emTransacao = true;
        $registro = ccRows($conn, 'SELECT id FROM ofx_importacoes WHERE hash_arquivo = ? LIMIT 1', 's', [$hash]);
        if ($registro && !$reprocessar) {
            $conn->rollback();
            $emTransacao = false;
            return [
                $mf,
                $af,
                'Este arquivo já foi importado. Para conferir novamente os valores, use Reprocessar no modal de importação.'
            ];
        }
        $processadas = 0;
        $ignoradas = 0;
        $usadas = [];
        $fitids = [];
        $periodoInicio = null;
        $periodoFim = null;
        foreach ($lista as $indice => $item) {
            $nome = trim($item['MEMO'] ?: $item['NAME']);
            $nome = $nome !== '' ? $nome : 'Transação OFX';
            if (ccPagamentoFatura($nome)) {
                $ignoradas++;
                continue;
            }
            $fitid = $item['FITID'];
            if ($fitid === '' || strlen($fitid) > 255) {
                throw new RuntimeException('Há um lançamento sem FITID válido. A importação foi cancelada.');
            }
            if (isset($fitids[$fitid])) {
                throw new RuntimeException('O arquivo repete um FITID. Revise o OFX antes de importar.');
            }
            $fitids[$fitid] = true;
            $data = substr($item['DTPOSTED'], 0, 8);
            $dataOriginal = DateTime::createFromFormat('!Ymd', $data);
            if (!$dataOriginal || $dataOriginal->format('Ymd') !== $data) {
                throw new RuntimeException('Data inválida no lançamento ' . $nome);
            }
            $numero = str_replace(',', '.', $item['TRNAMT']);
            if (!preg_match('/^-?\d+(?:\.\d{1,2})?$/D', $numero)) {
                throw new RuntimeException('Valor inválido no lançamento ' . $nome);
            }
            $valorBanco = (float) $numero;
            if ($valorBanco == 0) {
                $ignoradas++;
                continue;
            }
            $credito = strtoupper($item['TRNTYPE']) === 'CREDIT' || $valorBanco > 0;
            $valor = $credito ? -abs($valorBanco) : abs($valorBanco);
            $dados = $credito ? ['parcela' => 1, 'total' => 1, 'nome' => $nome] : extrairParcela($nome);
            $np = $dados['parcela'];
            $tp = $dados['total'];
            $nomeBanco = $dados['nome'] ?: $nome;
            if (mb_strlen($nomeBanco, 'UTF-8') > 255) {
                throw new RuntimeException('Descrição excede 255 caracteres.');
            }
            $dataSql = $dataOriginal->format('Y-m-d');
            $periodoInicio = $periodoInicio === null ? $dataSql : min($periodoInicio, $dataSql);
            $periodoFim = $periodoFim === null ? $dataSql : max($periodoFim, $dataSql);
            $base = dataNoMes($af, $mf, (int) $dataOriginal->format('d'));
            $primeiroMes = adicionarMeses($base, 1 - $np)->format('Y-m');
            $chave = hash(
                'sha256',
                'v2|' . ($credito ? 'credito' : 'compra') . '|' . normalizarTexto($nomeBanco) . '|' . $dataSql . '|' . $tp . '|' . $primeiroMes . ($tp === 1 ? '|' . $fitid : '')
            );
            // FITID é conferido dentro da competência, pois não identifica a compra inteira.
            $porFitid = ccRows(
                $conn,
                'SELECT DISTINCT c.* FROM compras c JOIN cartoes p ON p.compra_id = c.id WHERE p.fitid = ? AND p.data >= ? AND p.data < ?',
                'sss',
                [$fitid, $inicio, $fim]
            );
            $candidatas = [];
            foreach ($porFitid as $c) {
                if ($c['data_compra'] !== $dataSql || (int) $c['total_parcelas'] !== $tp || normalizarTexto($c['nome_original'] ?? $c['nome']) !== normalizarTexto($nomeBanco) || (($c['valor_total'] < 0) !== $credito)) {
                    throw new RuntimeException('FITID associado a dados diferentes: ' . $nomeBanco . '. Nenhuma alteração foi confirmada.');
                }
                $candidatas[$c['id']] = $c;
            }
            if (!$candidatas && $tp > 1) {
                $possiveis = ccRows(
                    $conn,
                    "SELECT * FROM compras WHERE data_compra = ? AND total_parcelas = ? AND origem = 'ofx'",
                    'si',
                    [$dataSql, $tp]
                );
                foreach ($possiveis as $c) {
                    if (normalizarTexto($c['nome_original'] ?? $c['nome']) !== normalizarTexto($nomeBanco)) {
                        continue;
                    }
                    $parcelas = ccRows($conn, 'SELECT data, parcela FROM cartoes WHERE compra_id = ?', 'i', [(int) $c['id']]);
                    $mesesInicio = [];
                    foreach ($parcelas as $p) {
                        $mesesInicio[adicionarMeses(new DateTime($p['data']), 1 - (int) $p['parcela'])->format('Y-m')] = true;
                    }
                    if (isset($mesesInicio[$primeiroMes])) {
                        $candidatas[$c['id']] = $c;
                    }
                }
            }
            foreach ($usadas as $idUsado => $ignorado) {
                unset($candidatas[$idUsado]);
            }
            $escolha = $escolhas[$indice] ?? null;
            if ($escolha !== null) {
                if ((int) $escolha !== 0 && !isset($candidatas[(int) $escolha])) {
                    throw new RuntimeException('A correspondência escolhida mudou. Envie o arquivo novamente.');
                }
                $compra = (int) $escolha === 0 ? null : $candidatas[(int) $escolha];
            }
            elseif (count($candidatas) > 1) {
                throw new CcRevisao(
                    $indice,
                    $nomeBanco . ' — parcela ' . $np . '/' . $tp . ' — R$ ' . number_format($valor, 2, ',', '.'),
                    array_values($candidatas)
                );
            }
            else {
                $compra = $candidatas ? reset($candidatas) : null;
            }
            if ($compra) {
                $compraId = (int) $compra['id'];
            }
            else {
                $nomeExibido = $nomeBanco;

                if (!$credito && $tp === 1) {
                    $regrasNome = ccRows(
                        $conn,
                        'SELECT nome_personalizado FROM cartao_nomes_recorrentes
                         WHERE chave_descricao = ?',
                        's',
                        [ccChaveNomeRecorrente($nomeBanco)]
                    );

                    if ($regrasNome) {
                        $nomeExibido = $regrasNome[0]['nome_personalizado'];
                    }
                }

                ccExec(
                    $conn,
                    "INSERT INTO compras (nome, nome_original, categoria, valor_total, total_parcelas, data_compra, origem, identificador_ofx) VALUES (?, ?, 'pessoal', ?, ?, ?, 'ofx', ?)",
                    'ssdiss',
                    [$nomeExibido, $nomeBanco, round($valor * $tp, 2), $tp, $dataSql, $chave]
                );
                $compraId = (int) $conn->insert_id;
            }
            $usadas[$compraId] = true;
            for ($p = 1; $p <= $tp; $p++) {
                $dataParcela = adicionarMeses($base, $p - $np)->format('Y-m-d');
                $existentes = ccRows(
                    $conn,
                    'SELECT id, data FROM cartoes WHERE compra_id = ? AND parcela = ?',
                    'ii',
                    [$compraId, $p]
                );
                if (count($existentes) > 1) {
                    throw new RuntimeException('Há parcelas duplicadas na compra #' . $compraId . '. É necessário revisar o histórico antes de importar.');
                }
                if ($existentes) {
                    if ($p === $np) {
                        if (substr($existentes[0]['data'], 0, 7) !== substr($inicio, 0, 7)) {
                            throw new RuntimeException('Competência divergente na compra #' . $compraId);
                        }
                        ccExec(
                            $conn,
                            'UPDATE cartoes SET valor = ?, fitid = ? WHERE id = ?',
                            'dsi',
                            [$valor, $fitid, (int) $existentes[0]['id']]
                        );
                    }
                }
                else {
                    ccExec(
                        $conn,
                        'INSERT INTO cartoes (compra_id, data, valor, parcela, paga, fitid) VALUES (?, ?, ?, ?, 0, ?)',
                        'isdis',
                        [$compraId, $dataParcela, $valor, $p, $p === $np ? $fitid : null]
                    );
                }
            }
            ccExec(
                $conn,
                'UPDATE compras SET valor_total = (SELECT COALESCE(SUM(valor),0) FROM cartoes WHERE compra_id = ?) WHERE id = ?',
                'ii',
                [$compraId, $compraId]
            );
            $processadas++;
        }
        if (!$registro) {
            ccExec(
                $conn,
                'INSERT INTO ofx_importacoes (nome_arquivo, hash_arquivo, periodo_inicio, periodo_fim, quantidade_transacoes) VALUES (?, ?, ?, ?, ?)',
                'ssssi',
                [$arquivo, $hash, $periodoInicio, $periodoFim, count($lista)]
            );
        }
        $conn->commit();
        $emTransacao = false;
        return [
            $mf,
            $af,
            $processadas . ' lançamentos conferidos com o OFX. ' . $ignoradas . ' pagamentos/valores zero ignorados. Nomes, categorias e pagamentos preservados.'
        ];
    }
    catch (Throwable $e) {
        if ($emTransacao) {
            $conn->rollback();
        }
        throw $e;
    }
    finally {
        ccRows($conn, "SELECT RELEASE_LOCK('mycashflow_cartao_importacao')");
    }
}
if (isset($_POST['renomear_compra'])) {
    $nome = is_string($_POST['novo_nome'] ?? null) ? trim($_POST['novo_nome']) : '';

    try {
        $_SESSION['cartao_aviso'] = ccRenomearCompra(
            $conn,
            (int) ($_POST['compra_id'] ?? 0),
            $nome,
            isset($_POST['repetir_nome'])
        );
    } catch (Throwable $e) {
        $_SESSION['cartao_aviso'] = 'Não foi possível renomear: ' . $e->getMessage();
    }

    voltarPagina($paginaAtual, $mes, $ano);
}
if (isset($_POST['cancelar_revisao'])) {
    unset($_SESSION['cc_pendente']);
    voltarPagina($paginaAtual, $mes, $ano);
}
if (isset($_POST['upload_fatura']) || isset($_POST['resolver_ofx'])) {
    try {
        if (isset($_POST['resolver_ofx'])) {
            $pendente = $_SESSION['cc_pendente'] ?? null;
            if (
                !$pendente || !hash_equals($pendente['token'], (string) ($_POST['revisao_token'] ?? ''))
            ) {
                throw new RuntimeException('Revisão expirada. Envie novamente o OFX.');
            }
            $opcao = filter_var($_POST['correspondencia'] ?? null, FILTER_VALIDATE_INT);
            $validas = array_column($pendente['opcoes'], 'id');
            if (
                $opcao === false || ($opcao !== 0 && !in_array($opcao, array_map('intval', $validas), true))
            ) {
                throw new RuntimeException('Selecione uma correspondência válida.');
            }
            $pendente['escolhas'][$pendente['indice']] = $opcao;
        }
        else {
            if (!isset($_FILES['fatura']) || $_FILES['fatura']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Não foi possível receber o arquivo.');
            }
            if ($_FILES['fatura']['size'] > 5 * 1024 * 1024) {
                throw new RuntimeException('O limite é de 5 MB por OFX.');
            }
            $arquivo = basename($_FILES['fatura']['name']);
            if (strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) !== 'ofx') {
                throw new RuntimeException('Selecione um arquivo OFX.');
            }
            $conteudo = file_get_contents($_FILES['fatura']['tmp_name']);
            if ($conteudo === false || trim($conteudo) === '') {
                throw new RuntimeException('O arquivo está vazio ou ilegível.');
            }
            $pendente = [
                'conteudo' => $conteudo,
                'arquivo' => $arquivo,
                'mes' => $mes,
                'ano' => $ano,
                'reprocessar' => isset($_POST['reprocessar']),
                'escolhas' => []
            ];
            unset($_SESSION['cc_pendente']);
        }
        [$destinoMes, $destinoAno, $avisoOfx] = ccImportar(
            $conn,
            $pendente['conteudo'],
            $pendente['arquivo'],
            $pendente['mes'],
            $pendente['ano'],
            $pendente['reprocessar'],
            $pendente['escolhas']
        );
        unset($_SESSION['cc_pendente']);
        $_SESSION['cartao_aviso'] = $avisoOfx;
        voltarPagina($paginaAtual, $destinoMes, $destinoAno);
    }
    catch (CcRevisao $e) {
        $pendente['indice'] = $e->indice;
        $pendente['opcoes'] = $e->opcoes;
        $pendente['descricao'] = $e->getMessage();
        $pendente['token'] = bin2hex(random_bytes(16));
        $_SESSION['cc_pendente'] = $pendente;
        $_SESSION['cartao_aviso'] = 'Há mais de uma compra compatível. Nenhuma alteração foi gravada. Revise a correspondência para continuar.';
    }
    catch (Throwable $e) {
        $_SESSION['cartao_aviso'] = 'Importação não concluída: ' . $e->getMessage();
    }
    voltarPagina($paginaAtual, $mes, $ano);
}

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    'SELECT
        cartoes.id,
        cartoes.compra_id,
        cartoes.data,
        cartoes.valor,
        cartoes.parcela,
        cartoes.paga,
        compras.nome, compras.nome_original,
        compras.origem, compras.valor_total,
        compras.categoria,
        compras.total_parcelas
     FROM cartoes
     INNER JOIN compras
        ON compras.id = cartoes.compra_id
     WHERE MONTH(cartoes.data) = ?
       AND YEAR(cartoes.data) = ?
     ORDER BY cartoes.data ASC, cartoes.id ASC'
);

if (!$stmt) {
    die('Erro ao preparar consulta: ' . $conn->error);
}

$stmt->bind_param('ii', $mes, $ano);
$stmt->execute();

$resultado = $stmt->get_result();

$cartoes = [];
$comprasDoMes = [];
$total = 0;
$totalFatura = 0;
$quantidadeFatura = 0;
$pagasFatura = 0;

$regrasNomes = ccRows(
    $conn,
    'SELECT chave_descricao FROM cartao_nomes_recorrentes'
);

$chavesNomes = array_fill_keys(array_column($regrasNomes, 'chave_descricao'), true);

while ($linha = $resultado->fetch_assoc()) {
    $totalFatura += (float) $linha['valor'];
    $quantidadeFatura++;
    $pagasFatura += (int) $linha['paga'] === 1 ? 1 : 0;

    if ($filtroCategoria !== '' && $linha['categoria'] !== $filtroCategoria) {
        continue;
    }

    $linha['permite_nome_recorrente'] = ccPermiteNomeRecorrente($linha);
    $linha['repetir_nome'] = $linha['permite_nome_recorrente'] && isset(
        $chavesNomes[ccChaveNomeRecorrente($linha['nome_original'] ?? $linha['nome'])]
    );

    $cartoes[] = $linha;
    $comprasDoMes[$linha['compra_id']] = $linha;
    $total += (float) $linha['valor'];
}

$stmt->close();

$meses = [
    'Janeiro',
    'Fevereiro',
    'Março',
    'Abril',
    'Maio',
    'Junho',
    'Julho',
    'Agosto',
    'Setembro',
    'Outubro',
    'Novembro',
    'Dezembro'
];

$mensagemImportacao = '';

if (isset($_GET['importacao'])) {
    if ($_GET['importacao'] === 'duplicada') {
        $mensagemImportacao =
        'Este arquivo OFX já foi importado anteriormente.';
    }

    if ($_GET['importacao'] === 'ok') {
        $mensagemImportacao =
        (int) ($_GET['importadas'] ?? 0) .
        ' transação(ões) importada(s).';

        if ((int) ($_GET['ignoradas'] ?? 0) > 0) {
            $mensagemImportacao .=
            ' ' .
            (int) $_GET['ignoradas'] .
            ' transação(ões) ignorada(s).';
        }
    }
}

?>

<?php

$acaoFormulario = $paginaAtual . '?' . http_build_query(
    parametrosPaginaCartao($mes, $ano)
);
$rotuloFiltro = $filtroCategoria === ''
    ? 'Todas as categorias'
    : rotuloCategoria($filtroCategoria);
$rotuloTotal = $filtroCategoria === ''
    ? 'Total da fatura'
    : 'Total — ' . $rotuloFiltro;
$aviso = $_SESSION['cartao_aviso'] ?? '';
unset($_SESSION['cartao_aviso']);
$pagas = count(array_filter($cartoes, function ($item) {
    return (int) $item['paga'] === 1;
}
));
function tokenCartao()
{
    echo '<input type="hidden" name="csrf_cartao" value="' . escapar($_SESSION['csrf_cartao']) . '">';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            Cartão de Crédito
        </title>
        <link rel="stylesheet" href="../assets/css/style-cartao.css?v=1">
        <style>
            .cc-page {
                box-sizing: border-box;
                width: 100%;
                max-width: 1400px;
                margin: 0 auto;
                padding: 20px;
                flex: 1;
                color: #2c3e50
            }
            
            .cc-page * {
                box-sizing: border-box
            }
            
            .cc-top,.cc-actions,.cc-summary,.cc-dialog header,.cc-dialog footer {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap
            }
            
            .cc-top {
                justify-content: space-between;
                margin-bottom: 18px
            }
            
            .cc-top h1 {
                font-size: 24px;
                margin: 0 0 5px
            }
            
            .cc-top p {
                margin: 0;
                color: #596b77
            }
            
            .cc-page button,.cc-dialog button {
                padding: 9px 12px;
                border: 1px solid #ccd5da;
                border-radius: 6px;
                background: #fff;
                color: #2c3e50;
                cursor: pointer;
                font: inherit
            }
            
            .cc-page button:hover,.cc-dialog button:hover {
                background: #edf2f5
            }
            
            .cc-page .cc-primary,.cc-dialog .cc-primary {
                background: #2c3e50;
                color: white;
                border-color: #2c3e50
            }
            
            .cc-page .cc-danger,.cc-dialog .cc-danger {
                color: #a52626;
                border-color: #dca9a9
            }
            
            .cc-page button:disabled {
                opacity: .5;
                cursor: default
            }
            
            .cc-page button:focus-visible,.cc-dialog :focus-visible {
                outline: 3px solid #2781b4;
                outline-offset: 2px
            }
            
            .cc-card {
                background: #fff;
                border: 1px solid #dde3e7;
                border-radius: 10px;
                overflow: hidden
            }
            
            .cc-summary {
                justify-content: space-between;
                padding: 16px;
                border-bottom: 1px solid #e0e5e8
            }
            
            .cc-summary h2 {
                font-size: 18px;
                margin: 0
            }
            
            .cc-summary strong {
                font-size: 22px
            }
            
            .cc-summary small {
                display: block;
                margin-top: 4px;
                color: #586b75
            }
            
            .cc-scroll {
                overflow-x: auto
            }
            
            .cc-table {
                width: 100%;
                border-collapse: collapse;
                text-align: left
            }
            
            .cc-table th,.cc-table td {
                padding: 12px;
                border-bottom: 1px solid #e5e9ec
            }
            
            .cc-table th {
                background: #2c3e50;
                color: white;
                font-size: 13px
            }
            
            .cc-table td {
                font-size: 14px
            }
            
            .cc-table .cc-number {
                text-align: right;
                white-space: nowrap
            }
            
            .cc-table tr[data-paga="1"] {
                background: #eff8f3
            }
            
            .cc-table tfoot td {
                font-weight: bold;
                background: #f4f6f7
            }
            
            .cc-row-actions {
                display: flex;
                gap: 5px
            }
            
            .cc-row-actions button {
                font-size: 12px;
                padding: 6px 8px
            }
            
            .cc-status {
                white-space: nowrap;
                font-size: 12px
            }
            
            .cc-empty {
                text-align: center;
                padding: 32px!important
            }
            
            .cc-notice {
                padding: 12px 16px;
                background: #e7f2f8;
                border: 1px solid #c4dce9;
                border-radius: 6px;
                margin-bottom: 16px
            }
            
            .cc-dialog {
                box-sizing: border-box;
                width: calc(100% - 32px);
                max-width: 520px;
                border: 0;
                border-radius: 10px;
                padding: 22px;
                color: #2c3e50;
                max-height: 90vh;
                overflow: auto;
                font-family: Arial,sans-serif
            }
            
            .cc-dialog::backdrop {
                background: rgba(20,32,44,.55)
            }
            
            .cc-dialog header {
                justify-content: space-between;
                margin-bottom: 15px
            }
            
            .cc-dialog h2 {
                font-size: 20px;
                margin: 0
            }
            
            .cc-dialog label {
                display: block;
                font-weight: bold;
                margin: 14px 0 6px
            }
            
            .cc-dialog input:not([type=hidden]),.cc-dialog select {
                width: 100%;
                padding: 10px;
                border: 1px solid #bcc7cd;
                border-radius: 5px;
                font: inherit;
                box-sizing: border-box
            }
            
            .cc-dialog p {
                line-height: 1.5
            }
            
            .cc-dialog footer {
                justify-content: flex-end;
                margin-top: 20px
            }
            
            .cc-dialog .cc-check {
                display: flex;
                gap: 8px;
                align-items: center;
                font-weight: normal
            }
            
            .cc-dialog .cc-check input {
                width: auto
            }
            
            .cc-choices {
                max-height: 230px;
                overflow: auto
            }
            
            .cc-dialog small {
                display: block;
                margin-top: 8px;
                color: #596b77
            }
            
            .cc-row-actions form {
                margin: 0
            }
            
            @media(max-width:700px) {
                .cc-page {
                    padding: 12px
                }
            
                .cc-top h1 {
                    font-size: 21px
                }
            
                .cc-actions {
                    width: 100%
                }
            
                .cc-actions button {
                    flex: 1
                }
            
                .cc-table {
                    min-width: 850px
                }
            
                .cc-summary {
                    align-items: flex-start
                }
            
                .cc-summary strong {
                    font-size: 20px
                }
            
            }
        </style>
    </head>
    <body>
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <?php include __DIR__ . '/../includes/menu.php'; ?>
        <main class="cc-page">
            <?php foreach ([$mensagemImportacao, $aviso] as $textoAviso): if ($textoAviso !== ''): ?>
            <div class="cc-notice" role="status">
                <?= escapar($textoAviso) ?>
            </div>
            <?php endif; endforeach; ?>
            <?php if (isset($_SESSION['cc_pendente'])): $revisao = $_SESSION['cc_pendente']; ?>
            <div class="cc-notice">
                <strong>
                    Importação aguardando revisão
                </strong>
                <button type="button" data-open="cc-revisao">
                    Revisar correspondência
                </button>
            </div>
            <dialog id="cc-revisao" class="cc-dialog" aria-labelledby="cc-revisao-title">
                <header>
                    <h2 id="cc-revisao-title">
                        Identificar compra
                    </h2>
                    <button type="button" data-close aria-label="Fechar">
                        ×
                    </button>
                </header>
                <p>
                    <?= escapar($revisao['descricao']) ?>
                </p>
                <p>
                    Há mais de um cadastro compatível. Se forem duplicatas antigas, escolher um não apaga os demais; eles precisam ser revisados separadamente.
                </p>
                <form method="post" action="<?= escapar($acaoFormulario) ?>">
                    <?php tokenCartao(); ?>
                    <input type="hidden" name="revisao_token" value="<?= escapar($revisao['token']) ?>">
                    <label for="cc-correspondencia">
                        A qual compra pertence este lançamento?
                    </label>
                    <select id="cc-correspondencia" name="correspondencia" required>
                        <option value="">
                            Selecione
                        </option>
                        <?php foreach ($revisao['opcoes'] as $opcao): ?>
                        <option value="<?= (int) $opcao['id'] ?>">
                            #
                            <?= (int) $opcao['id'] ?>
                            —
                            <?= escapar($opcao['nome']) ?>
                            —
                            <?= escapar(rotuloCategoria($opcao['categoria'])) ?>
                            —
                            <?= escapar($opcao['data_compra']) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="0">
                            É outra compra: criar um cadastro separado
                        </option>
                    </select>
                    <footer>
                        <button name="cancelar_revisao" formnovalidate>
                            Cancelar importação
                        </button>
                        <button name="resolver_ofx" class="cc-primary">
                            Continuar importação
                        </button>
                    </footer>
                </form>
            </dialog>
            <?php endif; ?>
            <header class="cc-top">
                <div>
                    <h1>
                        Cartão de Crédito
                    </h1>
                    <p>
                        Consulte a fatura e organize suas compras.
                    </p>
                </div>
                <div class="cc-actions">
                    <button type="button" class="cc-primary" data-open="cc-importar">
                        Importar OFX
                    </button>
                    <button type="button" data-open="cc-lote" <?= !$cartoes ? 'disabled' : '' ?>>
                        Classificar compras
                    </button>
                    <button type="button" data-open="cc-filtro">
                        Filtrar mês e categoria
                    </button>
                </div>
            </header>
            <section class="cc-card" aria-label="Fatura mensal">
                <div class="cc-summary">
                    <div>
                        <h2>
                            <?= escapar($meses[$mes - 1]) ?>
                            /
                            <?= $ano ?>
                        </h2>
                        <small>
                            <?= escapar($rotuloFiltro) ?> ·
                            <?= count($cartoes) ?>
                            parcelas ·
                            <?= $pagas ?>
                            pagas
                        </small>
                    </div>
                    <div>
                        <strong>
                            R$
                            <?= number_format($total, 2, ',', '.') ?>
                        </strong>
                        <small>
                            <?= escapar($rotuloTotal) ?>
                        </small>
                        <?php if ($filtroCategoria !== ''): ?>
                        <small>
                            Fatura completa: R$ <?= number_format($totalFatura, 2, ',', '.') ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <button
                        type="button"
                        data-open="cc-pagar"
                        <?= !$quantidadeFatura || $pagasFatura === $quantidadeFatura ? 'disabled' : '' ?>
                    >
                        <?= $quantidadeFatura && $pagasFatura === $quantidadeFatura ? 'Fatura paga' : 'Marcar fatura completa como paga' ?>
                    </button>
                </div>
                <div
                    class="cc-scroll"
                    tabindex="0"
                    role="region"
                    aria-label="Lançamentos da fatura; role horizontalmente em telas pequenas"
                >
                    <table class="cc-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    Compra
                                </th>
                                <th scope="col">
                                    Categoria
                                </th>
                                <th scope="col">
                                    Data da parcela
                                </th>
                                <th scope="col">
                                    Parcela
                                </th>
                                <th scope="col" class="cc-number">
                                    Valor
                                </th>
                                <th scope="col">
                                    Situação
                                </th>
                                <th scope="col">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$cartoes): ?>
                            <tr>
                                <td colspan="7" class="cc-empty">
                                    Nenhum lançamento para os filtros selecionados.
                                    Altere o mês ou selecione Todas as categorias.
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php foreach ($cartoes as $cartao): ?>
                            <tr data-paga="<?= (int) $cartao['paga'] ?>">
                                <td>
                                    <?= escapar($cartao['nome']) ?>
                                    <small style="display:block;color:#596b77">
                                        <?= escapar($cartao['nome_original'] ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= escapar(rotuloCategoria($cartao['categoria'])) ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($cartao['data'])) ?>
                                </td>
                                <td>
                                    <?= (int) $cartao['parcela'] ?>
                                    /
                                    <?= (int) $cartao['total_parcelas'] ?>
                                </td>
                                <td class="cc-number">
                                    R$
                                    <?= number_format((float) $cartao['valor'], 2, ',', '.') ?>
                                </td>
                                <td class="cc-status">
                                    <?= (float) $cartao['valor'] < 0 ? 'Crédito/estorno' : ($cartao['paga'] ? 'Paga' : 'Em aberto') ?>
                                </td>
                                <td>
                                    <div class="cc-row-actions">
                                        <button type="button" data-row="<?= (int) $cartao['id'] ?>" data-open="cc-nome">
                                            Renomear
                                        </button>
                                        <button type="button" data-row="<?= (int) $cartao['id'] ?>" data-open="cc-categoria">
                                            Classificar
                                        </button>
                                        <button type="button" data-row="<?= (int) $cartao['id'] ?>" data-open="cc-ajustar">
                                            Ajustar
                                        </button>
                                        <button
                                            type="button"
                                            data-row="<?= (int) $cartao['id'] ?>"
                                            data-open="cc-excluir"
                                            class="cc-danger"
                                        >
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">
                                    <?= escapar($rotuloTotal) ?>
                                </td>
                                <td class="cc-number">
                                    R$
                                    <?= number_format($total, 2, ',', '.') ?>
                                </td>
                                <td colspan="2">
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </main>
        <dialog id="cc-importar" class="cc-dialog" aria-labelledby="cc-importar-title">
            <header>
                <h2 id="cc-importar-title">
                    Importar fatura OFX
                </h2>
                <button type="button" data-close aria-label="Fechar">
                    ×
                </button>
            </header>
            <form method="post" action="<?= escapar($acaoFormulario) ?>" enctype="multipart/form-data">
                <?php tokenCartao(); ?>
                <p>
                    O mês será identificado pelo nome do arquivo, como
                    <strong>
                        Ago_26.ofx
                    </strong>
                    . Se não houver essa referência, será usado
                    <?= escapar($meses[$mes - 1]) ?>
                    /
                    <?= $ano ?>
                    .
                </p>
                <label for="cc-arquivo">
                    Arquivo da fatura
                </label>
                <input id="cc-arquivo" type="file" name="fatura" accept=".ofx" required>
                <label class="cc-check">
                    <input type="checkbox" name="reprocessar" value="1">
                    Reprocessar arquivo já importado
                </label>
                <small>
                    Reprocessar substitui os valores das parcelas desta fatura pelos valores do OFX, inclusive ajustes manuais. Não remove duplicatas antigas nem lançamentos ausentes do arquivo.
                </small>
                <footer>
                    <button type="button" data-close>
                        Cancelar
                    </button>
                    <button class="cc-primary" name="upload_fatura">
                        Importar OFX
                    </button>
                </footer>
            </form>
        </dialog>
        <dialog id="cc-filtro" class="cc-dialog" aria-labelledby="cc-filtro-title">
            <header>
                <h2 id="cc-filtro-title">
                    Filtrar fatura
                </h2>
                <button type="button" data-close aria-label="Fechar">
                    ×
                </button>
            </header>
            <form method="get" action="<?= escapar($paginaAtual) ?>">
                <label for="cc-mes">
                    Mês
                </label>
                <select name="mes" id="cc-mes">
                    <?php foreach ($meses as $indice => $nomeMes): ?>
                    <option value="<?= $indice + 1 ?>" <?= $indice + 1 === $mes ? 'selected' : '' ?>>
                        <?= escapar($nomeMes) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <label for="cc-ano">
                    Ano
                </label>
                <input
                    id="cc-ano"
                    name="ano"
                    type="number"
                    min="2000"
                    max="2100"
                    value="<?= $ano ?>"
                    required
                >
                <label for="cc-filtro-categoria">
                    Categoria
                </label>
                <select name="filtro_categoria" id="cc-filtro-categoria">
                    <option value="" <?= $filtroCategoria === '' ? 'selected' : '' ?>>
                        Todas as categorias
                    </option>
                    <?php foreach (['pessoal', 'conjunta', 'unica'] as $categoriaFiltro): ?>
                    <option
                        value="<?= $categoriaFiltro ?>"
                        <?= $filtroCategoria === $categoriaFiltro ? 'selected' : '' ?>
                    >
                        <?= escapar(rotuloCategoria($categoriaFiltro)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small>
                    A tabela e seu total mostrarão somente a categoria selecionada.
                    Selecione Todas as categorias para voltar à fatura completa.
                </small>
                <footer>
                    <button type="button" data-close>
                        Cancelar
                    </button>
                    <button class="cc-primary">
                        Aplicar filtro
                    </button>
                </footer>
            </form>
        </dialog>
        <dialog id="cc-lote" class="cc-dialog" aria-labelledby="cc-lote-title">
            <header>
                <h2 id="cc-lote-title">
                    Classificar compras
                </h2>
                <button type="button" data-close aria-label="Fechar">
                    ×
                </button>
            </header>
            <form method="post" action="<?= escapar($acaoFormulario) ?>">
                <?php tokenCartao(); ?>
                <p>
                    A categoria será aplicada à compra inteira, incluindo suas parcelas nos outros meses.
                </p>
                <label class="cc-check">
                    <input type="checkbox" id="cc-todas">
                    Selecionar todas deste mês
                </label>
                <div class="cc-choices">
                    <?php foreach ($comprasDoMes as $compra): ?>
                    <label class="cc-check">
                        <input type="checkbox" name="compras[]" value="<?= (int) $compra['compra_id'] ?>">
                        <?= escapar($compra['nome']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <label for="cc-lote-cat">
                    Categoria
                </label>
                <select name="categoria" id="cc-lote-cat">
                    <?php foreach (['pessoal', 'conjunta', 'unica'] as $cat): ?>
                    <option value="<?= $cat ?>">
                        <?= escapar(rotuloCategoria($cat)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <footer>
                    <button type="button" data-close>
                        Cancelar
                    </button>
                    <button class="cc-primary" name="atualizar_categoria_lote">
                        Salvar categorias
                    </button>
                </footer>
            </form>
        </dialog>
        <?php foreach (['nome' => 'Renomear compra', 'categoria' => 'Classificar compra', 'ajustar' => 'Ajustar parcela', 'excluir' => 'Excluir compra'] as $tipoModal => $tituloModal): ?>
        <dialog
            id="cc-<?= $tipoModal ?>"
            class="cc-dialog"
            aria-labelledby="cc-<?= $tipoModal ?>-title"
        >
            <header>
                <h2 id="cc-<?= $tipoModal ?>-title">
                    <?= $tituloModal ?>
                </h2>
                <button type="button" data-close aria-label="Fechar">
                    ×
                </button>
            </header>
            <form method="post" action="<?= escapar($acaoFormulario) ?>">
                <?php tokenCartao(); ?>
                <p data-nome>
                </p>
                <input type="hidden" name="compra_id">
                <input type="hidden" name="id">
                <?php if ($tipoModal === 'nome'): ?>
                <p>
                    O nome será exibido em todas as parcelas, inclusive nas próximas importações. A descrição original do banco será mantida.
                </p>
                <label for="cc-novo-nome">
                    Nome da compra
                </label>
                <input id="cc-novo-nome" name="novo_nome" maxlength="255" required>
                <div id="cc-nome-recorrente" hidden>
                    <label class="cc-check" for="cc-repetir-nome">
                        <input
                            type="checkbox"
                            id="cc-repetir-nome"
                            name="repetir_nome"
                            value="1"
                        >
                        Usar este nome nas cobranças atuais e futuras com a mesma descrição
                    </label>
                    <small>
                        Ao marcar, os nomes das cobranças já cadastradas com essa descrição
                        também serão substituídos. Cada mensalidade permanece separada;
                        valores e categorias não mudam.
                    </small>
                    <small>
                        Desmarcar e salvar desativa a regra para futuras cobranças
                        e renomeia somente esta compra. Os outros nomes já salvos permanecem.
                    </small>
                    <small>
                        Descrição usada: <span id="cc-descricao-regra"></span>
                    </small>
                </div>
                <?php elseif ($tipoModal === 'categoria'): ?>
                <p>
                    Esta categoria vale para todas as parcelas da compra.
                </p>
                <label for="cc-cat">
                    Categoria
                </label>
                <select id="cc-cat" name="categoria">
                    <?php foreach (['pessoal', 'conjunta', 'unica'] as $cat): ?>
                    <option value="<?= $cat ?>">
                        <?= escapar(rotuloCategoria($cat)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php elseif ($tipoModal === 'ajustar'): ?>
                <p>
                    O ajuste altera somente esta parcela. Use valor negativo para um estorno. Uma nova conferência deste mês pelo OFX substitui o ajuste pelo valor do banco.
                </p>
                <label for="cc-valor">
                    Novo valor (R$)
                </label>
                <input
                    id="cc-valor"
                    type="number"
                    step="0.01"
                    min="-99999999.99"
                    max="99999999.99"
                    name="novo_valor"
                    required
                >
                <?php else: ?>
                <p>
                    Excluir as parcelas desta compra de
                    <strong>
                        <?= escapar($meses[$mes - 1]) ?>
                        /
                        <?= $ano ?>
                    </strong>
                    em diante? As parcelas anteriores serão preservadas.
                </p>
                <small>
                    Uma nova fatura OFX que contenha esta compra poderá recriar as parcelas, conforme a regra atual de importação.
                </small>
                <?php endif; ?>
                <footer>
                    <button type="button" data-close>
                        Cancelar
                    </button>
                    <button class="<?= $tipoModal === 'excluir' ? 'cc-danger' : 'cc-primary' ?>" name="<?= ['nome' => 'renomear_compra', 'categoria' => 'atualizar_categoria', 'ajustar' => 'ajustar_valor', 'excluir' => 'deletar_cartao'][$tipoModal] ?>">
                        <?= $tipoModal === 'excluir' ? 'Excluir deste mês em diante' : 'Salvar' ?>
                    </button>
                </footer>
            </form>
        </dialog>
        <?php endforeach; ?>
        <dialog id="cc-pagar" class="cc-dialog" aria-labelledby="cc-pagar-title">
            <header>
                <h2 id="cc-pagar-title">
                    Marcar fatura como paga
                </h2>
                <button type="button" data-close aria-label="Fechar">
                    ×
                </button>
            </header>
            <form method="post" action="<?= escapar($acaoFormulario) ?>">
                <?php tokenCartao(); ?>
                <input type="hidden" name="mes" value="<?= $mes ?>">
                <input type="hidden" name="ano" value="<?= $ano ?>">
                <p>
                    Marcar todas as parcelas de
                    <?= escapar($meses[$mes - 1]) ?>
                    /
                    <?= $ano ?>
                    como pagas?
                </p>
                <p>
                    Esta ação inclui todas as categorias da fatura,
                    mesmo quando a tabela está filtrada.
                    Total completo: <strong>R$ <?= number_format($totalFatura, 2, ',', '.') ?></strong>.
                </p>
                <footer>
                    <button type="button" data-close>
                        Cancelar
                    </button>
                    <button class="cc-primary" name="atualizar_fatura_paga">
                        Confirmar pagamento
                    </button>
                </footer>
            </form>
        </dialog>
        <script>
            const parcelas = <?= json_encode($cartoes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
            document.querySelectorAll('[data-open]').forEach(button => button.addEventListener('click', () => {
                const modal = document.getElementById(button.dataset.open);
                if (button.dataset.row) {
                    const row = parcelas.find(item => String(item.id) === button.dataset.row);
                    if (!row) return;
                    modal.querySelector('[data-nome]').textContent = row.nome + ' · Parcela ' + row.parcela + '/' + row.total_parcelas;
                    modal.querySelector('[name=compra_id]').value = row.compra_id;
                    modal.querySelector('[name=id]').value = row.id;
                    const nome = modal.querySelector('[name=novo_nome]');
                    if (nome) nome.value = row.nome;
                    const repetirNome = modal.querySelector('[name=repetir_nome]');

                    if (repetirNome) {
                        const permite = Boolean(row.permite_nome_recorrente);

                        modal.querySelector('#cc-nome-recorrente').hidden = !permite;
                        repetirNome.disabled = !permite;
                        repetirNome.checked = permite && Boolean(row.repetir_nome);
                        modal.querySelector('#cc-descricao-regra').textContent =
                            row.nome_original || row.nome;
                    }

                    const categoria = modal.querySelector('[name=categoria]');
                    if (categoria) categoria.value = row.categoria;
                    const valor = modal.querySelector('[name=novo_valor]');
                    if (valor) valor.value = Number(row.valor).toFixed(2);
                }
                modal.showModal();
            }));
            document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
            document.getElementById('cc-todas').addEventListener('change', event => {
                document.querySelectorAll('#cc-lote [name="compras[]"]').forEach(input => input.checked = event.target.checked);
            });
            document.querySelector('#cc-lote form').addEventListener('submit', event => {
                if (!document.querySelector('#cc-lote [name="compras[]"]:checked')) {
                    event.preventDefault();
                    alert('Selecione pelo menos uma compra.');
                }
            });
        </script>
    </body>
</html>
