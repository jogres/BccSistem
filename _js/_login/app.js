// _js/_login/app.js
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form');
  form.addEventListener('submit', e => {
    const email = form.email.value.trim();
    const senha = form.senha.value.trim();
    if (!email || !senha) {
      e.preventDefault();
      alert('Preencha e-mail e senha para continuar.');
    }
  });
});
