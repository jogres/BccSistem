<?php
// /_html/_vendas/form.php
require __DIR__ . '/../../_php/shared/verify_session.php';
include __DIR__ . '/../../_php/_menu/menu.php';
include __DIR__ . '/../../_php/_vendas/form.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /BccSistem/_html/_login/index.php');
    exit;
}

// JSON de todos os clientes para preencher ao selecionar
$clientes_json = json_encode(array_column($clientes, null, 'id_cliente'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $isEdit ? "Editar Venda #{$id_venda}" : "Cadastrar Venda" ?></title>
  <link rel="stylesheet" href="/BccSistem/_css/_menu/style.css">
  <link rel="stylesheet" href="/BccSistem/_css/_cadastro/style.css">
</head>
<body id="emp-create-body">
  <main id="emp-create-wrapper">
    <h1 id="emp-create-heading"><?= $isEdit ? "Editar Venda" : "Nova Venda" ?></h1>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="/BccSistem/_php/_vendas/process.php" method="post" class="form" id="sale-form">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id_venda" value="<?= $id_venda ?>">
      <?php endif; ?>

      <!-- Contrato -->
      <div class="form-group">
        <label class="form-label" for="numero_contrato">Contrato</label>
        <input class="form-input" id="numero_contrato" name="numero_contrato"
               required maxlength="50"
               value="<?= htmlspecialchars($venda['numero_contrato'] ?? '') ?>">
      </div>

      <!-- Seleção de cliente -->
      <div class="form-group">
        <label class="form-label" for="id_cliente">Cliente</label>
        <select class="form-input" id="id_cliente" name="id_cliente" >
          <option value="">— selecione —</option>
          <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id_cliente'] ?>"
              <?= isset($venda['id_cliente']) && $c['id_cliente'] == $venda['id_cliente'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Campos completos do cliente (sempre visíveis e editáveis) -->
      <?php $cn = $venda['cliente_data'] ?? []; ?>
      <div id="group-client-data">
        <?php foreach ([
          'nome'=>'Nome',
          'cpf'=>'CPF',
          'telefone'=>'Telefone',
          'celular'=>'Celular',
          'logradouro'=>'Logradouro',
          'numero'=>'Número',
          'complemento'=>'Complemento',
          'bairro'=>'Bairro',
          'cidade'=>'Cidade',
          'estado'=>'Estado',
          'cep'=>'CEP',
          'motivo'=>'Motivo / Observações'
        ] as $field=>$label): ?>
          <div class="form-group">
            <label class="form-label" for="cli_<?= $field ?>"><?= $label ?></label>
            <?php if ($field === 'motivo'): ?>
              <textarea class="form-input" id="cli_<?= $field ?>" name="cli_<?= $field ?>" rows="3"><?= htmlspecialchars($cn[$field] ?? '') ?></textarea>
            <?php else: ?>
              <input class="form-input"
                     id="cli_<?= $field ?>"
                     name="cli_<?= $field ?>"
                     <?= $field==='nome' ? 'required' : '' ?>
                     maxlength="<?= in_array($field,['cpf','estado']) ? ($field==='cpf'?11:2) : '' ?>"
                     placeholder="<?= $field==='cpf' ? 'somente números' : '' ?>"
                     value="<?= htmlspecialchars($cn[$field] ?? '') ?>">
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Demais campos da venda -->
      <div class="form-group">
        <label class="form-label" for="id_vendedor">Vendedor</label>
        <select class="form-input" id="id_vendedor" name="id_vendedor" required>
          <option value="">— selecione —</option>
          <?php foreach($funcionarios as $f): ?>
            <option value="<?= $f['id_funcionario'] ?>"
              <?= isset($venda['id_vendedor']) && $venda['id_vendedor']==$f['id_funcionario'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($f['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="id_virador">Virador</label>
        <select class="form-input" id="id_virador" name="id_virador" required>
          <option value="">— selecione —</option>
          <?php foreach($funcionarios as $f): ?>
            <option value="<?= $f['id_funcionario'] ?>"
              <?= isset($venda['id_virador']) && $venda['id_virador']==$f['id_funcionario'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($f['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="id_administradora">Administradora</label>
        <select class="form-input" id="id_administradora" name="id_administradora" required>
          <option value="">— selecione —</option>
          <?php foreach($administradoras as $a): ?>
            <option value="<?= $a['id_administradora'] ?>"
              <?= isset($venda['id_administradora']) && $venda['id_administradora']==$a['id_administradora'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="id_plano_comissao">Plano de Comissão</label>
        <select class="form-input" id="id_plano_comissao" name="id_plano_comissao" required>
          <option value="">— selecione —</option>
          <?php foreach($planos as $p): ?>
            <option value="<?= $p['id_plano_comissao'] ?>"
              <?= isset($venda['id_plano_comissao']) && $venda['id_plano_comissao']==$p['id_plano_comissao'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['nome_plano']) ?> (<?= $p['num_parcelas_comiss'] ?>x)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="modalidade">Modalidade</label>
        <select class="form-input" id="modalidade" name="modalidade" required>
          <option value="">— selecione —</option>
          <?php foreach(['Automóvel','Imóvel','Moto','Móveis','Outros'] as $m): ?>
            <option value="<?= $m ?>"
              <?= isset($venda['modalidade']) && $venda['modalidade']==$m ? 'selected' : '' ?>>
              <?= $m ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label" for="valor_total">Valor Total</label>
        <input class="form-input"
               type="number" step="0.01"
               id="valor_total" name="valor_total" required
               value="<?= htmlspecialchars($venda['valor_total'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="data_venda">Data da Venda</label>
        <input class="form-input"
               type="date"
               id="data_venda" name="data_venda" required
               value="<?= htmlspecialchars($venda['data_venda'] ?? date('Y-m-d')) ?>">
      </div>

      <div class="form-group">
        <label class="form-label" for="status">Status</label>
        <select class="form-input" id="status" name="status" required>
          <?php foreach(['PENDENTE','ATIVA','CANCELADA'] as $s): ?>
            <option value="<?= $s ?>"
              <?= isset($venda['status']) && $venda['status']==$s ? 'selected' : '' ?>>
              <?= ucfirst(strtolower($s)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group form-group-submit">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Atualizar' : 'Salvar' ?>
        </button>
      </div>
    </form>
  </main>

  <script>
    // Mapa de clientes
    const CLIENTES = <?= $clientes_json ?>;
    // Ao trocar cliente, preenche automaticamente todos os campos
    document.getElementById('id_cliente').addEventListener('change', function(){
      const data = CLIENTES[this.value] || {};
      Object.keys(data).forEach(key => {
        const el = document.getElementById('cli_' + key);
        if (el) el.value = data[key] || '';
      });
    });
    // Preenche no load se já estiver selecionado
    window.addEventListener('load', () => {
      document.getElementById('id_cliente').dispatchEvent(new Event('change'));
    });
  </script>
</body>
</html>
