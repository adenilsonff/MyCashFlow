const path=require('path');
const assert=require('assert/strict'),fs=require('fs'),cp=require('child_process'),crypto=require('crypto');
const {Client,base}=require('./client.cjs');
const db=sql=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['-u','root','--batch','--skip-column-names','mcf_test_20260921'],{input:sql,encoding:'utf8',stdio:['pipe','pipe','pipe']}).trim();
let n=0;const ok=(v,label)=>{assert.ok(v,label);n++;};
const tables=['contas','rendas','compras','cartoes','corretoras','corretora_taxas','operacoes','ofx_importacoes','cartao_nomes_recorrentes','contas_financeiras','movimentacoes_financeiras','investimentos_nacionais','investimentos_internacionais','div_datacom','analise_acompanhamento','analise_marcacoes'];
const snapshot=uid=>crypto.createHash('sha256').update(tables.map(t=>db(`SELECT * FROM ${t} WHERE usuario_id=${uid}`)).join('\n')).digest('hex');
const routes=['contas.php','rendas.php','cartao.php','daytrade.php','saldos.php','dashboard.php','investimentos.php','investimentos_nacionais.php','investimentos_internacionais.php','dividendos.php','div_datacom.php','div_compra.php','div_valor.php','patrimonio.php','analise.php','analise/api.php?action=assets','relatorios/financeiro.php','relatorios/gastos.php','relatorios/receitas.php','relatorios/cartao.php','relatorios/daytrade.php','relatorios/investimentos.php','relatorios/proventos.php'];
async function page(c,p){const r=await c.req('/views/'+p);ok(r.status===200,'GET '+p);return r.text;}
function token(html,name){return html.match(new RegExp('name="'+name+'"\\s+value="([a-f0-9]+)"'))?.[1]||'';}
async function post(c,p,data){return c.req('/views/'+p,{mcf_csrf:c.csrf,...data});}
async function api(c,data){const r=await fetch(base+'/views/analise/api.php',{method:'POST',headers:{cookie:c.cookie,'Content-Type':'application/json','X-CSRF-Token':c.anToken},body:JSON.stringify(data)});return {status:r.status,data:await r.json()};}
(async()=>{
const anon=new Client(),a=new Client(),b=new Client();
for(const p of [...routes,'daytrade/listar_operacoes.php?corretora_id=201','daytrade/editar_operacao.php?id=201','daytrade/editar_corretora.php?id=201','daytrade/salvar_operacao.php','daytrade/salvar_corretora.php','daytrade/ajustar_operacao.php'])ok((await anon.req('/views/'+p)).status===401,'auth '+p);
ok((await a.login('alfa@example.test')).status===302,'login A');ok((await b.login('beta@example.test')).status===302,'login B');
for(const [c,own,other,id] of [[a,'ALFA_PRIVADO','BETA_PRIVADO',101],[b,'BETA_PRIVADO','ALFA_PRIVADO',201]]){
 for(const p of routes){const html=await page(c,p+(p.includes('?')?'&':'?')+'mes=9&ano=2026&usuario_id=999');ok(!html.includes(other),'isolamento '+p);ok(!/Fatal error|Warning:|Não foi possível concluir a operação/.test(html),'sem erro '+p);
  if(['contas.php','rendas.php','cartao.php','daytrade.php','saldos.php'].includes(p))ok(html.includes(own),'dados próprios '+p);
  if(p==='analise.php')c.anToken=html.match(/data-csrf="([a-f0-9]+)"/)[1];
  for(const form of html.matchAll(/<form\b[^>]*method="post"[^>]*>([\s\S]*?)<\/form>/gi))ok(form[1].includes('name="mcf_csrf"'),'CSRF no formulário '+p);
 }
 const report=await page(c,'relatorios/financeiro.php?ano=2026');const indicators=report.match(/<div class="indicators">([\s\S]*?)<\/div><\/div>/)?.[1]||'';ok(indicators.includes(id===101?'1.111,00':'2.222,00'),'total próprio financeiro');ok(!indicators.includes(id===101?'2.222,00':'1.111,00'),'total alheio ausente nos indicadores (eixos podem coincidir)');
 const state=JSON.parse((await c.req('/views/analise/api.php?action=state&ticker=PETR4&tipo_ativo=acao&usuario_id=999')).text);ok(JSON.stringify(state).includes(own),'Análise marcação própria');ok(!JSON.stringify(state).includes(other),'Análise marcação alheia');
}
console.log('Leituras, totais, formulários e autenticação: OK');
const before=snapshot(2);
for(const p of ['contas.php','rendas.php','div_datacom.php','daytrade/salvar_corretora.php','saldos.php','cartao.php'])ok((await a.req('/views/'+p,{id:201})).status===403,'CSRF ausente '+p);
ok((await a.req('/views/contas.php',{mcf_csrf:b.csrf,id:201,deletar_conta:1})).status===403,'CSRF de outra sessão');
await page(a,'contas.php');
for(const data of [{atualizar_paga:1,id:201,paga:1},{ajustar_valor:1,id:201,novo_valor:999,alcance:'proximos'},{deletar_conta:1,id:201,alcance:'proximos'}])await post(a,'contas.php',data);
for(const data of [{atualizar_recebido:1,id:201,recebido:1},{ajustar_valor:1,id:201,novo_valor:999,alcance:'proximos',classificacao:'extra'},{deletar_renda:1,id:201,alcance:'proximos'}])await post(a,'rendas.php',data);
const ch=await page(a,'cartao.php');const ct=token(ch,'csrf_cartao');ok(ct.length===64,'csrf cartão');
for(const data of [{atualizar_fatura_paga:1,mes:9,ano:2026},{atualizar_categoria_lote:1,'compras[]':201,categoria:'conjunta'},{atualizar_paga:1,id:201,paga:1},{ajustar_valor:1,id:201,novo_valor:999},{deletar_cartao:1,compra_id:201,mes:9,ano:2026}])await post(a,'cartao.php',{csrf_cartao:ct,...data});
for(const data of [{ajustar_valor:1,id:201,novo_valor:999},{deletar_div:1,id:201}])await post(a,'div_datacom.php',data);
const sh=await page(a,'saldos.php');const st=token(sh,'csrf');ok(st.length===64,'csrf saldos');
for(const data of [{acao:'nova_movimentacao',conta_id:201,tipo_movimentacao:'entrada',data:'2026-09-20',descricao:'ATAQUE',valor:30},{acao:'editar_movimentacao',movimentacao_id:201,tipo_movimentacao:'entrada',data:'2026-09-20',descricao:'ATAQUE',valor:999},{acao:'excluir_movimentacao',movimentacao_id:201},{acao:'inativar_conta',conta_id:201},{acao:'excluir_conta',conta_id:201},{acao:'transferir',conta_origem:101,conta_destino:201,data:'2026-09-20',descricao:'ATAQUE',valor:10}])await post(a,'saldos.php',{csrf:st,...data});
ok((await a.req('/views/daytrade/editar_corretora.php?id=201')).status===404,'corretora estrangeira');
const edit=await a.req('/views/daytrade/editar_operacao.php?id=201');ok(!edit.text.includes('BETA_PRIVADO'),'operação estrangeira');
const list=await a.req('/views/daytrade/listar_operacoes.php?corretora_id=201');ok(!list.text.includes('BETA_PRIVADO'),'listagem direta estrangeira');
for(const data of [{acao:'salvar_nome',corretora_id:201,nome_corretora:'ATAQUE'},{acao:'adicionar_taxa',corretora_id:201,nome_taxa:'ATAQUE',percentual:2},{acao:'editar_taxa',corretora_id:101,taxa_id:201,nome_taxa:'ATAQUE',percentual:3},{acao:'excluir_taxa',corretora_id:101,taxa_id:201}])await post(a,'daytrade/editar_corretora.php',data);
await post(a,'daytrade/salvar_operacao.php',{corretora_id:201,data:'2026-09-20',acao:'PETR4',quantidade:1,valor_compra:10,valor_venda:20});
await post(a,'daytrade/ajustar_operacao.php',{id:201,corretora_id:201,total_compra:999,total_venda:1000,taxas:1});
for(const action of ['save','delete']){const r=await api(a,{action,id:201,versao:1,ticker:'PETR4',tipo_ativo:'acao',tipo:'linha',titulo:'ATAQUE',preco:'50',cor:'#ff0000',texto:'',usuario_id:2});ok(r.status===409,'Análise ID alheio '+action);}
ok(snapshot(2)===before,'nenhum registro da conta B foi alterado pelos ataques');
console.log('Adulteração de IDs, CSRF, vínculos e efeitos no banco: OK');
// Recurrence group collisions must not affect another account.
await post(a,'contas.php',{ajustar_valor:1,id:101,novo_valor:112,alcance:'proximos'});ok(db('SELECT valor FROM contas WHERE id=101')==='112.00','editar conta própria');ok(db('SELECT valor FROM contas WHERE id=201')==='222.00','grupo recorrente isolado');
await post(a,'rendas.php',{ajustar_valor:1,id:101,novo_valor:1112,alcance:'proximos',classificacao:'extra'});ok(db('SELECT valor FROM rendas WHERE id=101')==='1112.00','editar renda própria');
for(const [c,uid] of [[a,1],[b,2]]){
 await page(c,'contas.php');await post(c,'contas.php',{nova_conta:1,nomeConta:'NOVA_'+uid,tipoConta:'unica',categoria:'pessoal',vencimento:'2026-09-21',valor:45,usuario_id:999});
 const id=db(`SELECT id FROM contas WHERE nome='NOVA_${uid}' AND usuario_id=${uid}`);ok(!!id,'criar conta pela sessão '+uid);await post(c,'contas.php',{deletar_conta:1,id,alcance:'somente'});ok(db(`SELECT COUNT(*) FROM contas WHERE id=${id}`)==='0','excluir própria '+uid);
 await post(c,'rendas.php',{nova_renda:1,nome:'NOVA_'+uid,descricao:'teste',tipoRenda:'unica',classificacao:'extra',data:'2026-09-21',valor:55,usuario_id:999});
 const ri=db(`SELECT id FROM rendas WHERE nome='NOVA_${uid}' AND usuario_id=${uid}`);ok(!!ri,'criar renda '+uid);await post(c,'rendas.php',{deletar_renda:1,id:ri,alcance:'somente'});ok(db(`SELECT COUNT(*) FROM rendas WHERE id=${ri}`)==='0','excluir renda própria '+uid);
 const r=await api(c,{action:'save',id:0,versao:0,ticker:'PETR4',tipo_ativo:'acao',tipo:'nota',titulo:'NOTA_'+uid,preco:null,cor:'#ff0000',texto:'Teste',usuario_id:999});ok(r.status===200,'salvar nota '+uid);
 const ni=db(`SELECT id FROM analise_marcacoes WHERE titulo='NOTA_${uid}' AND usuario_id=${uid}`);ok(!!ni,'nota proprietário sessão');ok((await api(c,{action:'delete',id:Number(ni),versao:1,ticker:'PETR4',tipo_ativo:'acao'})).status===200,'excluir nota própria');
}
// Database constraint is a second line of defense against cross-account relations.
let rejected=false;try{db("INSERT INTO corretora_taxas(usuario_id,corretora_id,nome_taxa,percentual) VALUES(1,201,'ATAQUE',1)");}catch{rejected=true;}ok(rejected,'FK composta bloqueia vínculo cruzado');
await page(a,'login/logout.php');const oldCookie=a.cookie;ok((await post(a,'login/logout.php',{})).status===302,'logout POST');a.cookie=oldCookie;ok((await a.req('/views/contas.php')).status===401,'sessão antiga invalidada');
console.log(`PASS: ${n} verificações de integração`);fs.writeFileSync(path.join(process.env.MCF_TEST_OUTPUT || __dirname,'integration-result.json'),JSON.stringify({passed:n,date:new Date().toISOString(),database:'mcf_test_20260921'},null,2));
})().catch(e=>{console.error('FAIL',e.message,e.stack);process.exitCode=1;});
