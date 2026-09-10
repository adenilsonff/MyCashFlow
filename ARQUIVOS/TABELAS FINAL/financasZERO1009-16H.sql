-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/09/2026 às 21:37
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
  `quantidade` decimal(22,8) NOT NULL,
  `valor_unitario` decimal(18,4) NOT NULL,
  `valor_investido` decimal(24,8) NOT NULL,
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
  `valor_mercado` decimal(15,2) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('compra','venda') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartao_nomes_recorrentes`
--

CREATE TABLE `cartao_nomes_recorrentes` (
  `chave_descricao` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `descricao_original` varchar(255) NOT NULL,
  `nome_personalizado` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `nome_original` varchar(255) DEFAULT NULL,
  `categoria` varchar(20) NOT NULL DEFAULT 'pessoal',
  `valor_total` decimal(10,2) NOT NULL,
  `total_parcelas` int(11) NOT NULL DEFAULT 1,
  `data_compra` date NOT NULL,
  `origem` varchar(20) NOT NULL,
  `identificador_ofx` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('unica','mensal') NOT NULL,
  `vencimento` date NOT NULL,
  `paga` tinyint(1) DEFAULT 0,
  `porcentagem` decimal(5,2) DEFAULT NULL,
  `dias_restantes` int(11) DEFAULT NULL,
  `categoria` varchar(20) NOT NULL DEFAULT 'pessoal',
  `grupo_recorrencia` varchar(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretoras`
--

CREATE TABLE `corretoras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `div_datacom`
--

CREATE TABLE `div_datacom` (
  `id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `datacom` date NOT NULL,
  `datapag` date DEFAULT NULL,
  `valor` decimal(15,8) NOT NULL,
  `tipo` varchar(10) NOT NULL
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `rendas`
--

CREATE TABLE `rendas` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('unica','mensal') NOT NULL DEFAULT 'unica',
  `grupo_recorrencia` varchar(50) DEFAULT NULL,
  `recebido` tinyint(1) DEFAULT 0,
  `porcentagem` decimal(5,2) DEFAULT 0.00
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
-- Índices de tabela `cartao_nomes_recorrentes`
--
ALTER TABLE `cartao_nomes_recorrentes`
  ADD PRIMARY KEY (`chave_descricao`);

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
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `corretoras`
--
ALTER TABLE `corretoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Restrições para tabelas `operacoes`
--
ALTER TABLE `operacoes`
  ADD CONSTRAINT `operacoes_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
