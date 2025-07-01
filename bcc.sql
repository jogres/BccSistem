-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 25/06/2025 às 18:01
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

INSERT INTO `basic` (`idBasic`, `primeira`, `segunda`, `terceira`, `quarta`, `nome`, `segmento`, `idAdm`) VALUES
(8, 0.7, 0.1, 0.1, 0.1, 'Meia', 'Automóvel', 3),
(9, 0.7, 0.1, 0.1, 0.1, 'Meia', 'Imovel', 3),
(10, 0.1, 0.2, 0.1, 0.2, 'Meia', 'Imovel', 4),
(11, 0.1, 0.2, 0.1, 0.2, 'Meia', 'Automóvel', 4);

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

INSERT INTO `cad_adm` (`idAdm`, `cnpj`, `nome`) VALUES
(3, '59.956.185/0001-55', 'Consorcio Tradição'),
(4, '60.375.243/0001-36', 'Âncora Consórcios');

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

INSERT INTO `cad_cli` (`idCli`, `nome`, `cpf`, `idFun`, `endereco`, `telefone`, `tipo`, `descricao`, `cadDT`) VALUES
(90, 'Alencar Simon', '07083519994', 182347, 'Travessa José de Anchieta, 06, Bela Vista, Chapecó, SC, 89804-114', '(49)99950-8638', 'com_venda', 'Contrato Assinado', '2025-06-25 12:47:31'),
(91, 'Dardye Chaves Aragao', '11491438606', 165890, 'Antônio Costa, 318, Cintra, Monstes Claros, MG, 39400-394', '(38)98806-5859', 'com_venda', 'Contrato Assinado', '2025-06-25 12:54:24');

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
  `nivel` varchar(50) DEFAULT NULL,
  `foto` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



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

INSERT INTO `classic` (`idClassic`, `primeira`, `segunda`, `terceira`, `quarta`, `nome`, `segmento`, `idAdm`) VALUES
(6, 1, 0.1, 0.1, 0.1, 'Meia', 'Automóvel', 3),
(7, 1, 0.1, 0.1, 0.1, 'Meia', 'Imovel', 3),
(8, 0.2, 0.2, 0.2, 0.3, 'Meia', 'Imovel', 4),
(9, 0.2, 0.2, 0.2, 0.3, 'Meia', 'Automóvel', 4);

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

INSERT INTO `comissao` (`idCom`, `totalV`, `mesC`, `idFun`, `totalC`) VALUES
(3, 566298, '2025-06-01', 145678, 991.021),
(4, 566298, '2025-06-01', 182347, 991.021),
(5, 50000, '2025-06-01', 165890, 0);

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

--
-- Despejando dados para a tabela `master`
--

INSERT INTO `master` (`idMaster`, `primeira`, `segunda`, `terceira`, `quarta`, `nome`, `segmento`, `idAdm`) VALUES
(9, 1.2, 0.1, 0.1, 0.1, 'Meia', 'Automóvel', 3),
(10, 1.2, 0.1, 0.1, 0.1, 'Meia', 'Imovel', 3),
(11, 0.2, 0.3, 0.3, 0.3, 'Meia', 'Imovel', 4),
(12, 0.2, 0.3, 0.3, 0.3, 'Meia', 'Automóvel', 4);

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

INSERT INTO `notificacoes` (`id`, `idFun`, `mensagem`, `link`, `lida`, `data_criacao`, `parcela`, `idVenda`) VALUES
(5, 100234, 'Pagamento — Contrato 22094145 (R$ 566.298,00) — Diegho De Almeida Souza', '../../_html/_detalhes/detalhesVenda.php?idVenda=88', 1, '2025-06-16 00:00:00', 1, 88),
(6, 100234, 'Pagamento — Contrato 22094145 (R$ 566.298,00) — Diegho De Almeida Souza', '../../_html/_detalhes/detalhesVenda.php?idVenda=88', 0, '2025-07-16 00:00:00', 2, 88),
(7, 100234, 'Pagamento — Contrato 22094145 (R$ 566.298,00) — Diegho De Almeida Souza', '../../_html/_detalhes/detalhesVenda.php?idVenda=88', 0, '2025-08-16 00:00:00', 3, 88),
(8, 100234, 'Pagamento — Contrato 22094145 (R$ 566.298,00) — Diegho De Almeida Souza', '../../_html/_detalhes/detalhesVenda.php?idVenda=88', 0, '2025-09-16 00:00:00', 4, 88),
(9, 100234, 'Pagamento — Contrato 22092114 (R$ 50.000,00) — Bruna Marques Pereira', '../../_html/_detalhes/detalhesVenda.php?idVenda=89', 1, '2025-06-06 00:00:00', 1, 89),
(10, 100234, 'Pagamento — Contrato 22092114 (R$ 50.000,00) — Bruna Marques Pereira', '../../_html/_detalhes/detalhesVenda.php?idVenda=89', 0, '2025-07-06 00:00:00', 2, 89),
(11, 100234, 'Pagamento — Contrato 22092114 (R$ 50.000,00) — Bruna Marques Pereira', '../../_html/_detalhes/detalhesVenda.php?idVenda=89', 0, '2025-08-06 00:00:00', 3, 89),
(12, 100234, 'Pagamento — Contrato 22092114 (R$ 50.000,00) — Bruna Marques Pereira', '../../_html/_detalhes/detalhesVenda.php?idVenda=89', 0, '2025-09-06 00:00:00', 4, 89);

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

INSERT INTO `parcela1` (`idParcela1`, `idVenda`, `idFun`, `valor`, `dataConfirmacao`, `confirmada`) VALUES
(3, 88, 145678, 991.021, '2025-06-25 17:47:42', 1),
(4, 88, 182347, 991.021, '2025-06-25 17:47:42', 1),
(5, 89, 165890, 0, '2025-06-25 17:54:34', 1);

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

INSERT INTO `venda` (`id`, `idVenda`, `tipo`, `segmento`, `valor`, `dataV`, `idAdm`, `confirmada`) VALUES
(88, 22094145, 'Meia', 'Imovel', 566298, '2025-06-16', 3, 1),
(89, 22092114, 'Meia', 'Automóvel', 50000, '2025-06-06', 3, 1);

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

INSERT INTO `venda_cli` (`idCli`, `idVenda`) VALUES
(90, 88),
(91, 89);

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

INSERT INTO `venda_fun` (`idFun`, `idVenda`) VALUES
(145678, 88),
(165890, 89),
(182347, 88);

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
  MODIFY `idBasic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `cad_adm`
--
ALTER TABLE `cad_adm`
  MODIFY `idAdm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `cad_cli`
--
ALTER TABLE `cad_cli`
  MODIFY `idCli` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT de tabela `classic`
--
ALTER TABLE `classic`
  MODIFY `idClassic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `comissao`
--
ALTER TABLE `comissao`
  MODIFY `idCom` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `master`
--
ALTER TABLE `master`
  MODIFY `idMaster` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `parcela1`
--
ALTER TABLE `parcela1`
  MODIFY `idParcela1` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `parcela2`
--
ALTER TABLE `parcela2`
  MODIFY `idParcela2` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parcela3`
--
ALTER TABLE `parcela3`
  MODIFY `idParcela3` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `parcela4`
--
ALTER TABLE `parcela4`
  MODIFY `idParcela4` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `venda`
--
ALTER TABLE `venda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

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
CREATE DEFINER=`root`@`localhost` EVENT `evt_atualiza_nivel_fun` ON SCHEDULE EVERY 1 SECOND STARTS '2025-06-25 00:00:00' ON COMPLETION PRESERVE ENABLE COMMENT 'Atualiza nível de funcionários com base no total proporcional de' DO BEGIN
  -- 1) Soma proporcional das vendas do mês corrente para cada funcionário
  UPDATE cad_fun f
  LEFT JOIN (
    SELECT
      vf.idFun,
      SUM(v.valor / cnt.num_funcs) AS total_vendas
    FROM venda_fun vf
    JOIN venda v 
      ON v.id = vf.idVenda
    JOIN (
      SELECT
        idVenda,
        COUNT(*) AS num_funcs
      FROM venda_fun
      GROUP BY idVenda
    ) AS cnt 
      ON cnt.idVenda = v.id
    WHERE
      YEAR(v.dataV)  = YEAR(CURDATE())
      AND MONTH(v.dataV) = MONTH(CURDATE())
    GROUP BY vf.idFun
  ) AS s 
    ON s.idFun = f.idFun

  -- 2) Zera IDs de nível antes de reatribuir
  SET
    f.idMaster  = NULL,
    f.idClassic = NULL,
    f.idBasic   = NULL,

  -- 3) Reatribui faixas de nível conforme total proporcional
    f.idBasic   = CASE
                    WHEN COALESCE(s.total_vendas,0) > 215000
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

  -- 4) Atualiza o campo textual 'nivel'
    f.nivel     = CASE
                    WHEN COALESCE(s.total_vendas,0) <= 215000 THEN 'Aprendiz'
                    WHEN COALESCE(s.total_vendas,0) > 215000
                         AND COALESCE(s.total_vendas,0) <= 500000 THEN 'basic'
                    WHEN COALESCE(s.total_vendas,0) > 500000
                         AND COALESCE(s.total_vendas,0) <= 800000 THEN 'classic'
                    WHEN COALESCE(s.total_vendas,0) > 800000 THEN 'master'
                  END;
END$$

DELIMITER ;
COMMIT;

-- Ative o Event Scheduler se ainda não estiver ativo:
SET GLOBAL event_scheduler = ON;

DELIMITER $$
CREATE EVENT IF NOT EXISTS `evt_recalcula_comissoes`
  ON SCHEDULE
    EVERY 1 MINUTE
    STARTS CURRENT_TIMESTAMP
  ON COMPLETION PRESERVE
  ENABLE
  COMMENT 'Recalcula comissoes das parcelas confirmadas conforme nivel atual'
DO
BEGIN
  -- 1) Recalcula parcela1
  UPDATE parcela1 p
    JOIN venda v 
      ON v.id = p.idVenda
    JOIN cad_fun f 
      ON f.idFun = p.idFun
    LEFT JOIN basic   b ON f.nivel = 'basic'   AND b.idAdm = v.idAdm AND b.nome = v.tipo AND b.segmento = v.segmento
    LEFT JOIN classic c ON f.nivel = 'classic' AND c.idAdm = v.idAdm AND c.nome = v.tipo AND c.segmento = v.segmento
    LEFT JOIN master  m ON f.nivel = 'master'  AND m.idAdm = v.idAdm AND m.nome = v.tipo AND m.segmento = v.segmento
    JOIN (
      SELECT idVenda, COUNT(*) AS numFun
        FROM venda_fun
       GROUP BY idVenda
    ) cnt ON cnt.idVenda = v.id
  SET
    p.valor = (
      CASE f.nivel
        WHEN 'basic'   THEN b.primeira
        WHEN 'classic' THEN c.primeira
        WHEN 'master'  THEN m.primeira
        ELSE 0
      END / 100.0
    ) * (
      (CASE WHEN v.tipo = 'Meia Gazin' THEN v.valor/2 ELSE v.valor END)
      / cnt.numFun
    ),
    p.dataConfirmacao = NOW()
  WHERE p.confirmada = 1
    AND MONTH(p.dataConfirmacao) = MONTH(CURDATE())
    AND YEAR(p.dataConfirmacao) = YEAR(CURDATE());

  -- 2) Recalcula parcela2
  UPDATE parcela2 p
    JOIN venda v 
      ON v.id = p.idVenda
    JOIN cad_fun f 
      ON f.idFun = p.idFun
    LEFT JOIN basic   b ON f.nivel = 'basic'   AND b.idAdm = v.idAdm AND b.nome = v.tipo AND b.segmento = v.segmento
    LEFT JOIN classic c ON f.nivel = 'classic' AND c.idAdm = v.idAdm AND c.nome = v.tipo AND c.segmento = v.segmento
    LEFT JOIN master  m ON f.nivel = 'master'  AND m.idAdm = v.idAdm AND m.nome = v.tipo AND m.segmento = v.segmento
    JOIN (
      SELECT idVenda, COUNT(*) AS numFun
        FROM venda_fun
       GROUP BY idVenda
    ) cnt ON cnt.idVenda = v.id
  SET
    p.valor = (
      CASE f.nivel
        WHEN 'basic'   THEN b.segunda
        WHEN 'classic' THEN c.segunda
        WHEN 'master'  THEN m.segunda
        ELSE 0
      END / 100.0
    ) * (
      (CASE WHEN v.tipo = 'Meia Gazin' THEN v.valor/2 ELSE v.valor END)
      / cnt.numFun
    ),
    p.dataConfirmacao = NOW()
  WHERE p.confirmada = 1
    AND MONTH(p.dataConfirmacao) = MONTH(CURDATE())
    AND YEAR(p.dataConfirmacao) = YEAR(CURDATE());

  -- 3) Recalcula parcela3
  UPDATE parcela3 p
    JOIN venda v 
      ON v.id = p.idVenda
    JOIN cad_fun f 
      ON f.idFun = p.idFun
    LEFT JOIN basic   b ON f.nivel = 'basic'   AND b.idAdm = v.idAdm AND b.nome = v.tipo AND b.segmento = v.segmento
    LEFT JOIN classic c ON f.nivel = 'classic' AND c.idAdm = v.idAdm AND c.nome = v.tipo AND c.segmento = v.segmento
    LEFT JOIN master  m ON f.nivel = 'master'  AND m.idAdm = v.idAdm AND m.nome = v.tipo AND m.segmento = v.segmento
    JOIN (
      SELECT idVenda, COUNT(*) AS numFun
        FROM venda_fun
       GROUP BY idVenda
    ) cnt ON cnt.idVenda = v.id
  SET
    p.valor = (
      CASE f.nivel
        WHEN 'basic'   THEN b.terceira
        WHEN 'classic' THEN c.terceira
        WHEN 'master'  THEN m.terceira
        ELSE 0
      END / 100.0
    ) * (
      (CASE WHEN v.tipo = 'Meia Gazin' THEN v.valor/2 ELSE v.valor END)
      / cnt.numFun
    ),
    p.dataConfirmacao = NOW()
  WHERE p.confirmada = 1
    AND MONTH(p.dataConfirmacao) = MONTH(CURDATE())
    AND YEAR(p.dataConfirmacao) = YEAR(CURDATE());

  -- 4) Recalcula parcela4
  UPDATE parcela4 p
    JOIN venda v 
      ON v.id = p.idVenda
    JOIN cad_fun f 
      ON f.idFun = p.idFun
    LEFT JOIN basic   b ON f.nivel = 'basic'   AND b.idAdm = v.idAdm AND b.nome = v.tipo AND b.segmento = v.segmento
    LEFT JOIN classic c ON f.nivel = 'classic' AND c.idAdm = v.idAdm AND c.nome = v.tipo AND c.segmento = v.segmento
    LEFT JOIN master  m ON f.nivel = 'master'  AND m.idAdm = v.idAdm AND m.nome = v.tipo AND m.segmento = v.segmento
    JOIN (
      SELECT idVenda, COUNT(*) AS numFun
        FROM venda_fun
       GROUP BY idVenda
    ) cnt ON cnt.idVenda = v.id
  SET
    p.valor = (
      CASE f.nivel
        WHEN 'basic'   THEN b.quarta
        WHEN 'classic' THEN c.quarta
        WHEN 'master'  THEN m.quarta
        ELSE 0
      END / 100.0
    ) * (
      (CASE WHEN v.tipo = 'Meia Gazin' THEN v.valor/2 ELSE v.valor END)
      / cnt.numFun
    ),
    p.dataConfirmacao = NOW()
  WHERE p.confirmada = 1
    AND MONTH(p.dataConfirmacao) = MONTH(CURDATE())
    AND YEAR(p.dataConfirmacao) = YEAR(CURDATE());

  -- 5) Atualiza totais na tabela comissao
  UPDATE comissao c
    JOIN (
      SELECT vf.idFun,
             DATE_FORMAT(v.dataV, '%Y-%m-01') AS mesC,
             SUM(v.valor) AS totalV
        FROM venda_fun vf
        JOIN venda v 
          ON v.id = vf.idVenda
        JOIN parcela1 p1
          ON p1.idVenda = v.id
         AND p1.idFun   = vf.idFun
         AND p1.confirmada = 1
       WHERE MONTH(v.dataV) = MONTH(CURDATE())
         AND YEAR(v.dataV)  = YEAR(CURDATE())
       GROUP BY vf.idFun, mesC
    ) tv ON c.idFun = tv.idFun AND c.mesC = tv.mesC
    JOIN (
      SELECT t.idFun,
             DATE_FORMAT(t.dataConfirmacao, '%Y-%m-01') AS mesC,
             SUM(t.valor) AS totalC
        FROM (
          SELECT idFun, dataConfirmacao, valor FROM parcela1 WHERE confirmada = 1
          UNION ALL
          SELECT idFun, dataConfirmacao, valor FROM parcela2 WHERE confirmada = 1
          UNION ALL
          SELECT idFun, dataConfirmacao, valor FROM parcela3 WHERE confirmada = 1
          UNION ALL
          SELECT idFun, dataConfirmacao, valor FROM parcela4 WHERE confirmada = 1
        ) AS t
       WHERE MONTH(t.dataConfirmacao) = MONTH(CURDATE())
         AND YEAR(t.dataConfirmacao)  = YEAR(CURDATE())
       GROUP BY t.idFun, mesC
    ) tc ON c.idFun = tc.idFun AND c.mesC = tc.mesC
  SET
    c.totalV = tv.totalV,
    c.totalC = tc.totalC;

END$$
DELIMITER ;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
