<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__ . '/../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

function normalizarValor($valor) {
    $valor = trim((string)$valor);
    $valor = str_replace(['R$', ' '], '', $valor);

    if ($valor === '') {
        return 0;
    }

    if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } else {
        $valor = str_replace(',', '.', $valor);
    }

    return is_numeric($valor) ? (float)$valor : 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../daytrade.php");
    exit;
}

$operacao_id = isset($_POST['id'])
    ? (int)$_POST['id']
    : 0;

$corretora_id = isset($_POST['corretora_id'])
    ? (int)$_POST['corretora_id']
    : 0;

$mes = isset($_POST['mes'])
    ? (int)$_POST['mes']
    : (int)date('n');

$ano = isset($_POST['ano'])
    ? (int)$_POST['ano']
    : (int)date('Y');

$total_compra = isset($_POST['total_compra'])
    ? normalizarValor($_POST['total_compra'])
    : 0;

$total_venda = isset($_POST['total_venda'])
    ? normalizarValor($_POST['total_venda'])
    : 0;

$taxas = isset($_POST['taxas'])
    ? normalizarValor($_POST['taxas'])
    : 0;

$taxas_detalhes = null;
if (isset($_POST['taxas_valores']) || isset($_POST['taxas_nomes'])) {
    require_once __DIR__.'/../../includes/valores.php';
    try {
        $valores=$_POST['taxas_valores']??null;$nomes=$_POST['taxas_nomes']??null;
        if (!is_array($valores)||!is_array($nomes)||!array_is_list($valores)||!array_is_list($nomes)
            || count($valores)!==count($nomes)||count($valores)<1||count($valores)>100) throw new DomainException('Taxas inválidas. Reabra o ajuste.');
        $itens=[];$centavos=0;
        foreach($valores as $i=>$valor) {
            if(!is_string($nomes[$i])||trim($nomes[$i])===''||mb_strlen($nomes[$i])>100) throw new DomainException('Nome de taxa inválido.');
            $valor=mcfValorMonetario($valor);$centavos+=(int)round($valor*100);
            $itens[]=['nome'=>trim($nomes[$i]),'valor'=>number_format($valor,2,'.','')];
        }
        if($centavos>9999999999)throw new DomainException('O total das taxas ultrapassa o limite permitido.');
        $taxas=$centavos/100;
        $taxas_detalhes=json_encode($itens,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }catch(DomainException $e){http_response_code(422);exit(htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8'));}
}

if ($operacao_id <= 0 || $corretora_id <= 0) {
    die("Operação ou corretora inválida.");
}

if ($mes < 1 || $mes > 12) {
    $mes = (int)date('n');
}

if ($ano < 2000 || $ano > 2100) {
    $ano = (int)date('Y');
}

if ($total_compra < 0 || $total_venda < 0 || $taxas < 0) {
    die("Os valores informados não podem ser negativos.");
}

$stmt = $conn->prepare("
    SELECT id
    FROM (SELECT * FROM operacoes WHERE usuario_id = @mcf_usuario_id) AS operacoes
    WHERE id = ?
    AND corretora_id = ?
");

if (!$stmt) {
    die(
        "Erro ao consultar operação: " .
        htmlspecialchars(
            'Falha de banco de dados.',
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$stmt->bind_param(
    "ii",
    $operacao_id,
    $corretora_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado || $resultado->num_rows === 0) {
    $stmt->close();
    die("Operação não encontrada.");
}

$stmt->close();

$total_compra = round($total_compra, 2);
$total_venda = round($total_venda, 2);
$taxas = round($taxas, 2);

$valor_operacao =
    $total_compra +
    $total_venda;

$valor_operacao =
    round(
        $valor_operacao,
        2
    );

$lucro_bruto = 0;
$deducao_1 = 0;
$imposto_20 = 0;
$lucro_desc = 0;
$darf = 0;
$lucro_final = 0;

if ($total_compra > 0 && $total_venda > 0) {

    $lucro_bruto =
        $total_venda -
        $total_compra;

    $lucro_bruto =
        round(
            $lucro_bruto,
            2
        );

    $lucro_desc =
        $lucro_bruto -
        $taxas;

    $lucro_desc =
        round(
            $lucro_desc,
            2
        );

    if ($lucro_desc > 0) {

        $deducao_1 =
            floor(
                ($lucro_desc * 0.01) * 100
            ) / 100;

        $imposto_20 =
            round(
                $lucro_desc * 0.20,
                2
            );

        $darf =
            $imposto_20 -
            $deducao_1;

        $darf =
            round(
                $darf,
                2
            );

        if ($darf < 0) {
            $darf = 0;
        }

        $lucro_final =
            $lucro_desc -
            $darf;

        $lucro_final =
            round(
                $lucro_final,
                2
            );

    } else {

        $deducao_1 = 0;
        $imposto_20 = 0;
        $darf = 0;

        $lucro_final =
            $lucro_desc;

    }

}

$stmt = $conn->prepare("
    UPDATE operacoes
    SET
        total_compra = ?,
        total_venda = ?,
        valor_operacao = ?,
        lucro_bruto = ?,
        taxas = ?,
        taxas_detalhes = ?,
        deducao_1 = ?,
        imposto_20 = ?,
        lucro_desc = ?,
        darf = ?,
        lucro_final = ?
    WHERE usuario_id = @mcf_usuario_id AND id = ?
    AND corretora_id = ?
");

if (!$stmt) {
    die(
        "Erro ao preparar atualização: " .
        htmlspecialchars(
            'Falha de banco de dados.',
            ENT_QUOTES,
            'UTF-8'
        )
    );
}

$stmt->bind_param(
    "dddddsdddddii",
    $total_compra,
    $total_venda,
    $valor_operacao,
    $lucro_bruto,
    $taxas,
    $taxas_detalhes,
    $deducao_1,
    $imposto_20,
    $lucro_desc,
    $darf,
    $lucro_final,
    $operacao_id,
    $corretora_id
);

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../daytrade.php?corretora_id=" .
        $corretora_id .
        "&mes=" .
        $mes .
        "&ano=" .
        $ano
    );

    exit;
}

$erro = 'Falha de banco de dados.';

$stmt->close();

die(
    "Erro ao ajustar operação: " .
    htmlspecialchars(
        $erro,
        ENT_QUOTES,
        'UTF-8'
    )
);
?>