-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 09/06/2025 às 20:39
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
-- Estrutura para tabela `basic`
--

CREATE TABLE `basic` (
  `idBasic` int(11) NOT NULL,
  `primeira` float NOT NULL,
  `segunda` float NOT NULL,
  `terceira` float NOT NULL,
  `quarta` float NOT NULL,
  `nome` varchar(100) NOT NULL,
  `segmento` varchar(255) NOT NULL,
  `idAdm` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `basic`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `cad_adm`
--

CREATE TABLE `cad_adm` (
  `idAdm` int(11) NOT NULL,
  `cnpj` varchar(18) NOT NULL,
  `nome` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cad_adm`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cad_cli`
--

CREATE TABLE `cad_cli` (
  `idCli` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `idFun` int(11) NOT NULL,
  `endereco` varchar(200) NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `descricao` text DEFAULT NULL,
  `cadDT` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cad_cli`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `cad_fun`
--

CREATE TABLE `cad_fun` (
  `idFun` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `endereco` varchar(200) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `dataN` date NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `acesso` varchar(100) NOT NULL,
  `ativo` char(10) NOT NULL,
  `idMaster` int(11) DEFAULT NULL,
  `idClassic` int(11) DEFAULT NULL,
  `idBasic` int(11) DEFAULT NULL,
  `nivel` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cad_fun`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `classic`
--

CREATE TABLE `classic` (
  `idClassic` int(11) NOT NULL,
  `primeira` float NOT NULL,
  `segunda` float NOT NULL,
  `terceira` float NOT NULL,
  `quarta` float NOT NULL,
  `nome` varchar(100) NOT NULL,
  `segmento` varchar(255) NOT NULL,
  `idAdm` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `classic`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `comissao`
--

CREATE TABLE `comissao` (
  `idCom` int(11) NOT NULL,
  `totalV` float NOT NULL,
  `mesC` date NOT NULL,
  `idFun` int(11) NOT NULL,
  `totalC` float NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `comissao`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `master`
--

CREATE TABLE `master` (
  `idMaster` int(11) NOT NULL,
  `primeira` float NOT NULL,
  `segunda` float NOT NULL,
  `terceira` float NOT NULL,
  `quarta` float NOT NULL,
  `nome` varchar(100) NOT NULL,
  `segmento` varchar(255) NOT NULL,
  `idAdm` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `idFun` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` datetime DEFAULT current_timestamp(),
  `parcela` int(11) NOT NULL,
  `idVenda` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `notificacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `parcela1`
--

CREATE TABLE `parcela1` (
  `idParcela1` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL,
  `idFun` int(11) NOT NULL,
  `valor` float NOT NULL,
  `dataConfirmacao` datetime NOT NULL,
  `confirmada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parcela1`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `parcela2`
--

CREATE TABLE `parcela2` (
  `idParcela2` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL,
  `idFun` int(11) NOT NULL,
  `valor` float NOT NULL,
  `dataConfirmacao` datetime NOT NULL,
  `confirmada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parcela2`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `parcela3`
--

CREATE TABLE `parcela3` (
  `idParcela3` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL,
  `idFun` int(11) NOT NULL,
  `valor` float NOT NULL,
  `dataConfirmacao` datetime NOT NULL,
  `confirmada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parcela3`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `parcela4`
--

CREATE TABLE `parcela4` (
  `idParcela4` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL,
  `idFun` int(11) NOT NULL,
  `valor` float NOT NULL,
  `dataConfirmacao` datetime NOT NULL,
  `confirmada` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `parcela4`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `venda`
--

CREATE TABLE `venda` (
  `id` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `segmento` varchar(100) NOT NULL,
  `valor` float NOT NULL,
  `dataV` date NOT NULL,
  `idAdm` int(11) NOT NULL,
  `confirmada` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `venda`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `venda_cli`
--

CREATE TABLE `venda_cli` (
  `idCli` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `venda_cli`
--



-- --------------------------------------------------------

--
-- Estrutura para tabela `venda_fun`
--

CREATE TABLE `venda_fun` (
  `idFun` int(11) NOT NULL,
  `idVenda` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `venda_fun`
--


--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `basic`
--
ALTER TABLE `basic`
  ADD PRIMARY KEY (`idBasic`),
  ADD KEY `fk_basic_adm` (`idAdm`);

--
-- Índices de tabela `cad_adm`
--
ALTER TABLE `cad_adm`
  ADD PRIMARY KEY (`idAdm`),
  ADD UNIQUE KEY `cnpj` (`cnpj`);

--
-- Índices de tabela `cad_cli`
--
ALTER TABLE `cad_cli`
  ADD PRIMARY KEY (`idCli`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `fk_cli_fun` (`idFun`);

--
-- Índices de tabela `cad_fun`
--
ALTER TABLE `cad_fun`
  ADD PRIMARY KEY (`idFun`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `classic`
--
ALTER TABLE `classic`
  ADD PRIMARY KEY (`idClassic`),
  ADD KEY `fk_classic_adm` (`idAdm`);

--
-- Índices de tabela `comissao`
--
ALTER TABLE `comissao`
  ADD PRIMARY KEY (`idCom`),
  ADD KEY `fk_comissao_fun` (`idFun`);

--
-- Índices de tabela `master`
--
ALTER TABLE `master`
  ADD PRIMARY KEY (`idMaster`),
  ADD KEY `fk_master_adm` (`idAdm`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idFun` (`idFun`),
  ADD KEY `fk_notificacoes_venda` (`idVenda`);

--
-- Índices de tabela `parcela1`
--
ALTER TABLE `parcela1`
  ADD PRIMARY KEY (`idParcela1`),
  ADD KEY `fk_parcela1_venda` (`idVenda`),
  ADD KEY `fk_parcela1_fun` (`idFun`);

--
-- Índices de tabela `parcela2`
--
ALTER TABLE `parcela2`
  ADD PRIMARY KEY (`idParcela2`),
  ADD KEY `fk_parcela2_venda` (`idVenda`),
  ADD KEY `fk_parcela2_fun` (`idFun`);

--
-- Índices de tabela `parcela3`
--
ALTER TABLE `parcela3`
  ADD PRIMARY KEY (`idParcela3`),
  ADD KEY `fk_parcela3_venda` (`idVenda`),
  ADD KEY `fk_parcela3_fun` (`idFun`);

--
-- Índices de tabela `parcela4`
--
ALTER TABLE `parcela4`
  ADD PRIMARY KEY (`idParcela4`),
  ADD KEY `fk_parcela4_venda` (`idVenda`),
  ADD KEY `fk_parcela4_fun` (`idFun`);

--
-- Índices de tabela `venda`
--
ALTER TABLE `venda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_venda_adm` (`idAdm`);

--
-- Índices de tabela `venda_cli`
--
ALTER TABLE `venda_cli`
  ADD PRIMARY KEY (`idCli`,`idVenda`),
  ADD KEY `fk_vendacli_venda` (`idVenda`);

--
-- Índices de tabela `venda_fun`
--
ALTER TABLE `venda_fun`
  ADD PRIMARY KEY (`idFun`,`idVenda`),
  ADD KEY `fk_vendafun_venda` (`idVenda`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `basic`
--
ALTER TABLE `basic`
  MODIFY `idBasic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `cad_adm`
--
ALTER TABLE `cad_adm`
  MODIFY `idAdm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `cad_cli`
--
ALTER TABLE `cad_cli`
  MODIFY `idCli` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de tabela `classic`
--
ALTER TABLE `classic`
  MODIFY `idClassic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `comissao`
--
ALTER TABLE `comissao`
  MODIFY `idCom` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de tabela `master`
--
ALTER TABLE `master`
  MODIFY `idMaster` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `parcela1`
--
ALTER TABLE `parcela1`
  MODIFY `idParcela1` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `parcela2`
--
ALTER TABLE `parcela2`
  MODIFY `idParcela2` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `parcela3`
--
ALTER TABLE `parcela3`
  MODIFY `idParcela3` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `parcela4`
--
ALTER TABLE `parcela4`
  MODIFY `idParcela4` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `venda`
--
ALTER TABLE `venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `basic`
--
ALTER TABLE `basic`
  ADD CONSTRAINT `fk_basic_adm` FOREIGN KEY (`idAdm`) REFERENCES `cad_adm` (`idAdm`);

--
-- Restrições para tabelas `cad_cli`
--
ALTER TABLE `cad_cli`
  ADD CONSTRAINT `fk_cli_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`);

--
-- Restrições para tabelas `classic`
--
ALTER TABLE `classic`
  ADD CONSTRAINT `fk_classic_adm` FOREIGN KEY (`idAdm`) REFERENCES `cad_adm` (`idAdm`);

--
-- Restrições para tabelas `comissao`
--
ALTER TABLE `comissao`
  ADD CONSTRAINT `fk_comissao_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`);

--
-- Restrições para tabelas `master`
--
ALTER TABLE `master`
  ADD CONSTRAINT `fk_master_adm` FOREIGN KEY (`idAdm`) REFERENCES `cad_adm` (`idAdm`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `fk_notificacoes_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`),
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`);

--
-- Restrições para tabelas `parcela1`
--
ALTER TABLE `parcela1`
  ADD CONSTRAINT `fk_parcela1_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`),
  ADD CONSTRAINT `fk_parcela1_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `parcela2`
--
ALTER TABLE `parcela2`
  ADD CONSTRAINT `fk_parcela2_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`),
  ADD CONSTRAINT `fk_parcela2_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `parcela3`
--
ALTER TABLE `parcela3`
  ADD CONSTRAINT `fk_parcela3_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`),
  ADD CONSTRAINT `fk_parcela3_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `parcela4`
--
ALTER TABLE `parcela4`
  ADD CONSTRAINT `fk_parcela4_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`),
  ADD CONSTRAINT `fk_parcela4_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `venda`
--
ALTER TABLE `venda`
  ADD CONSTRAINT `fk_venda_adm` FOREIGN KEY (`idAdm`) REFERENCES `cad_adm` (`idAdm`);

--
-- Restrições para tabelas `venda_cli`
--
ALTER TABLE `venda_cli`
  ADD CONSTRAINT `fk_vendacli_cli` FOREIGN KEY (`idCli`) REFERENCES `cad_cli` (`idCli`),
  ADD CONSTRAINT `fk_vendacli_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `venda_fun`
--
ALTER TABLE `venda_fun`
  ADD CONSTRAINT `fk_vendafun_fun` FOREIGN KEY (`idFun`) REFERENCES `cad_fun` (`idFun`),
  ADD CONSTRAINT `fk_vendafun_venda` FOREIGN KEY (`idVenda`) REFERENCES `venda` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Eventos
--
CREATE DEFINER=`root`@`localhost` EVENT `evt_atualiza_nivel_fun` ON SCHEDULE EVERY 1 SECOND STARTS '2025-05-22 11:45:18' ON COMPLETION PRESERVE ENABLE COMMENT 'Atualiza nível de funcionários com base nas vendas do mês atual' DO BEGIN
  -- 4.1) Soma vendas do mês corrente para cada funcionário
  UPDATE cad_fun f
  LEFT JOIN (
    SELECT
      vf.idFun,
      SUM(v.valor) AS total_vendas
    FROM venda_fun vf
    JOIN venda v 
      ON v.id = vf.idVenda     -- ajuste do nome correto da coluna :contentReference[oaicite:8]{index=8}
    WHERE
      YEAR(v.dataV)  = YEAR(CURDATE())
      AND MONTH(v.dataV) = MONTH(CURDATE())
    GROUP BY vf.idFun
  ) s ON s.idFun = f.idFun

  -- 4.2) Zera todos os níveis antes de reatribuir
  SET
    f.idMaster  = NULL,
    f.idClassic = NULL,
    f.idBasic   = NULL,

    -- 4.3) Atribui os IDs conforme faixas de total_vendas
    f.idBasic   = CASE
                    WHEN COALESCE(s.total_vendas,0) > 400000
                         AND COALESCE(s.total_vendas,0) <= 500000 THEN 1
                    ELSE NULL
                  END,
    f.idClassic = CASE
                    WHEN COALESCE(s.total_vendas,0) > 500000
                         AND COALESCE(s.total_vendas,0) <= 800000 THEN 1
                    ELSE NULL
                  END,
    f.idMaster  = CASE
                    WHEN COALESCE(s.total_vendas,0) > 800000 THEN 1
                    ELSE NULL
                  END,

    -- 4.4) Atualiza o campo textual 'nivel'
    f.nivel     = CASE
                    WHEN COALESCE(s.total_vendas,0) <= 400000 THEN 'Aprendiz'
                    WHEN COALESCE(s.total_vendas,0) > 400000
                         AND COALESCE(s.total_vendas,0) <= 500000 THEN 'basic'
                    WHEN COALESCE(s.total_vendas,0) > 500000
                         AND COALESCE(s.total_vendas,0) <= 800000 THEN 'classic'
                    WHEN COALESCE(s.total_vendas,0) > 800000 THEN 'master'
                  END;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
