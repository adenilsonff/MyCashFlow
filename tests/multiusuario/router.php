<?php
// Montagem local para o servidor de teste; este arquivo não é implantado.
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
$path=preg_replace('#^/MyCashFlow(?=/|$)#','',$path);
if ($path==='' || $path==='/') $path='/index.php';
$root=realpath(__DIR__.'/../..');$file=realpath($root.$path);
if (!$file || !str_starts_with($file,$root.DIRECTORY_SEPARATOR) || !is_file($file) || preg_match('#^/(?:includes|tools|tests|models|controllers|docs|migrations|ARQUIVOS|views/BKP|config.php|\.)#i',$path)) { http_response_code(404);exit; }
if (pathinfo($file,PATHINFO_EXTENSION)==='php') { $_SERVER['SCRIPT_FILENAME']=$file; $_SERVER['SCRIPT_NAME']=$path;chdir(dirname($file));require $file;return; }
$types=['css'=>'text/css','js'=>'application/javascript','png'=>'image/png','svg'=>'image/svg+xml','jpg'=>'image/jpeg','webp'=>'image/webp','ico'=>'image/x-icon'];
$ext=pathinfo($file,PATHINFO_EXTENSION);if (!isset($types[$ext])) {http_response_code(404);exit;}
header('Content-Type: '.$types[$ext]);readfile($file);
