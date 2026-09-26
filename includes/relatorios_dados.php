<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }
require_once __DIR__.'/relatorios_core.php';
require_once __DIR__.'/visao_conjunta.php';

function relPessoas(mysqli $c, mixed $ids=null): array {
    // Um relatório financeiro só inclui titulares que autorizaram AMBOS os módulos.
    $receitas=mcfPessoasAutorizadas($c,'receitas',$ids);
    $despesas=mcfPessoasAutorizadas($c,'despesas',$ids);
    return array_intersect_key($receitas,$despesas);
}
function relCarregar(mysqli $c,array $f,array $pessoas): array {
    $periodos=relPeriodos($f);$inicio=min(array_column($periodos,'inicio'));$fim=max(array_column($periodos,'fim'));$rows=[];
    $c->begin_transaction(MYSQLI_TRANS_START_READ_ONLY | MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
    try {
    foreach($pessoas as $p) {
        $uid=(int)$p['id'];
        foreach(['receitas'=>['rendas','data','recebido','classificacao'],'despesas'=>['contas','vencimento','paga','categoria']] as $modulo=>[$t,$d,$s,$cat]) {
            $stmt=$c->prepare("SELECT id,nome,tipo,$d AS data,$s AS realizado,$cat AS categoria,valor FROM $t WHERE usuario_id=? AND $d BETWEEN ? AND ? ORDER BY $d,id LIMIT 6001");
            $stmt->bind_param('iss',$uid,$inicio,$fim);$stmt->execute();
            foreach($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) { if(count($rows)>=6000)throw new DomainException('Esta consulta supera 6.000 lançamentos, incluindo os períodos comparados. Reduza o intervalo ou os perfis para gerar o relatório completo.');$r['centavos']=relCentavos($r['valor']);unset($r['valor']);$r['modulo']=$modulo;$r['titular']=$p['nome']?:$p['email'];$r['usuario_id']=$uid;$rows[]=$r; }
            $stmt->close();
        }
    }
    $c->commit();
    }catch(Throwable $e){$c->rollback();throw $e;}
    usort($rows,fn($a,$b)=>[$a['data'],$a['usuario_id'],$a['modulo'],$a['id']]<=>[$b['data'],$b['usuario_id'],$b['modulo'],$b['id']]);
    $hoje=date('Y-m-d');
    return ['filtros'=>$f,'pessoas'=>array_values($pessoas),'gerado'=>date('d/m/Y H:i:s'),'hoje'=>$hoje,'periodos'=>array_map(fn($p)=>relAgregar($rows,$p,$f,$hoje),$periodos)];
}
