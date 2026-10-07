(() => {
  const popup = document.getElementById('mcf-avisos-popup');
  if (!popup || typeof popup.showModal !== 'function') return;
  document.getElementById('mcf-avisos-popup-fechar').addEventListener('click', () => popup.close());
  // O diálogo nativo mantém o foco dentro da janela e permite fechar com Escape.
  popup.showModal();
})();
