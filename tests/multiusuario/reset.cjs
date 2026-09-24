const path=require('path');
const fs=require('fs'),cp=require('child_process');
const mysql='C:/xampp/mysql/bin/mysql.exe',php='C:/xampp/php/php.exe';
// Only this disposable database may be reset. Never accepts an application database name.
cp.execFileSync(mysql,['-u','root'],{input:'DROP DATABASE IF EXISTS mcf_test_20260921; CREATE DATABASE mcf_test_20260921 CHARACTER SET utf8mb4;'});
cp.execFileSync(mysql,['-u','root','mcf_test_20260921'],{input:fs.readFileSync(path.join(__dirname,'schema-antes.sql'))});
const hash=cp.execFileSync(php,['-r','echo password_hash("Teste-Conta-2026!", PASSWORD_DEFAULT);']).toString();
cp.execFileSync(mysql,['-u','root','mcf_test_20260921'],{input:`INSERT INTO usuarios (id,email,senha,status_assinatura,data_expiracao) VALUES (1,'alfa@example.test','${hash}','ativo','2099-12-31'),(2,'beta@example.test','${hash}','ativo','2099-12-31');`});
cp.execFileSync(php,[path.resolve(__dirname,'../../tools/migrar_multiusuario.php'),'1'],{env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});
cp.execFileSync(process.execPath,[path.join(__dirname,'seed.cjs')]);
console.log('Base descartável reinicializada.');

cp.execFileSync(php,[path.resolve(__dirname,'../../tools/migrar_perfil.php'),'--apply'],{env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});
cp.execFileSync(php,[path.resolve(__dirname,'../../tools/migrar_compartilhamento.php'),'--apply'],{env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});

cp.execFileSync(php,[path.resolve(__dirname,'../../tools/migrar_melhorias.php'),'--apply'],{env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});

cp.execFileSync(php,[path.resolve(__dirname,'../../tools/migrar_cripto.php'),'--apply'],{env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});
