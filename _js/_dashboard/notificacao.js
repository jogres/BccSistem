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