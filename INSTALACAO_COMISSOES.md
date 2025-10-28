# 💰 Sistema de Comissionamento - Instalação

## 📋 Pré-requisitos

1. Sistema BccSistem já instalado e funcionando
2. Acesso ao banco de dados MySQL
3. Permissões de administrador no sistema

## 🗄️ Instalação do Banco de Dados

### Passo 1: Executar Script SQL

Execute o script SQL para criar a tabela de comissões:

```bash
mysql -u seu_usuario -p nome_do_banco < scripts/create_comissoes_table.sql
```

Ou execute diretamente no MySQL:

```sql
-- Tabela de Comissões
CREATE TABLE IF NOT EXISTS `comissoes` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `venda_id` bigint(20) UNSIGNED NOT NULL,
  `funcionario_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_comissao` enum('vendedor','virador') NOT NULL,
  `parcela` varchar(50) NOT NULL COMMENT 'Descrição da parcela (ex: Parcela 1, Parcela 2, Parcela Final)',
  `numero_parcela` int(11) NOT NULL COMMENT 'Número sequencial da parcela',
  `porcentagem` decimal(5,2) NOT NULL COMMENT 'Porcentagem de comissão (ex: 5.00, 10.50)',
  `valor_base` decimal(10,2) NOT NULL COMMENT 'Valor base calculado (já considerando meia parcela Gazin se necessário)',
  `valor_comissao` decimal(10,2) NOT NULL COMMENT 'Valor final da comissão (valor_base * porcentagem / 100)',
  `created_by` bigint(20) UNSIGNED NOT NULL COMMENT 'Admin que criou a comissão',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_venda_id` (`venda_id`),
  KEY `idx_funcionario_id` (`funcionario_id`),
  KEY `idx_tipo_comissao` (`tipo_comissao`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_comissoes_venda` FOREIGN KEY (`venda_id`) REFERENCES `vendas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_comissoes_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_comissoes_created_by` FOREIGN KEY (`created_by`) REFERENCES `funcionarios` (`id`) ON DELETE RESTRICT,
  UNIQUE KEY `unique_parcela_venda_tipo` (`venda_id`, `tipo_comissao`, `numero_parcela`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## ✅ Verificação

Após executar o script, verifique se a tabela foi criada:

```sql
SHOW TABLES LIKE 'comissoes';
DESCRIBE comissoes;
```

## 🎯 Funcionalidades Implementadas

### ✅ Regras de Negócio

1. **Comissões Separadas**: Vendedores e viradores recebem comissões separadas
2. **Acesso Restrito**: Apenas administradores podem acessar o comissionamento
3. **Regra Especial Gazin**: 
   - Se a administradora for "Gazin " e a venda for do tipo "Meia", o valor do crédito é dividido por 2 para o cálculo da comissão
4. **Gestão de Parcelas**:
   - Sistema controla qual parcela está sendo gerada (1, 2, 3, etc.)
   - Quando marca como "Parcela Final", a venda não aparece mais para esse tipo (vendedor/virador)
   - Se virador chegar na parcela final, venda ainda aparece para vendedor
5. **Validação de Porcentagem**: Aceita valores numéricos com até 2 casas decimais

### 📁 Arquivos Criados

- `scripts/create_comissoes_table.sql` - Script de criação da tabela
- `app/models/Comissao.php` - Modelo de dados
- `public/comissoes/index.php` - Página principal de comissões
- `public/comissoes/create.php` - Página de criação de comissão
- `public/api/comissoes.php` - API REST para comissões
- `public/assets/css/comissoes.css` - Estilos CSS específicos

### 🔧 Arquivos Modificados

- `app/lib/ActivityLogger.php` - Adicionado método `logComissaoCreated()`
- `app/views/partials/header.php` - Adicionado link "💰 Comissões" (apenas para admin)

## 🚀 Como Usar

1. **Acessar o Sistema**: Faça login como administrador
2. **Navegar para Comissões**: Clique em "💰 Comissões" no menu
3. **Escolher Tipo**: Selecione "Vendedor" ou "Virador"
4. **Selecionar Funcionário**: Clique no funcionário desejado
5. **Selecionar Venda**: Escolha a venda para gerar comissão
6. **Preencher Dados**: 
   - Informe a descrição da parcela (ex: "Parcela 1", "Parcela Final")
   - Informe a porcentagem de comissão (ex: 5.00, 10.50)
7. **Gerar**: Clique em "Gerar Comissão"

## 📊 Estrutura de Dados

### Tabela `comissoes`

- `id`: ID único da comissão
- `venda_id`: ID da venda relacionada
- `funcionario_id`: ID do funcionário (vendedor ou virador)
- `tipo_comissao`: Tipo (vendedor ou virador)
- `parcela`: Descrição da parcela (ex: "Parcela 1", "Parcela Final")
- `numero_parcela`: Número sequencial da parcela
- `porcentagem`: Porcentagem de comissão (ex: 5.00)
- `valor_base`: Valor base usado no cálculo (já considera regra Gazin)
- `valor_comissao`: Valor final da comissão calculada
- `created_by`: ID do administrador que criou a comissão
- `created_at`: Data de criação
- `updated_at`: Data de atualização

## 🔒 Segurança

- Apenas administradores podem acessar as páginas de comissão
- Todas as ações são registradas nos logs de auditoria
- Notificações são enviadas para administradores quando uma comissão é criada
- Validações no frontend e backend para garantir integridade dos dados

## 📝 Logs e Auditoria

Todas as comissões geradas são registradas em:
- `activity_logs`: Log de ações do sistema
- `notifications`: Notificações para administradores

## 🐛 Resolução de Problemas

### Erro: Tabela não existe
- Verifique se o script SQL foi executado corretamente
- Verifique se você tem permissões no banco de dados

### Erro: Acesso negado
- Certifique-se de estar logado como administrador
- Verifique se o middleware `require_admin.php` está funcionando

### Vendas não aparecem na lista
- Verifique se a venda não atingiu a "Parcela Final" para o tipo de comissão selecionado
- Verifique se o funcionário está associado à venda como vendedor ou virador

## 📞 Suporte

Em caso de dúvidas ou problemas, consulte os logs do sistema em `public/logs.php` (apenas administradores).

