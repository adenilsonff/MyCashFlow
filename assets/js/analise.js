(() => {
  'use strict';
  const $=id=>document.getElementById(id), app=$('analise-app'), C=window.AnaliseCore, L=window.LightweightCharts;
  const money=v=>v==null?'—':Number(v).toLocaleString('pt-BR',{style:'currency',currency:'BRL',maximumFractionDigits:4});
  const types={acao:'Ação',fii:'FII',etf:'ETF',bdr:'BDR'};
  const state={assets:[],asset:null,operations:[],annotations:[],raw:[],bars:[],history:null,request:0,controller:null,lines:[],blocked:new Set(),editor:null,saving:false};
  let chart, candle, line, volume, markerApi, lineTools, averages;
  const key=a=>a?`${a.ticker}|${a.tipo_ativo}`:'';
  const feedback=(text,error=false)=>{ $('an-feedback').textContent=text; $('an-feedback').hidden=!text; $('an-feedback').classList.toggle('is-error',error); };
  if(!window.AnalisePreferences){feedback('Não foi possível carregar as preferências do gráfico. Recarregue a página.',true);return;}
  const preferences=window.AnalisePreferences(app.dataset.user,()=>{$('an-preferences-status').textContent='O navegador não permitiu salvar as preferências. Elas serão mantidas apenas nesta página.';});
  const preferenceControls=['an-interval','an-range','an-style','an-show-average','an-show-operations','an-show-marks'];
  function restorePreferences(){
    for(const id of preferenceControls){
      const control=$(id),value=preferences.read(id);
      if(control.type==='checkbox'){if(typeof value==='boolean')control.checked=value;}
      else if(typeof value==='string'&&Array.from(control.options).some(option=>option.value===value))control.value=value;
    }
    if(!['1d','1wk','1mo'].includes($('an-interval').value))$('an-range').value='5d';
  }
  restorePreferences();
  for(const id of preferenceControls)$(id).addEventListener('change',()=>preferences.write(id,$(id).type==='checkbox'?$(id).checked:$(id).value));
  function el(tag,text,cls) { const e=document.createElement(tag); if(text!==undefined)e.textContent=text; if(cls)e.className=cls;return e; }
  async function api(action, data={}, signal) {
    const write=['watch','unwatch','save','delete'].includes(action), values={action,...data};
    let response;
    const params=new URLSearchParams(write?{}:values);
    if(Number(app.dataset.compartilhamento)>0) params.set('compartilhamento',app.dataset.compartilhamento);
    try{response=await fetch('analise/api.php'+(params.size?'?'+params:''),{
      method:write?'POST':'GET',credentials:'same-origin',cache:'no-store',signal,
      headers:write?{'Content-Type':'application/json','X-CSRF-Token':app.dataset.csrf,'X-MCF-Context-Version':app.dataset.contextVersion || '0'}:{},
      body:write?JSON.stringify(values):undefined
    });}catch(error){if(error.name==='AbortError')throw error;throw new Error('Não foi possível conectar ao servidor. Confira sua conexão e tente novamente.');}
    let result;try{result=await response.json();}catch{throw new Error('Resposta inválida do servidor. Tente novamente.');}
    if(!response.ok)throw new Error(result.message || 'Não foi possível concluir a solicitação.');
    return result;
  }
  function selectedData() {return {ticker:state.asset.ticker,tipo_ativo:state.asset.tipo_ativo};}
  function renderAssets() {
    const list=$('an-assets-list');list.replaceChildren();
    const term=$('an-search').value.toUpperCase().trim(),filter=$('an-filter').value;
    const assets=state.assets.filter(a=>a.ticker.includes(term)&&(!filter||a.tipo_ativo===filter));
    for(const a of assets) {
      const button=el('button',undefined,'an-asset'+(key(a)===key(state.asset)?' is-active':''));button.type='button';
      button.setAttribute('aria-pressed',String(key(a)===key(state.asset)));
      const top=el('span',undefined,'an-asset-top');top.append(el('strong',a.ticker),el('span',types[a.tipo_ativo]));
      button.append(top,el('small',a.carteira?`${a.quantidade} un. · PM ${money(a.preco_medio)}`:a.erro?'Revisar histórico':a.acompanhando?'Em acompanhamento':'Posição encerrada'));
      button.addEventListener('click',()=>select(a));list.append(button);
    }
    if(!assets.length)list.append(el('p',state.assets.length?'Nenhum ativo corresponde ao filtro.':'Sua lista está vazia. Adicione um ativo abaixo.','an-muted'));
  }
  async function reloadAssets(preferred) {
    const data=await api('assets');if(!data.ok)throw new Error(data.message);
    state.assets=data.assets;
    const asset=state.assets.find(a=>key(a)===preferred)||state.assets[0];
    renderAssets();
    if(asset)await select(asset);
    else {state.asset=null;state.operations=[];state.annotations=[];state.request++;state.controller?.abort();clearChart('Adicione um ativo para começar.');renderPrivate();$('an-symbol').textContent='Sua lista está vazia';$('an-category').textContent='ACOMPANHAMENTO';$('an-average').textContent='—';$('an-quantity').textContent='—';$('an-unwatch').hidden=true;}
  }
  function clearChart(message) {
    state.raw=[];state.bars=[];state.history=null;averages?.update();
    if(chart){candle.setData([]);line.setData([]);volume.setData([]);markerApi.setMarkers([]);clearLines();}
    $('an-close').textContent='—';$('an-chart-empty').textContent=message;$('an-chart-empty').hidden=false;
    $('an-source').textContent='BRAPI · sem histórico exibido.';
    $('an-ohlc').textContent='Selecione um candle para consultar seus preços.';
    updateDistances();
  }
  function clearLines(){lineTools?.reset();for(const [series,priceLine] of state.lines)series.removePriceLine(priceLine);state.lines=[];}
  function drawLines(){
    if(!chart)return;clearLines();
    if(!state.bars.length)return;
    const series=$('an-style').value==='candles'?candle:line;
    const marks=$('an-show-marks').checked?state.annotations.filter(m=>m.tipo==='linha'&&!preferences.flag(m.id,'hidden')).map(m=>({...m,locked:preferences.flag(m.id,'locked')})):[];
    if($('an-show-average').checked && state.asset?.preco_medio>0)marks.unshift({preco:state.asset.preco_medio,cor:'#c27803',titulo:'PM atual'});
    for(const m of marks)state.lines.push([series,series.createPriceLine({price:Number(m.preco),color:m.cor,lineWidth:2,lineStyle:L.LineStyle.Dashed,axisLabelVisible:true,title:m.titulo}),m]);
    series.applyOptions({autoscaleInfoProvider:original=>{
      const info=original();if(!info)return info;
      const prices=marks.map(m=>Number(m.preco)).filter(Number.isFinite);
      return {...info,priceRange:{minValue:Math.min(info.priceRange.minValue,...prices),maxValue:Math.max(info.priceRange.maxValue,...prices)}};
    }});
    lineTools?.setLines(state.lines);
  }
  function updateDistances(){
    const close=state.raw.length?state.raw.at(-1).close:null;
    for(const node of document.querySelectorAll('[data-line-distance]')){
      const mark=state.annotations.find(m=>String(m.id)===node.dataset.lineDistance);
      const distance=C.difference(close,mark?Number(mark.preco):null);
      node.className='an-line-distance';
      if(!distance){node.textContent='Diferença indisponível sem histórico de preços.';node.removeAttribute('title');continue;}
      const direction=distance.delta>0?'acima':distance.delta<0?'abaixo':'no preço';
      const percent=Math.abs(distance.percent).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2});
      node.textContent=distance.delta===0?'Último preço exibido igual à linha (0,00%).':`${money(Math.abs(distance.delta))} ${direction} da linha (${distance.delta>0?'+':'−'}${percent}%)`;
      node.classList.add(distance.delta>0?'is-above':distance.delta<0?'is-below':'is-equal');
      node.title=`Comparação com o último fechamento exibido: ${money(close)}. Não é uma cotação em tempo real.`;
    }
  }
  async function saveDragged(mark,price,context){
    if(preferences.flag(mark.id,'locked'))throw new Error('Esta linha está bloqueada para arraste. Desbloqueie-a na lista.');
    if(state.saving)throw new Error('Aguarde o salvamento em andamento.');
    state.saving=true;
    try{
      const r=await api('save',{...mark,...context.target,preco:price.toFixed(2),id:Number(mark.id),versao:Number(mark.versao)});
      if(!r.ok)throw new Error(r.message);
      if(context.key===key(state.asset))await refreshPrivate();
      feedback(`Linha de ${context.target.ticker} salva em ${money(price)}.`);
    }catch(error){
      // Em conflito de versão, recupera o valor real sem sobrescrever outra aba.
      if(context.key===key(state.asset)){try{await refreshPrivate();}catch{drawLines();}}
      throw error;
    }finally{state.saving=false;}
  }
  function renderChart(fit=true) {
    if(!chart||!state.raw.length)return;
    const interval=$('an-interval').value;
    state.bars=C.aggregate(state.raw,interval);
    averages?.update();
    candle.setData(state.bars.map(({volume,...b})=>b));
    line.setData(state.bars.map(b=>({time:b.time,value:b.close})));
    volume.setData(state.bars.map(b=>({time:b.time,value:b.volume,color:b.close>=b.open?'#a3d5c5':'#e9b2b8'})));
    candle.applyOptions({visible:$('an-style').value==='candles'});line.applyOptions({visible:$('an-style').value==='line'});
    markerApi.detach();markerApi=L.createSeriesMarkers($('an-style').value==='candles'?candle:line,
      $('an-show-operations').checked?C.markers(state.operations,state.raw,state.bars,interval):[]);
    const intraday=!['1d','1wk','1mo'].includes(interval);
    chart.timeScale().applyOptions({timeVisible:intraday,secondsVisible:false});
    drawLines();if(fit)chart.timeScale().fitContent();
    $('an-close').textContent=money(state.bars.at(-1).close);$('an-chart-empty').hidden=true;
    const h=state.history, date=t=>C.day(t).split('-').reverse().join('/');
    $('an-source').textContent=`BRAPI · ${date(state.raw[0].time)} a ${date(state.raw.at(-1).time)} · ${state.bars.length} candles · consulta ${new Date(h.fetched_at*1000).toLocaleString('pt-BR')}${h.stale?' · atualização pendente; exibindo cache':''}${h.discarded?' · registros inválidos descartados':''}. Sem garantia de tempo real; último candle pode estar em formação.`;
    $('an-operations-help').textContent=intraday?'Marcações indicam a data, sem horário de execução. A tabela mantém o preço original.':'C = compra · V = venda. Só são marcadas operações com pregão disponível no gráfico.';
    updateDistances();
  }
  function renderPrivate() {
    const body=$('an-operations');body.replaceChildren();
    for(const o of [...state.operations].reverse()) {
      const tr=el('tr');tr.append(el('td',o.data.split('-').reverse().join('/')),el('td',o.tipo_operacao==='compra'?'Compra':'Venda'),el('td',Math.abs(Number(o.quantidade))),el('td',money(o.valor_unitario)));body.append(tr);
    }
    if(!state.operations.length){const tr=el('tr'),td=el('td','Sem operações registradas para este ativo.');td.colSpan=4;tr.append(td);body.append(tr);}
    const list=$('an-annotations');list.replaceChildren();
    for(const m of state.annotations) {
      const item=el('div',undefined,'an-mark');item.style.borderLeftColor=m.cor;
      item.append(el('strong',m.titulo),el('small',m.tipo==='linha'?`Linha em ${money(m.preco)}`:'Anotação'));
      if(m.tipo==='linha'){
        const distance=el('p');distance.dataset.lineDistance=m.id;item.append(distance);
        const status=el('small',`${preferences.flag(m.id,'locked')?'Bloqueada para arraste':'Arraste liberado'} · ${!$('an-show-marks').checked||preferences.flag(m.id,'hidden')?'Oculta no gráfico':'Visível no gráfico'}`,'an-mark-status');item.append(status);
      }
      if(m.texto)item.append(el('p',m.texto));
      const actions=el('div',undefined,'an-mark-actions'),edit=el('button','Editar'),remove=el('button','Excluir');
      edit.type=remove.type='button';edit.addEventListener('click',()=>openEditor(m));
      remove.addEventListener('click',async()=>{
        if(state.saving)return;
        if(!confirm(`Excluir a marcação “${m.titulo}”?`))return;
        const target=selectedData(),targetKey=key(state.asset);remove.disabled=true;
        try{const r=await api('delete',{...target,id:Number(m.id),versao:Number(m.versao)});if(!r.ok)throw new Error(r.message);if(targetKey===key(state.asset))await refreshPrivate();feedback('Marcação excluída.');}
        catch(e){feedback(e.message,true);remove.disabled=false;}
      });actions.append(edit,remove);
      if(m.tipo==='linha'){
        for(const [flag,active,inactive] of [['locked','Desbloquear','Bloquear'],['hidden','Mostrar','Ocultar']]){
          const control=el('button',preferences.flag(m.id,flag)?active:inactive);control.type='button';
          control.dataset.markToggle=flag;control.setAttribute('aria-pressed',String(preferences.flag(m.id,flag)));
          control.title=flag==='locked'?'Impedir o arraste acidental; a edição pelo formulário continua disponível.':'Ocultar apenas esta linha, sem apagar a marcação.';
          control.addEventListener('click',()=>{if(state.saving)return;preferences.toggle(m.id,flag);renderPrivate();drawLines();});actions.append(control);
        }
      }
      item.append(actions);list.append(item);
    }
    if(!state.annotations.length)list.append(el('p','Nenhuma marcação neste ativo. Crie uma linha de preço ou uma nota.','an-muted'));
    $('an-new').disabled=!state.asset;
    updateDistances();
  }
  async function refreshPrivate(){
    const target=key(state.asset),r=await api('state',selectedData());
    if(target!==key(state.asset))return;
    state.operations=r.operations;state.annotations=r.annotations;updatePosition(r.position);renderPrivate();renderChart(false);
  }
  function updatePosition(position){
    if(!position||!state.asset)return;Object.assign(state.asset,position);
    $('an-average').textContent=money(position.preco_medio);$('an-quantity').textContent=Number(position.quantidade).toLocaleString('pt-BR');
    $('an-category').textContent=types[state.asset.tipo_ativo]+(position.carteira?' · EM CARTEIRA':' · ACOMPANHAMENTO');
    if(position.erro)feedback(position.erro,true);renderAssets();
  }
  function updateIntervals(){
    for(const option of $('an-interval').options){const disabled=state.blocked.has(`${state.asset?.ticker}|${option.value}`);option.disabled=disabled;if(disabled)option.textContent=option.textContent.replace('consultar acesso','indisponível');else option.textContent=option.textContent.replace('indisponível','consultar acesso');}
  }
  async function select(asset) {
    state.asset=asset;state.operations=[];state.annotations=[];renderPrivate();renderAssets();updateIntervals();
    if($('an-interval').selectedOptions[0].disabled)$('an-interval').value='1d';
    $('an-symbol').textContent=asset.ticker;$('an-category').textContent=types[asset.tipo_ativo]+(asset.carteira?' · EM CARTEIRA':' · ACOMPANHAMENTO');
    $('an-average').textContent=money(asset.preco_medio);$('an-quantity').textContent=Number(asset.quantidade).toLocaleString('pt-BR');$('an-unwatch').hidden=!asset.acompanhando;
    await load();
  }
  async function load() {
    if(!state.asset)return;
    const request=++state.request;state.controller?.abort();state.controller=new AbortController();
    const target=selectedData(),interval=$('an-interval').value, providerInterval=['1wk','1mo'].includes(interval)?'1d':interval;
    clearChart('Carregando histórico…');feedback(state.asset.erro||'',!!state.asset.erro);
    $('an-refresh').disabled=true;
    try {
      // Primeiro os dados privados locais, depois a única consulta ao provedor.
      const privateData=await api('state',target,state.controller.signal);
      if(request!==state.request)return;
      state.operations=privateData.operations;state.annotations=privateData.annotations;updatePosition(privateData.position);renderPrivate();
      const h=await api('history',{...target,range:$('an-range').value,interval:providerInterval},state.controller.signal);
      if(request!==state.request)return;
      if(!h.ok){if(h.code==='plan' && providerInterval!=='1d'){state.blocked.add(`${target.ticker}|${interval}`);updateIntervals();}clearChart(h.message);return;}
      state.history=h;state.raw=h.bars;renderChart();
    }catch(e){if(e.name!=='AbortError'&&request===state.request){clearChart(e.message);feedback(e.message,true);}}
    finally{if(request===state.request)$('an-refresh').disabled=false;}
  }
  function openEditor(m,initialPrice){
    if(!state.asset || state.saving)return;
    state.editor=selectedData();const form=$('an-mark-form');form.reset();
    const values=m||{id:0,versao:0,tipo:'linha',titulo:initialPrice?'Minha compra':'',preco:initialPrice?initialPrice.toFixed(2):'',cor:'#eab308',texto:''};
    for(const name of ['id','versao','tipo','titulo','preco','cor','texto'])form.elements[name].value=values[name]??'';
    $('an-dialog-title').textContent=m?'Editar marcação':'Nova marcação';$('an-mark-target').textContent=state.asset.ticker;
    $('an-form-error').textContent='';kindChanged();$('an-dialog').showModal();$('an-title').focus();
  }
  function kindChanged(){const note=$('an-kind').value==='nota';$('an-price-field').hidden=note;$('an-price').required=!note;$('an-note').required=note;}
  $('an-kind').addEventListener('change',kindChanged);$('an-new').addEventListener('click',()=>openEditor());$('an-cancel').addEventListener('click',()=>{if(!state.saving)$('an-dialog').close();});
  $('an-dialog').addEventListener('cancel',event=>{if(state.saving)event.preventDefault();});
  $('an-mark-form').addEventListener('submit',async event=>{
    event.preventDefault();if(state.saving)return;const form=event.currentTarget,button=form.querySelector('[type=submit]'),target={...state.editor};button.disabled=true;state.saving=true;
    try{const data=Object.fromEntries(new FormData(form)),r=await api('save',{...data,...target,id:Number(data.id),versao:Number(data.versao)});if(!r.ok)throw new Error(r.message);
      $('an-dialog').close();if(key(target)===key(state.asset))await refreshPrivate();feedback('Marcação salva na sua conta.');
    }catch(e){$('an-form-error').textContent=e.message;}finally{button.disabled=false;state.saving=false;}
  });
  $('an-watch-form').addEventListener('submit',async event=>{
    event.preventDefault();const form=event.currentTarget,button=form.querySelector('button');button.disabled=true;
    try{const data=Object.fromEntries(new FormData(form));data.ticker=data.ticker.toUpperCase().trim();const r=await api('watch',data);if(!r.ok)throw new Error(r.message);$('an-search').value='';$('an-filter').value='';await reloadAssets(key(data));form.reset();feedback('Ativo adicionado à sua lista.');}
    catch(e){feedback(e.message,true);}finally{button.disabled=false;}
  });
  $('an-unwatch').addEventListener('click',async()=>{if(!state.asset)return;const target=selectedData();$('an-unwatch').disabled=true;try{await api('unwatch',target);await reloadAssets(key(target));feedback('Ativo retirado do acompanhamento. Operações e marcações foram preservadas.');}catch(e){feedback(e.message,true);}finally{$('an-unwatch').disabled=false;}});
  $('an-search').addEventListener('input',renderAssets);$('an-filter').addEventListener('change',renderAssets);
  $('an-interval').addEventListener('change',()=>{if(!['1d','1wk','1mo'].includes($('an-interval').value)){$('an-range').value='5d';preferences.write('an-range','5d');}load();});
  $('an-range').addEventListener('change',load);$('an-style').addEventListener('change',()=>renderChart(false));
  $('an-show-average').addEventListener('change',drawLines);$('an-show-operations').addEventListener('change',()=>renderChart(false));
  $('an-show-marks').addEventListener('change',()=>{renderPrivate();drawLines();});
  $('an-fit').addEventListener('click',()=>chart?.timeScale().fitContent());$('an-refresh').addEventListener('click',load);
  if(!L||!window.AnaliseDrawing||!window.AnaliseAverages){feedback('Não foi possível carregar a biblioteca de gráficos. Recarregue a página.',true);return;}
  chart=L.createChart($('an-chart'),{autoSize:true,layout:{background:{type:'solid',color:'#ffffff'},textColor:'#506071',fontFamily:'Arial, sans-serif',attributionLogo:true},
    grid:{vertLines:{color:'#f0f3f6'},horzLines:{color:'#f0f3f6'}},rightPriceScale:{borderColor:'#e5eaf0'},timeScale:{borderColor:'#e5eaf0',tickMarkFormatter:(time,type)=>typeof time==='number'&&type>=3?new Date(time*1000).toLocaleTimeString('pt-BR',{timeZone:'America/Sao_Paulo',hour:'2-digit',minute:'2-digit'}):null},
    localization:{locale:'pt-BR',timeFormatter:t=>typeof t==='number'?new Date(t*1000).toLocaleString('pt-BR',{timeZone:'America/Sao_Paulo'}):C.day(t).split('-').reverse().join('/')}});
  candle=chart.addSeries(L.CandlestickSeries,{upColor:'#159677',downColor:'#d95765',borderVisible:false,wickUpColor:'#159677',wickDownColor:'#d95765'});
  line=chart.addSeries(L.LineSeries,{color:'#3865ca',lineWidth:2,visible:false});
  volume=chart.addSeries(L.HistogramSeries,{priceFormat:{type:'volume'},priceScaleId:'',lastValueVisible:false,priceLineVisible:false},1);
  chart.panes()[1].setHeight(95);markerApi=L.createSeriesMarkers(candle,[]);
  lineTools=window.AnaliseDrawing({chart,getSeries:()=>$('an-style').value==='candles'?candle:line,container:document.querySelector('.an-chart-wrap'),button:$('an-draw-line'),hint:$('an-drawing-hint'),
    capture:()=>({target:selectedData(),key:key(state.asset)}),onCreate:price=>openEditor(null,price),onMove:saveDragged,onEdit:openEditor,onError:error=>feedback(error.message,true)});
  lineTools.reset();
  averages=window.AnaliseAverages({chart,library:L,core:C,preferences,container:$('an-averages'),getBars:()=>state.bars});
  chart.subscribeCrosshairMove(param=>{if(!param.time)return;const b=state.bars.find(b=>String(b.time)===String(param.time)||C.day(b.time)===C.day(param.time)&&typeof b.time!=='number');if(b){const date=typeof b.time==='number'?new Date(b.time*1000).toLocaleString('pt-BR',{timeZone:'America/Sao_Paulo'}):C.day(b.time).split('-').reverse().join('/');$('an-ohlc').textContent=`${date} · A ${money(b.open)} · Máx ${money(b.high)} · Mín ${money(b.low)} · F ${money(b.close)} · Vol ${b.volume.toLocaleString('pt-BR')}`;}});
  reloadAssets().catch(e=>{feedback(e.message,true);$('an-assets-list').textContent='Não foi possível carregar seus ativos.';});
})();
