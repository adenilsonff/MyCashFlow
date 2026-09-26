<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) { http_response_code(404); exit; }

function relData(string $v): DateTimeImmutable {
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if (!$d || $d->format('Y-m-d')!==$v || $v<'2000-01-01' || $v>'2100-12-31') throw new DomainException('Informe uma data válida entre 2000 e 2100.');
    return $d;
}
function relOpcao(array $q,string $key,array $options,string $default): string {
    $v=$q[$key]??$default;
    if (!is_string($v)||!in_array($v,$options,true)) throw new DomainException('Filtro inválido: '.$key.'.');
    return $v;
}
function relFiltros(array $q): array {
    $periodo=relOpcao($q,'periodo',['semana','mes','trimestre','semestre','ano','personalizado','36meses','ytd'],'ano');
    $ref=$q['referencia']??(isset($q['ano']) && is_scalar($q['ano']) ? (int)$q['ano'].'-01-01' : date('Y-m-d'));
    if (!is_string($ref)) throw new DomainException('Data de referência inválida.');
    $d=relData($ref); $y=(int)$d->format('Y'); $m=(int)$d->format('n');
    $inicio=match($periodo) {
        'semana'=>$d->modify('-'.((int)$d->format('N')-1).' days'),
        'mes'=>$d->modify('first day of this month'),
        'trimestre'=>$d->setDate($y,(int)(floor(($m-1)/3)*3+1),1),
        'semestre'=>$d->setDate($y,$m<=6?1:7,1),
        '36meses'=>$d->modify('first day of this month')->modify('-35 months'),
        default=>$d->setDate($y,1,1)
    };
    $fim=match($periodo) {
        'semana'=>$inicio->modify('+6 days'),'mes'=>$inicio->modify('last day of this month'),
        'trimestre'=>$inicio->modify('+3 months -1 day'),'semestre'=>$inicio->modify('+6 months -1 day'),
        '36meses','ytd'=>$d,default=>$d->setDate($y,12,31)
    };
    if ($periodo==='personalizado') {
        if (!is_string($q['inicio']??null)||!is_string($q['fim']??null)) throw new DomainException('Informe início e fim.');
        $inicio=relData($q['inicio']);$fim=relData($q['fim']);
    }
    if($inicio>$fim || $inicio->diff($fim)->days>3660) throw new DomainException('O intervalo deve ser crescente e ter no máximo dez anos.');
    $agrupamento=relOpcao($q,'agrupamento',['dia','semana','mes','ano'],'mes');
    if($agrupamento==='dia' && $inicio->diff($fim)->days>366) throw new DomainException('Para intervalos acima de 367 dias, agrupe por semana, mês ou ano.');
    return ['periodo'=>$periodo,'referencia'=>$ref,'inicio'=>$inicio->format('Y-m-d'),'fim'=>$fim->format('Y-m-d'),
        'agrupamento'=>$agrupamento,'situacao'=>relOpcao($q,'situacao',['todos','realizados','pendentes','vencidos'],'todos'),
        'comparacao'=>relOpcao($q,'comparacao',['nenhuma','anterior','ano_anterior','tres_anos'],'nenhuma'),
        'visualizacao'=>relOpcao($q,'visualizacao',['ambos','grafico','tabela'],'ambos'),
        'grafico'=>relOpcao($q,'grafico',['barras','horizontal','empilhadas','linhas','area'],'barras'),
        'natureza'=>relOpcao($q,'natureza',['todos','pessoal','conjunta'],'todos'),
        'classificacao'=>relOpcao($q,'classificacao',['todos','regular','extra'],'todos'),
        'tipo'=>relOpcao($q,'tipo',['todos','unica','parcelada','recorrente'],'todos')];
}
function relAnoAnterior(string $v,int $anos): string {
    $d=new DateTimeImmutable($v); $y=(int)$d->format('Y')-$anos; $m=(int)$d->format('m');
    $first=$d->setDate($y,$m,1);return $first->setDate($y,$m,min((int)$d->format('d'),(int)$first->format('t')))->format('Y-m-d');
}
function relPeriodos(array $f): array {
    $out=[['inicio'=>$f['inicio'],'fim'=>$f['fim'],'nome'=>'Selecionado']];
    if($f['comparacao']==='anterior') {
        $a=new DateTimeImmutable($f['inicio']);$b=new DateTimeImmutable($f['fim']);$dias=$a->diff($b)->days+1;
        $out[]=['inicio'=>$a->modify('-'.$dias.' days')->format('Y-m-d'),'fim'=>$a->modify('-1 day')->format('Y-m-d'),'nome'=>'Anterior ('.$dias.' dias)'];
    } elseif(in_array($f['comparacao'],['ano_anterior','tres_anos'],true)) {
        for($i=1;$i<=($f['comparacao']==='tres_anos'?2:1);$i++) $out[]=['inicio'=>relAnoAnterior($f['inicio'],$i),'fim'=>relAnoAnterior($f['fim'],$i),'nome'=>$i.' ano(s) antes'];
    }
    return $out;
}
function relCentavos(string $v): int {
    if(!preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/',$v,$m)) throw new DomainException('Valor monetário inválido.');
    return ($m[1]==='-'?-1:1)*((int)$m[2]*100+(int)str_pad($m[3]??'',2,'0'));
}
function relMoeda(?int $v): string { return $v===null?'Sem dados':'R$ '.number_format($v/100,2,',','.'); }
function relChave(string $v,string $grupo): string {
    $d=new DateTimeImmutable($v);
    return match($grupo){'dia'=>$v,'semana'=>$d->modify('-'.((int)$d->format('N')-1).' days')->format('Y-m-d'),'mes'=>$d->format('Y-m'),default=>$d->format('Y')};
}
function relAgregar(array $rows,array $p,array $f,string $hoje): array {
    $buckets=[];$d=new DateTimeImmutable($p['inicio']);$fim=new DateTimeImmutable($p['fim']);
    while($d<=$fim) { $key=relChave($d->format('Y-m-d'),$f['agrupamento']);$buckets[$key]=['chave'=>$key,'receitas'=>0,'despesas'=>0,'recebido'=>0,'pago'=>0,'n'=>0,'nr'=>0,'nd'=>0];$d=$d->modify('+1 day'); }
    $detalhes=[];
    foreach($rows as $r) {
        if($r['data']<$p['inicio']||$r['data']>$p['fim'])continue;
        if($f['situacao']==='realizados'&&!$r['realizado'] || $f['situacao']==='pendentes'&&$r['realizado'] || $f['situacao']==='vencidos'&&($r['realizado']||$r['data']>=$hoje))continue;
        if($f['tipo']!=='todos'&&$r['tipo']!==$f['tipo'])continue;
        if($r['modulo']==='despesas'&&$f['natureza']!=='todos'&&$r['categoria']!==$f['natureza'])continue;
        if($r['modulo']==='receitas'&&$f['classificacao']!=='todos'&&$r['categoria']!==$f['classificacao'])continue;
        $key=relChave($r['data'],$f['agrupamento']);$b=&$buckets[$key];$v=$r['centavos'];$b['n']++;
        if($r['modulo']==='receitas'){ $b['receitas']+=$v;$b['nr']++;if($r['realizado'])$b['recebido']+=$v; }
        else { $b['despesas']+=$v;$b['nd']++;if($r['realizado'])$b['pago']+=$v; }
        unset($b);$r['grupo']=$key;$detalhes[]=$r;
    }
    $total=['receitas'=>0,'despesas'=>0,'recebido'=>0,'pago'=>0,'n'=>0,'nr'=>0,'nd'=>0];
    foreach($buckets as &$b){foreach($total as $k=>$_)$total[$k]+=$b[$k];$b['saldo']=$b['receitas']-$b['despesas'];$b['realizado']=$b['recebido']-$b['pago'];}unset($b);
    $total['saldo']=$total['receitas']-$total['despesas'];$total['realizado']=$total['recebido']-$total['pago'];
    return $p+['grupos'=>array_values($buckets),'total'=>$total,'detalhes'=>$detalhes];
}
function relDiferenca(?int $atual,?int $base): array {
    return ['valor'=>$atual===null||$base===null?null:$atual-$base,'percentual'=>$atual===null||$base===null||$base===0?null:100*($atual-$base)/abs($base)];
}
