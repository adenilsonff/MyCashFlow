<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(404);exit;}
const MCF_BACKUP_MAX=10485760;
function mcfBackupRotulos(): array {return ['clientes'=>'Clientes','contas_financeiras'=>'Contas financeiras','corretoras'=>'Corretoras','compras'=>'Compras no cartão','contas'=>'Despesas','rendas'=>'Receitas','investimentos_nacionais'=>'Investimentos nacionais','investimentos_internacionais'=>'Investimentos internacionais e cripto','div_datacom'=>'Eventos de proventos','operacoes'=>'Operações de day trade','corretora_taxas'=>'Taxas de corretoras','cartoes'=>'Parcelas de cartão','movimentacoes_financeiras'=>'Movimentações financeiras','cartao_nomes_recorrentes'=>'Nomes personalizados do cartão','cartao_categorias_recorrentes'=>'Categorias recorrentes do cartão','cartao_exclusoes'=>'Exclusões protegidas do cartão','ofx_importacoes'=>'Controle de importações OFX','analise_acompanhamento'=>'Ativos acompanhados','analise_marcacoes'=>'Linhas e notas de análise','usuario_modulos'=>'Preferências de módulos'];}
function mcfBackupTabelas(): array {
    return ['clientes','contas_financeiras','corretoras','compras','contas','rendas','investimentos_nacionais','investimentos_internacionais','div_datacom','operacoes','corretora_taxas','cartoes','movimentacoes_financeiras','cartao_nomes_recorrentes','cartao_categorias_recorrentes','cartao_exclusoes','ofx_importacoes','analise_acompanhamento','analise_marcacoes','usuario_modulos'];
}
function mcfBackupColunas(mysqli $c,string $t): array {return array_column($c->query("SHOW COLUMNS FROM `$t`")->fetch_all(MYSQLI_ASSOC),'Field');}
function mcfBackupGerar(mysqli $c,int $uid): string {
    $c->begin_transaction(MYSQLI_TRANS_START_READ_ONLY|MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT);
    try{$tables=[];$count=0;
        foreach(mcfBackupTabelas() as $t){$s=$c->prepare("SELECT * FROM `$t` WHERE usuario_id=?");$s->bind_param('i',$uid);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();$count+=count($rows);if($count>100000)throw new DomainException('O backup supera 100.000 registros. Solicite uma cópia assistida ao responsável pela instalação.');$tables[$t]=$rows;}
        $s=$c->prepare('SELECT nome,email FROM usuarios WHERE id=?');$s->bind_param('i',$uid);$s->execute();$owner=$s->get_result()->fetch_assoc();$s->close();
        $payload=['formato'=>'MyCashFlow-conta','versao'=>1,'gerado_em'=>gmdate('c'),'titular'=>$owner,'usuario_origem'=>$uid,'tabelas'=>$tables];
        $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);if(strlen($json)>MCF_BACKUP_MAX)throw new DomainException('O backup supera 10 MB. Solicite uma cópia assistida ao responsável pela instalação.');$c->commit();return $json;
    }catch(Throwable $e){$c->rollback();throw $e;}
}
function mcfBackupValidar(mysqli $c,string $json): array {
    if(strlen($json)>MCF_BACKUP_MAX)throw new DomainException('Arquivo maior que 10 MB.');
    try{$data=json_decode($json,true,64,JSON_THROW_ON_ERROR);}catch(JsonException $e){throw new DomainException('Arquivo JSON inválido.');}
    if(!is_array($data)||($data['formato']??'')!=='MyCashFlow-conta'||($data['versao']??null)!==1||!is_int($data['usuario_origem']??null)||$data['usuario_origem']<1||!is_array($data['tabelas']??null))throw new DomainException('Formato de backup não reconhecido.');
    // Aceitar as duas versões anteriores sem inventar exclusões históricas.
    $esperadas=mcfBackupTabelas();
    $semExclusoes=array_values(array_diff($esperadas,['cartao_exclusoes']));
    $semCategorias=array_values(array_diff($esperadas,['cartao_exclusoes','cartao_categorias_recorrentes']));
    if(in_array(array_keys($data['tabelas']),[$semExclusoes,$semCategorias],true)){
        $originais=$data['tabelas'];$data['tabelas']=[];
        foreach($esperadas as $t)$data['tabelas'][$t]=$originais[$t]??[];
    }
    if(array_keys($data['tabelas'])!==mcfBackupTabelas())throw new DomainException('Lista de tabelas incompatível. Use a mesma versão do MyCashFlow.');
    $count=0;
    foreach($data['tabelas'] as $t=>$rows){if(!is_array($rows)||!array_is_list($rows))throw new DomainException('Registros inválidos.');$cols=mcfBackupColunas($c,$t);$ids=[];
        foreach($rows as $row){if(++$count>100000)throw new DomainException('Limite de registros excedido.');if(!is_array($row)||array_keys($row)!==$cols||(int)($row['usuario_id']??0)!==$data['usuario_origem'])throw new DomainException('Estrutura ou titular incompatível.');foreach($row as $v)if(!is_null($v)&&!is_scalar($v))throw new DomainException('Valor de campo inválido.');if(isset($row['id'])){if(!ctype_digit((string)$row['id'])||(int)$row['id']<1||isset($ids[$row['id']]))throw new DomainException('Identificador inválido ou repetido.');$ids[$row['id']]=true;}}
    }return $data;
}
function mcfBackupRestaurar(mysqli $c,int $uid,array $data): int {
    // Revalidar também na confirmação. O arquivo nunca é executado como SQL.
    $data=mcfBackupValidar($c,json_encode($data,JSON_THROW_ON_ERROR));
    $c->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');$c->begin_transaction();
    try{
        $s=$c->prepare('SELECT id FROM usuarios WHERE id=? FOR UPDATE');$s->bind_param('i',$uid);$s->execute();if(!$s->get_result()->fetch_assoc())throw new DomainException('Conta de destino inexistente.');$s->close();
        foreach(mcfBackupTabelas() as $t){$s=$c->prepare("SELECT usuario_id FROM `$t` WHERE usuario_id=? FOR UPDATE");$s->bind_param('i',$uid);$s->execute();if($s->get_result()->num_rows)throw new DomainException('A restauração exige uma conta vazia, inclusive preferências de módulos. Nenhum dado foi substituído.');$s->close();}
        $s=$c->prepare('SET @mcf_usuario_id=?, @mcf_ator_id=?, @mcf_convite_id=0, @mcf_convite_versao=0');$s->bind_param('ii',$uid,$uid);$s->execute();$s->close();
        $maps=[];$count=0;$links=['corretora_taxas'=>['corretora_id'=>'corretoras'],'operacoes'=>['corretora_id'=>'corretoras'],'cartoes'=>['compra_id'=>'compras'],'movimentacoes_financeiras'=>['conta_id'=>'contas_financeiras']];
        foreach($data['tabelas'] as $t=>$rows){foreach($rows as $row){$old=$row['id']??null;unset($row['id']);$row['usuario_id']=$uid;
            foreach($links[$t]??[] as $field=>$parent){$source=$row[$field];if(!isset($maps[$parent][$source]))throw new DomainException('O backup possui vínculo ausente: '.$t.'.');$row[$field]=$maps[$parent][$source];}
            if($t==='movimentacoes_financeiras'&&$row['origem_id']!==null){$parent=match($row['origem_modulo']){'receita'=>'rendas','despesa'=>'contas','cartao'=>'cartoes','provento'=>'div_datacom','daytrade'=>'operacoes',default=>null};if(!$parent||!isset($maps[$parent][$row['origem_id']]))throw new DomainException('Vínculo de origem não reconhecido. Solicite restauração assistida; nenhum registro foi importado.');$row['origem_id']=$maps[$parent][$row['origem_id']];}
            $fields=implode(',',array_map(fn($k)=>'`'.$k.'`',array_keys($row)));$marks=implode(',',array_fill(0,count($row),'?'));$s=$c->prepare("INSERT INTO `$t` ($fields) VALUES ($marks)");$values=array_values($row);$s->bind_param(str_repeat('s',count($values)),...$values);$s->execute();if($old!==null)$maps[$t][$old]=$c->insert_id;$s->close();$count++;
        }}$c->commit();return $count;
    }catch(Throwable $e){$c->rollback();throw $e;}
}
