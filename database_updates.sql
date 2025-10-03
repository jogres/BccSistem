-- Atualizações do banco de dados para as novas funcionalidades
-- Execute este script após o create_db.sql

-- Tabela para tokens de recuperação de senha
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para tentativas de recuperação de senha
CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `action` enum('request','reset') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_reset_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para notificações
CREATE TABLE IF NOT EXISTS `notifications` (
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

-- Adicionar campos de auditoria para login
ALTER TABLE `funcionarios` 
ADD COLUMN `last_login_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
ADD COLUMN `last_login_ip` varchar(45) DEFAULT NULL AFTER `last_login_at`,
ADD COLUMN `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login_ip`,
ADD COLUMN `locked_until` timestamp NULL DEFAULT NULL AFTER `failed_login_attempts`;

-- Tabela para logs de atividade
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar campos extras aos clientes
ALTER TABLE `clientes` 
ADD COLUMN `email` varchar(255) DEFAULT NULL AFTER `telefone`,
ADD COLUMN `cpf` varchar(14) DEFAULT NULL AFTER `email`,
ADD COLUMN `cep` varchar(9) DEFAULT NULL AFTER `cidade`,
ADD COLUMN `endereco` varchar(255) DEFAULT NULL AFTER `cep`,
ADD COLUMN `observacoes` text DEFAULT NULL AFTER `endereco`,
ADD UNIQUE KEY `uq_clientes_cpf_ativo` (`cpf`, `deleted_at`),
ADD KEY `idx_clientes_email` (`email`),
ADD KEY `idx_clientes_cpf` (`cpf`),
ADD KEY `idx_clientes_cep` (`cep`);

-- Tabela para configurações do sistema
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT INTO `system_settings` (`key`, `value`, `description`) VALUES
('backup_enabled', '1', 'Backup automático habilitado'),
('backup_frequency', 'daily', 'Frequência do backup (daily, weekly, monthly)'),
('max_login_attempts', '5', 'Máximo de tentativas de login'),
('session_timeout', '7200', 'Timeout da sessão em segundos'),
('maintenance_mode', '0', 'Modo de manutenção ativo'),
('email_notifications', '1', 'Notificações por email habilitadas'),
('dashboard_cache_ttl', '300', 'TTL do cache do dashboard em segundos');

-- Tabela para sessões (melhor controle de sessões)
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionais para performance
ALTER TABLE `clientes` ADD FULLTEXT KEY `ft_clientes_search` (`nome`, `telefone`, `cidade`, `observacoes`);
ALTER TABLE `funcionarios` ADD FULLTEXT KEY `ft_funcionarios_search` (`nome`, `login`);

-- Triggers para logs de atividade
DELIMITER $$

CREATE TRIGGER `trg_clientes_activity_log` AFTER INSERT ON `clientes` FOR EACH ROW 
BEGIN
  INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
  VALUES (
    NEW.criado_por,
    'client_created',
    CONCAT('Cliente "', NEW.nome, '" cadastrado'),
    COALESCE(@current_ip, 'system'),
    COALESCE(@current_user_agent, 'system')
  );
END$$

CREATE TRIGGER `trg_clientes_activity_log_update` AFTER UPDATE ON `clientes` FOR EACH ROW 
BEGIN
  INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent)
  VALUES (
    COALESCE(@current_user_id, NEW.criado_por),
    'client_updated',
    CONCAT('Cliente "', NEW.nome, '" atualizado'),
    COALESCE(@current_ip, 'system'),
    COALESCE(@current_user_agent, 'system')
  );
END$$

DELIMITER ;
