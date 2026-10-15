<?php
declare(strict_types=1);

// O marcador mensal é independente dos lançamentos: excluir uma ocorrência
// não a recria; encerrar a série impede qualquer renovação posterior.
function mcfRendasExecutar(mysqli $c, string $sql, string $tipos='', array $valores=[]): mysqli_stmt {
    $s=$c->prepare($sql);
    if ($tipos!=='') $s->bind_param($tipos, ...$valores);
    $s->execute();
    return $s;
}

function mcfRendasRegistrar(mysqli $c, string $grupo, string $inicio, string $ultimo,
    string $nome, string $descricao, float $valor, string $classificacao): void {
    $dia=(int)substr($inicio,8,2);
    mcfRendasExecutar($c, 'INSERT INTO rendas_recorrencias
        (usuario_id,grupo_recorrencia,dia,ultimo_mes,nome,descricao,valor,classificacao)
        VALUES (@mcf_usuario_id,?,?,?,?,?,?,?)', 'sisssds',
        [$grupo,$dia,substr($ultimo,0,7).'-01',$nome,$descricao,$valor,$classificacao])->close();
}

function mcfRendasGerar(mysqli $c, int $ano): void {
    if ($ano<2000 || $ano>2100) return;
    $limite=sprintf('%04d-12-01',$ano);
    $c->begin_transaction();
    try {
        $s=mcfRendasExecutar($c,'SELECT * FROM rendas_recorrencias
            WHERE usuario_id=@mcf_usuario_id AND ativa=1 AND ultimo_mes<? ORDER BY id FOR UPDATE','s',[$limite]);
        $series=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
        foreach ($series as $r) {
            $mes=new DateTimeImmutable($r['ultimo_mes']);
            while (($mes=$mes->modify('+1 month'))->format('Y-m-d')<=$limite) {
                $data=$mes->format('Y-m-').sprintf('%02d',min((int)$r['dia'],(int)$mes->format('t')));
                // Protege também meses já existentes em uma importação anterior.
                mcfRendasExecutar($c,"INSERT INTO rendas
                    (usuario_id,nome,descricao,tipo,classificacao,data,recebido,valor,grupo_recorrencia)
                    SELECT @mcf_usuario_id,?,?,'recorrente',?,?,0,?,?
                    WHERE NOT EXISTS (SELECT 1 FROM rendas WHERE usuario_id=@mcf_usuario_id
                        AND grupo_recorrencia=? AND data>=? AND data<DATE_ADD(?, INTERVAL 1 MONTH))",
                    'ssssdssss',[$r['nome'],$r['descricao'],$r['classificacao'],$data,(float)$r['valor'],
                        $r['grupo_recorrencia'],$r['grupo_recorrencia'],$mes->format('Y-m-d'),$mes->format('Y-m-d')])->close();
            }
            mcfRendasExecutar($c,'UPDATE rendas_recorrencias SET ultimo_mes=? WHERE usuario_id=@mcf_usuario_id AND id=?','si',[$limite,(int)$r['id']])->close();
        }
        $c->commit();
    } catch(Throwable $e) {$c->rollback();throw $e;}
}

function mcfRendasPrepararAcesso(mysqli $c): void {
    // Renovação automática somente na sessão do titular. Não amplia permissões
    // de quem tem acesso compartilhado nem interfere em comandos de manutenção.
    if (PHP_SAPI==='cli' || empty($_SESSION['usuario_id']) || !empty($GLOBALS['mcf_contexto'])) return;
    if (!(int)$c->query('SELECT @mcf_usuario_id')->fetch_row()[0]) return;
    $ano=min(2100,(int)date('Y')+1);
    $pedido=filter_var($_GET['ano']??null,FILTER_VALIDATE_INT);
    if ($pedido!==false && $pedido>=2000 && $pedido<=2100) $ano=max($ano,$pedido);
    foreach (['referencia','fim'] as $campo) {
        $v=$_GET[$campo]??null;
        if (is_string($v) && preg_match('/^(20\d{2}|2100)-\d{2}-\d{2}$/D',$v)) {
            $data=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
            if ($data && $data->format('Y-m-d')===$v) $ano=max($ano,(int)$data->format('Y'));
        }
    }
    mcfRendasGerar($c,$ano);
}
