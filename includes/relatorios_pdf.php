<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/relatorios_view.php';
require_once __DIR__.'/vendor/dompdf/autoload.inc.php';
function relPdf(array $r,bool $detalhado): string {
    $options=new Dompdf\Options();
    $options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);$options->set('isJavascriptEnabled',false);
    $options->set('chroot',__DIR__.'/vendor/dompdf');$options->set('defaultFont','DejaVu Sans');
    $pdf=new Dompdf\Dompdf($options);$pdf->setPaper('A4','landscape');
    $css='@page{margin:30pt 28pt 36pt}body{font-family:DejaVu Sans;font-size:8pt;color:#23354b}h1{font-size:22pt;margin:0 0 8pt;color:#143f58}h2{font-size:13pt;margin:18pt 0 9pt}h3{font-size:10pt}small{font-size:8pt;font-weight:normal}p{line-height:1.5}table{width:100%;border-collapse:collapse;table-layout:fixed}th{background:#183f55;color:white;font-size:7pt}td,th{padding:6pt 4pt;border-bottom:1pt solid #dce4ea;overflow-wrap:break-word}td{font-size:7pt}thead{display:table-header-group}tr{page-break-inside:avoid}tfoot{display:table-row-group}.total{font-weight:bold;background:#eef4f7}.indicator{display:inline-block;width:30%;margin:4pt;padding:6pt;background:#eef4f7}.indicator span{display:block;font-size:8pt}.indicator strong{font-size:13pt}.notice{font-size:7pt;line-height:1.4;background:#f6f1e7;padding:8pt;margin:8pt 0}.meta{font-size:7pt;color:#526170}.chart{page-break-inside:avoid;margin:8pt 0}.details{margin-top:14pt}';
    $pdf->loadHtml('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><style>'.$css.'</style><body>'.relConteudo($r,true,$detalhado).'</body></html>','UTF-8');
    $pdf->render();$pdf->getCanvas()->page_text(28,570,'MyCashFlow | Financeiro | Página {PAGE_NUM} de {PAGE_COUNT}',$pdf->getFontMetrics()->getFont('DejaVu Sans'),8,[.3,.35,.4]);
    return $pdf->output();
}
