<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/saldos_consulta.php';
require_once __DIR__ . '/../includes/reservas.php';

$uid = mcfUsuarioId();
if (mcfDonoId() !== $uid) { mcfFalhar(403, 'Reservas e metas está disponível somente para o titular.'); }
header('Cache-Control: no-store, private');
$ano = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: (int) date('Y');
$mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: (int) date('m');
$ano = max(2000, min(2100, $ano));
$mes = max(1, min(12, $mes));
$aba = in_array($_GET['aba'] ?? '', ['reservas', 'metas', 'historico'], true) ? $_GET['aba'] : 'reservas';
$url = 'reservas.php?' . http_build_query(['aba' => $aba, 'ano' => $ano, 'mes' => $mes]);
$erro = ''; $pronto = true;
foreach (['rm_fontes', 'rm_partes', 'rm_objetivos', 'rm_reposicoes', 'rm_metas', 'rm_eventos'] as $tabela) {
    if (!rmRows($conn, 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?', 's', [$tabela])) { $pronto = false; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pronto) {
    // Mantém a proteção local mesmo se a proteção global mudar no futuro.
    if (!is_string($_POST['mcf_csrf'] ?? null) || empty($_SESSION['mcf_csrf']) || !hash_equals($_SESSION['mcf_csrf'], $_POST['mcf_csrf'])) {
        mcfFalhar(403, 'Formulário expirado. Recarregue a página.');
    }
    try {
        $_SESSION['rm_aviso'] = rmOperar($conn, $uid, $_POST);
        header('Location: ' . $url, true, 303);
        exit;
    } catch (DomainException $e) {
        $erro = $e->getMessage();
    } catch (Throwable $e) {
        error_log('Reservas e metas: ' . $e->getMessage());
        $erro = 'Não foi possível salvar. Nenhuma alteração desta operação foi aplicada. Confira a instalação e tente novamente.';
    }
}
function rmH($v) { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function rmDinheiro($v) { return 'R$ ' . number_format($v / 100, 2, ',', '.'); }
function rmCampoOperacao($acao) {
    echo mcfCsrfField();
    echo '<input type="hidden" name="operacao" value="' . bin2hex(random_bytes(16)) . '">';
    echo '<input type="hidden" name="acao" value="' . rmH($acao) . '">';
}
function rmDataDescricao($texto = 'Descrição / observação', $conferencia = false) { ?>
    <label>Data<input name="data" type="date" min="2000-01-01" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" <?= $conferencia ? 'readonly' : '' ?> required></label>
    <label class="rm-wide"><?= rmH($texto) ?><textarea name="descricao" maxlength="255" rows="2" required></textarea></label>
<?php }
$cssPagina = '/MyCashFlow/assets/css/reservas.css';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu.php';
?>
<main class="rm-page">
    <div class="rm-heading"><div><h2>Reservas e metas</h2><p>Organize o dinheiro guardado e acompanhe suas contribuições.</p></div>
        <?php if ($pronto): ?><div class="rm-actions"><button type="button" data-open="rm-entrada">Separar dinheiro</button><button type="button" class="rm-secondary" data-open="rm-nota">Observação</button><button type="button" class="rm-secondary" data-open="rm-filtro">Filtrar</button></div><?php endif; ?>
    </div>
    <?php if (!$pronto): ?>
        <div class="rm-card" role="alert"><h3>Preparar o módulo</h3><p>Execute primeiro o arquivo <strong>01-reservas-metas.sql</strong> no banco do sistema. Depois recarregue esta página.</p></div>
    <?php else:
        $r = rmResumo($conn, $uid); $s = $r['saldos']; $bolsos = rmBolsos($conn, $uid);
        $contas = saldosListarContas($conn, $uid); $nomesContas = array_column($contas, 'nome', 'id');
        $base = ($s['reserva'] ?? 0) + ($s['dividendos'] ?? 0) + ($s['juros'] ?? 0);
        $reposicoes = rmRows($conn, 'SELECT * FROM rm_reposicoes WHERE usuario_id=? AND valor>reposto ORDER BY data', 'i', [$uid]);
        $pendente = array_sum(array_map(fn($v) => $v['valor'] - $v['reposto'], $reposicoes));
        $objetivos = rmRows($conn, 'SELECT * FROM rm_objetivos WHERE usuario_id=? ORDER BY nome', 'i', [$uid]);
        $plano = rmRows($conn, 'SELECT * FROM rm_metas WHERE usuario_id=? AND ano=?', 'ii', [$uid, $ano])[0] ?? ['mensal'=>100000,'decimo_primeira'=>0,'decimo_segunda'=>0,'ferias'=>0,'restituicao'=>0,'sonho'=>5000000,'desafio'=>6500000];
        $totalAno = 0; foreach ($r['metas'][$ano] ?? [] as $valores) { $totalAno += array_sum($valores); }
        $basica = (int) $plano['mensal'] * 12;
        $projecao = $basica + $plano['decimo_primeira'] + $plano['decimo_segunda'] + $plano['ferias'] + $plano['restituicao'];
        $inicio = sprintf('%04d-%02d-01', $ano, $mes); $fim = date('Y-m-d', strtotime($inicio . ' +1 month'));
        $receitas = rmRows($conn, 'SELECT r.id,r.nome,r.valor,r.data FROM rendas r WHERE r.usuario_id=? AND r.recebido=1 AND r.data>=? AND r.data<? AND NOT EXISTS(SELECT 1 FROM rm_fontes f WHERE f.usuario_id=r.usuario_id AND f.renda_id=r.id) ORDER BY r.data,r.id', 'iss', [$uid, $inicio, $fim]);
        $juros = rmRows($conn, "SELECT COALESCE(SUM(valor),0) AS total FROM rm_fontes WHERE usuario_id=? AND origem='juros' AND data>=? AND data<?", 'iss', [$uid,$inicio,$fim])[0]['total'];
        $ultimo = rmRows($conn, "SELECT * FROM rm_eventos WHERE usuario_id=? AND estornado=0 AND acao IN ('entrada','mover','dividir','usar','repor') ORDER BY criado_em DESC LIMIT 1", 'i', [$uid])[0] ?? null;
        $aviso = $_SESSION['rm_aviso'] ?? ''; unset($_SESSION['rm_aviso']);
    ?>
    <?php if ($aviso): ?><p class="rm-alert rm-success" role="status"><?= rmH($aviso) ?></p><?php endif; ?>
    <?php if ($erro): ?><p class="rm-alert" role="alert"><?= rmH($erro) ?> Reabra o formulário para corrigir os dados.</p><?php endif; ?>
    <nav class="rm-tabs" aria-label="Seções de reservas">
        <?php foreach (['reservas'=>'Reservas','metas'=>'Fechamento e metas','historico'=>'Histórico'] as $key=>$label): ?><a <?= $aba === $key ? 'aria-current="page"' : '' ?> href="?<?= rmH(http_build_query(['aba'=>$key,'ano'=>$ano,'mes'=>$mes])) ?>"><?= rmH($label) ?></a><?php endforeach; ?>
        <span><?= sprintf('%02d/%04d', $mes, $ano) ?></span>
    </nav>
    <?php if ($aba === 'reservas'): ?>
    <p class="rm-help">As reservas mostram o <strong>saldo atual</strong>. O período selecionado filtra receitas disponíveis, juros e histórico. Este módulo distribui dinheiro que já existe em <a href="saldos.php">Contas e saldos</a>; não cria uma nova receita nem aumenta seu patrimônio.</p>
    <section class="rm-stats" aria-label="Resumo atual">
        <div><span>Nas reservas hoje</span><strong><?= rmDinheiro(array_sum($r['contas'])) ?></strong></div>
        <div><span>Base do complemento</span><strong><?= rmDinheiro($base) ?></strong><small>RESERV_MENS + dividendos + juros</small></div>
        <div><span>Complemento em 6 / 12 meses</span><strong><?= rmDinheiro(intdiv($base,6)) ?></strong><small>Em 12: <?= rmDinheiro(intdiv($base,12)) ?> / mês, sem prever juros futuros</small></div>
        <div><span>A repor</span><strong><?= rmDinheiro($pendente) ?></strong><small>Fora do saldo disponível</small></div>
    </section>
    <section class="rm-card"><div class="rm-section-title"><h3>Destinações</h3><button type="button" class="rm-secondary" data-open="rm-objetivo">Criar objetivo</button></div>
        <div class="rm-table"><table><thead><tr><th>Destinação</th><th class="rm-money">Saldo</th><th>Detalhes</th></tr></thead><tbody>
        <?php foreach ($bolsos as $key=>$label): if (in_array($key,['investido','utilizado'],true)) continue; ?>
            <tr><td><?= rmH($label) ?></td><td class="rm-money"><?= rmDinheiro($s[$key] ?? 0) ?></td><td><a href="#rm-lotes" data-bolso="<?= rmH($key) ?>">Ver valores</a></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <p class="rm-help">Enviado a investimentos: <?= rmDinheiro($s['investido'] ?? 0) ?> · Utilizado: <?= rmDinheiro($s['utilizado'] ?? 0) ?>. Esses valores já saíram das reservas.</p>
        <?php foreach ($objetivos as $o): $guardado = $s['o:'.$o['chave']] ?? 0; ?><p><strong><?= rmH($o['nome']) ?>:</strong> <?= rmDinheiro($guardado) ?> de <?= rmDinheiro($o['alvo']) ?><?= $o['prazo'] ? ' · Prazo: '.rmH(date('d/m/Y',strtotime($o['prazo']))) : '' ?><?= $o['alvo'] > 0 && $guardado >= $o['alvo'] ? ' · Objetivo alcançado' : '' ?></p><?php endforeach; ?>
    </section>
    <section class="rm-card" id="rm-lotes"><div class="rm-section-title"><h3>Valores disponíveis para movimentar</h3><label>Destinação<select id="rm-bolso-filtro"><option value="">Todas</option><?php foreach ($bolsos as $key=>$label): ?><option value="<?= rmH($key) ?>"><?= rmH($label) ?></option><?php endforeach; ?></select></label></div>
        <div class="rm-table"><table><thead><tr><th>Origem</th><th>Destinação / conta</th><th class="rm-money">Disponível</th><th>Meta</th><th>Ações</th></tr></thead><tbody>
        <?php $disponiveis = []; foreach ($r['partes'] as $p): if (in_array($p['bolso'],['investido','utilizado'],true)) continue; $disponiveis[]=$p; ?>
            <tr data-bolso-row="<?= rmH($p['bolso']) ?>"><td><?= rmH($p['descricao']) ?><small><?= rmH(date('d/m/Y',strtotime($p['data_fonte']))) ?> · <?= rmH($p['origem']) ?></small></td><td><?= rmH($bolsos[$p['bolso']] ?? $p['bolso']) ?><small><?= rmH($nomesContas[$p['conta_id']] ?? 'Conta indisponível') ?></small></td><td class="rm-money"><?= rmDinheiro($p['valor']) ?></td><td><?= $p['meta_ano'] ? rmH(sprintf('%02d/%d · %s',$p['meta_mes'],$p['meta_ano'],$p['meta_grupo'])) : 'Ainda não contabilizado' ?></td><td><div class="rm-actions">
                <button type="button" data-mover="<?= rmH($p['chave']) ?>" data-valor="<?= number_format($p['valor']/100,2,'.','') ?>" data-nome="<?= rmH($p['descricao']) ?>" data-extra="<?= $p['bolso']==='extras' ? '1':'0' ?>" data-marcado="<?= $p['meta_ano'] ? '1':'0' ?>">Movimentar</button>
            </div></td></tr>
        <?php endforeach; if (!$disponiveis): ?><tr><td colspan="5">Comece em “Separar dinheiro” para distribuir o saldo atual da sua conta.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <?php if ($reposicoes): ?><section class="rm-card"><h3>Reposições pendentes</h3><div class="rm-table"><table><thead><tr><th>Motivo</th><th>Reserva</th><th class="rm-money">Retirado</th><th class="rm-money">Reposto</th><th class="rm-money">Falta</th></tr></thead><tbody><?php foreach ($reposicoes as $repo): ?><tr><td><?= rmH($repo['motivo']) ?></td><td><?= rmH($bolsos[$repo['bolso']] ?? $repo['bolso']) ?></td><td class="rm-money"><?= rmDinheiro($repo['valor']) ?></td><td class="rm-money"><?= rmDinheiro($repo['reposto']) ?></td><td class="rm-money"><?= rmDinheiro($repo['valor']-$repo['reposto']) ?></td></tr><?php endforeach; ?></tbody></table></div><p class="rm-help">Para repor, escolha um valor disponível acima, clique em Movimentar e selecione “Repor uma retirada”.</p></section><?php endif; ?>
    <section class="rm-card"><h3>Conferência com as contas</h3><div class="rm-table"><table><thead><tr><th>Conta</th><th class="rm-money">Saldo no sistema</th><th class="rm-money">Nas reservas</th><th class="rm-money">Sem destinação</th></tr></thead><tbody>
        <?php foreach ($contas as $c): $real=(int)round((float)$c['saldo_atual']*100); $reservado=$r['contas'][$c['id']]??0; ?><tr><td><?= rmH($c['nome']) ?><?= !$c['ativa'] ? ' (inativa)' : '' ?></td><td class="rm-money"><?= rmDinheiro($real) ?></td><td class="rm-money"><?= rmDinheiro($reservado) ?></td><td class="rm-money <?= $real < $reservado ? 'rm-negative':'' ?>"><?= rmDinheiro($real-$reservado) ?><?= $real < $reservado ? ' — conferir saídas' : '' ?></td></tr><?php endforeach; ?></tbody></table></div>
        <p class="rm-help">Mantenha as movimentações bancárias em Contas e saldos. Ao usar ou enviar dinheiro para investimento, registre também essa mudança aqui. Juros registrados em <?= sprintf('%02d/%04d',$mes,$ano) ?>: <strong><?= rmDinheiro($juros) ?></strong>, mesmo após incorporá-los à RESERV_MENS.</p>
    </section>
    <?php elseif ($aba === 'metas'): ?>
    <div class="rm-section-title"><h3>Contribuições de <?= $ano ?></h3><div class="rm-actions"><button type="button" data-open="rm-metas">Ajustar metas</button><button type="button" class="rm-secondary" data-open="rm-fechar">Registrar conferência</button></div></div>
    <p class="rm-help">Conte o dinheiro quando decidir guardá-lo. A transferência posterior para investimento mantém a mesma contribuição. Retirar para uso reduz o conseguido do período original; as conferências salvas preservam uma fotografia dos valores daquele momento.</p>
    <section class="rm-stats"><div><span>Guardado no ano, após retiradas</span><strong><?= rmDinheiro($totalAno) ?></strong></div><div><span>Meta básica</span><strong><?= rmDinheiro($basica) ?></strong></div><div><span>Projeção com recebimentos pontuais</span><strong><?= rmDinheiro($projecao) ?></strong></div></section>
    <section class="rm-card"><div class="rm-table"><table><thead><tr><th>Mês</th><th class="rm-money">Meta mensal</th><th class="rm-money">Conseguido mensal</th><th class="rm-money">Extras 70%</th><th class="rm-money">Pontuais</th><th class="rm-money">Outros</th><th class="rm-money">Total</th></tr></thead><tbody>
        <?php $somas=['mensal'=>0,'extras'=>0,'pontuais'=>0,'outros'=>0]; for($m=1;$m<=12;$m++): $v=$r['metas'][$ano][$m]??[]; foreach($somas as $g=>$n){$somas[$g]+=$v[$g]??0;} ?><tr><td><?= sprintf('%02d/%04d',$m,$ano) ?></td><td class="rm-money"><?= rmDinheiro($plano['mensal']) ?></td><?php foreach($somas as $g=>$n): ?><td class="rm-money"><?= rmDinheiro($v[$g]??0) ?></td><?php endforeach; ?><td class="rm-money"><?= rmDinheiro(array_sum($v)) ?></td></tr><?php endfor; ?>
        </tbody><tfoot><tr><th>Total</th><th class="rm-money"><?= rmDinheiro($basica) ?></th><?php foreach($somas as $v): ?><th class="rm-money"><?= rmDinheiro($v) ?></th><?php endforeach; ?><th class="rm-money"><?= rmDinheiro($totalAno) ?></th></tr></tfoot></table></div>
        <p class="rm-help">Pontuais reúne 13º, férias, restituição e outras contribuições que você classificar assim. A origem continua visível no histórico; uma contribuição ocupa somente uma coluna.</p>
    </section>
    <section class="rm-card"><h3>Objetivos anuais</h3><?php $atingidas=[]; foreach(['Básica'=>$basica,'Projeção'=>$projecao,'Sonho'=>$plano['sonho'],'Desafio'=>$plano['desafio']] as $nome=>$alvo): if($alvo<=0)continue; if($totalAno >= $alvo){$atingidas[]=$nome.' · '.rmDinheiro($alvo);continue;} ?><div class="rm-goal"><span><?= rmH($nome) ?> · <?= rmDinheiro($alvo) ?></span><progress max="<?= (int)$alvo ?>" value="<?= min($totalAno,$alvo) ?>"></progress><small>Faltam <?= rmDinheiro($alvo-$totalAno) ?></small></div><?php endforeach; ?>
        <?php if($atingidas): ?><details><summary>Metas alcançadas (<?= count($atingidas) ?>)</summary><?php foreach($atingidas as $label): ?><p><?= rmH($label) ?></p><?php endforeach; ?></details><?php endif; ?>
    </section>
    <?php
        $extrasAno = rmRows($conn, "SELECT MONTH(data) AS mes, SUM(valor) AS valor FROM rm_fontes WHERE usuario_id=? AND origem='servico' AND data>=? AND data<? GROUP BY MONTH(data)", 'iss', [$uid, $ano.'-01-01', ($ano+1).'-01-01']);
        $extrasMes = array_column($extrasAno, 'valor', 'mes');
    ?>
    <section class="rm-card"><details><summary>Serviços extras registrados — referência 70/30</summary>
        <p class="rm-help">Entradas identificadas como Serviço extra neste módulo. A referência abaixo não acrescenta valores à meta: somente as destinações confirmadas contam. Valores enviados diretamente para investir podem seguir outra divisão.</p>
        <div class="rm-table"><table><thead><tr><th>Mês</th><th class="rm-money">Recebido e registrado</th><th class="rm-money">Referência 70%</th><th class="rm-money">Referência 30%</th></tr></thead><tbody>
        <?php for($m=1;$m<=12;$m++): $recebido=(int)($extrasMes[$m]??0); $setenta=intdiv($recebido*70+50,100); ?><tr><td><?= sprintf('%02d/%04d',$m,$ano) ?></td><td class="rm-money"><?= rmDinheiro($recebido) ?></td><td class="rm-money"><?= rmDinheiro($setenta) ?></td><td class="rm-money"><?= rmDinheiro($recebido-$setenta) ?></td></tr><?php endfor; ?>
        </tbody></table></div>
    </details></section>
    <?php else:
        $pagina=max(1,min(100000,(int)($_GET['pagina']??1))); $offset=($pagina-1)*50;
        $eventos=rmRows($conn,'SELECT * FROM rm_eventos WHERE usuario_id=? AND data>=? AND data<? ORDER BY criado_em DESC LIMIT 51 OFFSET '.$offset,'iss',[$uid,$inicio,$fim]);
        $mais=count($eventos)>50; $eventos=array_slice($eventos,0,50);
    ?>
    <section class="rm-card"><div class="rm-section-title"><h3>Histórico de <?= sprintf('%02d/%04d',$mes,$ano) ?></h3><?php if($ultimo): ?><button type="button" class="rm-secondary" data-open="rm-estornar">Desfazer última movimentação</button><?php endif; ?></div>
        <div class="rm-table"><table><thead><tr><th>Data</th><th>Operação</th><th>Descrição</th><th>Detalhes</th></tr></thead><tbody>
        <?php foreach($eventos as $ev): $d=json_decode($ev['detalhes'],true)??[]; ?><tr><td><?= rmH(date('d/m/Y',strtotime($ev['data']))) ?><small>Registrado em <?= rmH(date('d/m/Y H:i',strtotime($ev['criado_em']))) ?></small></td><td><?= rmH(['entrada'=>'Separar dinheiro','mover'=>'Destinar','dividir'=>'Divisão 70/30','usar'=>'Utilizar','repor'=>'Repor','objetivo'=>'Criar objetivo','metas'=>'Ajustar metas','nota'=>'Observação','fechar'=>'Conferência','estornar'=>'Desfazer'][$ev['acao']]??$ev['acao']) ?><?= $ev['estornado']?' · desfeito':'' ?></td><td class="rm-note"><?= rmH($ev['descricao']) ?></td><td>
        <?php if(isset($d['movimento'])): ?><span><?= rmDinheiro($d['movimento']['valor']) ?><?= isset($d['movimento']['destino']) ? ' · '.rmH($bolsos[$d['movimento']['destino']]??$d['movimento']['destino']) : '' ?></span><?php endif; ?>
        <?php if(isset($d['fechamento'])): ?><details><summary>Saldos e metas conferidos</summary><?php foreach($d['fechamento']['saldos'] as $b=>$v): ?><p><?= rmH($bolsos[$b]??$b) ?>: <?= rmDinheiro($v) ?></p><?php endforeach; ?><?php foreach($d['fechamento']['metas'] as $anoConferido=>$mesesConferidos): $valorConferido=0;foreach($mesesConferidos as $gruposConferidos){$valorConferido+=array_sum($gruposConferidos);} ?><p>Contribuições de <?= (int)$anoConferido ?> naquele momento: <?= rmDinheiro($valorConferido) ?></p><?php endforeach; ?></details><?php endif; ?>
        </td></tr><?php endforeach; if(!$eventos): ?><tr><td colspan="4">Nenhum registro neste período.</td></tr><?php endif; ?></tbody></table></div>
        <div class="rm-actions"><?php if($pagina>1): ?><a href="<?= rmH($url.'&pagina='.($pagina-1)) ?>">Anterior</a><?php endif; ?><span>Página <?= $pagina ?></span><?php if($mais): ?><a href="<?= rmH($url.'&pagina='.($pagina+1)) ?>">Próxima</a><?php endif; ?></div>
    </section>
    <?php endif; ?>

    <dialog id="rm-filtro"><form method="get"><div class="rm-dialog-head"><h3>Período</h3><button type="button" data-close aria-label="Fechar">×</button></div><input type="hidden" name="aba" value="<?= rmH($aba) ?>"><div class="rm-fields"><label>Mês<select name="mes"><?php for($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= $mes===$i?'selected':'' ?>><?= sprintf('%02d',$i) ?></option><?php endfor; ?></select></label><label>Ano<input type="number" name="ano" min="2000" max="2100" value="<?= $ano ?>" required></label></div><p class="rm-help">Os saldos das reservas permanecem atuais; o filtro seleciona o histórico, os juros e as receitas do período e o ano das metas.</p><button>Aplicar</button></form></dialog>

    <dialog id="rm-entrada"><form method="post" action="<?= rmH($url) ?>"><?php rmCampoOperacao('entrada'); ?><div class="rm-dialog-head"><h3>Separar dinheiro existente</h3><button type="button" data-close aria-label="Fechar">×</button></div>
        <p class="rm-help">Use somente dinheiro já recebido e presente em Contas e saldos. Não cadastre novamente valores já incluídos no saldo inicial das reservas.</p>
        <div class="rm-fields"><label class="rm-wide">Receita recebida de <?= sprintf('%02d/%04d',$mes,$ano) ?> (opcional)<select name="renda_id" id="rm-receita"><option value="">Sem vínculo — saldo inicial ou lançamento manual</option><?php foreach($receitas as $rec): ?><option value="<?= (int)$rec['id'] ?>" data-valor="<?= rmH($rec['valor']) ?>" data-nome="<?= rmH($rec['nome']) ?>"><?= rmH($rec['nome']) ?> · <?= rmDinheiro(rmCentavos($rec['valor'])) ?> · <?= rmH($rec['data']) ?></option><?php endforeach; ?></select></label>
        <label>Conta<select name="conta_id" required><option value="">Selecione</option><?php foreach($contas as $c): if(!$c['ativa'])continue; ?><option value="<?= (int)$c['id'] ?>"><?= rmH($c['nome']) ?></option><?php endforeach; ?></select></label>
        <label>Origem<select name="origem" required><?php foreach(['inicial'=>'Saldo inicial das reservas','salario'=>'Salário / sobra','servico'=>'Serviço extra','decimo'=>'13º salário','ferias'=>'Férias','restituicao'=>'Restituição','dividendo'=>'Dividendos','juros'=>'Juros creditados','daytrade'=>'Day trade líquido','outros'=>'Outros'] as $key=>$label): ?><option value="<?= $key ?>"><?= rmH($label) ?></option><?php endforeach; ?></select></label>
        <label>Valor (R$)<input name="valor" inputmode="decimal" placeholder="0,00" required></label><label>Destinação<select name="destino" required><?php foreach($bolsos as $key=>$label): if(in_array($key,['investido','utilizado'],true))continue; ?><option value="<?= rmH($key) ?>"><?= rmH($label) ?></option><?php endforeach; ?></select></label>
        <?php rmDataDescricao(); ?></div><p class="rm-help">A entrada ainda não conta na meta. Depois use Movimentar para confirmar a contribuição ou dividir os extras em 70/30.</p><button>Registrar separação</button></form></dialog>

    <dialog id="rm-mover"><form method="post" action="<?= rmH($url) ?>"><?php echo mcfCsrfField(); ?><input type="hidden" name="operacao" value="<?= bin2hex(random_bytes(16)) ?>"><input type="hidden" name="parte"><div class="rm-dialog-head"><h3>Movimentar valor</h3><button type="button" data-close aria-label="Fechar">×</button></div><p id="rm-origem-label"></p>
        <div class="rm-fields"><label class="rm-wide">Operação<select name="acao" id="rm-acao"><option value="mover">Destinar / incorporar juros / marcar contribuição</option><option value="dividir">Dividir extras: 70% investimento, 30% uso livre</option><option value="usar">Utilizar dinheiro / retirar complemento</option><option value="repor">Repor uma retirada</option></select></label>
        <label>Valor (R$)<input name="valor" inputmode="decimal" required></label>
        <label data-move-field>Destino<select name="destino" required><option value="">Selecione o destino</option><?php foreach($bolsos as $key=>$label): if($key==='utilizado')continue; ?><option value="<?= rmH($key) ?>"><?= rmH($label) ?></option><?php endforeach; ?></select></label>
        <label class="rm-wide" data-meta-field>Contribuição à meta<select name="grupo"><option value="">Não marcar nova contribuição / manter a existente</option><option value="mensal">Conseguido mensal — salário e sobras</option><option value="extras">Extras destinados a investimento</option><option value="pontuais">Pontuais — 13º, férias, restituição</option><option value="outros">Outros ganhos destinados à meta</option></select></label>
        <label class="rm-wide" data-repo-field>Retirada a repor<select name="reposicao"><option value="">Selecione</option><?php foreach($reposicoes as $repo): ?><option value="<?= rmH($repo['chave']) ?>"><?= rmH($repo['motivo']) ?> · faltam <?= rmDinheiro($repo['valor']-$repo['reposto']) ?></option><?php endforeach; ?></select></label>
        <label class="rm-wide rm-check" data-use-field><input type="checkbox" name="repor_depois" value="1"> Anotar este valor como pendente de reposição</label>
        <?php rmDataDescricao(); ?></div>
        <p class="rm-help" id="rm-move-help"></p><button>Confirmar movimentação</button></form></dialog>

    <dialog id="rm-objetivo"><form method="post" action="<?= rmH($url) ?>"><?php rmCampoOperacao('objetivo'); ?><input type="hidden" name="descricao" value="Novo objetivo de reserva"><div class="rm-dialog-head"><h3>Criar objetivo</h3><button type="button" data-close aria-label="Fechar">×</button></div><div class="rm-fields"><label class="rm-wide">Nome<input name="nome" maxlength="100" placeholder="Ex.: Reserva de emergência" required></label><label>Valor desejado (R$)<input name="alvo" inputmode="decimal" required></label><label>Prazo (opcional)<input name="prazo" type="date" min="<?= date('Y-m-d') ?>"></label></div><button>Criar</button></form></dialog>

    <dialog id="rm-metas"><form method="post" action="<?= rmH($url) ?>"><?php rmCampoOperacao('metas'); ?><input type="hidden" name="ano" value="<?= $ano ?>"><input type="hidden" name="descricao" value="Ajuste das metas de <?= $ano ?>"><div class="rm-dialog-head"><h3>Metas de <?= $ano ?></h3><button type="button" data-close aria-label="Fechar">×</button></div><p class="rm-help">Os valores de 13º, férias e restituição são referências para planejamento. Nenhum deles cria saldo ou contribuição automaticamente.</p><div class="rm-fields"><?php foreach(['mensal'=>'Meta por mês','decimo_primeira'=>'13º — primeira parcela','decimo_segunda'=>'13º — segunda parcela','ferias'=>'1/3 de férias','restituicao'=>'Restituição do IR','sonho'=>'Sonho anual','desafio'=>'Desafio anual'] as $key=>$label): ?><label><?= rmH($label) ?> (R$)<input name="<?= $key ?>" inputmode="decimal" value="<?= number_format($plano[$key]/100,2,',','') ?>" required></label><?php endforeach; ?></div><button>Salvar planejamento</button></form></dialog>

    <?php foreach(['nota'=>'Observação','fechar'=>'Registrar conferência de hoje'] as $key=>$label): ?><dialog id="rm-<?= $key ?>"><form method="post" action="<?= rmH($url) ?>"><?php rmCampoOperacao($key); ?><div class="rm-dialog-head"><h3><?= rmH($label) ?></h3><button type="button" data-close aria-label="Fechar">×</button></div><?php if($key==='fechar'): ?><p class="rm-help">Confira os saldos e faça as destinações antes de salvar. Este registro guarda os saldos e as contribuições atuais para consulta; não movimenta dinheiro nem trava o mês.</p><?php endif; ?><div class="rm-fields"><?php rmDataDescricao('Observação', $key==='fechar'); ?></div><button>Salvar</button></form></dialog><?php endforeach; ?>

    <?php if($ultimo): ?><dialog id="rm-estornar"><form method="post" action="<?= rmH($url) ?>"><?php rmCampoOperacao('estornar'); ?><input type="hidden" name="evento" value="<?= rmH($ultimo['chave']) ?>"><div class="rm-dialog-head"><h3>Desfazer última movimentação</h3><button type="button" data-close aria-label="Fechar">×</button></div><p><?= rmH($ultimo['descricao']) ?> · <?= rmH($ultimo['data']) ?></p><p>Os valores e as contribuições desta operação serão revertidos. O registro permanecerá no histórico como desfeito.</p><label>Motivo<input name="descricao" maxlength="255" required></label><button>Confirmar desfazer</button></form></dialog><?php endif; ?>

    <script>
    (() => {
        const root = document.querySelector('.rm-page');
        root.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.open).showModal()));
        root.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));
        const form = document.querySelector('#rm-mover form');
        const update = () => {
            const a = form.elements.acao.value;
            for (const [selector, visible] of [['[data-move-field]',a==='mover'],['[data-meta-field]',(a==='mover'||a==='repor')&&form.dataset.marcado!=='1'],['[data-repo-field]',a==='repor'],['[data-use-field]',a==='usar']]) {
                const el=form.querySelector(selector); el.hidden=!visible; el.querySelectorAll('input,select').forEach(i=>i.disabled=!visible);
            }
            document.getElementById('rm-move-help').textContent = {
                mover:'Para incorporar juros, escolha RESERV_MENS. Para contar um saldo já guardado na meta, mantenha o destino e escolha uma contribuição. Enviar para investimento não altera suas posições nem debita a conta bancária.',
                dividir:'70% serão separados para investir e contados na meta de extras na data informada. Os 30% ficarão disponíveis em Necessary/Funny. A transferência posterior não contará novamente.',
                usar:'O valor sairá da reserva e deixará de contar na meta. Registre o gasto ou a transferência bancária nas páginas correspondentes, sem duplicar a despesa.',
                repor:'A reposição transfere dinheiro disponível para a reserva de origem e reduz a pendência. Não cria receita.'
            }[a];
        };
        root.querySelectorAll('[data-mover]').forEach(b=>b.addEventListener('click',()=>{
            form.reset(); form.elements.parte.value=b.dataset.mover; form.elements.valor.value=b.dataset.valor;
            form.dataset.marcado=b.dataset.marcado;
            form.elements.acao.querySelector('[value="dividir"]').disabled=b.dataset.extra!=='1';
            document.getElementById('rm-origem-label').textContent=b.dataset.nome+' · disponível R$ '+b.dataset.valor.replace('.',',');
            update(); document.getElementById('rm-mover').showModal();
        }));
        form.elements.acao.addEventListener('change', update);
        const filter=document.getElementById('rm-bolso-filtro');
        const filterRows=()=>root.querySelectorAll('[data-bolso-row]').forEach(row=>row.hidden=filter.value!==''&&row.dataset.bolsoRow!==filter.value);
        if(filter) {filter.addEventListener('change',filterRows); root.querySelectorAll('[data-bolso]').forEach(a=>a.addEventListener('click',()=>{filter.value=a.dataset.bolso;filterRows();}));}
        document.getElementById('rm-receita').addEventListener('change',e=>{
            const opt=e.target.selectedOptions[0], f=e.target.form;
            f.elements.valor.readOnly=!!opt.value;
            if(opt.value){f.elements.valor.value=opt.dataset.valor;f.elements.descricao.value=opt.dataset.nome;}
        });
        root.querySelectorAll('form[method="post"]').forEach(f=>f.addEventListener('submit',()=>{const b=f.querySelector('button:not([type="button"])');if(b){b.disabled=true;b.textContent='Salvando…';}}));
    })();
    </script>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
