// /_consorcioBcc/_js/_menu/menu.js

document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('menu-toggle');
  const menu      = document.getElementById('main-menu');

  // Alterna visibilidade do menu em mobile
  toggleBtn.addEventListener('click', () => {
    menu.classList.toggle('open');
    menu.classList.toggle('collapsed');
  });

  // Submenus expansíveis
  document.querySelectorAll('#main-menu .has-submenu > a')
    .forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        this.parentElement.classList.toggle('active');
      });
    });
});
