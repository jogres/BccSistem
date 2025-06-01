<?php
  include('../../_php/_login/logado.php');
  include('../../_php/_dashboard/dashboard.php');
  require_once __DIR__ . '/../../config/db.php';

$notificacoes = [];
if ($isAdmin) {
  $stmt = $pdo->prepare("
    SELECT n.id, n.mensagem, n.link, n.data_criacao
    FROM notificacoes n
    INNER JOIN venda v ON n.idVenda = v.id
    WHERE n.idFun = ? AND n.lida = 0 AND v.confirmada = 0
    ORDER BY n.data_criacao DESC
  ");
  $stmt->execute([$_SESSION['user_id']]);
  $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../../_css/_menu/menu.css">
  <link rel="stylesheet" href="../../_css/_dashboard/dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .notificacoes { margin: 20px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ccc; }
    .notificacoes h3 { margin-bottom: 10px; }
    .notificacao { margin-bottom: 8px; }
    .notificacao a { color: #0077cc; text-decoration: none; }
    .notificacao small { color: #888; font-size: 0.9em; }
  </style>
</head>
<body>
  <div class="container">
    <button class="menu-toggle float" aria-label="Abrir menu">&#9776;</button>
    <nav class="main-nav">
      <button class="menu-toggle inmenu" aria-label="Fechar menu">&#9776;</button>
      <ul class="nav-links">
        <?php foreach ($menu as $link => $nome): ?>
          <li class="nav-item"><a href="<?= $link ?>" class="nav-link"><?= $nome ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-user-actions">
        <span class="user-name"><?= htmlspecialchars($nomeP) ?></span>
        <form action="../../_php/_login/deslogar.php" method="post" class="logout-form">
          <button type="submit" class="logout-button">Sair</button>
        </form>
      </div>
    </nav>

    <main class="dashboard">
      <h2>Bem-vindo, <?= htmlspecialchars($nomeP) ?></h2>

      <?php if ($isAdmin && !empty($notificacoes)): ?>
        <section class="notificacoes">
          <h3>Notificações Recentes</h3>
          <?php foreach ($notificacoes as $notif): ?>
            <div class="notificacao" id="notificacao-<?= $notif['id'] ?>">
              <a href="<?= htmlspecialchars($notif['link']) ?>"><?= htmlspecialchars($notif['mensagem']) ?></a><br>
              <small><?= date('d/m/Y H:i', strtotime($notif['data_criacao'])) ?></small>
              <button class="btn-marcar-lida" data-id="<?= $notif['id'] ?>">Marcar como lida</button>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
          
      <!-- Grid dos cards principais (dois grandes em cima, dois menores embaixo) -->
      <div class="stats">
        <div class="stat-card">
          <h3>Total de Vendas (Mês)</h3>
          <p>R$ <?= number_format($totalVendas, 2, ',', '.') ?></p>
        </div>
        <div class="stat-card">
          <h3>Total Comissão (Mês)</h3>
          <p>R$ <?= number_format($totalComissao, 2, ',', '.') ?></p>
        </div>
        <div class="stat-card">
          <h3>
            <?php if ($isAdmin): ?>
              Total Clientes Cadastrados
            <?php else: ?>
              Total Clientes (Mês)
            <?php endif; ?>
          </h3>
          <p>
            <?php if ($isAdmin): ?>
              <?= $totalClientes ?>
            <?php else: ?>
              <?= array_sum(array_values($clientesData)) ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="stat-card aniversariantes">
          <h3>Aniversariantes do Dia</h3>
          <p>
            <?php
              // Exemplo de array de aniversariantes
              if (!empty($aniversariantes)) {
                foreach ($aniversariantes as $aniv) {
                  echo htmlspecialchars($aniv) . '<br>';
                }
              } else {
                echo "Nenhum aniversariante hoje";
              }
            ?>
          </p>
        </div>
      </div>
            
      <div class="charts">
        <div class="chart-container">
          <h4><?= $isAdmin ? 'Vendas Totais por Dia' : 'Minhas Vendas por Dia' ?></h4>
          <canvas id="salesChart"></canvas>
        </div>
        <?php if (!$isAdmin): ?>
          <div class="chart-container">
            <h4>Meus Clientes por Dia</h4>
            <canvas id="clientsChart"></canvas>
          </div>
        <?php endif; ?>
      </div>
    </main>
        
  </div>

  <script>
    const toggle = document.querySelector('.menu-toggle');
    const menu = document.querySelector('.main-nav');
    toggle.addEventListener('click', () => {
      menu.classList.toggle('open');
    });
    // Fechar o menu ao clicar fora dele (opcional)
    document.addEventListener('click', e => {
      if (window.innerWidth <= 900 && menu.classList.contains('open')) {
        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
          menu.classList.remove('open');
        }
      }
    });
    const floatBtn = document.querySelector('.menu-toggle.float');
    const nav = document.querySelector('.main-nav');
    const inMenuBtn = document.querySelector('.menu-toggle.inmenu');

    // Mostrar menu
    floatBtn.addEventListener('click', (e) => {
      nav.classList.add('open');
      floatBtn.style.display = 'none'; // Esconde ao abrir
      e.stopPropagation();
    });

    // Fechar menu pelo botão interno
    inMenuBtn.addEventListener('click', (e) => {
      nav.classList.remove('open');
      floatBtn.style.display = 'block'; // Mostra ao fechar
      e.stopPropagation();
    });

    // Fechar menu ao clicar fora
    document.addEventListener('click', (e) => {
      if (
        nav.classList.contains('open') &&
        window.innerWidth < 1920 &&
        !nav.contains(e.target) &&
        !floatBtn.contains(e.target)
      ) {
        nav.classList.remove('open');
        floatBtn.style.display = 'block'; // Mostra novamente
      }
    });

// Previne que cliques no menu fechem ele
    nav.addEventListener('click', (e) => e.stopPropagation());
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
  </script>
  <script>
document.querySelectorAll('.btn-marcar-lida').forEach(button => {
  button.addEventListener('click', () => {
    const id = button.getAttribute('data-id');
    fetch('../../_php/_notificacoes/marcar_notificacao_lida.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'id=' + encodeURIComponent(id),
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'sucesso') {
        const notifDiv = document.getElementById('notificacao-' + id);
        if (notifDiv) {
          notifDiv.remove();
        }
      } else {
        alert('Erro ao marcar notificação como lida.');
      }
    })
    .catch(error => {
      console.error('Erro:', error);
      alert('Erro ao processar a solicitação.');
    });
  });
});
</script>


</body>
</html>
