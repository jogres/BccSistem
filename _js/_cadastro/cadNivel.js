    document.addEventListener('DOMContentLoaded', function() {
      const selectNiveis = document.getElementById('select-niveis');
      const formContainer = document.querySelector('.form-container');

      selectNiveis.addEventListener('change', function() {
        const nivel = this.value;
        formContainer.classList.toggle('editing', nivel !== 'basic' && nivel !== 'classic' && nivel !== 'master');
      });

      // Inicializa o estado do formulário
      selectNiveis.dispatchEvent(new Event('change'));
    });