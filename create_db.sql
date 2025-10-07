-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/09/2025 às 11:35
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `bcc`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tabela` varchar(32) NOT NULL,
  `registro_id` bigint(20) UNSIGNED NOT NULL,
  `acao` enum('CREATE','UPDATE','DELETE') NOT NULL,
  `dados_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_antes`)),
  `dados_depois` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dados_depois`)),
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `audit_logs`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `estado` char(2) NOT NULL,
  `criado_por` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `clientes`
--


--
-- Acionadores `clientes`
--
DELIMITER $$
CREATE TRIGGER `trg_clientes_ai` AFTER INSERT ON `clientes` FOR EACH ROW BEGIN
  INSERT INTO audit_logs (tabela, registro_id, acao, dados_antes, dados_depois, actor_id)
  VALUES (
    'clientes',
    NEW.id,
    'CREATE',
    NULL,
    JSON_OBJECT(
      'id', NEW.id, 'nome', NEW.nome, 'telefone', NEW.telefone,
      'cidade', NEW.cidade, 'estado', NEW.estado,
      'criado_por', NEW.criado_por, 'created_at', NEW.created_at,
      'updated_at', NEW.updated_at, 'deleted_at', NEW.deleted_at
    ),
    COALESCE(@actor_id, NEW.criado_por)
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_clientes_au` AFTER UPDATE ON `clientes` FOR EACH ROW BEGIN
  INSERT INTO audit_logs (tabela, registro_id, acao, dados_antes, dados_depois, actor_id)
  VALUES (
    'clientes',
    NEW.id,
    'UPDATE',
    JSON_OBJECT(
      'id', OLD.id, 'nome', OLD.nome, 'telefone', OLD.telefone,
      'cidade', OLD.cidade, 'estado', OLD.estado,
      'criado_por', OLD.criado_por, 'created_at', OLD.created_at,
      'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at
    ),
    JSON_OBJECT(
      'id', NEW.id, 'nome', NEW.nome, 'telefone', NEW.telefone,
      'cidade', NEW.cidade, 'estado', NEW.estado,
      'criado_por', NEW.criado_por, 'created_at', NEW.created_at,
      'updated_at', NEW.updated_at, 'deleted_at', NEW.deleted_at
    ),
    COALESCE(@actor_id, NEW.criado_por)
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_clientes_bd` BEFORE DELETE ON `clientes` FOR EACH ROW BEGIN
  INSERT INTO audit_logs (tabela, registro_id, acao, dados_antes, dados_depois, actor_id)
  VALUES (
    'clientes',
    OLD.id,
    'DELETE',
    JSON_OBJECT(
      'id', OLD.id, 'nome', OLD.nome, 'telefone', OLD.telefone,
      'cidade', OLD.cidade, 'estado', OLD.estado,
      'criado_por', OLD.criado_por, 'created_at', OLD.created_at,
      'updated_at', OLD.updated_at, 'deleted_at', OLD.deleted_at
    ),
    NULL,
    COALESCE(@actor_id, OLD.criado_por)
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(120) NOT NULL,
  `login` varchar(64) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `is_ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `funcionarios`
--

INSERT INTO `funcionarios` (`id`, `nome`, `login`, `senha_hash`, `role_id`, `is_ativo`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin', '$2y$10$qNlr0jrgwzgrKKNDxzBFcO4eLbi2a1Pw5ZvVjsNw3NBjITs4ujVqm', 1, 1, '2025-09-17 05:30:22', '2025-09-17 05:30:22');

--
-- Acionadores `funcionarios`
--
DELIMITER $$
CREATE TRIGGER `trg_func_ai` AFTER INSERT ON `funcionarios` FOR EACH ROW BEGIN
  INSERT INTO audit_logs (tabela, registro_id, acao, dados_antes, dados_depois, actor_id)
  VALUES (
    'funcionarios',
    NEW.id,
    'CREATE',
    NULL,
    JSON_OBJECT(
      'id', NEW.id, 'nome', NEW.nome, 'login', NEW.login,
      'role_id', NEW.role_id, 'is_ativo', NEW.is_ativo,
      'created_at', NEW.created_at, 'updated_at', NEW.updated_at,
      'senha_hash', '*masked*'
    ),
    COALESCE(@actor_id, NEW.id)
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_func_au` AFTER UPDATE ON `funcionarios` FOR EACH ROW BEGIN
  INSERT INTO audit_logs (tabela, registro_id, acao, dados_antes, dados_depois, actor_id)
  VALUES (
    'funcionarios',
    NEW.id,
    'UPDATE',
    JSON_OBJECT(
      'id', OLD.id, 'nome', OLD.nome, 'login', OLD.login,
      'role_id', OLD.role_id, 'is_ativo', OLD.is_ativo,
      'created_at', OLD.created_at, 'updated_at', OLD.updated_at,
      'senha_hash', '*masked*'
    ),
    JSON_OBJECT(
      'id', NEW.id, 'nome', NEW.nome, 'login', NEW.login,
      'role_id', NEW.role_id, 'is_ativo', NEW.is_ativo,
      'created_at', NEW.created_at, 'updated_at', NEW.updated_at,
      'senha_hash', '*masked*'
    ),
    COALESCE(@actor_id, NEW.id)
  );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_func_bd` BEFORE DELETE ON `funcionarios` FOR EACH ROW BEGIN
  INSERT INTO audit_logs (tabela, registro_id, acao, dados_antes, dados_depois, actor_id)
  VALUES (
    'funcionarios',
    OLD.id,
    'DELETE',
    JSON_OBJECT(
      'id', OLD.id, 'nome', OLD.nome, 'login', OLD.login,
      'role_id', OLD.role_id, 'is_ativo', OLD.is_ativo,
      'created_at', OLD.created_at, 'updated_at', OLD.updated_at,
      'senha_hash', '*masked*'
    ),
    NULL,
    COALESCE(@actor_id, OLD.id)
  );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `funcionario_id` bigint(20) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` timestamp NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nome` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `roles`
--

INSERT INTO `roles` (`id`, `nome`) VALUES
(1, 'ADMIN'),
(3, 'APRENDIZ'),
(2, 'PADRAO');

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_clientes_ativos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_clientes_ativos` (
`id` bigint(20) unsigned
,`nome` varchar(120)
,`telefone` varchar(20)
,`cidade` varchar(100)
,`estado` char(2)
,`criado_por` bigint(20) unsigned
,`created_at` timestamp
,`updated_at` timestamp
,`deleted_at` timestamp
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_clientes_ativos`
--
DROP TABLE IF EXISTS `vw_clientes_ativos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_clientes_ativos`  AS SELECT `clientes`.`id` AS `id`, `clientes`.`nome` AS `nome`, `clientes`.`telefone` AS `telefone`, `clientes`.`cidade` AS `cidade`, `clientes`.`estado` AS `estado`, `clientes`.`criado_por` AS `criado_por`, `clientes`.`created_at` AS `created_at`, `clientes`.`updated_at` AS `updated_at`, `clientes`.`deleted_at` AS `deleted_at` FROM `clientes` WHERE `clientes`.`deleted_at` is null ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_tbl_reg` (`tabela`,`registro_id`),
  ADD KEY `idx_audit_created` (`created_at`),
  ADD KEY `fk_audit_logs_actor_setnull` (`actor_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clientes_telefone_ativo` (`telefone`,`deleted_at`),
  ADD KEY `idx_clientes_criado_por` (`criado_por`),
  ADD KEY `idx_clientes_estado` (`estado`),
  ADD KEY `idx_clientes_telefone` (`telefone`),
  ADD KEY `idx_clientes_deleted_at` (`deleted_at`),
  ADD KEY `idx_clientes_uf_cidade` (`estado`,`cidade`);
ALTER TABLE `clientes` ADD FULLTEXT KEY `ft_clientes_nome` (`nome`);

--
-- Índices de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `fk_func_role` (`role_id`);

--
-- Índices de tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token`),
  ADD KEY `idx_funcionario_id` (`funcionario_id`),
  ADD KEY `idx_expira_em` (`expira_em`),
  ADD KEY `idx_usado` (`usado`);

--
-- Índices de tabela `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_id`) REFERENCES `funcionarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_audit_logs_actor_setnull` FOREIGN KEY (`actor_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cli_func` FOREIGN KEY (`criado_por`) REFERENCES `funcionarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD CONSTRAINT `fk_func_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
