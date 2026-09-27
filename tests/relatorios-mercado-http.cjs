const fs=require('fs'),path=require('path'),cp=require('child_process'),assert=require('assert/strict');
const root=path.resolve(__dirname,'..'),out=path.resolve(root,'../resultados/relatorios-etapa3'),base='http://127.0.0.1:8099';process.env.MCF_TEST_URL=base;
const {Client}=require('./multiusuario/client.cjs');
const db=sql=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['--no-defaults','-h','127.0.0.1','-P','3307','-u','root','--batch','--skip-column-names','mcf_reports_test'],{input:sql,encoding:'utf8'}).trim();
let server,n=0;const ok=(v,msg)=>{assert.ok(v,msg);n++;};const token=p=>p.text.match(/name="snapshot" value="([a-f0-9]+)"/)?.[1];
async function pdf(c,snapshot,detalhe='detalhado',csrf=c.csrf){const r=await fetch(base+'/views/relatorios/modulo-pdf.php',{method:'POST',headers:{cookie:c.cookie,'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({snapshot,detalhe,mcf_csrf:csrf})});return {status:r.status,data:Buffer.from(await r.arrayBuffer())};}
(async()=>{
  try{await fetch(base,{signal:AbortSignal.timeout(500)});throw Error('Porta ocupada');}catch(e){if(e.message==='Porta ocupada')throw e;}
  fs.mkdirSync(out,{recursive:true});const sessions=path.join(out,'sessions');fs.mkdirSync(sessions,{recursive:true});
  cp.execFileSync(process.execPath,[path.join(__dirname,'relatorios-reset.cjs')],{stdio:'inherit'});
  db("INSERT INTO corretoras(id,usuario_id,nome) VALUES(501,1,'CORRETORA_ALFA'),(502,2,'CORRETORA_BETA');");
  for(const uid of [1,2]){
    const ticker=uid===1?'TEST3':'BETA3';
    db(`INSERT INTO investimentos_nacionais(usuario_id,ticker,tipo_ativo,quantidade,valor_unitario,data,tipo_operacao) VALUES(${uid},'${ticker}','acao',100,10,'2023-01-01','compra');`);
    for(const y of [2024,2025,2026]){
      db(`INSERT INTO investimentos_nacionais(usuario_id,ticker,tipo_ativo,quantidade,valor_unitario,data,tipo_operacao) VALUES(${uid},'${ticker}','acao',10,20,'${y}-03-01','compra'),(${uid},'${ticker}','acao',-3,25,'${y}-03-10','compra');
      INSERT INTO investimentos_internacionais(usuario_id,ticker,tipo_ativo,quantidade,valor_unitario,valor_investido,data,tipo_operacao) VALUES(${uid},'BTC','cripto',0.001,100000,100,'${y}-03-01','compra'),(${uid},'BTC','cripto',0.0005,120000,60,'${y}-04-01','venda');
      INSERT INTO div_datacom(usuario_id,ticker,tipo_ativo,datacom,datapag,valor,tipo) VALUES(${uid},'${ticker}','acao','${y}-03-15','${y}-04-10',0.12345678,'DIV'),(${uid},'${ticker}','acao','${y}-05-01',NULL,0.25,'JCP'),(${uid},'SEM3','acao','${y}-03-01',NULL,100,'DIV');
      INSERT INTO operacoes(usuario_id,corretora_id,data,acao,quantidade,total_compra,total_venda,lucro_bruto,taxas,darf,lucro_final) VALUES(${uid},${500+uid},'${y}-03-01','${ticker}',10,100,180,80,10,20,50),(${uid},${500+uid},'${y}-04-01','${ticker}',10,100,80,-20,5,0,-25),(${uid},${500+uid},'${y}-05-01','${ticker}',1,10,0,0,0,0,0);`);
    }
  }
  server=cp.spawn('C:/xampp/php/php.exe',['-d','session.save_path='+sessions,'-S','127.0.0.1:8099','-t',root,path.join(__dirname,'multiusuario/router.php')],{env:{...process.env,MCF_DB_HOST:'127.0.0.1',MCF_DB_PORT:'3307',MCF_DB_NAME:'mcf_reports_test',MCF_SESSION_NAME:'MCFREPORTTEST'},windowsHide:true,stdio:['ignore',fs.openSync(path.join(out,'server.log'),'a'),fs.openSync(path.join(out,'server.log'),'a')]});
  for(let i=0;i<30;i++){try{await fetch(base);break;}catch{await new Promise(r=>setTimeout(r,100));}}
  const a=new Client(),b=new Client(),anon=new Client();await a.login('alfa@example.test');await b.login('beta@example.test');await b.req('/views/relatorios/daytrade.php');
  for(const m of ['investimentos','proventos','daytrade']){
    const url='/views/relatorios/'+m+'.php?ano=2026&comparacao=tres_anos';
    ok((await anon.req(url)).status===401,'anônimo');let p=await a.req(url);ok(p.status===200,'tela '+m+' status='+p.status+' '+p.text.slice(0,500));ok(!p.text.includes('BETA3'),'isolamento');ok(p.text.includes('2 ano(s) antes'),'comparações');
    const expected={investimentos:'R$ 125,00',proventos:'R$ 45,19',daytrade:'R$ 25,00'}[m];ok(p.text.includes(expected),'total '+m);
    if(m==='proventos')ok(!p.text.includes('SEM3'),'posição inelegível');
    let r=await pdf(a,token(p));ok(r.status===200&&r.data.subarray(0,5).toString()==='%PDF-','PDF '+m);fs.writeFileSync(path.join(out,m+'-detalhado.pdf'),r.data);
    ok((await pdf(b,token(p))).status===410,'sessão');ok((await pdf(a,token(p),'resumido','wrong')).status===403,'csrf');ok((await a.req(url+'&pessoas[]=2')).status===403,'titular');
    for(const chart of ['barras','horizontal','empilhadas','linhas','area']){p=await a.req(url+'&grafico='+chart);ok(p.status===200,'gráfico');r=await pdf(a,token(p),'resumido');ok(r.status===200,'PDF gráfico');fs.writeFileSync(path.join(out,m+'-'+chart+'.pdf'),r.data);}
    for(const q of ['ticker[]=x','selecionar=1','corretora_id=-1','periodo=personalizado&inicio=2026-02-30&fim=2026-03-01'])ok((await a.req('/views/relatorios/'+m+'.php?'+q)).status===422,'filtro inválido');
  }
  let p=await a.req('/views/relatorios/investimentos.php?ano=2026&moeda=USD');ok(p.status===200&&p.text.includes('US$ 40,00')&&!p.text.includes('R$ 40,00'),'USD separado');let r=await pdf(a,token(p));fs.writeFileSync(path.join(out,'investimentos-usd.pdf'),r.data);ok(r.status===200,'PDF USD');
  p=await a.req('/views/relatorios/proventos.php?ano=2026&data_base=datapag');ok(p.text.includes('R$ 14,94')&&!p.text.includes('JCP / acao'),'data pagamento exclui não informada');
  p=await a.req('/views/relatorios/daytrade.php?ano=2026&operacao=abertas');ok(p.text.includes('1 registros')&&p.text.includes('R$ 0,00'),'abertas');
  p=await a.req('/views/relatorios/daytrade.php?ano=2026&corretora_id=502');ok(!p.text.includes('BETA3')&&p.text.includes('0 registros'),'corretora outro titular não vaza');
  db("INSERT INTO compartilhamentos(proprietario_id,leitor_id,estado) VALUES(2,1,'ativo'); INSERT INTO compartilhamento_modulos(compartilhamento_id,modulo,nivel) SELECT id,'investimentos','leitura' FROM compartilhamentos WHERE proprietario_id=2 AND leitor_id=1;");
  for(const m of ['investimentos','proventos']){p=await a.req('/views/relatorios/'+m+'.php?ano=2026&pessoas[]=1&pessoas[]=2');ok(p.status===200&&p.text.includes('BETA3'),'leitura '+m);ok((await pdf(a,token(p))).status===200,'exporta autorizado');}
  ok((await a.req('/views/relatorios/daytrade.php?pessoas[]=2')).status===403,'daytrade separado');const stable=token(p);db("UPDATE compartilhamentos SET estado='revogado',versao=versao+1 WHERE proprietario_id=2 AND leitor_id=1");ok((await pdf(a,stable)).status===403,'revogação');
  p=await a.req('/views/relatorios/daytrade.php?ano=2026');const snapshot=token(p);db("UPDATE operacoes SET lucro_final=99999 WHERE usuario_id=1 AND data='2026-03-01'");r=await pdf(a,snapshot);fs.writeFileSync(path.join(out,'daytrade-snapshot.pdf'),r.data);ok(r.status===200,'snapshot');db("UPDATE operacoes SET lucro_final=50 WHERE usuario_id=1 AND data='2026-03-01'");
  fs.writeFileSync(path.join(out,'http-result.json'),JSON.stringify({passed:n,database:'mcf_reports_test',port:3307},null,2));console.log('PASS: '+n+' verificações HTTP de mercado');
  if(process.argv.includes('--serve')){console.log('QA ativo em '+base);await new Promise(()=>{});}
})().catch(e=>{console.error(e);process.exitCode=1;}).finally(()=>server?.kill());
