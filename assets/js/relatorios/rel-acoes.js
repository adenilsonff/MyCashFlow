document.addEventListener("DOMContentLoaded", () => {
    const dados = window.relatorioDados;
    const ctx = document.getElementById('graficoAcoes').getContext('2d');

    // Paleta de cores
    const cores = [
        'rgba(255, 99, 132, 0.6)',
        'rgba(54, 162, 235, 0.6)',
        'rgba(255, 206, 86, 0.6)',
        'rgba(75, 192, 192, 0.6)',
        'rgba(153, 102, 255, 0.6)',
        'rgba(255, 159, 64, 0.6)'
    ];
    const coresExpandida = dados.map((_, i) => cores[i % cores.length]);

    // função que cria gráfico conforme tipo
    function criarGrafico(tipo) {
        return new Chart(ctx, {
            type: tipo,
            data: {
                labels: dados.map(d => d.ticker),
                datasets: [{
                    label: 'Investido',
                    data: dados.map(d => d.investido),
                    backgroundColor: coresExpandida
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Investimento por Ação' }
                }
            }
        });
    }

    // cria gráfico inicial
    let chart = criarGrafico('bar');

    // troca dinâmica do tipo
    const selectTipo = document.getElementById('tipoGrafico');
    if (selectTipo) {
        selectTipo.addEventListener('change', e => {
            chart.destroy();
            chart = criarGrafico(e.target.value);
        });
    }
});
