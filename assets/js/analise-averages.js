(() => {
  'use strict';
  window.AnaliseAverages=function({chart,library,core,preferences,container,getBars}){
    const defaults=[{type:'ema',period:9,color:'#2563eb',visible:false},{type:'ema',period:21,color:'#9333ea',visible:false},{type:'sma',period:50,color:'#ea580c',visible:false}];
    const saved=preferences.read('moving-averages');
    const settings=defaults.map((fallback,i)=>{
      const value=Array.isArray(saved)?saved[i]:null;
      return value&&['sma','ema'].includes(value.type)&&Number.isInteger(value.period)&&value.period>=1&&value.period<=500&&/^#[0-9a-f]{6}$/i.test(value.color)&&typeof value.visible==='boolean'?{type:value.type,period:value.period,color:value.color,visible:value.visible}:{...fallback};
    });
    const rows=settings.map((config,i)=>{
      const row=document.createElement('div');row.className='an-ma-row';
      row.innerHTML=`<label><input type="checkbox" data-ma="visible"> Média ${i+1}</label><label>Tipo<select data-ma="type"><option value="sma">Simples (MMS)</option><option value="ema">Exponencial (MME)</option></select></label><label>Períodos<input data-ma="period" type="number" min="1" max="500" step="1" required></label><label>Cor<input data-ma="color" type="color"></label><output class="an-ma-status" aria-live="polite"></output>`;
      const controls=Object.fromEntries([...row.querySelectorAll('[data-ma]')].map(control=>[control.dataset.ma,control]));
      for(const name of ['type','period','color'])controls[name].value=config[name];controls.visible.checked=config.visible;
      const series=chart.addSeries(library.LineSeries,{color:config.color,lineWidth:2,priceLineVisible:false,lastValueVisible:false,crosshairMarkerVisible:false,visible:config.visible,priceScaleId:'right'},0);
      row.addEventListener('change',()=>{
        const period=Number(controls.period.value);
        if(!Number.isInteger(period)||period<1||period>500){controls.period.value=config.period;row.querySelector('output').textContent='Informe um período inteiro entre 1 e 500.';return;}
        Object.assign(config,{type:controls.type.value,period,color:controls.color.value,visible:controls.visible.checked});
        preferences.write('moving-averages',settings);update();
      });container.append(row);return {series,config,status:row.querySelector('output')};
    });
    function update(){
      const bars=getBars();
      for(const {series,config,status} of rows){
        series.applyOptions({color:config.color,visible:config.visible,title:`${config.type==='sma'?'MMS':'MME'} ${config.period}`});
        const data=config.visible?core.movingAverage(bars,config.period,config.type):[];
        series.setData(data);
        const last=data.at(-1)?.value;
        status.textContent=!config.visible?'Oculta':!bars.length?'Sem histórico exibido':last===undefined?`Histórico insuficiente: ${bars.length} de ${config.period} candles`:`${config.type==='sma'?'MMS':'MME'} ${config.period}: ${last.toLocaleString('pt-BR',{style:'currency',currency:'BRL',maximumFractionDigits:4})}`;
      }
    }
    update();return {update};
  };
})();
