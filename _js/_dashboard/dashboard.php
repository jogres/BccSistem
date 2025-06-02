    const dias = <?= json_encode($dias) ?>;
    const vendasData = <?= json_encode($vendasData) ?>;

    new Chart(document.getElementById('salesChart'), {
      type: 'line',
      data: { labels: dias, datasets:[{ label:'Vendas', data:vendasData, fill:false, tension:0.3 }] },
      options:{ scales:{ x:{ title:{ display:true,text:'Dia do Mês' }}, y:{ beginAtZero:true } } }
    });

    <?php if (!$isAdmin): ?>
    const clientesData = <?= json_encode($clientesData) ?>;
    new Chart(document.getElementById('clientsChart'), {
      type:'bar',
      data:{ labels:dias, datasets:[{ label:'Clientes', data:clientesData, barPercentage:0.6 }] },
      options:{ scales:{ x:{ title:{ display:true,text:'Dia do Mês' }}, y:{ beginAtZero:true } } }
    });
    <?php endif; ?>