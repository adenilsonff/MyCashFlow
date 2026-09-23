<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/compartilhamento_consulta.php';
require_once __DIR__.'/carteiras_posicoes.php';

function mcfInvestimentoTabela(string $mercado): string {
    return match($mercado) {'nacional'=>'investimentos_nacionais','internacional'=>'investimentos_internacionais',default=>throw new DomainException('Mercado inválido.')};
}
function mcfInvestimentoRevisao(array $r): string {
    return hash('sha256',implode('|',array_map(static fn($k)=>(string)($r[$k] ?? ''),['id','ticker','tipo_ativo','quantidade','valor_unitario','data','tipo_operacao'])));
}
function mcfInvestimentoCorrigir(mysqli $c, array $dados): void {
    $mercado=mcfTexto($dados,'mercado');$tabela=mcfInvestimentoTabela($mercado);$internacional=$mercado==='internacional';
    $id=mcfCompartilhamentoInt($dados,'operacao_id');$acao=mcfTexto($dados,'acao');$uid=mcfDonoId();
    if (!in_array($acao,['corrigir','excluir'],true)) throw new DomainException('Operação inválida.');
    $lock='mycashflow_'.$tabela.'_registro';$bloqueado=false;
    try {
        $s=$c->prepare('SELECT GET_LOCK(?,10) adquirido');$s->bind_param('s',$lock);$s->execute();$bloqueado=(int)$s->get_result()->fetch_assoc()['adquirido']===1;$s->close();
        if (!$bloqueado) throw new DomainException('Há outra operação em andamento. Tente novamente.');
        if (!empty($GLOBALS['mcf_contexto'])) {
            $ctx=$GLOBALS['mcf_contexto'];$atual=mcfCompartilhamentoExigir($c,(int)$ctx['id'],'investimentos','edicao');
            if ((int)$atual['versao']!==(int)$ctx['versao']) throw new DomainException('A permissão mudou. Reabra a página.');
        }
        $c->begin_transaction();
        $s=$c->prepare('SELECT id,ticker,tipo_ativo,quantidade,valor_unitario,data,tipo_operacao FROM '.$tabela.' WHERE usuario_id=? ORDER BY data,id FOR UPDATE');
        $s->bind_param('i',$uid);$s->execute();$ops=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();$indice=null;
        foreach ($ops as $i=>$r) if ((int)$r['id']===$id) $indice=$i;
        if ($indice===null) throw new DomainException('Operação não disponível para este titular.');
        if (!hash_equals(mcfInvestimentoRevisao($ops[$indice]),mcfTexto($dados,'revisao'))) throw new DomainException('Esta operação mudou em outra aba. Recarregue antes de corrigir.');
        if ($acao==='excluir') unset($ops[$indice]);
        else {
            $ticker=strtoupper(trim(mcfTexto($dados,'ticker')));if (!$internacional) $ticker=acoesTicker($ticker);
            $tipo=mcfTexto($dados,'tipo_ativo');$op=mcfTexto($dados,'tipo_operacao');
            $quantidade=trim(mcfTexto($dados,'quantidade'));$preco=trim(mcfTexto($dados,'valor_unitario'));
            $data=mcfCompartilhamentoData(mcfTexto($dados,'data'));
            if ($data<'1000-01-01' || $data>date('Y-m-d')) throw new DomainException('Informe uma data válida até hoje.');
            if (!in_array($tipo,$internacional ? ['stock','etf','reit','adr','cripto'] : ['acao','fii','etf','bdr'],true) || !in_array($op,['compra','venda'],true)) throw new DomainException('Tipo de ativo ou operação inválido.');
            if (!preg_match($internacional ? '/^[A-Z][A-Z0-9.-]{0,19}$/D' : '/^[A-Z0-9]{1,10}$/D',$ticker)) throw new DomainException('Ticker inválido.');
            if (!preg_match($internacional ? '/^[0-9]{1,14}(?:\.[0-9]{1,8})?$/D' : '/^[1-9][0-9]{0,9}$/D',$quantidade) || bccomp($quantidade,'0',8)<=0 || (!$internacional && bccomp($quantidade,'2147483647',8)>0)) throw new DomainException('Quantidade inválida.');
            if (!preg_match($internacional ? '/^[0-9]{1,14}(?:\.[0-9]{1,8})?$/D' : '/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/D',$preco) || bccomp($preco,'0',8)<=0) throw new DomainException('Preço inválido. Use ponto para os decimais.');
            $total=bcmul($quantidade,$preco,16);
            if ($internacional && (bccomp($total,'9999999999999999.99999999',12)>0 || bccomp($total,'0.000000005',12)<0)) throw new DomainException('Valor da operação fora do limite.');
            $qAssinada=$op==='venda' ? '-'.$quantidade : $quantidade;
            $ops[$indice]=['id'=>$id,'ticker'=>$ticker,'tipo_ativo'=>$tipo,'tipo_operacao'=>$op,'quantidade'=>$qAssinada,'valor_unitario'=>$preco,'data'=>$data];
        }
        // Recalcula o histórico inteiro do titular antes de persistir; nunca usa posições de outra conta.
        if ($internacional) internacionalConsolidar(array_values($ops));else acoesConsolidar(array_values($ops));
        if ($acao==='excluir') {
            $s=$c->prepare('DELETE FROM '.$tabela.' WHERE id=? AND usuario_id=?');$s->bind_param('ii',$id,$uid);
        } elseif ($internacional) {
            $total=bcadd($total,'0.000000005',8);if ($op==='venda') $total='-'.$total;
            $s=$c->prepare('UPDATE investimentos_internacionais SET ticker=?,tipo_ativo=?,tipo_operacao=?,quantidade=?,valor_unitario=?,data=?,valor_investido=?,valor_mercado=NULL,logo=NULL WHERE id=? AND usuario_id=?');
            $s->bind_param('sssssssii',$ticker,$tipo,$op,$qAssinada,$preco,$data,$total,$id,$uid);
        } else {
            $s=$c->prepare('UPDATE investimentos_nacionais SET ticker=?,tipo_ativo=?,tipo_operacao=?,quantidade=?,valor_unitario=?,data=?,valor_mercado=NULL,logo=NULL WHERE id=? AND usuario_id=?');
            $s->bind_param('ssssssii',$ticker,$tipo,$op,$qAssinada,$preco,$data,$id,$uid);
        }
        $s->execute();$s->close();$c->commit();
    } catch (Throwable $e) { $c->rollback();throw $e; }
    finally { if ($bloqueado) { $s=$c->prepare('SELECT RELEASE_LOCK(?)');$s->bind_param('s',$lock);$s->execute();$s->close(); } }
}
