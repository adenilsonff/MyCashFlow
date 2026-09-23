<?php
// Executar somente após backup recuperável. Não executa a migração multiusuário.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!getenv('MCF_DB_NAME')) { fwrite(STDERR, "Defina MCF_DB_NAME explicitamente.\n"); exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__.'/../includes/perfil.php';
try {
    $c = new mysqli(getenv('MCF_DB_HOST') ?: '127.0.0.1', getenv('MCF_DB_USER') ?: 'root', getenv('MCF_DB_PASS') ?: '', getenv('MCF_DB_NAME'), (int)(getenv('MCF_DB_PORT') ?: 3306));
    $c->set_charset('utf8mb4');
    $cols = array_column($c->query('SHOW COLUMNS FROM usuarios')->fetch_all(MYSQLI_ASSOC), 'Field');
    if (in_array('nome', $cols, true) || in_array('email_normalizado', $cols, true)) throw new RuntimeException('Migração já aplicada ou estrutura parcial. Inspecione antes de continuar.');
    foreach ($c->query('SELECT id,email FROM usuarios')->fetch_all(MYSQLI_ASSOC) as $u) {
        try { mcfEmail($u['email']); } catch (DomainException $e) { throw new RuntimeException('E-mail inválido na conta ID '.$u['id'].'. Nenhuma correção automática.'); }
        if (trim($u['email']) !== trim($u['email'], ' ')) throw new RuntimeException('Espaços de controle no e-mail da conta ID '.$u['id'].'. Resolva antes da migração.');
    }
    $duplicados = $c->query('SELECT GROUP_CONCAT(id ORDER BY id) ids FROM usuarios GROUP BY LOWER(TRIM(email)) HAVING COUNT(*)>1')->fetch_all(MYSQLI_ASSOC);
    if ($duplicados) throw new RuntimeException('Duplicidades entre IDs: '.implode('; ', array_column($duplicados, 'ids')).'. Nenhuma correção automática.');
    if (($argv[1] ?? '') === '--check') { echo "Pré-verificação aprovada; nenhuma alteração.\n"; exit; }
    if (($argv[1] ?? '') !== '--apply') throw new RuntimeException('Use --check ou --apply após preparar o backup.');
    $c->query(file_get_contents(__DIR__.'/../migrations/20260922_perfil.sql'));
    echo "Migração 20260922_perfil aplicada. IDs e valores existentes preservados.\n";
} catch (Throwable $e) {
    fwrite(STDERR, ($e instanceof RuntimeException && !$e instanceof mysqli_sql_exception ? $e->getMessage() : 'Falha na migração. Inspecione a estrutura; DDL não é revertido por ROLLBACK.')."\n"); exit(1);
}
