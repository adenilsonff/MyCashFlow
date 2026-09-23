(function (root) {
  'use strict';
  const day = time => typeof time === 'number'
    ? new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(time * 1000))
    : typeof time === 'string' ? time : `${time.year}-${String(time.month).padStart(2,'0')}-${String(time.day).padStart(2,'0')}`;
  function bucket(date, interval) {
    if (interval === '1mo') return date.slice(0,7);
    if (interval === '1wk') {
      const d = new Date(`${date}T12:00:00Z`);
      d.setUTCDate(d.getUTCDate() - (d.getUTCDay()+6)%7);
      return d.toISOString().slice(0,10);
    }
    return date;
  }
  function aggregate(bars, interval) {
    if (!['1wk','1mo'].includes(interval)) return bars.map(b => ({...b}));
    const groups = new Map();
    for (const b of bars) {
      const key = bucket(day(b.time),interval), prev = groups.get(key);
      if (!prev) groups.set(key,{...b});
      else { prev.high=Math.max(prev.high,b.high); prev.low=Math.min(prev.low,b.low); prev.close=b.close; prev.volume+=b.volume; }
    }
    return [...groups.values()];
  }
  function markers(operations, raw, displayed, interval) {
    // Marcações intradiárias representam a data, não um horário de execução conhecido.
    const actualDays=new Set(raw.map(b=>day(b.time))), times=new Map();
    for (const b of displayed) {
      const key=bucket(day(b.time),interval);
      if (!times.has(key)) times.set(key,b.time);
    }
    const groups=new Map();
    for(const op of operations) {
      if(!actualDays.has(op.data) || !['compra','venda'].includes(op.tipo_operacao)) continue;
      const time=times.get(bucket(op.data,interval));
      if(time===undefined) continue;
      const key=`${time}|${op.tipo_operacao}`;
      const g=groups.get(key) || {time,kind:op.tipo_operacao,quantity:0,count:0};
      g.quantity+=Math.abs(Number(op.quantidade));g.count++;groups.set(key,g);
    }
    return [...groups.values()].sort((a,b)=>a.time<b.time?-1:a.time>b.time?1:0).map(g=>({
      time:g.time,position:g.kind==='compra'?'belowBar':'aboveBar',color:g.kind==='compra'?'#0d8462':'#d04555',
      shape:g.kind==='compra'?'arrowUp':'arrowDown',text:`${g.kind==='compra'?'C':'V'} ${g.quantity}${g.count>1?` (${g.count})`:''}`
    }));
  }
  function difference(last, reference) {
    if (typeof last !== 'number' || typeof reference !== 'number' || !Number.isFinite(last) || !Number.isFinite(reference) || last <= 0 || reference <= 0) return null;
    const delta=last-reference;
    return {delta,percent:delta/reference*100};
  }
  function movingAverage(bars, period, type='sma') {
    if(!Number.isInteger(period)||period<1||period>500||!['sma','ema'].includes(type))return [];
    const result=[],window=[];let sum=0,previous=null;
    for(const bar of bars){
      if(typeof bar.close!=='number'||!Number.isFinite(bar.close)||bar.close<=0){window.length=0;sum=0;previous=null;result.push({time:bar.time});continue;}
      window.push(bar.close);sum+=bar.close;
      if(window.length>period)sum-=window.shift();
      if(window.length<period){result.push({time:bar.time});continue;}
      const value=type==='sma'||previous===null?sum/period:previous+(bar.close-previous)*(2/(period+1));
      previous=value;result.push({time:bar.time,value});
    }
    return result;
  }
  const api={day,bucket,aggregate,markers,difference,movingAverage};
  if(typeof module!=='undefined' && module.exports) module.exports=api;
  else root.AnaliseCore=api;
})(typeof globalThis!=='undefined'?globalThis:this);
