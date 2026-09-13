<?php
include __DIR__ . '/../config.php';

// Captura os dados enviados pelo formulário
$corretora   = $_POST['corretora'] ?? '';
$nome_taxa   = $_POST['nome_taxa'] ?? '';
$taxa_valor  = $_POST['taxa_valor'] ?? '';

// Se a corretora não existir ainda, cria
if ($corretora) {
    $stmt = $conn->prepare("SELECT id FROM corretoras WHERE nome = ?");
    $stmt->bind_param("s", $corretora);
    $stmt->execute();
    $result = $stmt->get_result();
    $corretora_id = null;

    if ($row = $result->fetch_assoc()) {
        $corretora_id = $row['id'];
    } else {
        $stmt_insert = $conn->prepare("INSERT INTO corretoras (nome) VALUES (?)");
        $stmt_insert->bind_param("s", $corretora);
        $stmt_insert->execute();
        $corretora_id = $stmt_insert->insert_id;
        $stmt_insert->close();
    }
    $stmt->close();

    // Insere a taxa vinculada à corretora
    if ($nome_taxa && $taxa_valor) {
        $stmt_taxa = $conn->prepare("INSERT INTO corretora_taxas (corretora_id, nome_taxa, percentual) VALUES (?, ?, ?)");
        $stmt_taxa->bind_param("isd", $corretora_id, $nome_taxa, $taxa_valor);
        $stmt_taxa->execute();
        $stmt_taxa->close();
    }

    header("Location: daytrade.php?msg=sucesso");
    exit;
} else {
    echo "Preencha todos os campos!";
}
$conn->close();
?>
