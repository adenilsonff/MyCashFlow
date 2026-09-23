/* Handles stay in time/price space through the public chart API, never saved as pixels. */
'use strict';
window.AnaliseDrawing = function ({chart, getSeries, container, button, hint, capture, onCreate, onMove, onEdit, onError}) {
  const overlay=document.createElement('div');overlay.className='an-drawing-layer';container.append(overlay);
  const preview=document.createElement('output');preview.className='an-drag-price';preview.hidden=true;overlay.append(preview);
  let entries=[],drawing=false,enabled=false,drag=null,pending=0,frame=0,lastFrame=0;
  const defaultHint='Desenhe uma linha ou arraste o botão ↕ de uma marcação. Esc cancela.';
  const money=p=>p.toLocaleString('pt-BR',{style:'currency',currency:'BRL',maximumFractionDigits:4});
  const chartScale=()=>getSeries().priceScale();
  function controls() {
    button.disabled=!enabled||pending>0;
    button.setAttribute('aria-pressed',String(drawing));
    button.textContent=drawing?'Cancelar desenho':'Desenhar linha';
    overlay.classList.toggle('is-drawing',drawing&&enabled&&pending===0);
    overlay.classList.toggle('is-busy',pending>0);
    for(const entry of entries)entry.handle.disabled=pending>0;
  }
  function setDrawing(value){drawing=value&&enabled&&pending===0;hint.textContent=drawing?'Clique no gráfico de preços para posicionar uma linha amarela. Esc cancela.':defaultHint;controls();schedule();}
  function bounds(){return {width:chart.timeScale().width(),height:chart.panes()[0].getHeight()};}
  function local(event) {const rect=overlay.getBoundingClientRect();return {x:event.clientX-rect.left,y:event.clientY-rect.top};}
  function priceAt(y){const height=bounds().height;const value=getSeries().coordinateToPrice(Math.max(1,Math.min(height-1,y)));return Number.isFinite(value)&&value>0&&value<1e12?Number(value.toFixed(2)):null;}
  function sync(){
    const {width,height}=bounds();overlay.style.width=`${width}px`;overlay.style.height=`${height}px`;
    const occupied=[];
    for(const entry of entries){
      const price=drag?.entry===entry?drag.price:Number(entry.mark.preco),y=getSeries().priceToCoordinate(price);
      const visible=enabled&&y!==null&&Number.isFinite(y)&&y>=1&&y<=height-1;
      entry.handle.hidden=!visible;
      if(!visible)continue;
      const neighbours=occupied.filter(other=>Math.abs(other-y)<26).length;occupied.push(y);
      entry.handle.style.top=`${y}px`;entry.handle.style.left=`${8+neighbours*28}px`;
      entry.handle.title=`${entry.mark.titulo}: ${money(price)}. Arraste para ajustar; Enter abre a edição.`;
      if(drag?.entry===entry){preview.style.top=`${Math.max(4,Math.min(height-32,y-34))}px`;preview.textContent=money(price);}
    }
  }
  function schedule(){if(!frame&&(drawing||entries.length))frame=requestAnimationFrame(tick);}
  function tick(time){frame=0;if(!document.hidden&&time-lastFrame>=32){sync();lastFrame=time;}schedule();}
  function release(){
    if(!drag)return null;const previous=drag;drag=null;
    if(previous.entry.handle.hasPointerCapture(previous.pointerId))previous.entry.handle.releasePointerCapture(previous.pointerId);
    previous.scale.applyOptions({autoScale:previous.autoScale});preview.hidden=true;return previous;
  }
  function cancelDrag(){
    const previous=release();if(!previous)return;
    previous.entry.priceLine.applyOptions({price:Number(previous.entry.mark.preco)});
    hint.textContent='Movimento cancelado. O preço salvo foi mantido.';sync();
  }
  function begin(event,entry){
    if(!enabled||pending||drag||event.button!==0)return;
    event.preventDefault();event.stopPropagation();setDrawing(false);
    const p=local(event),scale=chartScale();
    drag={entry,pointerId:event.pointerId,price:Number(entry.mark.preco),startY:p.y,offsetY:p.y-getSeries().priceToCoordinate(Number(entry.mark.preco)),moved:false,scale,autoScale:scale.options().autoScale,context:capture()};
    scale.applyOptions({autoScale:false});entry.handle.setPointerCapture(event.pointerId);
    preview.hidden=false;hint.textContent='Arraste até o preço desejado. Solte para salvar ou pressione Esc para cancelar.';sync();
  }
  function move(event){
    if(!drag||event.pointerId!==drag.pointerId)return;
    event.preventDefault();const p=local(event);
    if(Math.abs(p.y-drag.startY)<3&&!drag.moved)return;
    const price=priceAt(p.y-drag.offsetY);if(price===null||price<=0)return;
    drag.moved=true;drag.price=price;drag.entry.priceLine.applyOptions({price});sync();
  }
  async function finish(event){
    if(!drag||event.pointerId!==drag.pointerId)return;
    event.preventDefault();const previous=release();
    if(!previous.moved||previous.price===Number(previous.entry.mark.preco)){hint.textContent=defaultHint;return;}
    pending++;controls();hint.textContent='Salvando o novo preço…';
    try{await onMove({...previous.entry.mark},previous.price,previous.context);hint.textContent=defaultHint;}
    catch(error){if(entries.includes(previous.entry))previous.entry.priceLine.applyOptions({price:Number(previous.entry.mark.preco)});onError(error);hint.textContent='Não foi possível salvar. Confira o preço na lista antes de tentar novamente.';}
    finally{pending--;controls();sync();}
  }
  overlay.addEventListener('click',event=>{
    if(!drawing||!enabled||pending||event.target.closest('button'))return;
    const {x,y}=local(event),{width,height}=bounds();if(x<0||x>width||y<0||y>height)return;
    const price=priceAt(y);if(price===null||price<=0)return;setDrawing(false);onCreate(price);
  });
  button.addEventListener('click',()=>setDrawing(!drawing));
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&(drawing||drag)){event.preventDefault();cancelDrag();setDrawing(false);}});
  window.addEventListener('blur',cancelDrag);
  // Também redimensiona quando não há alças (linhas ocultas ou bloqueadas).
  new ResizeObserver(()=>sync()).observe(container);
  return {
    reset(){cancelDrag();setDrawing(false);enabled=false;for(const entry of entries)entry.handle.remove();entries=[];if(frame)cancelAnimationFrame(frame);frame=0;preview.hidden=true;controls();},
    setLines(lines){
      enabled=true;
      for(const [series,priceLine,mark] of lines){
        if(!mark.id||mark.locked)continue; // Linhas bloqueadas e o preço médio não têm alça de arraste.
        const handle=document.createElement('button');handle.type='button';handle.className='an-line-handle';handle.textContent='↕';handle.style.setProperty('--line-color',mark.cor);
        handle.dataset.markId=mark.id;handle.setAttribute('aria-label',`Mover linha ${mark.titulo}`);
        const entry={handle,priceLine,mark:{...mark}};entries.push(entry);overlay.append(handle);
        handle.addEventListener('pointerdown',event=>begin(event,entry));handle.addEventListener('pointermove',move);handle.addEventListener('pointerup',finish);
        handle.addEventListener('pointercancel',cancelDrag);handle.addEventListener('lostpointercapture',()=>{if(drag?.entry===entry)cancelDrag();});
        handle.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();if(!pending)onEdit(entry.mark);}});
      }
      controls();sync();schedule();
    }
  };
};
