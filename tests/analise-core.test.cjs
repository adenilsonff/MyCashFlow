const assert=require('node:assert/strict');
const C=require('../assets/js/analise-core.js');
const bars=[
 {time:'2026-08-28',open:10,high:15,low:9,close:12,volume:100},
 {time:'2026-08-31',open:12,high:16,low:11,close:14,volume:200},
 {time:'2026-09-01',open:14,high:18,low:13,close:17,volume:300},
 {time:'2026-09-08',open:18,high:20,low:16,close:19,volume:400}
];
const week=C.aggregate(bars,'1wk');
assert.equal(week.length,3);assert.deepEqual(week[1],{time:'2026-08-31',open:12,high:18,low:11,close:17,volume:500});
const month=C.aggregate(bars,'1mo');assert.equal(month.length,2);assert.equal(month[1].open,14);assert.equal(month[1].close,19);assert.equal(month[1].volume,700);
const ops=[{data:'2026-08-31',tipo_operacao:'compra',quantidade:5},{data:'2026-09-01',tipo_operacao:'compra',quantidade:7},{data:'2026-09-01',tipo_operacao:'venda',quantidade:-2},{data:'2026-09-07',tipo_operacao:'compra',quantidade:10}];
const marks=C.markers(ops,bars,week,'1wk');assert.equal(marks.length,2);assert.equal(marks[0].text,'C 12 (2)');assert.equal(marks[1].text,'V 2');
assert.equal(C.day(Date.parse('2026-09-01T01:00:00Z')/1000),'2026-08-31');
assert.equal(C.bucket('2027-01-01','1wk'),'2026-12-28');
assert.deepEqual(C.aggregate([],'1mo'),[]);
assert.equal(bars[1].close,14);
assert.deepEqual(C.difference(42,40),{delta:2,percent:5});
assert.deepEqual(C.difference(38,40),{delta:-2,percent:-5});
assert.deepEqual(C.difference(40,40),{delta:0,percent:0});
for(const [last,reference] of [[null,40],[42,0],[42,-1],[Infinity,40],[NaN,40],[42,null]])assert.equal(C.difference(last,reference),null);
console.log('PASS: aggregation, month/week boundaries, holidays, grouped operations, timezone, immutable source');
console.log('PASS: line distance above/below/equal and unavailable/invalid prices');
const assertMA=require('node:assert/strict');
const {movingAverage}=require('../assets/js/analise-core.js');
const maBars=[1,2,6,4,10].map((close,i)=>({time:i+1,close}));
assertMA.deepEqual(movingAverage(maBars,3,'sma'),[{time:1},{time:2},{time:3,value:3},{time:4,value:4},{time:5,value:20/3}]);
assertMA.deepEqual(movingAverage(maBars,3,'ema'),[{time:1},{time:2},{time:3,value:3},{time:4,value:3.5},{time:5,value:6.75}]);
assertMA.deepEqual(movingAverage(maBars,1,'ema').map(b=>b.value),[1,2,6,4,10]);
assertMA.ok(movingAverage(maBars,9).every(b=>!('value' in b)));
assertMA.deepEqual(movingAverage(maBars,0),[]);assertMA.deepEqual(movingAverage(maBars,2.5),[]);assertMA.deepEqual(movingAverage(maBars,501),[]);assertMA.deepEqual(movingAverage(maBars,3,'bad'),[]);
assertMA.deepEqual(movingAverage([{time:1,close:10},{time:2,close:NaN},{time:3,close:20},{time:4,close:30}],2,'ema'),[{time:1},{time:2},{time:3},{time:4,value:25}]);
assertMA.deepEqual(maBars.map(b=>b.close),[1,2,6,4,10]);
console.log('PASS: MMS rolling window, MME seeded with MMS, warmup, period one, invalid periods, gaps and immutable data');
