<?php
// Execute pelo terminal: php tools/instalar_analise.php
// Migração aditiva e repetível: não modifica os lançamentos existentes.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/../config.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try {
    $sql=file_get_contents(__DIR__.'/../migrations/20260919_analise.sql');
    foreach(explode(';',$sql) as $statement) {
        if(trim($statement)!=='') $conn->query($statement);
    }
    foreach(['analise_acompanhamento','analise_marcacoes'] as $table) {
        $conn->query('SELECT id,usuario_id,ticker,tipo_ativo FROM '.$table.' LIMIT 0');
    }
    echo 'Estruturas do menu Análise prontas. Nenhum lançamento financeiro foi alterado.'.PHP_EOL;
} catch(Throwable $e) {
    fwrite(STDERR,'Não foi possível concluir a instalação de Análise. Verifique acesso ao banco e compatibilidade das tabelas.'.PHP_EOL);
    exit(1);
}
