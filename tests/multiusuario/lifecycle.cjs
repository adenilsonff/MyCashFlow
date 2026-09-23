const path=require('path');
const assert=require('assert/strict'),cp=require('child_process'),fs=require('fs');const {Client,base}=require('./client.cjs');
const db=s=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['-u','root','mcf_test_20260921'],{input:s});
let n=0;const ok=(v,m)=>{assert.ok(v,m);n++;};
(async()=>{
 const c=new Client();await c.req('/views/login/login.php');const pre=c.cookie;await c.login('alfa@example.test');ok(pre!==c.cookie,'ID renovado no login');
 const cfg=await c.req('/views/configuracao.php');ok(cfg.status===200&&cfg.text.includes('alfa@example.test')&&!cfg.text.includes('beta@example.test'),'configuração própria');
 const unauth=new Client();ok((await unauth.req('/views/configuracao.php')).status===401,'configuração protegida');
 const history=await c.req('/views/analise/api.php?action=history&ticker=PETR4&tipo_ativo=acao&range=3mo&interval=1d');ok(history.status===200,'histórico sem erro interno');const h=JSON.parse(history.text);ok(h.ok===true||typeof h.code==='string','contrato BRAPI preservado');
 for(const route of ['/includes/saldos_consulta.php','/views/BKP/salvar_taxa.php','/tools/redefinir_senha_local.php','/config.php'])ok((await unauth.req(route)).status===404,'interno negado no servidor de teste '+route);
 await c.req('/views/login/logout.php');ok((await c.req('/views/contas.php')).status===200,'GET logout não encerra sessão');
 db("UPDATE usuarios SET status_assinatura='inativo' WHERE id=1");ok((await c.req('/views/contas.php')).status===403,'assinatura validada no servidor');db("UPDATE usuarios SET status_assinatura='ativo' WHERE id=1");
 cp.execFileSync('C:/xampp/php/php.exe',[path.resolve(__dirname,'../../tools/redefinir_senha_local.php'),'1'],{input:'Senha-Temporaria-Teste-2026!',env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});ok((await c.req('/views/contas.php')).status===401,'reset local revoga sessão');
 cp.execFileSync('C:/xampp/php/php.exe',[path.resolve(__dirname,'../../tools/redefinir_senha_local.php'),'1'],{input:'Teste-Conta-2026!',env:{...process.env,MCF_DB_NAME:'mcf_test_20260921'}});
 console.log(`PASS: ${n} verificações de ciclo de sessão, configuração, BRAPI e rotas internas`);fs.writeFileSync(path.join(process.env.MCF_TEST_OUTPUT || __dirname,'lifecycle-result.json'),JSON.stringify({passed:n,liveHistory:h.ok,date:new Date().toISOString()},null,2));
})().catch(e=>{console.error('FAIL',e.message);process.exitCode=1;});
