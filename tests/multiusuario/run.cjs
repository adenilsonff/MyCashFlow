// Executa SOMENTE contra mcf_test_20260921. Esta base descartável será reinicializada.
const cp=require('child_process'),fs=require('fs'),path=require('path'),os=require('os');
const php='C:/xampp/php/php.exe',root=path.resolve(__dirname,'../..');
const temp=fs.mkdtempSync(path.join(os.tmpdir(),'mcf-multiusuario-'));
const port=8098,url='http://127.0.0.1:'+port;
const env={...process.env,MCF_DB_NAME:'mcf_test_20260921',MCF_SESSION_NAME:'MCFTEST',MCF_TEST_URL:url,MCF_TEST_OUTPUT:temp};
let server;
(async()=>{
  try {await fetch(url,{signal:AbortSignal.timeout(500)});throw new Error('Porta 8098 já ocupada; nenhum banco foi alterado.');} catch(e){if(e.message.startsWith('Porta'))throw e;}
  cp.execFileSync(process.execPath,[path.join(__dirname,'reset.cjs')],{env,stdio:'inherit'});
  server=cp.spawn(php,['-d','session.save_path='+temp,'-d','upload_tmp_dir='+temp,'-d','sys_temp_dir='+temp,'-S','127.0.0.1:'+port,'-t',root,path.join(__dirname,'router.php')],{env,windowsHide:true,stdio:'ignore'});
  let ready=false;
  for(let i=0;i<30;i++){try{const r=await fetch(url+'/views/login/login.php');if(r.status===200){ready=true;break;}}catch{}await new Promise(r=>setTimeout(r,100));}
  if(!ready)throw new Error('Servidor de testes indisponível.');
  for(const name of ['integration','extended','lifecycle','compartilhamento','compartilhamento-edicao','melhorias','cripto','dashboard-anual','perfil'])cp.execFileSync(process.execPath,[path.join(__dirname,name+'.cjs')],{env,stdio:'inherit'});
  cp.execFileSync(php,[path.join(root,'tests/visao-conjunta.test.php')],{env,stdio:'inherit'});
  cp.execFileSync(php,[path.join(root,'tests/analise.test.php')],{env,stdio:'inherit'});
  cp.execFileSync(process.execPath,[path.join(root,'tests/analise-core.test.cjs')],{env,stdio:'inherit'});
  cp.execFileSync(php,[path.join(root,'tests/comparativo.test.php')],{env,stdio:'inherit'});
  cp.execFileSync(php,[path.join(root,'tests/guardas.test.php')],{env,stdio:'inherit'});
  cp.execFileSync(php,[path.join(root,'tests/cripto.test.php')],{env,stdio:'inherit'});
  console.log('Resultados: '+temp);
} )().catch(e=>{console.error(e.message);process.exitCode=1;}).finally(()=>{if(server)server.kill();});
