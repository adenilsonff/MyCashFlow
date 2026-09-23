-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: financas
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `analise_acompanhamento`
--

DROP TABLE IF EXISTS `analise_acompanhamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analise_acompanhamento` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_analise_usuario_ativo` (`usuario_id`,`ticker`,`tipo_ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `analise_marcacoes`
--

DROP TABLE IF EXISTS `analise_marcacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analise_marcacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `tipo` enum('linha','nota') NOT NULL,
  `titulo` varchar(80) NOT NULL,
  `preco` decimal(16,4) DEFAULT NULL,
  `cor` char(7) NOT NULL DEFAULT '#6366f1',
  `texto` varchar(2000) NOT NULL DEFAULT '',
  `versao` int(10) unsigned NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_analise_marcacoes` (`usuario_id`,`ticker`,`tipo_ativo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cartao_nomes_recorrentes`
--

DROP TABLE IF EXISTS `cartao_nomes_recorrentes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartao_nomes_recorrentes` (
  `chave_descricao` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `descricao_original` varchar(255) NOT NULL,
  `nome_personalizado` varchar(255) NOT NULL,
  PRIMARY KEY (`chave_descricao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cartoes`
--

DROP TABLE IF EXISTS `cartoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cartoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `compra_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `parcela` int(11) NOT NULL,
  `paga` tinyint(1) NOT NULL DEFAULT 0,
  `fitid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `compra_id` (`compra_id`),
  CONSTRAINT `cartoes_ibfk_1` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `documento` varchar(20) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `compras`
--

DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `nome_original` varchar(255) DEFAULT NULL,
  `categoria` varchar(20) NOT NULL DEFAULT 'pessoal',
  `valor_total` decimal(10,2) NOT NULL,
  `total_parcelas` int(11) NOT NULL DEFAULT 1,
  `data_compra` date NOT NULL,
  `origem` varchar(20) NOT NULL,
  `identificador_ofx` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contas`
--

DROP TABLE IF EXISTS `contas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contas_financeiras`
--

DROP TABLE IF EXISTS `contas_financeiras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contas_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `instituicao` varchar(100) DEFAULT NULL,
  `icone` varchar(255) DEFAULT NULL,
  `tipo` enum('corrente','poupanca','carteira','corretora','outros') NOT NULL,
  `saldo_inicial` decimal(15,2) NOT NULL DEFAULT 0.00,
  `data_saldo_inicial` date NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cf_usuario` (`usuario_id`),
  KEY `idx_cf_usuario_ativa` (`usuario_id`,`ativa`),
  CONSTRAINT `fk_cf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `corretora_taxas`
--

DROP TABLE IF EXISTS `corretora_taxas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corretora_taxas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corretora_id` int(11) NOT NULL,
  `nome_taxa` varchar(100) NOT NULL,
  `percentual` decimal(10,5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `corretora_id` (`corretora_id`),
  CONSTRAINT `corretora_taxas_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `corretoras`
--

DROP TABLE IF EXISTS `corretoras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corretoras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `div_datacom`
--

DROP TABLE IF EXISTS `div_datacom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `div_datacom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `datacom` date NOT NULL,
  `datapag` date DEFAULT NULL,
  `valor` decimal(15,8) NOT NULL,
  `tipo` enum('DIV','JCP','REND') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_div_usuario` (`usuario_id`),
  KEY `idx_div_ticker` (`ticker`),
  KEY `idx_div_tipo_ativo` (`tipo_ativo`),
  KEY `idx_div_usuario_datacom` (`usuario_id`,`datacom`),
  KEY `idx_div_usuario_ticker` (`usuario_id`,`ticker`),
  CONSTRAINT `fk_div_datacom_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investimentos_internacionais`
--

DROP TABLE IF EXISTS `investimentos_internacionais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investimentos_internacionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `tipo_operacao` enum('compra','venda') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inv_int_usuario` (`usuario_id`),
  KEY `idx_inv_int_ticker` (`ticker`),
  KEY `idx_inv_int_tipo_ativo` (`tipo_ativo`),
  KEY `idx_inv_int_usuario_ticker_data` (`usuario_id`,`ticker`,`data`),
  CONSTRAINT `fk_inv_int_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `investimentos_nacionais`
--

DROP TABLE IF EXISTS `investimentos_nacionais`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `investimentos_nacionais` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `valor_unitario` decimal(10,2) NOT NULL,
  `data` date NOT NULL,
  `valor_mercado` decimal(15,2) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_operacao` enum('compra','venda') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inv_nac_usuario` (`usuario_id`),
  KEY `idx_inv_nac_ticker` (`ticker`),
  KEY `idx_inv_nac_tipo_ativo` (`tipo_ativo`),
  KEY `idx_inv_nac_usuario_ticker_data` (`usuario_id`,`ticker`,`data`),
  CONSTRAINT `fk_inv_nac_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `movimentacoes_financeiras`
--

DROP TABLE IF EXISTS `movimentacoes_financeiras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimentacoes_financeiras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mf_usuario` (`usuario_id`),
  KEY `idx_mf_conta` (`conta_id`),
  KEY `idx_mf_usuario_data` (`usuario_id`,`data`),
  KEY `idx_mf_conta_data` (`conta_id`,`data`),
  KEY `idx_mf_transferencia` (`transferencia_id`),
  KEY `idx_mf_origem` (`usuario_id`,`origem_modulo`,`origem_id`),
  CONSTRAINT `fk_mf_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas_financeiras` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_mf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_mf_valor_positivo` CHECK (`valor` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ofx_importacoes`
--

DROP TABLE IF EXISTS `ofx_importacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ofx_importacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_arquivo` varchar(255) NOT NULL,
  `hash_arquivo` varchar(64) NOT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fim` date DEFAULT NULL,
  `quantidade_transacoes` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `operacoes`
--

DROP TABLE IF EXISTS `operacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `operacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `corretora_id` (`corretora_id`),
  CONSTRAINT `operacoes_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recuperacao_senha`
--

DROP TABLE IF EXISTS `recuperacao_senha`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `data_expiracao` datetime NOT NULL,
  `utilizado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rendas`
--

DROP TABLE IF EXISTS `rendas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rendas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status_assinatura` enum('ativo','inativo') DEFAULT 'ativo',
  `data_expiracao` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-21 12:16:55
