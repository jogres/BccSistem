    document.addEventListener('DOMContentLoaded', function() {
      const formVenda = document.getElementById('formVenda');
      const formNumFuncs = document.getElementById('formNumFuncs');

      // auto-submit ao mudar venda
      document.querySelectorAll('#formVenda input[name="venda"]').forEach(rb => {
        rb.addEventListener('change', () => document.getElementById('formVenda').submit());
      });
      // auto-submit ao mudar número de funcionários
      document.querySelector('#formNumFuncs input[name="num_funcs"]')?.addEventListener('change', () => {
        document.getElementById('formNumFuncs').submit();
      });
    });