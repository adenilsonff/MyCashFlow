<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/compartilhamento_melhorias.php';
require_once __DIR__.'/../includes/notificacoes.php';
$uid=mcfUsuarioId();$erro='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        $acao=$_POST['acao']??'compartilhamento';
        if ($acao==='ler') {
            if (!is_string($_POST['chave']??null)) throw new DomainException('Aviso inválido.');
            mcfNotificacaoMarcarLida($conn,$uid,$_POST['chave']);
        } elseif ($acao==='salvar_cartao' || $acao==='excluir_cartao') {
            if (!mcfModuloAtivo($conn,$uid,'cartao')) mcfFalhar(403,'Ative o módulo de cartão para configurar os lembretes.');
            if ($acao==='salvar_cartao') mcfLembreteCartaoSalvar($conn,$uid,$_POST);
            else {
                $id=mcfCompartilhamentoInt($_POST,'id');
                $s=$conn->prepare('DELETE FROM notificacao_cartoes WHERE id=? AND usuario_id=?');$s->bind_param('ii',$id,$uid);$s->execute();$s->close();
            }
        } elseif ($acao==='compartilhamento') {
            $id=mcfCompartilhamentoInt($_POST,'id');$version=mcfCompartilhamentoInt($_POST,'versao');
            $s=$conn->prepare('INSERT INTO compartilhamento_vistos(usuario_id,compartilhamento_id,versao) SELECT ?,id,? FROM compartilhamentos WHERE id=? AND (proprietario_id=? OR leitor_id=?) AND versao>=? ON DUPLICATE KEY UPDATE versao=GREATEST(compartilhamento_vistos.versao,VALUES(versao))');
            $s->bind_param('iiiiii',$uid,$version,$id,$uid,$uid,$version);$s->execute();$s->close();
        } else throw new DomainException('Ação inválida.');
        header('Location: avisos.php'.(in_array($acao,['salvar_cartao','excluir_cartao'],true)?'?cartoes=1#cartoes':''),true,303);exit;
    } catch(DomainException $e) {http_response_code(422);$erro=$e->getMessage();}
}
$avisos=mcfAvisos($conn);$financeiros=mcfNotificacoesDaSessao($conn);
$novos=count(array_filter($financeiros,static fn($a)=>!$a['lido']));
$filtro=is_string($_GET['filtro']??null)&&in_array($_GET['filtro'],['todos','lidos'],true)?$_GET['filtro']:'novos';
$lista=array_values(array_filter($financeiros,static fn($a)=>$filtro==='todos'||($filtro==='lidos'?$a['lido']:!$a['lido'])));
$paginas=max(1,(int)ceil(count($lista)/30));$pagina=min($paginas,max(1,(int)(filter_var($_GET['pagina']??1,FILTER_VALIDATE_INT)?:1)));
$lista=array_slice($lista,($pagina-1)*30,30);
$cartaoAtivo=mcfModuloAtivo($conn,$uid,'cartao');$cartoes=$cartaoAtivo?mcfLembretesCartao($conn,$uid):[];
$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$rotulos=['conta'=>'Conta a vencer','atraso'=>'Conta em atraso','cartao'=>'Cartão','dividendo'=>'Provento','assinatura'=>'Assinatura'];
$cssPagina='/MyCashFlow/assets/css/compartilhamento.css';
require __DIR__.'/../includes/header.php';require __DIR__.'/../includes/menu.php';
?>
<link rel="stylesheet" href="/MyCashFlow/assets/css/notificacoes.css?v=<?= filemtime(__DIR__.'/../assets/css/notificacoes.css') ?>">
<main class="compartilhamento notificacoes">
<a class="mcf-back" href="configuracao.php">← Voltar para Configuração</a>
<h2 class="mcf-page-title">Central de avisos</h2>
<p>Seus lembretes financeiros e avisos de compartilhamento em um só lugar.</p>
<p class="ajuda">Os lembretes financeiros são da sua conta. São atualizados ao abrir as páginas do sistema e respeitam os módulos que você ativou.</p>
<?php if($erro): ?><p role="alert" class="aviso erro"><?= $e($erro) ?></p><?php endif; ?>
<div class="notificacoes-resumo"><strong><?= $novos+count($avisos) ?> aviso(s) novo(s)</strong><span><?= $novos ?> financeiro(s) · <?= count($avisos) ?> de compartilhamento</span></div>
<section aria-labelledby="avisos-financeiros">
<h3 id="avisos-financeiros">Lembretes financeiros</h3>
<nav class="links-modulos" aria-label="Filtrar lembretes">
<?php foreach(['novos'=>'Não lidos','todos'=>'Todos os atuais','lidos'=>'Lidos'] as $v=>$label): ?><a href="?filtro=<?= $v ?>"<?= $filtro===$v?' aria-current="page"':'' ?>><?= $label ?></a><?php endforeach; ?>
</nav>
<p class="ajuda">Marcar como lido não paga uma conta nem confirma um recebimento. Avisos resolvidos ou fora do período saem da lista, inclusive dos lidos.</p>
<?php if(!$lista): ?><p class="notificacoes-vazio">Nenhum lembrete <?= $filtro==='novos'?'novo':'neste filtro' ?> no momento.</p><?php endif; ?>
<?php foreach($lista as $av): ?>
<article class="notificacao<?= $av['urgente']?' notificacao-urgente':'' ?>" data-notificacao="<?= $e($av['chave']) ?>">
<div><span class="notificacao-tipo"><?= $e($rotulos[$av['tipo']]) ?></span> <span class="ajuda"><?= $av['lido']?'Lido':'Não lido' ?></span></div>
<h4><?= $e($av['titulo']) ?></h4><p><?= $e($av['texto']) ?></p>
<div class="acoes"><a href="<?= $e($av['url']) ?>">Consultar <?= $av['tipo']==='assinatura'?'assinatura':'lançamentos' ?></a>
<?php if(!$av['lido']): ?><form method="post"><?= mcfCsrfField() ?><input type="hidden" name="acao" value="ler"><input type="hidden" name="chave" value="<?= $e($av['chave']) ?>"><button>Marcar como lido</button></form><?php endif; ?></div>
</article>
<?php endforeach; ?>
<?php if($paginas>1): ?><nav class="links-modulos" aria-label="Páginas de lembretes"><?php if($pagina>1): ?><a href="?filtro=<?= $filtro ?>&amp;pagina=<?= $pagina-1 ?>">← Anterior</a><?php endif; ?><span>Página <?= $pagina ?> de <?= $paginas ?></span><?php if($pagina<$paginas): ?><a href="?filtro=<?= $filtro ?>&amp;pagina=<?= $pagina+1 ?>">Próxima →</a><?php endif; ?></nav><?php endif; ?>
</section>
<section aria-labelledby="avisos-compartilhamento"><h3 id="avisos-compartilhamento">Avisos de compartilhamento</h3>
<p class="ajuda">Marcar como lido não aceita nem altera um acesso.</p>
<?php if(!$avisos): ?><p>Nenhum aviso novo de compartilhamento.</p><?php endif; ?>
<?php foreach($avisos as $av): $other=(int)$av['proprietario_id']===$uid?'leitor':'proprietario'; ?>
<article><h4><?= $e($av[$other.'_nome']?:$av[$other.'_email']) ?></h4><p><?= $e($av[$other.'_email']) ?> — <?= $e(['pendente'=>'Convite aguardando aceitação','ativo'=>'Permissões aceitas e ativas','revogado'=>'Acesso encerrado','recusado'=>'Convite recusado'][$av['estado']]) ?></p><div class="acoes"><a href="compartilhamento.php">Consultar convite e permissões</a><form method="post"><?= mcfCsrfField() ?><input type="hidden" name="id" value="<?= (int)$av['id'] ?>"><input type="hidden" name="versao" value="<?= (int)$av['versao'] ?>"><button>Marcar como lido</button></form></div></article>
<?php endforeach; ?></section>
<?php if($cartaoAtivo): ?>
<section id="cartoes"><h3>Lembretes de fechamento dos cartões</h3>
<p>Cadastre o nome e o dia habitual de fechamento. O aviso aparece nos 3 dias anteriores e no dia do fechamento.</p>
<p class="ajuda">É um lembrete: não altera suas compras nem calcula a fatura. A data pode variar conforme o banco. Para dias 29, 30 ou 31 em meses menores, usamos o último dia do mês.</p>
<?php foreach($cartoes as $c): ?>
<details<?= isset($_GET['cartoes'])?' open':'' ?>><summary><?= $e($c['nome']) ?> · fecha no dia <?= (int)$c['dia_fechamento'] ?></summary>
<form class="notificacao-cartao-form" method="post"><?= mcfCsrfField() ?><input type="hidden" name="acao" value="salvar_cartao"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><label>Nome do cartão<input name="nome" value="<?= $e($c['nome']) ?>" maxlength="80" required></label><label>Dia de fechamento<input type="number" name="dia_fechamento" min="1" max="31" value="<?= (int)$c['dia_fechamento'] ?>" required></label><button>Salvar alteração</button></form>
<form method="post" class="notificacao-remover"><?= mcfCsrfField() ?><input type="hidden" name="acao" value="excluir_cartao"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="secundario">Remover lembrete</button></form></details>
<?php endforeach; ?>
<details<?= !$cartoes||$erro?' open':'' ?>><summary>Adicionar cartão</summary><form class="notificacao-cartao-form" method="post"><?= mcfCsrfField() ?><input type="hidden" name="acao" value="salvar_cartao"><label>Nome do cartão<input name="nome" maxlength="80" placeholder="Ex.: Cartão principal" required></label><label>Dia de fechamento<input type="number" name="dia_fechamento" min="1" max="31" required></label><button>Adicionar lembrete</button></form></details>
</section><?php endif; ?>
<details><summary>Como os avisos funcionam</summary><p>Despesas: até 7 dias antes do vencimento e enquanto estiverem em atraso e não pagas. Proventos: pagamentos cadastrados previstos para os próximos 7 dias, com posição positiva na data com. Assinatura: 15 dias antes de vencer.</p><p>Um aviso lido não reaparece a cada visita. Mudanças no lançamento, passagem para atraso ou um novo fechamento mensal podem gerar outro aviso. Não enviamos estes lembretes por e-mail ou pelo celular.</p></details>
</main><?php require __DIR__.'/../includes/footer.php'; ?>
