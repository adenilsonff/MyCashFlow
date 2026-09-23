const fs=require('fs');const base=process.env.MCF_TEST_URL || 'http://127.0.0.1:8097';
class Client {
 constructor(){this.cookie='';this.csrf='';}
 async req(url,data,headers={}){
  const r=await fetch(base+url,{redirect:'manual',signal:AbortSignal.timeout(90000),method:data?'POST':'GET',headers:{cookie:this.cookie,...(data?{'Content-Type':'application/x-www-form-urlencoded'}:{}),...headers},body:data?new URLSearchParams(data):undefined});
  if(r.headers.get('set-cookie'))this.cookie=r.headers.get('set-cookie').split(';')[0];
  const text=await r.text();const token=text.match(/name="mcf_csrf" value="([a-f0-9]+)"/);if(token)this.csrf=token[1];return {status:r.status,text,location:r.headers.get("location")};
 }
 async login(email){await this.req('/views/login/login.php');return this.req('/views/login/login.php',{email,senha:'Teste-Conta-2026!',mcf_csrf:this.csrf});}
}
module.exports={Client,base};

