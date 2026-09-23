-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22/09/2026 às 02:56
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
-- Estrutura para tabela `analise_acompanhamento`
--

CREATE TABLE `analise_acompanhamento` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `analise_marcacoes`
--

CREATE TABLE `analise_marcacoes` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `tipo` enum('linha','nota') NOT NULL,
  `titulo` varchar(80) NOT NULL,
  `preco` decimal(16,4) DEFAULT NULL,
  `cor` char(7) NOT NULL DEFAULT '#6366f1',
  `texto` varchar(2000) NOT NULL DEFAULT '',
  `versao` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartao_nomes_recorrentes`
--

CREATE TABLE `cartao_nomes_recorrentes` (
  `chave_descricao` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `descricao_original` varchar(255) NOT NULL,
  `nome_personalizado` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL
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
  `fitid` varchar(255) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL
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
  `nome_original` varchar(255) DEFAULT NULL,
  `categoria` varchar(20) NOT NULL DEFAULT 'pessoal',
  `valor_total` decimal(10,2) NOT NULL,
  `total_parcelas` int(11) NOT NULL DEFAULT 1,
  `data_compra` date NOT NULL,
  `origem` varchar(20) NOT NULL,
  `identificador_ofx` varchar(64) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('unica','parcelada','recorrente') NOT NULL,
  `categoria` enum('pessoal','conjunta') NOT NULL,
  `grupo_recorrencia` varchar(36) DEFAULT NULL,
  `parcela_atual` int(11) DEFAULT NULL,
  `total_parcelas` int(11) DEFAULT NULL,
  `vencimento` date NOT NULL,
  `paga` tinyint(1) NOT NULL DEFAULT 0,
  `valor` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas_financeiras`
--

CREATE TABLE `contas_financeiras` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `instituicao` varchar(100) DEFAULT NULL,
  `icone` varchar(255) DEFAULT NULL,
  `tipo` enum('corrente','poupanca','carteira','corretora','outros') NOT NULL,
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `data_saldo_inicial` date NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretoras`
--

CREATE TABLE `corretoras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretora_taxas`
--

CREATE TABLE `corretora_taxas` (
  `id` int(11) NOT NULL,
  `corretora_id` int(11) NOT NULL,
  `nome_taxa` varchar(100) NOT NULL,
  `percentual` decimal(10,5) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `div_datacom`
--

CREATE TABLE `div_datacom` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `datacom` date NOT NULL,
  `datapag` date DEFAULT NULL,
  `valor` decimal(15,8) NOT NULL,
  `tipo` enum('DIV','JCP','REND') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `investimentos_internacionais`
--

CREATE TABLE `investimentos_internacionais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(20) NOT NULL,
  `tipo_ativo` enum('stock','etf','reit','adr') NOT NULL,
  `quantidade` decimal(22,8) NOT NULL,
  `valor_unitario` decimal(18,4) NOT NULL,
  `valor_investido` decimal(24,8) NOT NULL,
  `data` date NOT NULL,
  `valor_mercado` decimal(18,4) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_operacao` enum('compra','venda') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `investimentos_nacionais`
--

CREATE TABLE `investimentos_nacionais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `data` date NOT NULL,
  `valor_mercado` decimal(15,2) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_operacao` enum('compra','venda') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_financeiras`
--

CREATE TABLE `movimentacoes_financeiras` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `conta_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida','transferencia_entrada','transferencia_saida') NOT NULL,
  `data` date NOT NULL,
  `descricao` varchar(150) NOT NULL,
  `valor` decimal(15,2) NOT NULL,
  `transferencia_id` char(36) DEFAULT NULL,
  `origem_modulo` enum('manual','receita','despesa','cartao','provento','investimento','daytrade') NOT NULL DEFAULT 'manual',
  `origem_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

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
  `quantidade_transacoes` int(11) NOT NULL DEFAULT 0,
  `usuario_id` int(11) NOT NULL
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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recuperacao_senha`
--

CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `data_expiracao` datetime NOT NULL,
  `utilizado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `tipo` enum('unica','parcelada','recorrente') NOT NULL DEFAULT 'unica',
  `grupo_recorrencia` varchar(50) DEFAULT NULL,
  `parcela_atual` int(11) DEFAULT NULL,
  `total_parcelas` int(11) DEFAULT NULL,
  `recebido` tinyint(1) NOT NULL DEFAULT 0,
  `porcentagem` decimal(5,2) NOT NULL DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `classificacao` enum('regular','extra') NOT NULL DEFAULT 'regular',
  `usuario_id` int(11) NOT NULL
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
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `analise_acompanhamento`
--
ALTER TABLE `analise_acompanhamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_analise_usuario_ativo` (`usuario_id`,`ticker`,`tipo_ativo`);

--
-- Índices de tabela `analise_marcacoes`
--
ALTER TABLE `analise_marcacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analise_marcacoes` (`usuario_id`,`ticker`,`tipo_ativo`);

--
-- Índices de tabela `cartao_nomes_recorrentes`
--
ALTER TABLE `cartao_nomes_recorrentes`
  ADD PRIMARY KEY (`usuario_id`,`chave_descricao`),
  ADD KEY `idx_mcf_owner` (`usuario_id`);

--
-- Índices de tabela `cartoes`
--
ALTER TABLE `cartoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`),
  ADD KEY `fk_mcf_cartoes_relation` (`compra_id`,`usuario_id`);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mcf_id_owner` (`id`,`usuario_id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`);

--
-- Índices de tabela `contas`
--
ALTER TABLE `contas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`);

--
-- Índices de tabela `contas_financeiras`
--
ALTER TABLE `contas_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mcf_id_owner` (`id`,`usuario_id`),
  ADD KEY `idx_cf_usuario` (`usuario_id`),
  ADD KEY `idx_cf_usuario_ativa` (`usuario_id`,`ativa`);

--
-- Índices de tabela `corretoras`
--
ALTER TABLE `corretoras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mcf_id_owner` (`id`,`usuario_id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`);

--
-- Índices de tabela `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`),
  ADD KEY `fk_mcf_corretora_taxas_relation` (`corretora_id`,`usuario_id`);

--
-- Índices de tabela `div_datacom`
--
ALTER TABLE `div_datacom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_div_usuario` (`usuario_id`),
  ADD KEY `idx_div_ticker` (`ticker`),
  ADD KEY `idx_div_tipo_ativo` (`tipo_ativo`),
  ADD KEY `idx_div_usuario_datacom` (`usuario_id`,`datacom`),
  ADD KEY `idx_div_usuario_ticker` (`usuario_id`,`ticker`);

--
-- Índices de tabela `investimentos_internacionais`
--
ALTER TABLE `investimentos_internacionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inv_int_usuario` (`usuario_id`),
  ADD KEY `idx_inv_int_ticker` (`ticker`),
  ADD KEY `idx_inv_int_tipo_ativo` (`tipo_ativo`),
  ADD KEY `idx_inv_int_usuario_ticker_data` (`usuario_id`,`ticker`,`data`);

--
-- Índices de tabela `investimentos_nacionais`
--
ALTER TABLE `investimentos_nacionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inv_nac_usuario` (`usuario_id`),
  ADD KEY `idx_inv_nac_ticker` (`ticker`),
  ADD KEY `idx_inv_nac_tipo_ativo` (`tipo_ativo`),
  ADD KEY `idx_inv_nac_usuario_ticker_data` (`usuario_id`,`ticker`,`data`);

--
-- Índices de tabela `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mf_usuario` (`usuario_id`),
  ADD KEY `idx_mf_conta` (`conta_id`),
  ADD KEY `idx_mf_usuario_data` (`usuario_id`,`data`),
  ADD KEY `idx_mf_conta_data` (`conta_id`,`data`),
  ADD KEY `idx_mf_transferencia` (`transferencia_id`),
  ADD KEY `idx_mf_origem` (`usuario_id`,`origem_modulo`,`origem_id`),
  ADD KEY `fk_mcf_movimentacoes_financeiras_relation` (`conta_id`,`usuario_id`);

--
-- Índices de tabela `ofx_importacoes`
--
ALTER TABLE `ofx_importacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mcf_ofx_owner` (`usuario_id`,`hash_arquivo`),
  ADD KEY `idx_mcf_owner` (`usuario_id`);

--
-- Índices de tabela `operacoes`
--
ALTER TABLE `operacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`),
  ADD KEY `fk_mcf_operacoes_relation` (`corretora_id`,`usuario_id`);

--
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `rendas`
--
ALTER TABLE `rendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mcf_owner` (`usuario_id`);

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
-- AUTO_INCREMENT de tabela `analise_acompanhamento`
--
ALTER TABLE `analise_acompanhamento`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `analise_marcacoes`
--
ALTER TABLE `analise_marcacoes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `cartoes`
--
ALTER TABLE `cartoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT de tabela `contas_financeiras`
--
ALTER TABLE `contas_financeiras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `investimentos_internacionais`
--
ALTER TABLE `investimentos_internacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `investimentos_nacionais`
--
ALTER TABLE `investimentos_nacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ofx_importacoes`
--
ALTER TABLE `ofx_importacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `operacoes`
--
ALTER TABLE `operacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `rendas`
--
ALTER TABLE `rendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `analise_acompanhamento`
--
ALTER TABLE `analise_acompanhamento`
  ADD CONSTRAINT `fk_mcf_analise_acompanhamento_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `analise_marcacoes`
--
ALTER TABLE `analise_marcacoes`
  ADD CONSTRAINT `fk_mcf_analise_marcacoes_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `cartao_nomes_recorrentes`
--
ALTER TABLE `cartao_nomes_recorrentes`
  ADD CONSTRAINT `fk_mcf_cartao_nomes_recorrentes_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `cartoes`
--
ALTER TABLE `cartoes`
  ADD CONSTRAINT `cartoes_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mcf_cartoes_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_mcf_cartoes_relation` FOREIGN KEY (`compra_id`,`usuario_id`) REFERENCES `compras` (`id`, `usuario_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk_mcf_compras_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `contas`
--
ALTER TABLE `contas`
  ADD CONSTRAINT `fk_mcf_contas_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `contas_financeiras`
--
ALTER TABLE `contas_financeiras`
  ADD CONSTRAINT `fk_cf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `corretoras`
--
ALTER TABLE `corretoras`
  ADD CONSTRAINT `fk_mcf_corretoras_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  ADD CONSTRAINT `corretora_taxas_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mcf_corretora_taxas_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_mcf_corretora_taxas_relation` FOREIGN KEY (`corretora_id`,`usuario_id`) REFERENCES `corretoras` (`id`, `usuario_id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `div_datacom`
--
ALTER TABLE `div_datacom`
  ADD CONSTRAINT `fk_div_datacom_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `investimentos_internacionais`
--
ALTER TABLE `investimentos_internacionais`
  ADD CONSTRAINT `fk_inv_int_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `investimentos_nacionais`
--
ALTER TABLE `investimentos_nacionais`
  ADD CONSTRAINT `fk_inv_nac_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `movimentacoes_financeiras`
--
ALTER TABLE `movimentacoes_financeiras`
  ADD CONSTRAINT `fk_mcf_movimentacoes_financeiras_relation` FOREIGN KEY (`conta_id`,`usuario_id`) REFERENCES `contas_financeiras` (`id`, `usuario_id`),
  ADD CONSTRAINT `fk_mf_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas_financeiras` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `ofx_importacoes`
--
ALTER TABLE `ofx_importacoes`
  ADD CONSTRAINT `fk_mcf_ofx_importacoes_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `operacoes`
--
ALTER TABLE `operacoes`
  ADD CONSTRAINT `fk_mcf_operacoes_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_mcf_operacoes_relation` FOREIGN KEY (`corretora_id`,`usuario_id`) REFERENCES `corretoras` (`id`, `usuario_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `operacoes_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `rendas`
--
ALTER TABLE `rendas`
  ADD CONSTRAINT `fk_mcf_rendas_owner` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
