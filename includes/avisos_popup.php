<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {http_response_code(404);exit;}
// Apenas na primeira página consultada da sessão; fechar não marca avisos como lidos.
if (!isset($_SESSION['usuario_id'],$conn) || !empty($_SESSION['mcf_avisos_apresentados']) || ($_SERVER['REQUEST_METHOD']??'GET')!=='GET') return;
$_SESSION['mcf_avisos_apresentados']=true;
if (basename($_SERVER['SCRIPT_FILENAME']??'')==='avisos.php' || empty($novosAvisos)) return;
$resumoPopup=[];
$rotulosPopup=['atraso'=>'Contas em atraso','conta'=>'Contas a vencer','cartao'=>'Fechamento de cartões','dividendo'=>'Proventos previstos','assinatura'=>'Vencimento da assinatura'];
foreach(mcfNotificacoesDaSessao($conn) as $avisoPopup) {
    if (!$avisoPopup['lido']) $resumoPopup[$avisoPopup['tipo']]=($resumoPopup[$avisoPopup['tipo']]??0)+1;
}
$compartilhamentosPopup=count($avisosCompartilhamento);
?>
<link rel="stylesheet" href="/MyCashFlow/assets/css/avisos-popup.css?v=<?= filemtime(__DIR__.'/../assets/css/avisos-popup.css') ?>">
<dialog id="mcf-avisos-popup" class="mcf-avisos-popup" aria-labelledby="mcf-avisos-popup-titulo" aria-describedby="mcf-avisos-popup-descricao">
<h2 id="mcf-avisos-popup-titulo">Você tem <?= (int)$novosAvisos ?> <?= $novosAvisos===1?'aviso novo':'avisos novos' ?></h2>
<p id="mcf-avisos-popup-descricao">Confira o que precisa da sua atenção na sua conta.</p>
<ul>
<?php foreach($rotulosPopup as $tipoPopup=>$rotuloPopup): if(empty($resumoPopup[$tipoPopup])) continue; ?>
<li<?= $tipoPopup==='atraso'?' class="mcf-popup-atraso"':'' ?>><span><?= $rotuloPopup ?></span><strong><?= (int)$resumoPopup[$tipoPopup] ?></strong></li>
<?php endforeach; ?>
<?php if($compartilhamentosPopup): ?><li><span>Compartilhamento</span><strong><?= $compartilhamentosPopup ?></strong></li><?php endif; ?>
</ul>
<p class="mcf-popup-ajuda">Fechar esta janela mantém os avisos como não lidos. Você pode consultá-los a qualquer momento em Avisos.</p>
<div class="mcf-popup-acoes"><a data-mcf-own href="/MyCashFlow/views/avisos.php" autofocus>Ver meus avisos</a><button type="button" id="mcf-avisos-popup-fechar">Agora não</button></div>
</dialog>
<script src="/MyCashFlow/assets/js/avisos-popup.js?v=<?= filemtime(__DIR__.'/../assets/js/avisos-popup.js') ?>" defer></script>
