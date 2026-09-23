/* Preferências locais por conta. Nenhum preço ou conteúdo das notas é armazenado aqui. */
(() => {
  'use strict';
  window.AnalisePreferences = function(userId,onFailure) {
    const prefix=`mycashflow:analise:v1:${userId}:`,memory=new Map();
    function read(key){
      try{const raw=localStorage.getItem(prefix+key);if(raw!==null){const value=JSON.parse(raw);memory.set(key,value);return value;}}
      catch{/* Armazenamento indisponível ou valor inválido não impede o gráfico. */}
      return memory.get(key);
    }
    function write(key,value){
      memory.set(key,value);
      try{localStorage.setItem(prefix+key,JSON.stringify(value));}
      catch{onFailure();}
    }
    return {
      read,write,
      flag(id,name){return read(`mark:${id}:${name}`)===true;},
      toggle(id,name){write(`mark:${id}:${name}`,!this.flag(id,name));}
    };
  };
})();
