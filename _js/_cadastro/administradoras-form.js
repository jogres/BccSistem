// /_consorcioBcc/_js/_cadastro/administradoras-form.js
(function() {
  // Função que formata a string como CNPJ: 00.000.000/0000-00
  function formatCNPJ(value) {
    const digits = value.replace(/\D/g, '').slice(0, 14);
    return digits
      .replace(/(\d{2})(\d)/, '$1.$2')           // 00.###
      .replace(/(\d{2}\.\d{3})(\d)/, '$1.$2')    // 00.000.###
      .replace(/\.(\d{3})(\d)/, '.$1/$2')        // 00.000.000/####
      .replace(/(\d{4})(\d)/, '$1-$2');          // 00.000.000/0000-##
  }

  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('cnpj');
    if (!input) return;
    // Atualiza o valor a cada digitação
    input.addEventListener('input', e => {
      e.target.value = formatCNPJ(e.target.value);
    });
  });
})();
