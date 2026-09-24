const assert=require('node:assert/strict'),cp=require('node:child_process');const {Client}=require('./client.cjs');
if(process.env.MCF_DB_NAME!=='mcf_test_20260921')throw new Error('Use o runner isolado.');
const db=sql=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['-u','root','mcf_test_20260921'],{input:sql,encoding:'utf8'});
let n=0;const eq=(a,b,m)=>{assert.deepEqual(a,b,m);n++;};
const series=(html,name)=>JSON.parse(html.match(new RegExp("label: '"+name+"',\\s*data: (\\[[^\\]]*\\])"))[1]);
(async()=>{try{
 for(const uid of [1,2]){
  for(const [date,val] of [['2023-12-31',999],['2024-01-01',111],['2024-12-31',333],['2025-01-01',999]]){
   db(`INSERT INTO rendas(usuario_id,nome,descricao,tipo,classificacao,data,valor) VALUES(${uid},'QA_ANUAL','QA_ANUAL','unica','regular','${date}',${uid*val});INSERT INTO contas(usuario_id,nome,tipo,categoria,vencimento,valor) VALUES(${uid},'QA_ANUAL','unica','pessoal','${date}',${uid*val/3});`);
  }
 }
 for(const [email,uid] of [['alfa@example.test',1],['beta@example.test',2]]){
  const c=new Client();await c.login(email);const r=await c.req('/views/dashboard.php?ano_grafico=2024');eq(r.status,200,'dashboard acessível');
  const labels=JSON.parse(r.text.match(/labels: (\[[^\]]*\])/)[1]);eq(labels.length,12,'doze meses');eq(labels[0],'Jan/24','começa em janeiro');eq(labels[11],'Dez/24','termina em dezembro');
  const expected=[111*uid,...Array(10).fill(0),333*uid];eq(series(r.text,'Receitas'),expected,'ano completo sem misturar usuários/anos');eq(series(r.text,'Despesas'),expected.map(x=>x/3),'despesas completas');
  const invalid=await c.req('/views/dashboard.php?ano_grafico[]=2024');eq(invalid.status,200,'entrada inválida usa padrão');eq(JSON.parse(invalid.text.match(/labels: (\[[^\]]*\])/)[1]).length,12,'padrão mantém doze meses');
 }
 console.log(`PASS: ${n} verificações de gráfico anual (janeiro/dezembro, zeros, anos e contas isolados)`);
}finally{db("DELETE FROM rendas WHERE nome='QA_ANUAL';DELETE FROM contas WHERE nome='QA_ANUAL';");}})().catch(e=>{console.error(e);process.exitCode=1});
