// Somente a base descartável do runner. Não execute contra o banco principal.
const assert=require('node:assert/strict'),cp=require('node:child_process'),fs=require('node:fs'),path=require('node:path');
const {Client}=require('./client.cjs');
if(process.env.MCF_DB_NAME!=='mcf_test_20260921')throw new Error('Use o runner isolado.');
const db=sql=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['-u','root','-N','-B','mcf_test_20260921'],{input:sql,encoding:'utf8'}).trim();
let n=0;const ok=(v,m)=>{assert.ok(v,m);n++;};const token=(h,k)=>h.match(new RegExp('name="'+k+'"\\s+value="([a-f0-9]+)"'))?.[1]||'';
(async()=>{
 const a=new Client(),b=new Client();ok((await a.login('alfa@example.test')).status===302,'login titular');ok((await b.login('beta@example.test')).status===302,'login destinatário');
 const id=Number(db('SELECT id FROM compartilhamentos WHERE proprietario_id=1 AND leitor_id=2'));const version=()=>db('SELECT versao FROM compartilhamentos WHERE id='+id);
 const betaBefore=db('SELECT * FROM investimentos_internacionais WHERE usuario_id=2 ORDER BY id');
 const count=()=>db("SELECT COUNT(*) FROM investimentos_internacionais WHERE ticker='QACRYPTO'");
 async function trade(c,data={},delegated=false){const url='/views/investimentos_internacionais.php'+(delegated?'?compartilhamento='+id:'');const r=await c.req(url);return c.req(url,{mcf_csrf:c.csrf,csrf:token(r.text,'csrf'),tipo_ativo:'cripto',ticker:'QACRYPTO',modo:'quantidade',quantidade:'0.12500000',valor_unitario:'40000.12345678',data:'2026-01-15',acao:'compra',...(delegated?{mcf_contexto:id,mcf_contexto_versao:version()}:{}),...data});}
 async function grant(level,actions=[]){await a.req('/views/compartilhamento.php');const d={mcf_csrf:a.csrf,acao:'revisar',id,versao:version(),granular:1,'modulos[0]':'investimentos','nivel[investimentos]':level};for(const action of actions)d['acoes[investimentos]['+action+']']='1';ok((await a.req('/views/compartilhamento.php',d)).status===303,'revisar permissão');await b.req('/views/compartilhamento.php');ok((await b.req('/views/compartilhamento.php',{mcf_csrf:b.csrf,acao:'aceitar',id,versao:version()})).status===303,'aceitar permissão');}
 ok(db("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='mcf_test_20260921' AND TABLE_NAME='investimentos_internacionais' AND COLUMN_NAME='tipo_ativo'").includes("'cripto'"),'fixture tem migração cripto');
 ok((await trade(a,{usuario_id:2})).status===302,'compra fracionária');
 const purchase=db("SELECT id FROM investimentos_internacionais WHERE ticker='QACRYPTO' AND tipo_operacao='compra' AND usuario_id=1");ok(!!purchase,'proprietário vem da sessão');
 ok(db('SELECT CONCAT(quantidade,\'|\',valor_unitario,\'|\',valor_investido) FROM investimentos_internacionais WHERE id='+purchase)==='0.12500000|40000.12345678|5000.01543210','persistência decimal exata');
 ok((await trade(a,{quantidade:'0.025',valor_unitario:'50000.00000001',data:'2026-02-15',acao:'venda'})).status===302,'venda parcial fracionária');
 ok(db("SELECT quantidade FROM investimentos_internacionais WHERE ticker='QACRYPTO' AND tipo_operacao='venda'")==='-0.02500000','venda negativa no banco');
 const before=count();await trade(a,{quantidade:'0.10000001',acao:'venda',data:'2026-03-15'});ok(count()===before,'não permite vender um satoshi além da posição');
 for(const invalid of [{quantidade:'0.000000001'},{valor_unitario:'0.000000001'},{quantidade:'0'},{valor_unitario:'-1'},{csrf:'invalid'},{mcf_csrf:'invalid'}]){await trade(a,invalid);ok(count()===before,'entrada inválida não grava '+JSON.stringify(invalid));}
 ok((await trade(a,{ticker:'QATINY',quantidade:'1000000',valor_unitario:'0.00000001'})).status===302,'preço pequeno');ok(db("SELECT valor_investido FROM investimentos_internacionais WHERE ticker='QATINY'")==='0.01000000','preço pequeno mantém total');
 let r=await a.req('/views/investimentos_internacionais.php');ok(r.status===200&&r.text.includes('QACRYPTO')&&r.text.includes('QATINY'),'carteira mostra criptos');
 r=await b.req('/views/investimentos_internacionais.php');ok(!r.text.includes('QACRYPTO')&&!r.text.includes('QATINY'),'conta alheia isolada');
 r=await a.req('/views/relatorios/investimentos.php?ano=2026&mes=0');ok(r.status===200&&r.text.includes('QACRYPTO')&&r.text.includes('Criptomoeda'),'relatório inclui cripto');
 r=await b.req('/views/relatorios/investimentos.php?ano=2026&mes=0');ok(!r.text.includes('QACRYPTO'),'relatório privado isolado');
 const revision=async(c,op,delegated=false)=>{const r=await c.req('/views/investimentos_operacoes.php?mercado=internacional'+(delegated?'&compartilhamento='+id:''));const form=[...r.text.matchAll(/<form\b[^>]*>[\s\S]*?<\/form>/g)].map(m=>m[0]).find(f=>f.includes('name="operacao_id" value="'+op+'"'));return token(form||'','revisao');};
 const correction=async(c,changes={},delegated=false)=>c.req('/views/investimentos_operacoes.php?mercado=internacional'+(delegated?'&compartilhamento='+id:''),{mcf_csrf:c.csrf,mercado:'internacional',acao:'corrigir',operacao_id:purchase,revisao:await revision(c,purchase,delegated),ticker:'QACRYPTO',tipo_ativo:'cripto',tipo_operacao:'compra',quantidade:'0.15',valor_unitario:'40000.12345679',data:'2026-01-15',...(delegated?{mcf_contexto:id,mcf_contexto_versao:version()}:{}),...changes});
 const oldRevision=await revision(a,purchase);ok((await correction(a)).status===303,'correção preserva oito casas');ok(db('SELECT valor_unitario FROM investimentos_internacionais WHERE id='+purchase)==='40000.12345679','preço corrigido exato');
 ok((await correction(a,{revisao:oldRevision})).status===422,'versão antiga não sobrescreve');ok((await correction(a,{quantidade:'0.01'})).status===422,'correção não deixa venda sem posição');ok((await correction(b,{revisao:await revision(a,purchase)})).status===422,'ID alheio não pode ser corrigido');
 await grant('leitura');ok((await trade(b,{},true)).status===403,'leitor não registra cripto');
 r=await b.req('/views/visao_conjunta.php?modulo=investimentos');ok(r.status===200&&r.text.includes('QACRYPTO'),'visão conjunta autorizada inclui cripto');
 await grant('edicao',['cadastrar']);ok((await trade(b,{ticker:'QADELEGATE',quantidade:'0.01'},true)).status===302,'cadastro delegado');ok(db("SELECT usuario_id FROM investimentos_internacionais WHERE ticker='QADELEGATE'")==='1','cripto delegada pertence ao titular');ok((await correction(b,{},true)).status===403,'cadastrar não autoriza corrigir');
 await grant('edicao',['editar']);ok((await correction(b,{valor_unitario:'40000.12345680'},true)).status===303,'edição delegada de cripto');const deniedBefore=count();const denied=await trade(b,{},true);ok(denied.status===403||(denied.status===200&&denied.text.includes('Não foi possível registrar a operação.')),'cadastro não autorizado informa erro');ok(count()===deniedBefore,'editar não autoriza cadastrar no banco');
 ok(db("SELECT ator_id FROM historico_alteracoes WHERE tabela='investimentos_internacionais' AND registro='"+purchase+"' AND acao='editar' ORDER BY id DESC LIMIT 1")==='2','auditoria registra autor delegado');
 const sale=db("SELECT id FROM investimentos_internacionais WHERE ticker='QACRYPTO' AND tipo_operacao='venda'");
 await grant('edicao',['excluir']);ok((await correction(b,{acao:'excluir',operacao_id:sale,revisao:await revision(b,sale,true)},true)).status===303,'exclusão delegada de venda');
 await a.req('/views/compartilhamento.php');ok((await a.req('/views/compartilhamento.php',{mcf_csrf:a.csrf,acao:'revogar',id,versao:version()})).status===303,'revogar');ok((await b.req('/views/investimentos_internacionais.php?compartilhamento='+id)).status===404,'revogação impede acesso cripto');
 ok(db('SELECT * FROM investimentos_internacionais WHERE usuario_id=2 ORDER BY id')===betaBefore,'todas as operações preservam a conta do destinatário');
 console.log(`PASS: ${n} verificações HTTP/SQL de cripto`);fs.writeFileSync(path.join(process.env.MCF_TEST_OUTPUT,'cripto-result.json'),JSON.stringify({passed:n,date:new Date().toISOString()},null,2));
})().catch(e=>{console.error('FAIL cripto:',e.message,e.stack);process.exitCode=1});
