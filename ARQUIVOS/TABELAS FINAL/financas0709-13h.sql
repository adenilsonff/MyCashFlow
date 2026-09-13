-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/09/2026 às 18:01
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
-- Banco de dados: `financas`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `acoes_internacionais`
--

CREATE TABLE `acoes_internacionais` (
  `id` int(11) NOT NULL,
  `ticker` varchar(20) NOT NULL,
  `quantidade` decimal(18,4) NOT NULL,
  `valor_unitario` decimal(18,4) NOT NULL,
  `valor_investido` decimal(18,2) NOT NULL,
  `data` date NOT NULL,
  `valor_mercado` decimal(18,4) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `tipo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `acoes_nacionais`
--

CREATE TABLE `acoes_nacionais` (
  `id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `data` date NOT NULL,
  `valor_mercado` decimal(15,2) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartoes`
--

CREATE TABLE `cartoes` (
  `id` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `parcela` int(11) NOT NULL,
  `paga` tinyint(1) NOT NULL DEFAULT 0,
  `fitid` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `categoria` varchar(20) NOT NULL DEFAULT 'pessoal',
  `valor_total` decimal(10,2) NOT NULL,
  `total_parcelas` int(11) NOT NULL DEFAULT 1,
  `data_compra` date NOT NULL,
  `origem` varchar(20) NOT NULL,
  `identificador_ofx` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('unica','mensal') NOT NULL,
  `categoria` enum('pessoal','conjunta') NOT NULL,
  `vencimento` date NOT NULL,
  `paga` tinyint(1) NOT NULL DEFAULT 0,
  `valor` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `contas`
--

INSERT INTO `contas` (`id`, `nome`, `tipo`, `categoria`, `vencimento`, `paga`, `valor`, `criado_em`) VALUES
(1, 'Celular', 'mensal', 'pessoal', '2026-09-05', 0, 54.00, '2026-09-07 15:48:18'),
(2, 'Celular', 'mensal', 'pessoal', '2026-10-05', 0, 54.00, '2026-09-07 15:48:18'),
(3, 'Celular', 'mensal', 'pessoal', '2026-11-05', 0, 54.00, '2026-09-07 15:48:18'),
(4, 'Celular', 'mensal', 'pessoal', '2026-12-05', 0, 54.00, '2026-09-07 15:48:18'),
(5, 'Celular', 'mensal', 'pessoal', '2027-01-05', 0, 54.00, '2026-09-07 15:48:18'),
(6, 'Celular', 'mensal', 'pessoal', '2027-02-05', 0, 54.00, '2026-09-07 15:48:18'),
(7, 'Celular', 'mensal', 'pessoal', '2027-03-05', 0, 54.00, '2026-09-07 15:48:18'),
(8, 'Celular', 'mensal', 'pessoal', '2027-04-05', 0, 54.00, '2026-09-07 15:48:18'),
(9, 'Celular', 'mensal', 'pessoal', '2027-05-05', 0, 54.00, '2026-09-07 15:48:18'),
(10, 'Celular', 'mensal', 'pessoal', '2027-06-05', 0, 54.00, '2026-09-07 15:48:18'),
(11, 'Celular', 'mensal', 'pessoal', '2027-07-05', 0, 54.00, '2026-09-07 15:48:18'),
(12, 'Celular', 'mensal', 'pessoal', '2027-08-05', 0, 54.00, '2026-09-07 15:48:18'),
(13, 'IRPF', 'unica', 'pessoal', '2026-09-30', 0, 555.00, '2026-09-07 15:48:43'),
(14, 'Luz', 'mensal', 'conjunta', '2026-09-10', 0, 120.00, '2026-09-07 15:49:25'),
(15, 'Luz', 'mensal', 'conjunta', '2026-10-10', 0, 120.00, '2026-09-07 15:49:25'),
(16, 'Luz', 'mensal', 'conjunta', '2026-11-10', 0, 120.00, '2026-09-07 15:49:25'),
(17, 'Luz', 'mensal', 'conjunta', '2026-12-10', 0, 120.00, '2026-09-07 15:49:25'),
(18, 'Luz', 'mensal', 'conjunta', '2027-01-10', 0, 120.00, '2026-09-07 15:49:25'),
(19, 'Luz', 'mensal', 'conjunta', '2027-02-10', 0, 120.00, '2026-09-07 15:49:25'),
(20, 'Luz', 'mensal', 'conjunta', '2027-03-10', 0, 120.00, '2026-09-07 15:49:25'),
(21, 'Luz', 'mensal', 'conjunta', '2027-04-10', 0, 120.00, '2026-09-07 15:49:25'),
(22, 'Luz', 'mensal', 'conjunta', '2027-05-10', 0, 120.00, '2026-09-07 15:49:25'),
(23, 'Luz', 'mensal', 'conjunta', '2027-06-10', 0, 120.00, '2026-09-07 15:49:25'),
(24, 'Luz', 'mensal', 'conjunta', '2027-07-10', 0, 120.00, '2026-09-07 15:49:25'),
(25, 'Luz', 'mensal', 'conjunta', '2027-08-10', 0, 120.00, '2026-09-07 15:49:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretoras`
--

CREATE TABLE `corretoras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `corretoras`
--

INSERT INTO `corretoras` (`id`, `nome`) VALUES
(1, 'CLEAR');

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretora_taxas`
--

CREATE TABLE `corretora_taxas` (
  `id` int(11) NOT NULL,
  `corretora_id` int(11) NOT NULL,
  `nome_taxa` varchar(100) NOT NULL,
  `percentual` decimal(10,5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `corretora_taxas`
--

INSERT INTO `corretora_taxas` (`id`, `corretora_id`, `nome_taxa`, `percentual`) VALUES
(1, 1, 'Liquidação', 0.01799),
(2, 1, 'Emolumentos', 0.00499);

-- --------------------------------------------------------

--
-- Estrutura para tabela `div_datacom`
--

CREATE TABLE `div_datacom` (
  `id` int(11) NOT NULL,
  `id_acao` int(11) NOT NULL,
  `datacom` date NOT NULL,
  `datapag` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('DIV','JCP') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ofx_importacoes`
--

CREATE TABLE `ofx_importacoes` (
  `id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `hash_arquivo` varchar(64) NOT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `quantidade_transacoes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `operacoes`
--

CREATE TABLE `operacoes` (
  `id` int(11) NOT NULL,
  `corretora_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `acao` varchar(20) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_compra` decimal(15,2) DEFAULT NULL,
  `valor_venda` decimal(15,2) DEFAULT NULL,
  `total_compra` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_venda` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_operacao` decimal(15,2) NOT NULL DEFAULT 0.00,
  `lucro_bruto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `taxas` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deducao_1` decimal(15,2) NOT NULL DEFAULT 0.00,
  `imposto_20` decimal(15,2) NOT NULL DEFAULT 0.00,
  `lucro_desc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `darf` decimal(15,2) NOT NULL DEFAULT 0.00,
  `lucro_final` decimal(15,2) NOT NULL DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `operacoes`
--

INSERT INTO `operacoes` (`id`, `corretora_id`, `data`, `acao`, `quantidade`, `valor_compra`, `valor_venda`, `total_compra`, `total_venda`, `valor_operacao`, `lucro_bruto`, `taxas`, `deducao_1`, `imposto_20`, `lucro_desc`, `darf`, `lucro_final`, `criado_em`) VALUES
(1, 1, '2026-08-24', 'VALE3', 1500, 76.56, 77.03, 115405.00, 115875.00, 231280.00, 470.00, 53.19, 4.17, 130.41, 416.81, 79.19, 333.45, '2026-09-07 13:26:56'),
(2, 1, '2026-08-25', 'VALE3', 3000, 77.36, 77.49, 232067.00, 232455.00, 464522.00, 388.00, 106.83, 2.81, 56.65, 281.17, 53.42, 224.94, '2026-09-07 13:29:20'),
(3, 1, '2026-08-26', 'VAL.PET', 5800, 54.86, 54.84, 318178.00, 318086.00, 636264.00, -92.00, 146.33, 0.00, 0.00, -238.33, 0.00, -238.33, '2026-09-07 13:32:17'),
(4, 1, '2026-08-27', 'PETR4', 1100, 42.79, 42.63, 47070.00, 46894.00, 93964.00, -176.00, 33.11, 0.00, 0.00, -209.11, 0.00, -209.11, '2026-09-07 13:34:39'),
(5, 1, '2026-08-28', 'PETR4', 2000, 43.11, 42.97, 86210.00, 85940.00, 172150.00, -270.00, 53.11, 0.00, 0.00, -323.11, 0.00, -323.11, '2026-09-07 13:37:29'),
(6, 1, '2026-08-31', 'PE.EM.CX', 7500, 42.88, 42.86, 321599.00, 321480.00, 643079.00, -119.00, 147.90, 0.00, 0.00, -266.90, 0.00, -266.90, '2026-09-07 13:41:10'),
(7, 1, '2026-09-01', 'PE.EB.VA', 6000, 75.60, 75.29, 453627.00, 451750.00, 905377.00, -1877.00, 208.22, 0.00, 0.00, -2085.22, 0.00, -2085.22, '2026-09-07 14:19:55'),
(8, 1, '2026-09-02', 'VALE3', 1500, 79.28, 79.56, 118915.00, 119344.00, 238259.00, 429.00, 54.79, 3.74, 73.05, 374.21, 71.10, 299.37, '2026-09-07 14:20:18'),
(9, 1, '2026-09-03', 'BB.VA', 7000, 47.75, 47.86, 334257.00, 335048.00, 669305.00, 791.00, 153.93, 6.37, 123.24, 637.07, 121.04, 509.66, '2026-09-07 14:20:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `rendas`
--

CREATE TABLE `rendas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `recebido` tinyint(1) NOT NULL DEFAULT 0,
  `porcentagem` decimal(5,2) NOT NULL DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status_assinatura` enum('ativo','inativo') DEFAULT 'ativo',
  `data_expiracao` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `senha`, `status_assinatura`, `data_expiracao`, `criado_em`) VALUES
(1, 'adenilson.ff@outlook.com', '$2y$10$/bpG/sjlWWJNUUbxZeNnfuUR4ISw.P51yjUUE0CDJLEyO5wZKgUOW', 'ativo', '2026-05-28', '2026-04-27 22:37:04');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `acoes_internacionais`
--
ALTER TABLE `acoes_internacionais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `acoes_nacionais`
--
ALTER TABLE `acoes_nacionais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cartoes`
--
ALTER TABLE `cartoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compra_id` (`compra_id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contas`
--
ALTER TABLE `contas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `corretoras`
--
ALTER TABLE `corretoras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `corretora_id` (`corretora_id`);

--
-- Índices de tabela `div_datacom`
--
ALTER TABLE `div_datacom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_acao` (`id_acao`);

--
-- Índices de tabela `ofx_importacoes`
--
ALTER TABLE `ofx_importacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `operacoes`
--
ALTER TABLE `operacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `corretora_id` (`corretora_id`);

--
-- Índices de tabela `rendas`
--
ALTER TABLE `rendas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acoes_internacionais`
--
ALTER TABLE `acoes_internacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `acoes_nacionais`
--
ALTER TABLE `acoes_nacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cartoes`
--
ALTER TABLE `cartoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `corretoras`
--
ALTER TABLE `corretoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `div_datacom`
--
ALTER TABLE `div_datacom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ofx_importacoes`
--
ALTER TABLE `ofx_importacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `operacoes`
--
ALTER TABLE `operacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `rendas`
--
ALTER TABLE `rendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `cartoes`
--
ALTER TABLE `cartoes`
  ADD CONSTRAINT `cartoes_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  ADD CONSTRAINT `corretora_taxas_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `div_datacom`
--
ALTER TABLE `div_datacom`
  ADD CONSTRAINT `div_datacom_ibfk_1` FOREIGN KEY (`id_acao`) REFERENCES `acoes_nacionais` (`id`);

--
-- Restrições para tabelas `operacoes`
--
ALTER TABLE `operacoes`
  ADD CONSTRAINT `operacoes_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
