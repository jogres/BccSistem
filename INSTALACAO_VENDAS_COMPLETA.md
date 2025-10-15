# 🛒 Instalação do Módulo de Vendas - Sistema BCC

## 📋 Pré-requisitos

- ✅ Sistema BCC já instalado e funcionando
- ✅ PHP 7.4+ com PDO MySQL
- ✅ MySQL/MariaDB
- ✅ Composer instalado
- ✅ PhpSpreadsheet instalado (`composer install`)

## 🚀 Passos da Instalação

### 1. Aplicar Atualização do Banco de Dados

Execute o script SQL no seu banco de dados:

```sql
-- No MySQL/MariaDB, execute:
SOURCE vendas_update_database.sql;
```

**OU** copie e cole o conteúdo do arquivo `vendas_update_database.sql` no seu cliente MySQL.

### 2. Criar Diretório de Uploads

Execute os seguintes comandos no terminal (dentro da pasta do projeto):

```bash
# Criar diretório para contratos
mkdir -p public/uploads/contratos

# Definir permissões (Linux/Unix)
chmod 755 public/uploads/contratos

# Criar arquivo .gitkeep
echo "" > public/uploads/contratos/.gitkeep

# Criar arquivo .gitignore
echo "*" > public/uploads/contratos/.gitignore
echo "!.gitkeep" >> public/uploads/contratos/.gitignore
```

**Para Windows:**
```cmd
mkdir public\uploads\contratos
echo. > public\uploads\contratos\.gitkeep
echo * > public\uploads\contratos\.gitignore
echo !.gitkeep >> public\uploads\contratos\.gitignore
```

### 3. Verificar Instalação

Acesse no navegador:
```
http://seu-dominio/verificar_instalacao_vendas.php
```

Este script verificará:
- ✅ Conexão com banco de dados
- ✅ Tabela `vendas` criada corretamente
- ✅ Foreign keys configuradas
- ✅ Arquivos PHP no lugar
- ✅ Diretório de uploads
- ✅ Dados necessários (clientes, funcionários)

### 4. Testar o Sistema

Após a verificação bem-sucedida, teste:

1. **Acesse a lista de vendas:**
   ```
   http://seu-dominio/public/vendas/index.php
   ```

2. **Teste com diferentes perfis:**
   - **Aprendiz**: Deve ver apenas suas vendas
   - **Padrão**: Deve poder criar vendas e ver apenas as suas
   - **Administrador**: Deve ver todas as vendas e poder editar

## 🔧 Estrutura Criada

### Tabela `vendas`
```sql
- id (INT UNSIGNED AUTO_INCREMENT PRIMARY KEY)
- cliente_id (INT UNSIGNED, FK para clientes.id)
- vendedor_id (INT UNSIGNED, FK para funcionarios.id)
- virador_id (INT UNSIGNED, FK para funcionarios.id)
- numero_contrato (VARCHAR(100) UNIQUE)
- rua, bairro, numero, cep, cpf (dados do cliente)
- segmento, tipo, administradora (dados da venda)
- valor_credito (DECIMAL(10,2))
- arquivo_contrato (VARCHAR(255))
- created_at, updated_at, deleted_at (timestamps)
```

### Arquivos Criados
```
app/models/Venda.php                    # Modelo de vendas
app/config/administradoras.php          # Configuração de administradoras
public/vendas/
├── index.php                          # Lista de vendas
├── create.php                         # Criar nova venda
├── edit.php                           # Editar venda (admin)
├── view.php                           # Ver detalhes da venda
├── delete.php                         # Excluir venda (admin)
└── export_excel.php                   # Exportar para Excel (admin)
public/api/cliente_info.php             # API para dados do cliente
```

## 🔒 Permissões por Perfil

| Perfil | Ver Vendas | Criar Vendas | Editar Vendas | Exportar Excel |
|--------|------------|--------------|---------------|----------------|
| **Aprendiz** | ✅ Apenas próprias | ❌ Não | ❌ Não | ❌ Não |
| **Padrão** | ✅ Apenas próprias | ✅ Sim | ❌ Não | ❌ Não |
| **Administrador** | ✅ Todas | ✅ Sim | ✅ Sim | ✅ Sim |

## 🎯 Funcionalidades

### Para Aprendizes
- 📋 Visualizar apenas suas próprias vendas
- 🔍 Filtrar vendas por período, tipo, etc.
- 👁️ Ver detalhes completos das vendas

### Para Padrão
- 📋 Visualizar apenas suas próprias vendas
- ➕ Criar novas vendas
- 🔍 Filtrar e buscar vendas
- 👁️ Ver detalhes completos das vendas

### Para Administradores
- 📋 Visualizar todas as vendas
- ➕ Criar novas vendas
- ✏️ Editar vendas existentes
- 🗑️ Excluir vendas
- 📊 Exportar vendas para Excel
- 🔍 Filtros avançados

## 🔧 Configurações

### Administradoras
Edite o arquivo `app/config/administradoras.php`:
```php
<?php
return [
    'Administradora A',
    'Administradora B',
    'Administradora C',
    // Adicione mais conforme necessário
];
```

### Segmentos (Interesses)
Os segmentos são obtidos do arquivo `app/config/interesses.php` existente.

## 🐛 Solução de Problemas

### Erro de Foreign Key
Se houver erro de foreign key, execute:
```sql
-- Verificar tipos das colunas
DESCRIBE clientes;
DESCRIBE funcionarios;

-- Se necessário, ajustar tipos
ALTER TABLE clientes MODIFY COLUMN id INT UNSIGNED AUTO_INCREMENT;
ALTER TABLE funcionarios MODIFY COLUMN id INT UNSIGNED AUTO_INCREMENT;
```

### Erro de Permissão
Se houver erro de acesso negado:
1. Verifique se o usuário está logado
2. Confirme o perfil do usuário na tabela `funcionarios`
3. Verifique se o role_id corresponde ao perfil correto

### Erro de Upload
Se houver erro no upload de contratos:
1. Verifique se o diretório `public/uploads/contratos` existe
2. Confirme as permissões do diretório (755)
3. Verifique se o PHP tem permissão de escrita

## 📞 Suporte

Em caso de problemas:
1. Execute o script de verificação
2. Verifique os logs de erro do PHP
3. Confirme se todos os arquivos estão no lugar
4. Teste com diferentes perfis de usuário

## ✅ Checklist de Instalação

- [ ] Script SQL executado com sucesso
- [ ] Tabela `vendas` criada
- [ ] Foreign keys configuradas
- [ ] Diretório de uploads criado
- [ ] Arquivos PHP no lugar
- [ ] Script de verificação executado
- [ ] Teste com usuário Aprendiz
- [ ] Teste com usuário Padrão
- [ ] Teste com usuário Administrador
- [ ] Upload de contrato funcionando
- [ ] Exportação Excel funcionando

---

**🎉 Instalação concluída! O módulo de vendas está pronto para uso.**
