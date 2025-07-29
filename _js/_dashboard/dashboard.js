// File: /_js/_dashboard/dashboard.js
// Script de interatividade e gráficos do Dashboard

document.addEventListener('DOMContentLoaded', function() {
  // 1) Filtro de vendas por mês e ano
  const filterForm = document.getElementById('filter-form');
  if (filterForm) {
    filterForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(filterForm);
      const month = formData.get('month');
      const year  = formData.get('year');
      // Atualiza a URL com parâmetros de filtro
      const params = new URLSearchParams(window.location.search);
      params.set('month', month);
      params.set('year',  year);
      window.location.search = params.toString();
    });
  }

  // 2) Tornar as tabelas responsivas (scroll horizontal em mobile)
  const tableGroups = document.querySelectorAll('.dashboard-analytics .table-responsive');
  tableGroups.forEach(wrapper => {
    // já vem com div.table-responsive no HTML; nada a fazer
  });

  // 3) Inicialização dos gráficos (Chart.js)
  // Busca as variáveis vindas do PHP
  const clientsLabels = JSON.parse(document.getElementById('chart-data').dataset.clientsLabels);
  const clientsData   = JSON.parse(document.getElementById('chart-data').dataset.clientsData);
  const salesLabels   = JSON.parse(document.getElementById('chart-data').dataset.salesLabels);
  const salesData     = JSON.parse(document.getElementById('chart-data').dataset.salesData);
  const parcelsCount  = parseInt(document.getElementById('chart-data').dataset.parcelsCount, 10);

  // Lê as cores definidas no CSS
  const rootStyles   = getComputedStyle(document.documentElement);
  const colorGold    = rootStyles.getPropertyValue('--brand-gold').trim();
  const colorGreen   = rootStyles.getPropertyValue('--brand-green').trim();
  const colorBlue    = rootStyles.getPropertyValue('--brand-blue').trim();

  // 3.1) Gráfico de barras – Clientes por vendedor (dourado)
  new Chart(document.getElementById('chart-clients'), {
    type: 'bar',
    data: {
      labels: clientsLabels,
      datasets: [{
        label: 'Clientes',
        data: clientsData,
        backgroundColor: colorGold
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true }
      }
    }
  });

  // 3.2) Gráfico de linha – Vendas semanais (verde)
  new Chart(document.getElementById('chart-sales'), {
    type: 'line',
    data: {
      labels: salesLabels,
      datasets: [{
        label: 'Vendas',
        data: salesData,
        borderColor: colorGreen,
        backgroundColor: 'transparent',
        pointBackgroundColor: colorGreen,
        tension: 1.0
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: { min: 0, ticks: { stepSize: 1 } }
      }
    }
  });

  // 3.3) Gráfico donut – Parcelas pendentes (azul)
  new Chart(document.getElementById('chart-parcels'), {
    type: 'doughnut',
    data: {
      labels: ['Pendentes'],
      datasets: [{
        data: [parcelsCount],
        backgroundColor: [colorBlue]
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
});
