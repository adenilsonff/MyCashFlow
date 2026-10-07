<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'&&realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){http_response_code(404);exit;}
/** Valida antes de converter; zero é permitido nos cadastros que já o aceitavam. */
function mcfValorMonetario($entrada, bool $negativo=false, string $limite='99999999.99'): float {
    if(!is_string($entrada)&&!is_int($entrada))throw new DomainException('Valor inválido. Informe um número com até duas casas decimais, como 1234,56.');
    $v=trim((string)$entrada);$v=preg_replace('/^R\$\s*/u','',$v);
    $sinal=$negativo?'-?':'';
    if(preg_match('/^'.$sinal.'(?:\d+|\d{1,3}(?:\.\d{3})+),\d{1,2}$/D',$v))$v=str_replace(',','.',str_replace('.','',$v));
    elseif(!preg_match('/^'.$sinal.'\d+(?:\.\d{1,2})?$/D',$v))throw new DomainException('Valor inválido. Informe um número com até duas casas decimais, como 1234,56.');
    if(strlen($v)>40||bccomp(ltrim($v,'-'),$limite,2)>0)throw new DomainException('O valor informado ultrapassa o limite permitido neste cadastro.');
    return (float)$v;
}
