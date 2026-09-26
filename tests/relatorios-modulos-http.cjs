'use strict';
const fs=require('fs'),path=require('path'),cp=require('child_process'),assert=require('assert/strict');
const root=path.resolve(__dirname,'..'),out=path.resolve(root,'../resultados/relatorios-etapa2');
const base='http://127.0.0.1:8099';process.env.MCF_TEST_URL=base;
const {Client}=require('./multiusuario/client.cjs');
const db=sql=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['--no-defaults','-h','127.0.0.1','-P','3307','-u','root','--batch','--skip-column-names','mcf_reports_test'],{input:sql,encoding:'utf8'}).trim();
let server,n=0;const ok=(v,msg)=>{assert.ok(v,msg);n++;};
const token=page=>page.text.match(/name="snapshot" value="([a-f0-9]+)"/)?.[1];
async function pdf(c,snapshot,detalhe='detalhado',endpoint='modulo-pdf.php',csrf=c.csrf){const r=await fetch(base+'/views/relatorios/'+endpoint,{method:'POST',headers:{cookie:c.cookie,'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({snapshot,detalhe,mcf_csrf:csrf})});return {status:r.status,data:Buffer.from(await r.arrayBuffer())};}
(async()=>{
  try{await fetch(base,{signal:AbortSignal.timeout(500)});throw Error('Porta ocupada');}catch(e){if(e.message==='Porta ocupada')throw e;}
  fs.mkdirSync(out,{recursive:true});const sessions=path.join(out,'sessions');fs.mkdirSync(sessions,{recursive:true});
  cp.execFileSync(process.execPath,[path.join(__dirname,'relatorios-reset.cjs')],{stdio:'inherit'});
  let seed='';
  for(let y=2024;y<=2026;y++)for(let m=1;m<=12;m++){
    if(m===2)continue;const date=`${y}-${String(m).padStart(2,'0')}-10`;
    seed+=`INSERT INTO contas(usuario_id,nome,tipo,categoria,vencimento,valor,paga) VALUES(1,'GASTO_QA_${y}_${m}','recorrente','conjunta','${date}',${m===1?0:123.45},${m%2}); INSERT INTO rendas(usuario_id,nome,tipo,classificacao,data,valor,recebido) VALUES(1,'RENDA_QA_${y}_${m}','recorrente','extra','${date}',${m===1?0:1000},${m%2});`;
    seed+=`INSERT INTO compras(usuario_id,nome,categoria,valor_total,total_parcelas,data_compra,origem) VALUES(1,'COMPRA_QA_${y}_${m}','pessoal',999999,3,'${date}','manual'); SET @compra=LAST_INSERT_ID(); INSERT INTO cartoes(usuario_id,compra_id,data,valor,parcela,paga) VALUES(1,@compra,'${date}',${m===1?0:100},1,${m%2}),(1,@compra,'${date}',${m===1?0:-25},2,${m%2});`;
  }
  seed+="INSERT INTO compras(usuario_id,nome,categoria,valor_total,total_parcelas,data_compra,origem) VALUES(2,'CARTAO_BETA_PRIVADO','unica',200,1,'2026-03-01','manual'); INSERT INTO cartoes(usuario_id,compra_id,data,valor,parcela,paga) VALUES(2,LAST_INSERT_ID(),'2026-03-01',200,1,0); INSERT INTO contas(usuario_id,nome,tipo,categoria,vencimento,valor) VALUES(1,'<script>alert(2)</script>','unica','pessoal','2026-03-01',0);";
  db(seed);
  server=cp.spawn('C:/xampp/php/php.exe',['-d','session.save_path='+sessions,'-S','127.0.0.1:8099','-t',root,path.join(__dirname,'multiusuario/router.php')],{env:{...process.env,MCF_DB_HOST:'127.0.0.1',MCF_DB_PORT:'3307',MCF_DB_NAME:'mcf_reports_test',MCF_SESSION_NAME:'MCFREPORTTEST'},windowsHide:true,stdio:['ignore',fs.openSync(path.join(out,'server.log'),'a'),fs.openSync(path.join(out,'server.log'),'a')]});
  for(let i=0;i<30;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
  const a=new Client(),b=new Client(),anon=new Client();await a.login('alfa@example.test');await b.login('beta@example.test');await b.req('/views/relatorios/gastos.php');
  for(const mod of ['gastos','receitas','cartao']){
    const url='/views/relatorios/'+mod+'.php?ano=2026&comparacao=tres_anos';
    ok((await anon.req(url)).status===401,'anônimo '+mod);
    let page=await a.req(url);ok(page.status===200,'tela '+mod);ok(!page.text.includes('BETA_PRIVADO'),'isolamento '+mod);ok(page.text.includes('2 ano(s) antes'),'comparação '+mod);
    if(mod==='gastos')ok(page.text.includes('&lt;script&gt;alert(2)&lt;/script&gt;'),'escape');
    if(mod==='cartao'){ok(page.text.includes('R$ 750,00'),'líquido sem valor total da compra');ok(page.text.includes('R$ -250,00'),'crédito negativo');}
    const stable=token(page);let result=await pdf(a,stable);ok(result.status===200&&result.data.subarray(0,5).toString()==='%PDF-','pdf '+mod);fs.writeFileSync(path.join(out,mod+'-detalhado.pdf'),result.data);
    ok((await pdf(b,stable)).status===410,'sessão '+mod);ok((await pdf(a,stable,'resumido','modulo-pdf.php','wrong')).status===403,'csrf '+mod);
    ok((await pdf(a,stable,'resumido','financeiro-pdf.php')).status===422,'endpoint financeiro '+mod);
    ok((await a.req(url+'&pessoas[]=2')).status===403,'titular não autorizado '+mod);
    for(const graph of ['barras','horizontal','empilhadas','linhas','area']){page=await a.req(url+'&grafico='+graph);ok(page.status===200,'gráfico '+mod+graph);result=await pdf(a,token(page),'resumido');ok(result.status===200,'exportação '+mod+graph);fs.writeFileSync(path.join(out,mod+'-'+graph+'.pdf'),result.data);}
    for(const q of ['pessoas=1','selecionar=1','periodo[]=ano','inicio=2026-02-30&fim=2026-03-01&periodo=personalizado'])ok((await a.req('/views/relatorios/'+mod+'.php?'+q)).status===422,'filtro inválido');
  }
  db("INSERT INTO compartilhamentos(proprietario_id,leitor_id,estado) VALUES(2,1,'ativo'); INSERT INTO compartilhamento_modulos(compartilhamento_id,modulo,nivel) SELECT id,'receitas','leitura' FROM compartilhamentos WHERE proprietario_id=2 AND leitor_id=1;");
  let p=await a.req('/views/relatorios/receitas.php?ano=2026&pessoas[]=2');ok(p.status===200&&p.text.includes('BETA_PRIVADO'),'receitas independente');ok((await pdf(a,token(p))).status===200,'exportação leitura');
  for(const mod of ['gastos','cartao'])ok((await a.req('/views/relatorios/'+mod+'.php?pessoas[]=2')).status===403,'permissão separada '+mod);
  db("UPDATE compartilhamentos SET estado='revogado',versao=versao+1 WHERE proprietario_id=2 AND leitor_id=1");ok((await pdf(a,token(p))).status===403,'revogação');
  db("UPDATE compartilhamentos SET estado='ativo',versao=versao+1 WHERE proprietario_id=2 AND leitor_id=1; INSERT INTO compartilhamento_modulos(compartilhamento_id,modulo,nivel) SELECT id,'cartao','leitura' FROM compartilhamentos WHERE proprietario_id=2 AND leitor_id=1;");
  p=await a.req('/views/relatorios/cartao.php?ano=2026&pessoas[]=1&pessoas[]=2&natureza=unica');ok(p.status===200&&p.text.includes('CARTAO_BETA_PRIVADO')&&!p.text.includes('COMPRA_QA'),'reembolsável consolidado');
  p=await a.req('/views/relatorios/cartao.php?ano=2026');const stable=token(p);db("UPDATE cartoes SET valor=999999 WHERE usuario_id=1 AND data='2026-03-10' AND valor=100");const snapshot=await pdf(a,stable);ok(snapshot.status===200,'snapshot após edição');fs.writeFileSync(path.join(out,'cartao-snapshot.pdf'),snapshot.data);db("UPDATE cartoes SET valor=100 WHERE usuario_id=1 AND data='2026-03-10' AND valor=999999");
  p=await a.req('/views/relatorios/financeiro.php');ok((await pdf(a,token(p))).status===422,'financeiro não aceito no endpoint de módulos');
  ok((await a.req('/views/relatorios/cartao.php?tipo=recorrente')).status===422,'recorrente não existe em cartão');
  fs.writeFileSync(path.join(out,'http-result.json'),JSON.stringify({passed:n,port:3307,database:'mcf_reports_test'},null,2));console.log('PASS: '+n+' verificações HTTP de módulos');
  if(process.argv.includes('--serve')){console.log('QA ativo em '+base);await new Promise(()=>{});}
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(()=>server?.kill());
