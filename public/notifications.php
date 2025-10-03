<?php
require __DIR__ . '/../app/lib/Database.php';
require __DIR__ . '/../app/lib/Auth.php';
require __DIR__ . '/../app/lib/Helpers.php';
require __DIR__ . '/../app/lib/Notification.php';
require __DIR__ . '/../app/middleware/require_login.php';

$user = Auth::user();
$action = $_GET['action'] ?? '';

// Ações AJAX
if ($action === 'mark_read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $success = Notification::markAsRead($id, $user['id']);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'mark_all_read') {
    $count = Notification::markAllAsRead($user['id']);
    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

// Buscar notificações
$notifications = Notification::getUserNotifications($user['id'], 50);
$unreadCount = Notification::getUnreadCount($user['id']);

include __DIR__ . '/../app/views/partials/header.php';
?>
<div class="card">
  <div class="cluster" style="justify-content:space-between; align-items:center">
    <h1>🔔 Notificações</h1>
    <?php if ($unreadCount > 0): ?>
      <button class="btn secondary" onclick="markAllAsRead()">
        Marcar todas como lidas (<?= $unreadCount ?>)
      </button>
    <?php endif; ?>
  </div>

  <?php if (empty($notifications)): ?>
    <div class="notice" style="text-align:center; padding:2rem">
      <p>Nenhuma notificação encontrada.</p>
    </div>
  <?php else: ?>
    <div class="notifications-list" style="display: flex; flex-direction: column; gap: 1rem;">
      <?php foreach ($notifications as $notification): ?>
        <div class="notification-item <?= $notification['read_at'] ? 'read' : 'unread' ?>" 
             data-id="<?= $notification['id'] ?>">
          <div class="notification-content">
            <div class="notification-header">
              <h4 class="notification-title"><?= e($notification['title']) ?></h4>
              <span class="notification-time">
                <?= date('d/m/Y H:i', strtotime($notification['created_at'])) ?>
              </span>
            </div>
            <p class="notification-message"><?= e($notification['message']) ?></p>
            <?php if ($notification['action_url']): ?>
              <a href="<?= e($notification['action_url']) ?>" class="notification-action">
                Ver detalhes →
              </a>
            <?php endif; ?>
          </div>
          <?php if (!$notification['read_at']): ?>
            <button class="btn secondary btn-sm" onclick="markAsRead(<?= $notification['id'] ?>)">
              Marcar como lida
            </button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>


<script>
function markAsRead(id) {
  fetch('?action=mark_read&id=' + id)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const item = document.querySelector(`[data-id="${id}"]`);
        item.classList.remove('unread');
        item.classList.add('read');
        item.querySelector('button').remove();
        
        // Atualizar contador no header se existir
        const badge = document.querySelector('.notification-badge');
        if (badge) {
          const count = parseInt(badge.textContent) - 1;
          badge.textContent = count > 0 ? count : '';
          if (count === 0) {
            badge.style.display = 'none';
          }
        }
      }
    })
    .catch(error => console.error('Erro:', error));
}

function markAllAsRead() {
  fetch('?action=mark_all_read')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Recarregar a página para atualizar tudo
        location.reload();
      }
    })
    .catch(error => console.error('Erro:', error));
}
</script>

<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>
