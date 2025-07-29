// /_consorcioBcc/_js/_cadastro/form.js
document.addEventListener('DOMContentLoaded', () => {
  const form      = document.getElementById('emp-create-form');
  const errorBox  = document.getElementById('emp-create-error');

  form.addEventListener('submit', e => {
    // Limpa erro anterior
    if (errorBox) errorBox.remove();

    const nome       = form.nome.value.trim();
    const cpf        = form.cpf.value.replace(/\D/g, '');
    const email      = form.email.value.trim();
    const senha      = form.senha.value;
    const estado     = form.estado.value.trim();
    let clientErrors = [];

    // Validações básicas
    if (!nome) {
      clientErrors.push('Preencha o nome completo.');
    }

    if (!/^\d{11}$/.test(cpf)) {
      clientErrors.push('CPF deve ter 11 dígitos numéricos.');
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      clientErrors.push('E-mail em formato inválido.');
    }

    if (senha.length < 6) {
      clientErrors.push('Senha precisa ter ao menos 6 caracteres.');
    }

    if (estado && !/^[A-Za-z]{2}$/.test(estado)) {
      clientErrors.push('Estado deve conter 2 letras (ex: SP).');
    }

    if (clientErrors.length) {
      e.preventDefault();

      // Cria caixa de erro
      const div = document.createElement('div');
      div.id = 'emp-create-error';
      div.className = 'alert alert-error';
      div.innerHTML = clientErrors.map(msg => `<p>${msg}</p>`).join('');
      form.parentNode.insertBefore(div, form);
    }
  });

  // Máscara simples de CPF (XXX.XXX.XXX-XX)
  const cpfInput = form.cpf;
  cpfInput.addEventListener('input', () => {
    let v = cpfInput.value.replace(/\D/g, '');
    v = v.slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    cpfInput.value = v;
  });
});
