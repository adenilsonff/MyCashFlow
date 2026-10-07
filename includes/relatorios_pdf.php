<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/relatorios_modulos_view.php';
require_once __DIR__.'/vendor/dompdf/autoload.inc.php';
function relPdf(array $r,bool $detalhado): string {
    $options=new Dompdf\Options();
    $options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);$options->set('isJavascriptEnabled',false);
    $options->set('chroot',__DIR__.'/vendor/dompdf');$options->set('defaultFont','DejaVu Sans');
    $pdf=new Dompdf\Dompdf($options);$pdf->setPaper('A4','landscape');
    $css=<<<'CSS'
@page{margin:26pt 28pt 36pt}
body{font-family:DejaVu Sans;font-size:8pt;color:#23354b}
h1{font-size:18pt;margin:0 0 5pt;color:#143f58}
h2{font-size:12pt;margin:12pt 0 6pt}h3{font-size:9pt;margin:8pt 0 5pt}
small{font-size:8pt;font-weight:normal}p{line-height:1.35;margin:4pt 0}
table{width:100%;border-collapse:collapse;table-layout:fixed}
th{background:#183f55;color:white;font-size:7pt}
td,th{padding:4pt;border-bottom:1pt solid #dce4ea;overflow-wrap:break-word;line-height:1.3}
td{font-size:7pt}thead{display:table-header-group}tr{page-break-inside:avoid}
tfoot{display:table-row-group}.total{font-weight:bold;background:#eef4f7;page-break-before:avoid}
.indicator{display:inline-block;width:30%;margin:2pt;padding:5pt;background:#eef4f7}
.indicator span{display:block;font-size:8pt}.indicator strong{font-size:12pt}
.notice{font-size:7pt;line-height:1.35;background:#f6f1e7;padding:6pt;margin:6pt 0}
.meta{font-size:7pt;color:#526170}.chart{page-break-inside:avoid;margin:6pt 0}
.chart h2,.chart h3{margin-top:0}.chart img{width:82%!important;display:block;margin:0 auto}.details{margin-top:0;page-break-before:always}
.indicators,.indicator{page-break-inside:avoid}h2,h3{page-break-after:avoid}
.rel-detail-table th:nth-child(1){width:9%}.rel-detail-table th:nth-child(2){width:13%}
.rel-detail-table th:nth-child(3){width:30%}.rel-detail-table th:nth-child(4){width:17%}
.rel-detail-table th:nth-child(5){width:19%}.rel-detail-table th:nth-child(6){width:12%}
.rel-detail-table td:last-child{white-space:nowrap;text-align:right}
CSS;
    $pdf->loadHtml('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><style>'.$css.'</style><body>'.(isset($r['modulo'])?relModuloConteudo($r,true,$detalhado):relConteudo($r,true,$detalhado)).'</body></html>','UTF-8');
    $pdf->render();$pdf->getCanvas()->page_text(28,570,'MyCashFlow | '.(isset($r['modulo'])?relModulo($r['modulo'])['titulo']:'Financeiro').' | Página {PAGE_NUM} de {PAGE_COUNT}',$pdf->getFontMetrics()->getFont('DejaVu Sans'),8,[.3,.35,.4]);
    return $pdf->output();
}
