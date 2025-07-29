// /_consorcioBcc/_js/_listas/list.js
document.addEventListener('DOMContentLoaded', () => {
  const perPageSelect = document.querySelector('.per-page-select');
  const form          = perPageSelect.closest('form');

  perPageSelect.addEventListener('change', () => {
    // Força page=1 ao trocar per_page
    const inputPage = form.querySelector('input[name="page"]');
    if (inputPage) {
      inputPage.value = '1';
    } else {
      const hidden = document.createElement('input');
      hidden.type  = 'hidden';
      hidden.name  = 'page';
      hidden.value = '1';
      form.appendChild(hidden);
    }
    form.submit();
  });
});
