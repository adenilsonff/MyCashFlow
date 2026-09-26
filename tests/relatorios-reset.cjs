'use strict';
// Instância descartável exclusiva: 127.0.0.1:3307, nunca usa os padrões do XAMPP.
const cp=require('child_process'),fs=require('fs'),path=require('path');
const root=path.resolve(__dirname,'..'),mysql='C:/xampp/mysql/bin/mysql.exe',php='C:/xampp/php/php.exe';
const baseArgs=['--no-defaults','-h','127.0.0.1','-P','3307','-u','root'];
const schema=path.join(__dirname,'multiusuario/schema-antes.sql');
cp.execFileSync(mysql,baseArgs,{input:'DROP DATABASE IF EXISTS mcf_reports_test; CREATE DATABASE mcf_reports_test CHARACTER SET utf8mb4;'});
const sql=s=>cp.execFileSync(mysql,[...baseArgs,'mcf_reports_test'],{input:s});
sql(fs.readFileSync(schema));
const hash=cp.execFileSync(php,['-r','echo password_hash("Teste-Conta-2026!", PASSWORD_DEFAULT);']).toString();
sql(`INSERT INTO usuarios(id,email,senha,status_assinatura,data_expiracao) VALUES(1,'alfa@example.test','${hash}','ativo','2099-12-31'),(2,'beta@example.test','${hash}','ativo','2099-12-31');`);
const env={...process.env,MCF_DB_HOST:'127.0.0.1',MCF_DB_PORT:'3307',MCF_DB_USER:'root',MCF_DB_PASS:'',MCF_DB_NAME:'mcf_reports_test'};
cp.execFileSync(php,[path.join(root,'tools/migrar_multiusuario.php'),'1',schema],{env,stdio:'inherit'});
for(const tool of ['perfil','compartilhamento','melhorias','cripto'])cp.execFileSync(php,['-d','mysqli.default_port=3307',path.join(root,'tools/migrar_'+tool+'.php'),'--apply'],{env,stdio:'inherit'});
for(const [uid,id,name] of [[1,101,'ALFA_PRIVADO'],[2,201,'BETA_PRIVADO']])sql(`INSERT INTO contas(id,usuario_id,nome,tipo,categoria,vencimento,valor) VALUES(${id},${uid},'${name}','unica','pessoal','2026-09-20',${uid*111}); INSERT INTO rendas(id,usuario_id,nome,tipo,classificacao,data,valor) VALUES(${id},${uid},'${name}','unica','regular','2026-09-20',${uid*1111});`);
console.log('Fixture de relatórios criada exclusivamente em 127.0.0.1:3307/mcf_reports_test');
