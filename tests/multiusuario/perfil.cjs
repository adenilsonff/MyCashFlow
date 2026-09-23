const assert=require('assert/strict'),cp=require('child_process'),fs=require('fs'),path=require('path');
const {Client,base}=require('./client.cjs');
const db=s=>cp.execFileSync('C:/xampp/mysql/bin/mysql.exe',['-u','root','-N','-B','mcf_test_20260921'],{input:s,encoding:'utf8'}).trim();
let n=0;const ok=(v,m)=>{assert.ok(v,m);n++;};
const password='Teste-Conta-2026!',next='Nova-Senha-Teste-2026!';
const login=async(email,senha=password)=>{const c=new Client();await c.req('/views/login/login.php');const r=await c.req('/views/login/login.php',{email,senha,mcf_csrf:c.csrf});return [c,r];};
const post=(c,data)=>c.req('/views/perfil.php',{mcf_csrf:c.csrf,...data});
const dados=(extra={})=>({acao:'dados',nome:'Pessoa Alfa',email:'alfa@example.test',...extra});
(async()=>{
 const tables=db('SHOW TABLES').split(/\r?\n/).filter(t=>t!=='usuarios');
 const before=Object.fromEntries(tables.map(t=>[t,db('SELECT * FROM `'+t+'`')]));
 const betaBefore=db('SELECT * FROM usuarios WHERE id=2');
 const internalBefore=db('SELECT id,status_assinatura,data_expiracao,criado_em FROM usuarios WHERE id=1');
 const anon=new Client();
 ok((await anon.req('/views/perfil.php')).status===401,'perfil exige login');
 ok((await anon.req('/views/perfil.php',dados())).status===401,'POST exige login');
 const [a,ar]=await login('  ALFA@EXAMPLE.TEST  '),[other]=await login('alfa@example.test'),[b,br]=await login('beta@example.test');
 ok(ar.status===302&&br.status===302,'login normalizado e contas antigas sem nome');
 let r=await a.req('/views/perfil.php?usuario_id=2&id=2');
 ok(r.status===200&&r.text.includes('alfa@example.test')&&!r.text.includes('beta@example.test'),'GET ignora IDs de terceiros');
 ok(r.text.includes('value="" minlength="2"'),'nome antigo vazio');
 await b.req('/views/perfil.php');
 ok((await a.req('/views/perfil.php',dados())).status===403,'sem CSRF');
 ok((await post(a,dados({mcf_csrf:'invalido'}))).status===403,'CSRF incorreto');
 ok((await post(a,dados({mcf_csrf:b.csrf}))).status===403,'CSRF de outra conta');
 for(const nome of ['', 'A','x'.repeat(101),'<script>alert(1)</script>','Teste\u0000','\u200b\u200b'])ok((await post(a,dados({nome}))).status===422,'nome inválido rejeitado');
 for(const email of ['erro','a'.repeat(101)+'@test.com','BETA@EXAMPLE.TEST',' beta@example.test ']) {
   ok((await post(a,dados({nome:'NÃO SALVAR',email,senha_atual:password}))).status===422,'email inválido/duplicado');
   ok(db('SELECT nome IS NULL FROM usuarios WHERE id=1')==='1','sem alteração parcial');
 }
 ok((await post(a,dados({email:'nova@example.test'}))).status===422,'troca email exige senha');
 ok((await post(a,dados({email:'nova@example.test',senha_atual:'errada'}))).status===422,'senha errada não troca email');
 ok((await post(a,{acao:'dados','nome[]':'Ataque',email:'alfa@example.test'})).status===422,'campo malformado');
 r=await post(a,dados({nome:'Ana & "Bia"',usuario_id:2,id:2,admin:1,status_assinatura:'inativo',data_expiracao:'2000-01-01',senha:'injetada',email_normalizado:'beta@example.test'}));
 ok(r.status===303,'atualiza próprio nome com IDs adulterados');
 ok(db('SELECT * FROM usuarios WHERE id=2')===betaBefore,'outra conta intacta');
 ok(db('SELECT id,status_assinatura,data_expiracao,criado_em FROM usuarios WHERE id=1')===internalBefore,'campos internos intactos');
 r=await a.req('/views/perfil.php');ok(r.text.includes('Ana &amp; &quot;Bia&quot;'),'nome escapado no perfil');
 r=await other.req('/views/dashboard.php');ok(r.text.includes('Ana &amp; &quot;Bia&quot;'),'nome atualizado em outra sessão/dashboard');
 // Defesa de saída mesmo se um valor legado tiver vindo de importação administrativa.
 db("UPDATE usuarios SET nome='<img src=x onerror=alert(1)>' WHERE id=1");
 r=await a.req('/views/perfil.php');ok(!r.text.includes('<img src=x')&&r.text.includes('&lt;img src=x'),'HTML legado escapado');
 ok((await post(a,dados({nome:'Pessoa Compartilhada'}))).status===303,'restaura nome válido');
 ok((await post(b,{acao:'dados',nome:'Pessoa Compartilhada',email:'beta@example.test'})).status===303,'nomes repetidos permitidos');
 r=await post(a,dados({email:'  NOVA@EXAMPLE.TEST  ',senha_atual:password}));ok(r.status===303,'email atualizado');
 ok(db('SELECT email FROM usuarios WHERE id=1')==='nova@example.test','email armazenado normalizado');
 ok((await login('alfa@example.test'))[1].status!==302,'email antigo deixa de entrar');
 ok((await login('NOVA@example.test'))[1].status===302,'email novo entra');
 await a.req('/views/perfil.php');
 const oldHash=db('SELECT senha FROM usuarios WHERE id=1');
 for(const data of [{senha_atual:'errada',nova_senha:next,confirmar_senha:next},{senha_atual:password,nova_senha:next,confirmar_senha:'diverge'},{senha_atual:password,nova_senha:'curta',confirmar_senha:'curta'},{senha_atual:password,nova_senha:'x'.repeat(73),confirmar_senha:'x'.repeat(73)},{senha_atual:password,nova_senha:'senha-com-nulo\0a',confirmar_senha:'senha-com-nulo\0a'}])ok((await post(a,{acao:'senha',...data})).status===422,'troca de senha inválida');
 ok(db('SELECT senha FROM usuarios WHERE id=1')===oldHash,'hash intacto após erros');
 const previousCookie=a.cookie,previousCsrf=a.csrf;
 ok((await post(a,{acao:'senha',senha_atual:password,nova_senha:next,confirmar_senha:next,usuario_id:2})).status===303,'senha trocada');
 ok(a.cookie!==previousCookie,'ID da sessão renovado');
 r=await a.req('/views/perfil.php');ok(r.status===200&&a.csrf!==previousCsrf,'sessão mantida com CSRF renovado');
 ok((await post(a,dados({mcf_csrf:previousCsrf}))).status===403,'token anterior inválido');
 ok((await other.req('/views/perfil.php')).status===401,'outra sessão revogada');
 ok((await b.req('/views/perfil.php')).status===200,'sessão de outro usuário preservada');
 const stale=new Client();stale.cookie=previousCookie;ok((await stale.req('/views/perfil.php')).status===401,'cookie anterior inutilizável');
 ok((await login('nova@example.test',password))[1].status!==302,'senha antiga não entra');
 ok((await login('nova@example.test',next))[1].status===302,'senha nova entra');
 const fresh=new Client();await fresh.req('/views/login/register.php');
 const register=extra=>fresh.req('/views/login/register.php',{mcf_csrf:fresh.csrf,nome:'Pessoa Nova',email:'perfil-novo@example.test',senha:next,confirmar_senha:next,...extra});
 ok((await register({nome:''})).status===422,'cadastro exige nome');
 ok((await register({senha:'curta',confirmar_senha:'curta'})).status===422,'cadastro usa mesma regra senha');
 ok((await register({email:' NOVA@EXAMPLE.TEST '})).status===422,'cadastro rejeita duplicidade normalizada');
 ok((await register({usuario_id:1,id:1,status_assinatura:'inativo',data_expiracao:'2000-01-01'})).text.includes('Usuário cadastrado com sucesso'),'cadastro novo');
 const [newUser,newLogin]=await login('perfil-novo@example.test',next);ok(newLogin.status===302,'nova conta entra');
 ok((await newUser.req('/views/dashboard.php')).status===200,'nova conta acessa sistema');
 ok((await newUser.req('/views/perfil.php')).text.includes('Pessoa Nova'),'nova conta tem nome');
 for(const t of tables)ok(db('SELECT * FROM `'+t+'`')===before[t],'dados financeiros/análise preservados '+t);
 for(const [client,privateName,foreignName] of [[a,'ALFA_PRIVADO','BETA_PRIVADO'],[b,'BETA_PRIVADO','ALFA_PRIVADO']]) {
   for(const route of ['contas.php','relatorios/financeiro.php','analise.php']) {
     r=await client.req('/views/'+route);ok(r.status===200&&!r.text.includes(foreignName),'isolamento após perfil '+route);
   }
 }
 ok(fs.readFileSync(path.resolve(__dirname,'../../assets/js/analise-preferences.js'),'utf8').includes('user'),'preferências continuam por usuário');
 const method=await fetch(base+'/views/perfil.php',{method:'PUT',headers:{cookie:a.cookie}});ok(method.status===405,'PUT não altera perfil');
 console.log('PASS: '+n+' verificações de perfil, sessões e preservação');
 fs.writeFileSync(path.join(process.env.MCF_TEST_OUTPUT,'perfil-result.json'),JSON.stringify({passed:n},null,2));
})().catch(e=>{console.error('FAIL perfil: '+e.message);process.exitCode=1;});
