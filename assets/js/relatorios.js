'use strict';
document.querySelectorAll('[data-group]').forEach(link=>link.addEventListener('click',()=>{
  const detail=document.querySelector(link.getAttribute('href'));
  detail.open=true;
  detail.querySelectorAll('[data-row-group]').forEach(row=>{row.hidden=row.dataset.rowGroup!==link.dataset.group;});
}));
document.querySelectorAll('.reset-detail').forEach(button=>button.addEventListener('click',()=>{
  button.closest('.details').querySelectorAll('[data-row-group]').forEach(row=>{row.hidden=false;});
}));
const form=document.querySelector('.report-filters');
if(form){
  form.elements.moeda?.addEventListener('change',()=>{if(form.elements.tipo_ativo)form.elements.tipo_ativo.value='todos';});
  const update=()=>{const custom=form.elements.periodo.value==='personalizado';for(const name of ['inicio','fim']){form.elements[name].disabled=!custom;form.elements[name].required=custom;}};
  form.elements.periodo.addEventListener('change',update);update();
  form.addEventListener('submit',event=>{if(!form.querySelector('[name="pessoas[]"]:checked')){event.preventDefault();alert('Selecione ao menos um perfil.');}});
}
