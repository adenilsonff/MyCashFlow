<?php
// Administração local de credenciais; nenhuma conta web ganha acesso financeiro administrativo.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$uid=filter_var($argv[1]??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$uid || !getenv('MCF_DB_NAME')) exit("Defina MCF_DB_NAME e informe o ID do usuário. A nova senha deve chegar pela entrada padrão.\n");
$password=rtrim(stream_get_contents(STDIN),"\r\n");
if (strlen($password)<12 || strlen($password)>72) exit("Use entre 12 e 72 bytes para a senha.\n");
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$c=new mysqli(getenv('MCF_DB_HOST')?:'127.0.0.1',getenv('MCF_DB_USER')?:'root',getenv('MCF_DB_PASS')?:'',getenv('MCF_DB_NAME'),(int)(getenv('MCF_DB_PORT')?:3306));
$hash=password_hash($password,PASSWORD_DEFAULT); unset($password);
$s=$c->prepare('UPDATE usuarios SET senha=? WHERE id=?');$s->bind_param('si',$hash,$uid);$s->execute();
echo $s->affected_rows===1 ? "Senha alterada; sessões anteriores serão rejeitadas.\n" : "Usuário não encontrado.\n";
