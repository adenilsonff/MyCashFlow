<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!getenv('MCF_DB_NAME') || !in_array($argv[1] ?? '', ['--check','--apply'], true)) { fwrite(STDERR,"Defina MCF_DB_NAME e use --check ou --apply após backup.\n"); exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $c = new mysqli(getenv('MCF_DB_HOST') ?: '127.0.0.1', getenv('MCF_DB_USER') ?: 'root', getenv('MCF_DB_PASS') ?: '', getenv('MCF_DB_NAME'), (int)(getenv('MCF_DB_PORT') ?: 3306));
    $c->set_charset('utf8mb4');
    $tables = array_column($c->query('SHOW TABLES')->fetch_all(), 0);
    if (array_intersect(['compartilhamentos','compartilhamento_modulos'], $tables)) throw new RuntimeException('Estrutura existente ou parcial. Inspecione antes de continuar; migração não reaplicada.');
    $columns = array_column($c->query('SHOW COLUMNS FROM usuarios')->fetch_all(MYSQLI_ASSOC), 'Field');
    if (!in_array('nome', $columns, true) || !in_array('email_normalizado', $columns, true)) throw new RuntimeException('O perfil individual precisa estar instalado.');
    if ($argv[1] === '--check') { echo "Pré-verificação aprovada. Nenhuma alteração.\n"; exit; }
    foreach (explode(';', file_get_contents(__DIR__.'/../migrations/20260922_compartilhamento.sql')) as $sql) if (trim($sql) !== '') $c->query($sql);
    echo "Migração 20260922_compartilhamento aplicada; tabelas novas vazias, sem alterar usuários ou finanças.\n";
} catch (Throwable $e) {
    fwrite(STDERR, ($e instanceof RuntimeException && !$e instanceof mysqli_sql_exception ? $e->getMessage() : 'Falha de migração. Inspecione a estrutura; DDL não é revertido por ROLLBACK.')."\n"); exit(1);
}
