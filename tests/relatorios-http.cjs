'use strict';
const fs=require('fs'),path=require('path'),cp=require('child_process'),assert=require('assert/strict');
const root=path.resolve(__dirname,'..'),out=path.resolve(root,'../resultados/relatorios');
const base='http://127.0.0.1:8099';process.env.MCF_TEST_URL=base;
const {Client}=require('./multiusuario/client.cjs');
const db=sql=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['--no-defaults','-h','127.0.0.1','-P','3307','-u','root','--batch','--skip-column-names','mcf_reports_test'],{input:sql,encoding:'utf8'}).trim();
let server,n=0;const ok=(v,label)=>{assert.ok(v,label);n++;};
const snapshot=html=>html.match(/name="snapshot" value="([a-f0-9]+)"/)?.[1];
async function pdf(c,token,detalhe='resumido',csrf=c.csrf){const r=await fetch(base+'/views/relatorios/financeiro-pdf.php',{method:'POST',headers:{cookie:c.cookie,'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({snapshot:token,detalhe,mcf_csrf:csrf})});const data=await r.arrayBuffer();return {status:r.status,headers:r.headers,arrayBuffer:async()=>data};}
(async()=>{
  try{await fetch(base,{signal:AbortSignal.timeout(500)});throw Error('Porta 8099 ocupada');}catch(e){if(e.message==='Porta 8099 ocupada')throw e;}
  fs.mkdirSync(out,{recursive:true});const sessions=path.join(out,'sessions');fs.mkdirSync(sessions,{recursive:true});
  cp.execFileSync(process.execPath,[path.join(__dirname,'relatorios-reset.cjs')],{stdio:'inherit'});
  db("INSERT INTO usuarios(id,email,senha,status_assinatura,data_expiracao) SELECT 3,'gama@example.test',senha,'ativo','2099-12-31' FROM usuarios WHERE id=1;");
  let seed='';
  for(let y=2024;y<=2026;y++)for(let m=1;m<=12;m++){if(m===2)continue;for(let i=1;i<=8;i++){const date=`${y}-${String(m).padStart(2,'0')}-${String(i).padStart(2,'0')}`;seed+=`INSERT INTO rendas(usuario_id,nome,tipo,classificacao,data,valor,recebido) VALUES(1,'RECEITA_TESTE_${y}_${m}_${i}','unica','regular','${date}',${m===1?0:1000+i},${i%2}); INSERT INTO contas(usuario_id,nome,tipo,categoria,vencimento,valor,paga) VALUES(1,'DESPESA_TESTE_${y}_${m}_${i}','unica','pessoal','${date}',${m===1?0:123.45},${i%2});`;}}
  seed+="INSERT INTO contas(usuario_id,nome,tipo,categoria,vencimento,valor) VALUES(1,'<script>alert(1)</script>','unica','conjunta','2026-09-01',12.34);";
  db(seed);
  const env={...process.env,MCF_DB_HOST:'127.0.0.1',MCF_DB_PORT:'3307',MCF_DB_NAME:'mcf_reports_test',MCF_SESSION_NAME:'MCFREPORTTEST'};
  server=cp.spawn('C:/xampp/php/php.exe',['-d','session.save_path='+sessions,'-S','127.0.0.1:8099','-t',root,path.join(__dirname,'multiusuario/router.php')],{env,windowsHide:true,stdio:['ignore',fs.openSync(path.join(out,'server.log'),'a'),fs.openSync(path.join(out,'server.log'),'a')]});
  for(let i=0;i<30;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
  const anon=new Client(),a=new Client(),b=new Client();
  ok((await anon.req('/views/relatorios/financeiro.php')).status===401,'anonymous screen');
  ok((await anon.req('/views/relatorios/financeiro-pdf.php')).status===401,'anonymous pdf');
  ok((await a.login('alfa@example.test')).status===302,'login alfa');ok((await b.login('beta@example.test')).status===302,'login beta');
  const url='/views/relatorios/financeiro.php?periodo=ytd&referencia=2026-09-26&comparacao=tres_anos';
  let page=await a.req(url);ok(page.status===200,'three year screen');ok(!page.text.includes('BETA_PRIVADO'),'isolation');ok(page.text.includes('&lt;script&gt;alert(1)&lt;/script&gt;'),'escaped description');
  fs.writeFileSync(path.join(out,'screen.html'),page.text);const token=snapshot(page.text);ok(!!token,'snapshot');
  let response=await pdf(a,token,'detalhado');ok(response.status===200,'detailed pdf');ok(response.headers.get('content-type')==='application/pdf','content type');ok(response.headers.get('cache-control').includes('no-store'),'private');
  let bytes=Buffer.from(await response.arrayBuffer());ok(bytes.subarray(0,5).toString()==='%PDF-','valid signature');fs.writeFileSync(path.join(out,'http-detalhado.pdf'),bytes);
  await b.req('/views/relatorios/financeiro.php');
  ok((await pdf(b,token)).status===410,'snapshot isolated by session');ok((await pdf(a,token,'resumido','wrong')).status===403,'csrf');
  ok((await a.req('/views/relatorios/financeiro.php?pessoas[]=2')).status===403,'unshared person');
  db("INSERT INTO compartilhamentos(proprietario_id,leitor_id,estado) VALUES(2,1,'ativo'); INSERT INTO compartilhamento_modulos(compartilhamento_id,modulo,nivel) SELECT id,'receitas','leitura' FROM compartilhamentos WHERE proprietario_id=2 AND leitor_id=1;");
  ok((await a.req('/views/relatorios/financeiro.php?pessoas[]=2')).status===403,'one module insufficient');
  db("INSERT INTO compartilhamento_modulos(compartilhamento_id,modulo,nivel) SELECT id,'despesas','leitura' FROM compartilhamentos WHERE proprietario_id=2 AND leitor_id=1;");
  page=await a.req('/views/relatorios/financeiro.php?ano=2026&pessoas[]=1&pessoas[]=2');ok(page.status===200&&page.text.includes('BETA_PRIVADO')&&page.text.includes('ALFA_PRIVADO'),'authorized consolidation');const shared=snapshot(page.text);
  ok((await pdf(a,shared)).status===200,'read-only export authorized');
  db("UPDATE compartilhamentos SET estado='revogado',versao=versao+1 WHERE proprietario_id=2 AND leitor_id=1;");
  ok((await pdf(a,shared)).status===403,'revoked pdf blocked');
  ok((await a.req('/views/relatorios/financeiro.php?pessoas[]=3')).status===403,'third party');
  for(const q of ['periodo[]=ano','periodo=personalizado&inicio=2026-02-30&fim=2026-03-01','selecionar=1','pessoas=1','grafico=hack'])ok((await a.req('/views/relatorios/financeiro.php?'+q)).status===422,'invalid input '+q);
  page=await a.req(url);const stable=snapshot(page.text);
  db("UPDATE rendas SET valor=999999 WHERE usuario_id=1 AND nome='RECEITA_TESTE_2026_3_1'");
  response=await pdf(a,stable);ok(response.status===200,'snapshot export after edit');fs.writeFileSync(path.join(out,'http-snapshot.pdf'),Buffer.from(await response.arrayBuffer()));
  for(const type of ['barras','horizontal','empilhadas','linhas','area']){page=await a.req('/views/relatorios/financeiro.php?ano=2026&grafico='+type);ok(page.status===200,'chart '+type);response=await pdf(a,snapshot(page.text));ok(response.status===200,'pdf '+type);fs.writeFileSync(path.join(out,'http-'+type+'.pdf'),Buffer.from(await response.arrayBuffer()));}
  for(const view of ['tabela','grafico']){page=await a.req('/views/relatorios/financeiro.php?ano=2026&visualizacao='+view);ok(page.status===200,'view '+view);response=await pdf(a,snapshot(page.text));ok(response.status===200,'export '+view);fs.writeFileSync(path.join(out,'http-'+view+'.pdf'),Buffer.from(await response.arrayBuffer()));}
  fs.writeFileSync(path.join(out,'http-result.json'),JSON.stringify({passed:n,database:'mcf_reports_test',port:3307,at:new Date().toISOString()},null,2));console.log('PASS: '+n+' verificações HTTP de relatórios');
  if(process.argv.includes('--serve')){console.log('Servidor de QA: '+base+' | alfa@example.test | Teste-Conta-2026!');await new Promise(()=>{});}
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(()=>server?.kill());
