    const dias = <?= json_encode($dias) ?>;
    const vendasData = <?= json_encode($vendasData) ?>;
<?php if ($isAdmin): ?>
    const perfLabels = <?= json_encode(array_column($vendasPorFunc,'funcionario')) ?>;
    const perfVendas = <?= json_encode(array_column($vendasPorFunc,'total_vendas')) ?>;
    const perfClientes = <?= json_encode(array_column($clientesPorFunc,'total_clientes')) ?>;
    new Chart(document.getElementById('perfChart'), {
      type:'bar',data:{labels:perfLabels,datasets:[{label:'Vendas',data:perfVendas,backgroundColor:'rgba(0,64,128,0.7)'},{label:'Clientes',data:perfClientes,backgroundColor:'rgba(21,153,0,0.7)'}]} ,options:{responsive:true,scales:{y:{beginAtZero:true}}}
    });
<?php endif; ?>    

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

