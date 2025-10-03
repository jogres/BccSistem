# 🔔 Sistema de Notificações - BccSistem

## 📋 **Visão Geral**

O sistema de notificações do BccSistem é um sistema completo de comunicação interna que permite o envio, gerenciamento e visualização de notificações em tempo real para todos os usuários do sistema.

---

## 🗄️ **Estrutura do Banco de Dados**

### **Tabela `notifications`**

```sql
CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
  `action_url` varchar(500) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_read_at` (`read_at`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **Campos da Tabela:**
- **`id`**: Identificador único da notificação
- **`user_id`**: ID do funcionário destinatário
- **`title`**: Título da notificação
- **`message`**: Conteúdo/mensagem da notificação
- **`type`**: Tipo da notificação (info, success, warning, error)
- **`action_url`**: URL de ação opcional (link para detalhes)
- **`read_at`**: Timestamp de quando foi lida (NULL = não lida)
- **`created_at`**: Timestamp de criação

---

## 🏗️ **Arquitetura do Sistema**

### **1. Classe `Notification` (`app/lib/Notification.php`)**

#### **Métodos Principais:**

##### **✅ `create()` - Criar Notificação**
```php
public static function create(
    int $userId, 
    string $title, 
    string $message, 
    string $type = self::TYPE_INFO, 
    ?string $actionUrl = null
): int
```

**Funcionalidade:**
- Cria uma nova notificação para um usuário específico
- Retorna o ID da notificação criada
- Tipos disponíveis: `info`, `success`, `warning`, `error`

**Exemplo de uso:**
```php
Notification::create(
    1, 
    'Novo Cliente Cadastrado', 
    'Cliente João Silva foi cadastrado no sistema',
    Notification::TYPE_INFO,
    'clientes/edit.php?id=123'
);
```

##### **✅ `markAsRead()` - Marcar como Lida**
```php
public static function markAsRead(int $notificationId, int $userId): bool
```

**Funcionalidade:**
- Marca uma notificação específica como lida
- Verifica se a notificação pertence ao usuário
- Retorna `true` se foi marcada com sucesso

##### **✅ `markAllAsRead()` - Marcar Todas como Lidas**
```php
public static function markAllAsRead(int $userId): int
```

**Funcionalidade:**
- Marca todas as notificações não lidas do usuário como lidas
- Retorna o número de notificações marcadas

##### **✅ `getUserNotifications()` - Buscar Notificações**
```php
public static function getUserNotifications(
    int $userId, 
    int $limit = 20, 
    bool $unreadOnly = false
): array
```

**Funcionalidade:**
- Busca notificações do usuário
- Ordenadas por data de criação (mais recentes primeiro)
- Opção de filtrar apenas não lidas
- Limite configurável de resultados

##### **✅ `getUnreadCount()` - Contar Não Lidas**
```php
public static function getUnreadCount(int $userId): int
```

**Funcionalidade:**
- Conta quantas notificações não lidas o usuário possui
- Usado para exibir badge no header

##### **✅ `cleanupOld()` - Limpeza Automática**
```php
public static function cleanupOld(): int
```

**Funcionalidade:**
- Remove notificações antigas (mais de 30 dias)
- Retorna o número de notificações removidas
- Mantém o banco de dados limpo

---

### **2. Métodos Específicos de Negócio**

#### **✅ `notifyNewClient()` - Notificar Novo Cliente**
```php
public static function notifyNewClient(int $clientId, string $clientName, int $createdBy): void
```

**Funcionalidade:**
- Notifica todos os administradores sobre novo cliente
- Exclui o usuário que criou o cliente
- Inclui link para edição do cliente

#### **✅ `notifyInactiveUser()` - Notificar Usuário Inativo**
```php
public static function notifyInactiveUser(int $userId, string $userName): void
```

**Funcionalidade:**
- Notifica administradores sobre funcionário inativado
- Tipo de notificação: `warning`

#### **✅ `notifySuspiciousLogin()` - Notificar Login Suspeito**
```php
public static function notifySuspiciousLogin(string $login, string $ip): void
```

**Funcionalidade:**
- Notifica administradores sobre tentativas de login suspeitas
- Tipo de notificação: `error`
- Inclui informações de login e IP

---

## 🎨 **Interface do Usuário**

### **1. Header (`app/views/partials/header.php`)**

#### **Badge de Notificações:**
```php
<?php
$unreadCount = Notification::getUnreadCount($user['id']);
?>

<a href="<?= e(base_url('notifications.php')) ?>" class="nav-link notification-badge">
  🔔 Notificações
  <?php if ($unreadCount > 0): ?>
    <span class="badge"><?= $unreadCount ?></span>
  <?php endif; ?>
</a>
```

**Funcionalidade:**
- Exibe contador de notificações não lidas
- Badge vermelho com número quando há notificações
- Link direto para página de notificações

### **2. Página de Notificações (`public/notifications.php`)**

#### **Funcionalidades:**
- **Lista completa** de notificações do usuário
- **Botão "Marcar todas como lidas"** quando há notificações não lidas
- **Botões individuais** para marcar como lida
- **Links de ação** para URLs específicas
- **Atualização AJAX** sem recarregar a página

#### **Ações AJAX:**

##### **✅ Marcar como Lida:**
```javascript
function markAsRead(id) {
  fetch('?action=mark_read&id=' + id)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Atualiza interface
        const item = document.querySelector(`[data-id="${id}"]`);
        item.classList.remove('unread');
        item.classList.add('read');
        item.querySelector('button').remove();
        
        // Atualiza contador no header
        const badge = document.querySelector('.notification-badge');
        if (badge) {
          const count = parseInt(badge.textContent) - 1;
          badge.textContent = count > 0 ? count : '';
          if (count === 0) {
            badge.style.display = 'none';
          }
        }
      }
    });
}
```

##### **✅ Marcar Todas como Lidas:**
```javascript
function markAllAsRead() {
  fetch('?action=mark_all_read')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        location.reload(); // Recarrega para atualizar tudo
      }
    });
}
```

---

## 🎯 **Tipos de Notificações**

### **1. INFO (`info`)**
- **Cor:** Azul
- **Uso:** Informações gerais, avisos neutros
- **Exemplos:**
  - Novo cliente cadastrado
  - Backup realizado com sucesso
  - Sistema atualizado

### **2. SUCCESS (`success`)**
- **Cor:** Verde
- **Uso:** Ações bem-sucedidas
- **Exemplos:**
  - Cliente atualizado com sucesso
  - Senha alterada
  - Dados salvos

### **3. WARNING (`warning`)**
- **Cor:** Laranja/Amarelo
- **Uso:** Avisos importantes, situações que requerem atenção
- **Exemplos:**
  - Funcionário inativado
  - Sistema em manutenção
  - Quota de backup quase esgotada

### **4. ERROR (`error`)**
- **Cor:** Vermelho
- **Uso:** Erros críticos, problemas de segurança
- **Exemplos:**
  - Tentativas de login suspeitas
  - Falha no backup
  - Erro de sistema

---

## 🔄 **Fluxo de Funcionamento**

### **1. Criação de Notificação:**
```
Evento do Sistema → Notification::create() → Banco de Dados → Badge Atualizado
```

### **2. Visualização:**
```
Usuário acessa notificações → getUnreadCount() → Badge no header
```

### **3. Marcação como Lida:**
```
Usuário clica "Marcar como lida" → AJAX → markAsRead() → Interface atualizada
```

### **4. Limpeza Automática:**
```
Cron Job/Agendamento → cleanupOld() → Notificações antigas removidas
```

---

## 🚀 **Funcionalidades Avançadas**

### **1. Notificações Automáticas:**
- **Novo cliente:** Notifica administradores
- **Funcionário inativo:** Alerta de segurança
- **Login suspeito:** Detecção de intrusão

### **2. URLs de Ação:**
- Links diretos para páginas específicas
- Navegação contextual
- Melhora a experiência do usuário

### **3. Controle de Estado:**
- Notificações lidas/não lidas
- Contador em tempo real
- Interface responsiva

### **4. Limpeza Automática:**
- Remove notificações antigas
- Mantém performance do banco
- Configurável (atualmente 30 dias)

---

## 📱 **Responsividade e UX**

### **1. Design Responsivo:**
- Interface adaptada para mobile
- Botões touch-friendly
- Layout flexível

### **2. Feedback Visual:**
- Badges com contadores
- Estados visuais (lida/não lida)
- Animações suaves

### **3. Acessibilidade:**
- Contrastes adequados
- Textos descritivos
- Navegação por teclado

---

## 🔧 **Configurações e Manutenção**

### **1. Limpeza Manual:**
```php
// Limpar notificações antigas
$removed = Notification::cleanupOld();
echo "Removidas {$removed} notificações antigas";
```

### **2. Backup:**
- Notificações incluídas no backup automático
- Retenção configurável
- Logs de atividade

### **3. Monitoramento:**
- Logs de criação/leitura
- Métricas de engajamento
- Alertas de sistema

---

## 🎊 **Benefícios do Sistema**

### **✅ Comunicação Eficiente:**
- Notificações em tempo real
- Informações centralizadas
- Histórico completo

### **✅ Segurança:**
- Alertas de segurança
- Detecção de anomalias
- Auditoria completa

### **✅ Produtividade:**
- Informações importantes destacadas
- Links diretos para ações
- Interface intuitiva

### **✅ Manutenibilidade:**
- Código organizado e documentado
- Limpeza automática
- Performance otimizada

---

**🎉 O sistema de notificações do BccSistem é uma solução completa e robusta para comunicação interna, oferecendo funcionalidades avançadas de notificação, interface intuitiva e integração perfeita com o sistema de gestão!**
