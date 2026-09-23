<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/perfil.php';

function mcfModulosCompartilhaveis(): array {
    return ['despesas'=>'Despesas', 'receitas'=>'Receitas', 'cartao'=>'Cartão de crédito',
        'saldos'=>'Contas, saldos e movimentações', 'investimentos'=>'Investimentos e proventos',
        'daytrade'=>'Day trade', 'analise'=>'Análise: linhas e notas'];
}
function mcfCompartilhamentoInt(array $dados, string $campo): int {
    $v = $dados[$campo] ?? null;
    if (!is_scalar($v) || !($id = filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]))) throw new DomainException('Solicitação inválida. Recarregue a página.');
    return (int)$id;
}
function mcfCompartilhamentoModulos(array $dados): array {
    $modulos = $dados['modulos'] ?? [];
    if (!is_array($modulos) || !$modulos || count($modulos) > 7) throw new DomainException('Selecione ao menos um módulo.');
    foreach ($modulos as $m) if (!is_string($m) || !isset(mcfModulosCompartilhaveis()[$m])) throw new DomainException('Módulo inválido.');
    return array_values(array_unique($modulos));
}
function mcfCompartilhamentoSalvarModulos(mysqli $c, int $id, array $modulos, array $dados): void {
    $niveis=$dados['nivel'] ?? [];
    if (!is_array($niveis)) throw new DomainException('Permissão inválida.');
    foreach ($niveis as $m=>$nivel) if (!isset(mcfModulosCompartilhaveis()[$m]) || !is_string($nivel) || !in_array($nivel,['leitura','edicao'],true)) throw new DomainException('Permissão inválida.');
    $s=$c->prepare('DELETE FROM compartilhamento_modulos WHERE compartilhamento_id=?'); $s->bind_param('i',$id); $s->execute(); $s->close();
    $s=$c->prepare('INSERT INTO compartilhamento_modulos (compartilhamento_id,modulo,nivel,cadastrar,editar,excluir) VALUES (?,?,?,?,?,?)');
    foreach ($modulos as $m) {
        $nivel=$niveis[$m] ?? 'leitura'; $flags=[];
        foreach (['cadastrar','editar','excluir'] as $a) $flags[$a]=$nivel==='edicao' && (!isset($dados['granular']) || isset($dados['acoes'][$m][$a])) ? 1 : 0;
        if ($nivel==='edicao' && !array_sum($flags)) throw new DomainException('Escolha ao menos uma ação de alteração ou use somente visualizar.');
        $s->bind_param('issiii',$id,$m,$nivel,$flags['cadastrar'],$flags['editar'],$flags['excluir']); $s->execute();
    } $s->close();
}
function mcfCompartilhamentoAlterar(mysqli $c, array $dados): string {
    $ator=mcfUsuarioId(); $acao=mcfTexto($dados,'acao');
    if (!in_array($acao,['convidar','revisar','aceitar','recusar','revogar','sair'],true)) throw new DomainException('Operação inválida.');
    $c->begin_transaction();
    try {
        // A identidade é sempre a da sessão, inclusive após esperar uma trava.
        $s=$c->prepare('SELECT senha FROM usuarios WHERE id=? FOR UPDATE'); $s->bind_param('i',$ator); $s->execute(); $u=$s->get_result()->fetch_assoc(); $s->close();
        if (!$u || !hash_equals(hash('sha256',$u['senha']),$_SESSION['auth_version'] ?? '')) throw new DomainException('Sua sessão expirou. Entre novamente.');
        if ($acao==='convidar') {
            $modulos=mcfCompartilhamentoModulos($dados); $email=mcfEmail(mcfTexto($dados,'email'));
            $s=$c->prepare("SELECT id FROM usuarios WHERE email_normalizado=? AND status_assinatura='ativo' AND data_expiracao>=CURDATE()");
            $s->bind_param('s',$email); $s->execute(); $dest=$s->get_result()->fetch_assoc(); $s->close();
            if (!$dest || (int)$dest['id']===$ator) throw new DomainException('Informe o e-mail de outra conta ativa no MyCashFlow.');
            $leitor=(int)$dest['id'];
            $s=$c->prepare('SELECT id FROM compartilhamentos WHERE proprietario_id=? AND leitor_id=? FOR UPDATE');
            $s->bind_param('ii',$ator,$leitor); $s->execute(); $existe=$s->get_result()->fetch_assoc(); $s->close();
            if ($existe) throw new DomainException('Já existe um vínculo com essa conta. Use “Revisar módulos e enviar convite” na lista abaixo.');
            $s=$c->prepare('INSERT INTO compartilhamentos (proprietario_id,leitor_id) VALUES (?,?)');
            $s->bind_param('ii',$ator,$leitor); $s->execute(); $id=(int)$c->insert_id; $s->close();
            mcfCompartilhamentoSalvarModulos($c,$id,$modulos,$dados);
            $mensagem='Convite criado. A outra pessoa precisa aceitá-lo na área Compartilhamento da própria conta.';
        } else {
            $id=mcfCompartilhamentoInt($dados,'id'); $versao=mcfCompartilhamentoInt($dados,'versao');
            $s=$c->prepare('SELECT proprietario_id,leitor_id,estado,versao FROM compartilhamentos WHERE id=? AND (proprietario_id=? OR leitor_id=?) FOR UPDATE');
            $s->bind_param('iii',$id,$ator,$ator); $s->execute(); $v=$s->get_result()->fetch_assoc(); $s->close();
            $dono=in_array($acao,['revisar','revogar'],true);
            if (!$v || (int)$v[$dono ? 'proprietario_id' : 'leitor_id']!==$ator) throw new DomainException('Compartilhamento não disponível.');
            if ((int)$v['versao']!==$versao) throw new DomainException('Este convite foi atualizado. Recarregue a página antes de continuar.');
            if (in_array($acao,['aceitar','recusar'],true) && $v['estado']!=='pendente') throw new DomainException('Este convite não está mais pendente.');
            if ($acao==='sair' && $v['estado']!=='ativo') throw new DomainException('Este acesso não está ativo.');
            if ($acao==='aceitar') {
                $d=(int)$v['proprietario_id'];
                $s=$c->prepare("SELECT id FROM usuarios WHERE id=? AND status_assinatura='ativo' AND data_expiracao>=CURDATE()");
                $s->bind_param('i',$d); $s->execute(); $ativo=$s->get_result()->fetch_assoc(); $s->close();
                if (!$ativo) throw new DomainException('O titular não está com acesso ativo.');
            }
            $estado=match($acao) {'revisar'=>'pendente','aceitar'=>'ativo','recusar'=>'recusado',default=>'revogado'};
            if ($acao==='revisar') mcfCompartilhamentoSalvarModulos($c,$id,mcfCompartilhamentoModulos($dados),$dados);
            $s=$c->prepare('UPDATE compartilhamentos SET estado=?,versao=versao+1 WHERE id=?'); $s->bind_param('si',$estado,$id); $s->execute(); $s->close();
            $mensagem=match($acao) {'revisar'=>'Convite atualizado. O acesso anterior fica suspenso até a nova aceitação.', 'aceitar'=>'Convite aceito. Você pode consultar os módulos autorizados.', 'recusar'=>'Convite recusado.', default=>'Acesso revogado.'};
        }
        $c->commit(); return $mensagem;
    } catch (Throwable $e) { $c->rollback(); if ($e instanceof mysqli_sql_exception && $e->getCode()===1062) throw new DomainException('Já existe um convite. Recarregue a página.'); throw $e; }
}
function mcfCompartilhamentoListar(mysqli $c): array {
    $ator=mcfUsuarioId();
    $s=$c->prepare('SELECT c.id,c.proprietario_id,c.leitor_id,c.estado,c.versao,p.nome AS proprietario_nome,p.email AS proprietario_email,l.nome AS leitor_nome,l.email AS leitor_email,m.modulo,m.nivel,m.cadastrar,m.editar,m.excluir FROM compartilhamentos c JOIN usuarios p ON p.id=c.proprietario_id JOIN usuarios l ON l.id=c.leitor_id LEFT JOIN compartilhamento_modulos m ON m.compartilhamento_id=c.id WHERE c.proprietario_id=? OR c.leitor_id=? ORDER BY c.atualizado_em DESC,c.id DESC,m.modulo');
    $s->bind_param('ii',$ator,$ator); $s->execute(); $rows=$s->get_result()->fetch_all(MYSQLI_ASSOC); $s->close(); $out=[];
    foreach ($rows as $r) { $id=$r['id']; if (!isset($out[$id])) { $out[$id]=$r; $out[$id]['modulos']=[]; $out[$id]['niveis']=[]; unset($out[$id]['modulo']); } if (isset(mcfModulosCompartilhaveis()[$r['modulo'] ?? ''])) { $out[$id]['modulos'][]=$r['modulo']; $out[$id]['niveis'][$r['modulo']]=$r['nivel']; $out[$id]['acoes'][$r['modulo']]=array_intersect_key($r,array_flip(['cadastrar','editar','excluir'])); } }
    return array_values($out);
}
function mcfCompartilhamentoExigir(mysqli $c, int $id, string $modulo, string $nivel='leitura'): array {
    if (!isset(mcfModulosCompartilhaveis()[$modulo])) mcfFalhar(404,'Consulta não disponível.');
    $ator=mcfUsuarioId();
    $s=$c->prepare("SELECT c.proprietario_id,c.versao,m.nivel,m.cadastrar,m.editar,m.excluir,p.nome,p.email FROM compartilhamentos c JOIN compartilhamento_modulos m ON m.compartilhamento_id=c.id JOIN usuarios p ON p.id=c.proprietario_id WHERE c.id=? AND c.leitor_id=? AND c.estado='ativo' AND m.modulo=? AND p.status_assinatura='ativo' AND p.data_expiracao>=CURDATE()");
    $s->bind_param('iis',$id,$ator,$modulo); $s->execute(); $v=$s->get_result()->fetch_assoc(); $s->close();
    if (!$v) mcfFalhar(404,'Consulta não disponível. O acesso pode ter sido revogado ou atualizado.');
    if ($nivel==='edicao' && $v['nivel']!=='edicao') mcfFalhar(403,'Este módulo foi autorizado somente para consulta.');
    return $v;
}
