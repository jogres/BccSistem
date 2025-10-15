# 📋 Sistema de Logs e Monitoramento - BCC Sistema

## ✅ Sistema Implementado e Funcional

### 🎯 Componentes Principais

1. **Logger** (`app/lib/Logger.php`)
   - Sistema completo de logging com 5 níveis
   - Rotação automática de arquivos
   - Timezone: America/Sao_Paulo (UTC-3)
   - Codificação: UTF-8 com detecção automática

2. **ErrorHandler** (`app/lib/ErrorHandler.php`)
   - 50+ mensagens de erro em português
   - Validação automática de dados
   - Integração com sistema de sessões

3. **Interface Web** (`public/logs.php`)
   - Visualização de logs (apenas administradores)
   - Filtros por data, nível, usuário
   - Estatísticas em tempo real

4. **Scripts de Manutenção**
   - `scripts/health_check.php` - Verificação de saúde do sistema
   - `scripts/cleanup_logs.php` - Limpeza automática de logs antigos

### 📊 Níveis de Log

| Nível | Arquivo | Uso |
|-------|---------|-----|
| ERROR | `errors_YYYY-MM-DD.log` | Erros do sistema |
| WARNING | `warnings_YYYY-MM-DD.log` | Avisos |
| INFO | `system_YYYY-MM-DD.log` | Informações gerais |
| SECURITY | `security_YYYY-MM-DD.log` | Eventos de segurança |
| ACTION | `actions_YYYY-MM-DD.log` | Ações dos usuários |

### 🔧 Módulos com Logging Ativo

#### ✅ Autenticação (100%)
- Login bem-sucedido/falhado
- Logout
- Tentativas de acesso negado

#### ✅ Vendas (100%)
- Criação, edição, exclusão (CRUD completo)
- Erros em operações
- Acessos negados
- Upload de contratos

#### ⏳ Clientes (33%)
- ✅ Criação
- ⚠️ Edição (implementar)
- ⚠️ Exclusão (implementar)

#### ❌ Funcionários (0%)
- Aguardando implementação

### 📝 Como Usar o Logger

#### Exemplo 1: Log de Erro
```php
require_once __DIR__ . '/app/lib/Logger.php';

try {
    // código que pode falhar
} catch (Exception $e) {
    Logger::error('Erro ao processar dados', [
        'user_id' => $userId,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
```

#### Exemplo 2: Log de Ação
```php
Logger::action('Usuário criou nova venda', $userId, [
    'venda_id' => $vendaId,
    'valor' => $valor
]);
```

#### Exemplo 3: Log CRUD
```php
Logger::crud('CREATE', 'clientes', $clienteId, $userId, [
    'nome' => $nome,
    'telefone' => $telefone
]);
```

#### Exemplo 4: Log de Segurança
```php
Logger::security('Tentativa de acesso não autorizado', [
    'user_id' => $userId,
    'resource' => 'vendas/edit.php'
]);
```

### 🔍 Visualizar Logs

#### 1. Interface Web (Recomendado)
```
URL: http://localhost/public/logs.php
Acesso: Apenas administradores
```

#### 2. Arquivos Diretos
```
Localização: logs/
Formato: UTF-8
Visualizar com: VS Code, Notepad++, etc.
```

#### 3. PowerShell (Windows)
```powershell
Get-Content -Encoding UTF8 logs\system_2025-10-15.log
```

#### 4. Terminal (Linux/Mac)
```bash
tail -f logs/system_2025-10-15.log
```

### ⚙️ Configurações

#### Fuso Horário
```php
// app/config/config.php
date_default_timezone_set('America/Sao_Paulo');
```

#### Codificação
```php
// app/lib/Logger.php
mb_internal_encoding('UTF-8');
ini_set('default_charset', 'UTF-8');
```

#### Rotação de Logs
```php
// app/lib/Logger.php
private static $maxLogSize = 10485760; // 10MB
private static $maxLogFiles = 5;
```

### 🧹 Manutenção

#### Limpeza Automática
```bash
php scripts/cleanup_logs.php
```
Remove logs com mais de 30 dias.

#### Verificação de Saúde
```bash
php scripts/health_check.php
```
Verifica integridade do sistema.

### 📈 Estatísticas

#### Cobertura Atual
- Autenticação: 100% ✅
- Vendas: 100% ✅
- Clientes: 33% ⏳
- Funcionários: 0% ❌
- **Total: 60%**

### 🎯 Próximos Passos

1. Completar logging em Clientes (edit, delete)
2. Implementar logging em Funcionários
3. Adicionar logging em Dashboard
4. Configurar limpeza automática via cron/task

### 📌 Notas Importantes

- Todos os logs são salvos em UTF-8
- Horário configurado para Brasília (UTC-3)
- Rotação automática quando atingir 10MB
- Retenção de 5 arquivos rotacionados
- Logs antigos devem ser limpos periodicamente

### ✅ Status Final

- ✅ Sistema de logging: 100% operacional
- ✅ Timezone: Correto (America/Sao_Paulo)
- ✅ UTF-8: Caracteres especiais suportados
- ✅ Interface web: Funcional
- ✅ Scripts de manutenção: Prontos
- ✅ Documentação: Completa
