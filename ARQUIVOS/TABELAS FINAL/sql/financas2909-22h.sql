-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 30/09/2026 às 03:04
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

--
-- Acionadores `analise_acompanhamento`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_analise_acompanhamento_DELETE` AFTER DELETE ON `analise_acompanhamento` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'analise','analise_acompanhamento',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'criado_em',OLD.`criado_em`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_analise_acompanhamento_INSERT` AFTER INSERT ON `analise_acompanhamento` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'analise','analise_acompanhamento',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'criado_em',NEW.`criado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_analise_acompanhamento_UPDATE` AFTER UPDATE ON `analise_acompanhamento` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'criado_em',OLD.`criado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'criado_em',NEW.`criado_em`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'analise','analise_acompanhamento',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'criado_em',OLD.`criado_em`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'criado_em',NEW.`criado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_analise_acompanhamento_DELETE` BEFORE DELETE ON `analise_acompanhamento` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='analise' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_analise_acompanhamento_INSERT` BEFORE INSERT ON `analise_acompanhamento` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='analise' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_analise_acompanhamento_UPDATE` BEFORE UPDATE ON `analise_acompanhamento` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'criado_em',OLD.`criado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'criado_em',NEW.`criado_em`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='analise' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Acionadores `analise_marcacoes`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_analise_marcacoes_DELETE` AFTER DELETE ON `analise_marcacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'analise','analise_marcacoes',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'tipo',OLD.`tipo`,'titulo',OLD.`titulo`,'preco',OLD.`preco`,'cor',OLD.`cor`,'texto',OLD.`texto`,'versao',OLD.`versao`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_analise_marcacoes_INSERT` AFTER INSERT ON `analise_marcacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'analise','analise_marcacoes',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'tipo',NEW.`tipo`,'titulo',NEW.`titulo`,'preco',NEW.`preco`,'cor',NEW.`cor`,'texto',NEW.`texto`,'versao',NEW.`versao`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_analise_marcacoes_UPDATE` AFTER UPDATE ON `analise_marcacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'tipo',OLD.`tipo`,'titulo',OLD.`titulo`,'preco',OLD.`preco`,'cor',OLD.`cor`,'texto',OLD.`texto`,'versao',OLD.`versao`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'tipo',NEW.`tipo`,'titulo',NEW.`titulo`,'preco',NEW.`preco`,'cor',NEW.`cor`,'texto',NEW.`texto`,'versao',NEW.`versao`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'analise','analise_marcacoes',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'tipo',OLD.`tipo`,'titulo',OLD.`titulo`,'preco',OLD.`preco`,'cor',OLD.`cor`,'texto',OLD.`texto`,'versao',OLD.`versao`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'tipo',NEW.`tipo`,'titulo',NEW.`titulo`,'preco',NEW.`preco`,'cor',NEW.`cor`,'texto',NEW.`texto`,'versao',NEW.`versao`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_analise_marcacoes_DELETE` BEFORE DELETE ON `analise_marcacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='analise' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_analise_marcacoes_INSERT` BEFORE INSERT ON `analise_marcacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='analise' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_analise_marcacoes_UPDATE` BEFORE UPDATE ON `analise_marcacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'tipo',OLD.`tipo`,'titulo',OLD.`titulo`,'preco',OLD.`preco`,'cor',OLD.`cor`,'texto',OLD.`texto`,'versao',OLD.`versao`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'tipo',NEW.`tipo`,'titulo',NEW.`titulo`,'preco',NEW.`preco`,'cor',NEW.`cor`,'texto',NEW.`texto`,'versao',NEW.`versao`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='analise' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartao_categorias_recorrentes`
--

CREATE TABLE `cartao_categorias_recorrentes` (
  `usuario_id` int(11) NOT NULL,
  `chave_descricao` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `descricao_original` varchar(255) NOT NULL,
  `categoria` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cartao_categorias_recorrentes`
--

INSERT INTO `cartao_categorias_recorrentes` (`usuario_id`, `chave_descricao`, `descricao_original`, `categoria`) VALUES
(1, 'c00d6eb360d93e9ca99c47d772db31fb84e2140f073793e7f9a34ba8e526f9cd', 'MP*MELIMAIS            OSASCO        BR', 'conjunta'),
(1, 'cdfe8737c29de9c674721b26eb8a8ffd774e96821c80fa64a3a19d5b0f1ca07e', 'EBN         *SPOTIFY   CURITIBA      BR', 'unica');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartao_exclusoes`
--

CREATE TABLE `cartao_exclusoes` (
  `usuario_id` int(11) NOT NULL,
  `chave_importacao` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `excluir_desde` date NOT NULL,
  `nome` varchar(255) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `categoria` varchar(20) NOT NULL,
  `versao` char(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL
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

--
-- Despejando dados para a tabela `cartao_nomes_recorrentes`
--

INSERT INTO `cartao_nomes_recorrentes` (`chave_descricao`, `descricao_original`, `nome_personalizado`, `usuario_id`) VALUES
('2da87a9eef9df2643a5aefcca54a571bcd525f6982e9bdcbe10c74eac1d48c99', 'Microsoft*Store        Sao Paulo     BR', 'GamePass', 1),
('4f8e028fc7eae1967aad1c70a45abfc1321d97866dbc8057099f36344f3fb92f', 'MP*HBOMAXASSIN         OSASCO        BR', 'HBO MAX', 1),
('c00d6eb360d93e9ca99c47d772db31fb84e2140f073793e7f9a34ba8e526f9cd', 'MP*MELIMAIS            OSASCO        BR', 'Meli', 1),
('cdfe8737c29de9c674721b26eb8a8ffd774e96821c80fa64a3a19d5b0f1ca07e', 'EBN         *SPOTIFY   CURITIBA      BR', 'Spotify museu', 1),
('fc5822fa784740edc1a6d9915cdb2fd7d6329d878e3a6208d2fd067d6db1b7cb', 'VIVA                   CAMPOS DO JOR BR', 'Suplemento Vivas Mais', 1);

--
-- Acionadores `cartao_nomes_recorrentes`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_cartao_nomes_recorrentes_DELETE` AFTER DELETE ON `cartao_nomes_recorrentes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'cartao','cartao_nomes_recorrentes',OLD.`chave_descricao`,'excluir',JSON_OBJECT('chave_descricao',OLD.`chave_descricao`,'descricao_original',OLD.`descricao_original`,'nome_personalizado',OLD.`nome_personalizado`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_cartao_nomes_recorrentes_INSERT` AFTER INSERT ON `cartao_nomes_recorrentes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'cartao','cartao_nomes_recorrentes',NEW.`chave_descricao`,'cadastrar',NULL,JSON_OBJECT('chave_descricao',NEW.`chave_descricao`,'descricao_original',NEW.`descricao_original`,'nome_personalizado',NEW.`nome_personalizado`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_cartao_nomes_recorrentes_UPDATE` AFTER UPDATE ON `cartao_nomes_recorrentes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('chave_descricao',OLD.`chave_descricao`,'descricao_original',OLD.`descricao_original`,'nome_personalizado',OLD.`nome_personalizado`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('chave_descricao',NEW.`chave_descricao`,'descricao_original',NEW.`descricao_original`,'nome_personalizado',NEW.`nome_personalizado`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'cartao','cartao_nomes_recorrentes',NEW.`chave_descricao`,'editar',JSON_OBJECT('chave_descricao',OLD.`chave_descricao`,'descricao_original',OLD.`descricao_original`,'nome_personalizado',OLD.`nome_personalizado`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('chave_descricao',NEW.`chave_descricao`,'descricao_original',NEW.`descricao_original`,'nome_personalizado',NEW.`nome_personalizado`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_cartao_nomes_recorrentes_DELETE` BEFORE DELETE ON `cartao_nomes_recorrentes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_cartao_nomes_recorrentes_INSERT` BEFORE INSERT ON `cartao_nomes_recorrentes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_cartao_nomes_recorrentes_UPDATE` BEFORE UPDATE ON `cartao_nomes_recorrentes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('chave_descricao',OLD.`chave_descricao`,'descricao_original',OLD.`descricao_original`,'nome_personalizado',OLD.`nome_personalizado`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('chave_descricao',NEW.`chave_descricao`,'descricao_original',NEW.`descricao_original`,'nome_personalizado',NEW.`nome_personalizado`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Despejando dados para a tabela `cartoes`
--

INSERT INTO `cartoes` (`id`, `compra_id`, `data`, `valor`, `parcela`, `paga`, `fitid`, `usuario_id`) VALUES
(1, 1, '2026-06-22', 19.90, 1, 1, '2026042248540000000090110000000001', 1),
(2, 2, '2026-06-26', 194.90, 1, 1, '2026042648540000000090110000000002', 1),
(3, 3, '2026-06-27', 264.21, 1, 1, '2026042748540000000090110000000003', 1),
(4, 4, '2026-06-28', 31.43, 1, 1, '2026042848540000000090110000000004', 1),
(5, 5, '2026-06-03', 23.90, 1, 1, '2026050348540000000090110000000005', 1),
(6, 6, '2026-06-18', 242.55, 1, 1, '2026051848540000000090110000000006', 1),
(7, 7, '2026-06-22', 19.90, 1, 1, '2026052248540000000090110000000007', 1),
(8, 8, '2026-06-30', 125.97, 1, 1, '2026043048540000000090110000000008', 1),
(9, 9, '2026-06-22', 44.39, 1, 1, '2026042248540000000090110000000009', 1),
(10, 10, '2026-06-25', 100.01, 1, 1, '2026042548540000000090110000000010', 1),
(11, 10, '2026-07-25', 99.99, 2, 0, '2026042548540000000090110000000013', 1),
(12, 10, '2026-08-25', 99.99, 3, 0, '2026042548540000000090110000000006', 1),
(13, 11, '2026-05-26', 162.66, 1, 0, NULL, 1),
(14, 11, '2026-06-26', 162.66, 2, 1, '2026032648540000000090110000000011', 1),
(15, 11, '2026-07-26', 162.66, 3, 0, '2026032648540000000090110000000015', 1),
(16, 12, '2026-03-29', 146.75, 1, 0, NULL, 1),
(17, 12, '2026-04-29', 146.75, 2, 0, NULL, 1),
(18, 12, '2026-05-29', 146.75, 3, 0, NULL, 1),
(19, 12, '2026-06-29', 146.75, 4, 1, '2026012948540000000090110000000012', 1),
(20, 13, '2026-05-30', 129.99, 1, 0, NULL, 1),
(21, 13, '2026-06-30', 129.99, 2, 1, '2026033048540000000090110000000013', 1),
(22, 14, '2026-06-30', 1199.00, 1, 1, '2026043048540000000090110000000014', 1),
(23, 14, '2026-07-30', 1199.00, 2, 0, '2026043048540000000090110000000017', 1),
(24, 14, '2026-08-30', 1199.00, 3, 0, '2026043048540000000090110000000010', 1),
(25, 14, '2026-09-30', 1199.00, 4, 0, '2026043048540000000090110000000018', 1),
(26, 14, '2026-10-30', 1199.00, 5, 0, NULL, 1),
(27, 14, '2026-11-30', 1199.00, 6, 0, NULL, 1),
(28, 14, '2026-12-30', 1199.00, 7, 0, NULL, 1),
(29, 14, '2027-01-30', 1199.00, 8, 0, NULL, 1),
(30, 14, '2027-02-28', 1199.00, 9, 0, NULL, 1),
(31, 14, '2027-03-30', 1199.00, 10, 0, NULL, 1),
(32, 15, '2026-06-30', 179.85, 1, 1, '2026043048540000000090110000000015', 1),
(33, 15, '2026-07-30', 179.85, 2, 0, '2026043048540000000090110000000018', 1),
(34, 16, '2026-04-01', 639.33, 1, 0, NULL, 1),
(35, 16, '2026-05-01', 639.33, 2, 0, NULL, 1),
(36, 16, '2026-06-01', 639.33, 3, 1, '2026030148540000000090110000000016', 1),
(37, 17, '2026-07-08', -190.98, 1, 0, '2026060848540000000090110000000001', 1),
(38, 18, '2026-07-28', 31.43, 1, 0, '2026052848540000000090110000000002', 1),
(39, 19, '2026-07-30', 259.00, 1, 0, '2026053048540000000090110000000003', 1),
(40, 20, '2026-07-03', 78.00, 1, 0, '2026060348540000000090110000000004', 1),
(41, 21, '2026-07-03', 420.00, 1, 0, '2026060348540000000090110000000005', 1),
(42, 22, '2026-07-03', 42.55, 1, 0, '2026060348540000000090110000000006', 1),
(43, 23, '2026-07-03', 209.98, 1, 0, '2026060348540000000090110000000007', 1),
(44, 24, '2026-07-03', 23.90, 1, 0, '2026060348540000000090110000000008', 1),
(45, 25, '2026-07-04', 212.00, 1, 0, '2026060448540000000090110000000009', 1),
(46, 26, '2026-07-21', 19.90, 1, 0, '2026062148540000000090110000000010', 1),
(47, 27, '2026-07-11', 89.90, 1, 0, '2026061148540000000090110000000011', 1),
(48, 28, '2026-07-26', 59.99, 1, 0, '2026052648540000000090110000000012', 1),
(49, 29, '2026-07-26', 84.00, 1, 0, '2026052648540000000090110000000014', 1),
(50, 29, '2026-08-26', 84.00, 2, 0, '2026052648540000000090110000000007', 1),
(51, 29, '2026-09-26', 84.00, 3, 0, '2026052648540000000090110000000015', 1),
(52, 30, '2026-07-26', 183.33, 1, 0, '2026052648540000000090110000000016', 1),
(53, 30, '2026-08-26', 183.32, 2, 0, '2026052648540000000090110000000008', 1),
(54, 30, '2026-09-26', 183.32, 3, 0, '2026052648540000000090110000000014', 1),
(55, 31, '2026-07-31', 195.04, 1, 0, '2026053148540000000090110000000019', 1),
(56, 31, '2026-08-31', 195.03, 2, 0, '2026053148540000000090110000000011', 1),
(57, 31, '2026-09-30', 195.03, 3, 0, '2026053148540000000090110000000019', 1),
(58, 31, '2026-10-31', 195.04, 4, 0, NULL, 1),
(59, 32, '2026-07-06', 27.80, 1, 0, '2026060648540000000090110000000020', 1),
(60, 32, '2026-08-06', 27.80, 2, 0, '2026060648540000000090110000000012', 1),
(61, 32, '2026-09-06', 27.80, 3, 0, '2026060648540000000090110000000020', 1),
(62, 32, '2026-10-06', 27.80, 4, 0, NULL, 1),
(63, 32, '2026-11-06', 27.80, 5, 0, NULL, 1),
(64, 32, '2026-12-06', 27.80, 6, 0, NULL, 1),
(65, 33, '2026-07-11', 80.61, 1, 0, '2026061148540000000090110000000021', 1),
(66, 33, '2026-08-11', 80.60, 2, 0, '2026061148540000000090110000000013', 1),
(67, 33, '2026-09-11', 80.60, 3, 0, '2026061148540000000090110000000021', 1),
(68, 33, '2026-10-11', 80.61, 4, 0, NULL, 1),
(69, 34, '2026-08-27', 31.43, 1, 0, '2026062748540000000090110000000001', 1),
(70, 35, '2026-08-28', 63.47, 1, 0, '2026062848540000000090110000000002', 1),
(71, 36, '2026-08-03', 23.90, 1, 0, '2026070348540000000090110000000003', 1),
(72, 37, '2026-08-21', 19.90, 1, 0, '2026072148540000000090110000000004', 1),
(73, 38, '2026-08-26', 59.99, 1, 0, '2026062648540000000090110000000005', 1),
(74, 39, '2026-08-29', 200.00, 1, 0, '2026062948540000000090110000000009', 1),
(75, 39, '2026-09-29', 199.99, 2, 0, '2026062948540000000090110000000017', 1),
(76, 40, '2026-08-14', 453.22, 1, 0, '2026071448540000000090110000000014', 1),
(77, 40, '2026-09-14', 453.22, 2, 0, '2026071448540000000090110000000022', 1),
(78, 40, '2026-10-14', 453.22, 3, 0, NULL, 1),
(79, 40, '2026-11-14', 453.22, 4, 0, NULL, 1),
(80, 41, '2026-08-17', 329.00, 1, 0, '2026071748540000000090110000000015', 1),
(81, 41, '2026-09-17', 329.00, 2, 0, '2026071748540000000090110000000024', 1),
(82, 41, '2026-10-17', 329.00, 3, 0, NULL, 1),
(83, 42, '2026-09-09', 193.00, 1, 0, '2026080948540000000090110000000001', 1),
(84, 43, '2026-09-26', 983.26, 1, 0, '2026072648540000000090110000000002', 1),
(85, 44, '2026-09-27', 31.43, 1, 0, '2026072748540000000090110000000003', 1),
(86, 45, '2026-09-30', 19.00, 1, 0, '2026073048540000000090110000000004', 1),
(87, 46, '2026-09-30', 349.00, 1, 0, '2026073048540000000090110000000005', 1),
(88, 47, '2026-09-30', 193.57, 1, 0, '2026073048540000000090110000000006', 1),
(89, 48, '2026-09-30', 420.00, 1, 0, '2026073148540000000090110000000007', 1),
(90, 49, '2026-09-30', 691.66, 1, 0, '2026073148540000000090110000000008', 1),
(91, 50, '2026-09-03', 23.90, 1, 0, '2026080348540000000090110000000009', 1),
(92, 51, '2026-09-05', 249.89, 1, 0, '2026080548540000000090110000000010', 1),
(93, 52, '2026-09-08', 94.89, 1, 0, '2026080848540000000090110000000011', 1),
(94, 53, '2026-09-20', 19.90, 1, 0, '2026082048540000000090110000000012', 1),
(95, 54, '2026-09-26', 59.99, 1, 0, '2026072648540000000090110000000013', 1),
(96, 55, '2026-09-26', 466.14, 1, 0, '2026072648540000000090110000000016', 1),
(97, 55, '2026-10-26', 466.14, 2, 0, NULL, 1),
(98, 55, '2026-11-26', 466.14, 3, 0, NULL, 1),
(99, 55, '2026-12-26', 466.14, 4, 0, NULL, 1),
(100, 55, '2027-01-26', 466.14, 5, 0, NULL, 1),
(101, 56, '2026-09-16', 375.00, 1, 0, '2026081648540000000090110000000023', 1),
(102, 56, '2026-10-16', 375.00, 2, 0, NULL, 1);

--
-- Acionadores `cartoes`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_cartoes_DELETE` AFTER DELETE ON `cartoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'cartao','cartoes',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'compra_id',OLD.`compra_id`,'data',OLD.`data`,'valor',OLD.`valor`,'parcela',OLD.`parcela`,'paga',OLD.`paga`,'fitid',OLD.`fitid`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_cartoes_INSERT` AFTER INSERT ON `cartoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'cartao','cartoes',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'compra_id',NEW.`compra_id`,'data',NEW.`data`,'valor',NEW.`valor`,'parcela',NEW.`parcela`,'paga',NEW.`paga`,'fitid',NEW.`fitid`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_cartoes_UPDATE` AFTER UPDATE ON `cartoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'compra_id',OLD.`compra_id`,'data',OLD.`data`,'valor',OLD.`valor`,'parcela',OLD.`parcela`,'paga',OLD.`paga`,'fitid',OLD.`fitid`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'compra_id',NEW.`compra_id`,'data',NEW.`data`,'valor',NEW.`valor`,'parcela',NEW.`parcela`,'paga',NEW.`paga`,'fitid',NEW.`fitid`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'cartao','cartoes',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'compra_id',OLD.`compra_id`,'data',OLD.`data`,'valor',OLD.`valor`,'parcela',OLD.`parcela`,'paga',OLD.`paga`,'fitid',OLD.`fitid`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'compra_id',NEW.`compra_id`,'data',NEW.`data`,'valor',NEW.`valor`,'parcela',NEW.`parcela`,'paga',NEW.`paga`,'fitid',NEW.`fitid`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_cartoes_DELETE` BEFORE DELETE ON `cartoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_cartoes_INSERT` BEFORE INSERT ON `cartoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_cartoes_UPDATE` BEFORE UPDATE ON `cartoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'compra_id',OLD.`compra_id`,'data',OLD.`data`,'valor',OLD.`valor`,'parcela',OLD.`parcela`,'paga',OLD.`paga`,'fitid',OLD.`fitid`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'compra_id',NEW.`compra_id`,'data',NEW.`data`,'valor',NEW.`valor`,'parcela',NEW.`parcela`,'paga',NEW.`paga`,'fitid',NEW.`fitid`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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
-- Estrutura para tabela `compartilhamentos`
--

CREATE TABLE `compartilhamentos` (
  `id` int(11) NOT NULL,
  `proprietario_id` int(11) NOT NULL,
  `leitor_id` int(11) NOT NULL,
  `estado` enum('pendente','ativo','revogado','recusado') NOT NULL DEFAULT 'pendente',
  `versao` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compartilhamento_modulos`
--

CREATE TABLE `compartilhamento_modulos` (
  `compartilhamento_id` int(11) NOT NULL,
  `modulo` enum('despesas','receitas','cartao','saldos','investimentos','daytrade','analise') NOT NULL,
  `nivel` enum('leitura','edicao') NOT NULL DEFAULT 'leitura',
  `cadastrar` tinyint(4) NOT NULL DEFAULT 1,
  `editar` tinyint(4) NOT NULL DEFAULT 1,
  `excluir` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compartilhamento_vistos`
--

CREATE TABLE `compartilhamento_vistos` (
  `usuario_id` int(11) NOT NULL,
  `compartilhamento_id` int(11) NOT NULL,
  `versao` int(11) NOT NULL
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

--
-- Despejando dados para a tabela `compras`
--

INSERT INTO `compras` (`id`, `nome`, `nome_original`, `categoria`, `valor_total`, `total_parcelas`, `data_compra`, `origem`, `identificador_ofx`, `usuario_id`) VALUES
(1, 'Meli', 'MP*MELIMAIS            OSASCO        BR', 'conjunta', 19.90, 1, '2026-04-22', 'ofx', '955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782', 1),
(2, 'POPULAR PET PETSHOP SA SAO JOSE DOS  BR', 'POPULAR PET PETSHOP SA SAO JOSE DOS  BR', 'conjunta', 194.90, 1, '2026-04-26', 'ofx', '52afd8c9758269fadefaec0ab56d235a6cebf373e7cac70fae727a0fac552b5b', 1),
(3, 'MERCADOLIVRE*MERCADOLIVSAO PAULO     BR', 'MERCADOLIVRE*MERCADOLIVSAO PAULO     BR', 'pessoal', 264.21, 1, '2026-04-27', 'ofx', '633b607901be93f6ab2bf235a88292fda08ad8fe8f285b11a66260b7fcd0c417', 1),
(4, 'HBO MAX', 'MP*HBOMAXASSIN         OSASCO        BR', 'conjunta', 31.43, 1, '2026-04-28', 'ofx', 'b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e', 1),
(5, 'Spotify museu', 'EBN         *SPOTIFY   CURITIBA      BR', 'unica', 23.90, 1, '2026-05-03', 'ofx', '4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261', 1),
(6, 'MP*MERCADOLIVRE        OSASCO        BR', 'MP*MERCADOLIVRE        OSASCO        BR', 'pessoal', 242.55, 1, '2026-05-18', 'ofx', '25e2c047b630586a56fa2a380de906ef6f36cc26c8a904d0bdc9c3c59c9b649b', 1),
(7, 'Meli', 'MP*MELIMAIS            OSASCO        BR', 'conjunta', 19.90, 1, '2026-05-22', 'ofx', 'cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3', 1),
(8, 'MARISA 608             TAUBATE       BR', 'MARISA 608             TAUBATE       BR', 'pessoal', 125.97, 1, '2026-04-30', 'ofx', '54c64ccfcf7816334c3db0fd94a852855a046fe4184bfe72e4f14ee30addf4c7', 1),
(9, 'DM*NintendoeShop       SAO PAULO     BR', 'DM*NintendoeShop       SAO PAULO     BR', 'pessoal', 44.39, 1, '2026-04-22', 'ofx', 'b494e1389dd853947a44925c15a74206b698fc4859b5f8de08d3f70f300cef01', 1),
(10, 'Aquela 01', 'MP*LOJAXIAOMI  SANTA RITA DBR', 'pessoal', 299.99, 3, '2026-04-25', 'ofx', '9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf', 1),
(11, 'MERCADOLIVRE*  ILHABELA    BR', 'MERCADOLIVRE*  ILHABELA    BR', 'pessoal', 487.98, 3, '2026-03-26', 'ofx', '12b30b569ba27f26924ee2aa3d21f21ec4cdb5675477ea6c4d98b281f08d1a7c', 1),
(12, 'MLP    *KaBuM  eldorado do BR', 'MLP    *KaBuM  eldorado do BR', 'pessoal', 587.00, 4, '2026-01-29', 'ofx', 'fd7c5b69466fd5d7c969fae8479bd89fe63ed403765317d0fbe7332b492ed063', 1),
(13, 'IGUASPO*IGUAS  CAMPINAS    BR', 'IGUASPO*IGUAS  CAMPINAS    BR', 'pessoal', 259.98, 2, '2026-03-30', 'ofx', '80e6d53a02117afd7847a41b8ce5bb6d2e9bdb66b5d715b7264678053a23ef1c', 1),
(14, 'Aquela 02', 'VIVARA TAB     TAUBATE     BR', 'pessoal', 11990.00, 10, '2026-04-30', 'ofx', '0817bce85a3108e717d35a7c1b1379e201f0cf8400fe76fd556dc1d8e81f8dfa', 1),
(15, 'A ESPORTIVA C  SANTO ANDRE BR', 'A ESPORTIVA C  SANTO ANDRE BR', 'pessoal', 359.70, 2, '2026-04-30', 'ofx', '8d2971f971121b18265910360bba3d4f96d706fe204bac017bccdd546bf607a8', 1),
(16, 'Correntinha Pam', 'PANDORA DO BR  SAO PAULO   BR', 'pessoal', 1917.99, 3, '2026-03-01', 'ofx', '661ac936e7ac9b11da8ce9333899dd291d72a9921d785c5913b914c726d29db1', 1),
(17, 'MP*GGJJK               HORTOLNDIA    BR', 'MP*GGJJK               HORTOLNDIA    BR', 'pessoal', -190.98, 1, '2026-06-08', 'ofx', 'da2b0c9b10c3e33d85f0d8106423ed805ce152caa8b666acc80433f6e5dd0885', 1),
(18, 'HBO MAX', 'MP*HBOMAXASSIN         OSASCO        BR', 'conjunta', 31.43, 1, '2026-05-28', 'ofx', 'eaa9bc4def5f6b7ff912b46ecc793f9194aa6af4763a31c116ddef4ebeacfbf7', 1),
(19, 'BRENO SILVA ROCHA      CAMPOS DO JOR BR', 'BRENO SILVA ROCHA      CAMPOS DO JOR BR', 'pessoal', 259.00, 1, '2026-05-30', 'ofx', '1b23cc47f95e3e4c65eb6cecfa6044e47b69e449e9994df68fd8856befafdbcc', 1),
(20, 'MP*LOJABIKEWAY         OSASCO        BR', 'MP*LOJABIKEWAY         OSASCO        BR', 'pessoal', 78.00, 1, '2026-06-03', 'ofx', 'b47ec78df1b30b6e8bf7d98e344cae60e2b88535850179e551c67e0c887c3bf6', 1),
(21, 'AUTO GAS               POUSO ALEGRE  BR', 'AUTO GAS               POUSO ALEGRE  BR', 'pessoal', 420.00, 1, '2026-06-03', 'ofx', '92d13cc5911bfd9d59743e60f3c70a3d492837cf22155631126b3de6704b98de', 1),
(22, 'MP*SUPRASERVICE        OSASCO        BR', 'MP*SUPRASERVICE        OSASCO        BR', 'pessoal', 42.55, 1, '2026-06-03', 'ofx', '4c2c7f40a1fe17410dd4c5afb98fbe62edef96436f2ce3712eeb568e1b6c779d', 1),
(23, 'MP*GGJJK               HORTOLNDIA    BR', 'MP*GGJJK               HORTOLNDIA    BR', 'pessoal', 209.98, 1, '2026-06-03', 'ofx', 'f409ec0f994fe82937339fefd6ab53dd8547579cd0adffe6e7ac21d11b2e27a5', 1),
(24, 'Spotify museu', 'EBN         *SPOTIFY   CURITIBA      BR', 'unica', 23.90, 1, '2026-06-03', 'ofx', 'aeec41d68be97743da65cfdeec9e58d6378e5b3bea7a63c639eb2d7dc8b370eb', 1),
(25, 'MP*MERCADOLIVRE        BIRIGUI       BR', 'MP*MERCADOLIVRE        BIRIGUI       BR', 'pessoal', 212.00, 1, '2026-06-04', 'ofx', 'd753c767fbc844177a72932845fbdf922c7f6a179fb046ee6f26be4d27b81e87', 1),
(26, 'Meli', 'MP*MELIMAIS            OSASCO        BR', 'conjunta', 19.90, 1, '2026-06-21', 'ofx', '54de9e6a13bee311ffcb23efbadcdbe1ce243a5d2f0cdc2a2f5754a0f5c4ba12', 1),
(27, 'MP*MERCADOLIVRE        PAIANDU       BR', 'MP*MERCADOLIVRE        PAIANDU       BR', 'pessoal', 89.90, 1, '2026-06-11', 'ofx', 'd77287820e004cdbbdde5d838519f0631f98a8194bdff20fb1dce244f0442b95', 1),
(28, 'GamePass', 'Microsoft*Store        Sao Paulo     BR', 'pessoal', 59.99, 1, '2026-05-26', 'ofx', 'f458b16fdd3724f7d81269e58588cf1632a652b29471802041baf30dfc6a6389', 1),
(29, 'MERCADOLIVRE*  OSASCO      BR', 'MERCADOLIVRE*  OSASCO      BR', 'pessoal', 252.00, 3, '2026-05-26', 'ofx', '58123bdc542de7e8f71e9597bd276beb431f44bae9af05194d78952293717b5b', 1),
(30, 'Pamela camisa da seleção', 'FISIA NIKE EC  EXTREMA     BR', 'pessoal', 549.97, 3, '2026-05-26', 'ofx', '84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2', 1),
(31, 'Breteles', 'MP*FREEFORCE   OSASCO      BR', 'pessoal', 780.14, 4, '2026-05-31', 'ofx', '32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5', 1),
(32, 'Prime', 'AMAZON PRIME   SAO PAULO   BR', 'conjunta', 166.80, 6, '2026-06-06', 'ofx', '9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb', 1),
(33, 'MERCADOLIVRE*  PAIANDU     BR', 'MERCADOLIVRE*  PAIANDU     BR', 'pessoal', 322.42, 4, '2026-06-11', 'ofx', 'bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c', 1),
(34, 'HBO MAX', 'MP*HBOMAXASSIN         OSASCO        BR', 'pessoal', 31.43, 1, '2026-06-27', 'ofx', '8d197e34da141ea2d90f230504fcb637de5eb394c251de04cbd55b1d57e7ad02', 1),
(35, 'MP*VSRMOTOS            JOINVILLE     BR', 'MP*VSRMOTOS            JOINVILLE     BR', 'pessoal', 63.47, 1, '2026-06-28', 'ofx', '745b100702eee8ebe9f3237da9f16672b7c49e6a211faece3288913dec0274ff', 1),
(36, 'Spotify museu', 'EBN         *SPOTIFY   CURITIBA      BR', 'unica', 23.90, 1, '2026-07-03', 'ofx', '7554900bcb83ca23cef94216af19a7afc7f3249571d802fed24710897ef2b49f', 1),
(37, 'Meli', 'MP*MELIMAIS            OSASCO        BR', 'conjunta', 19.90, 1, '2026-07-21', 'ofx', '4e8702025a5bc5e33722eb341b6265d5ecfc3f153e33f64464b6c522259181fc', 1),
(38, 'Microsoft*1 Meses de PCSao Paulo     BR', 'Microsoft*1 Meses de PCSao Paulo     BR', 'pessoal', 59.99, 1, '2026-06-26', 'ofx', 'c4118e67e9f0c444877caff76b36b7e80351b94099d40b1e67e852a3b52df4ca', 1),
(39, 'PayU        *  Barueri     BR', 'PayU        *  Barueri     BR', 'pessoal', 399.99, 2, '2026-06-29', 'ofx', '60b4fe6caf19cbd42e40b362e5c60bb7e3e8287cb5095c0df0ec2d892c6b2ae0', 1),
(40, 'Gol pneus', 'DPASCHOAL 196  TAUBATE     BR', 'pessoal', 1812.88, 4, '2026-07-14', 'ofx', '2f2c0a1d3288a327972a0a39db943213f11c303f3eb13b9fd751460b06261234', 1),
(41, 'Biz revisão', 'PetersonPierr  CAMPOS DO JOBR', 'pessoal', 987.00, 3, '2026-07-17', 'ofx', 'ed1a0bb1163563da30e10046a0c12c921efad0c2d2f9a90ebf00e1502772c63f', 1),
(42, 'Suplemento Vivas Mais', 'VIVA                   CAMPOS DO JOR BR', 'pessoal', 193.00, 1, '2026-08-09', 'ofx', '9d0e436385c49e444cfd5e64e9082257aafa407e5fa35359baedea19f6df9cee', 1),
(43, 'MP*HDSTORE             SERRA         BR', 'MP*HDSTORE             SERRA         BR', 'pessoal', 983.26, 1, '2026-07-26', 'ofx', '31a9db4935da12912ebf87b03d614960f63d5f939bb735fabfaf5ba9f66565db', 1),
(44, 'HBO MAX', 'MP*HBOMAXASSIN         OSASCO        BR', 'conjunta', 31.43, 1, '2026-07-27', 'ofx', '304b9ef13ec7cafcf0efa243ee020cbfab583c0819eefcffd3d62a6c37b20c05', 1),
(45, 'MP*DUCARTUCHOS         OSVALDO CRUZ  BR', 'MP*DUCARTUCHOS         OSVALDO CRUZ  BR', 'pessoal', 19.00, 1, '2026-07-30', 'ofx', '5ccd69e34e2a63d6c2f67a07a3ee0c23a988cee6273a799001273fe282379765', 1),
(46, 'MP*SYSTEMTRACEIN       SAO PAULO     BR', 'MP*SYSTEMTRACEIN       SAO PAULO     BR', 'pessoal', 349.00, 1, '2026-07-30', 'ofx', 'a16276f347c0a9c44e8d48720ba9ba067e5caa881a8228bb5e5f1a076600c5d1', 1),
(47, 'MP*TURUM               SAO CAETANO D BR', 'MP*TURUM               SAO CAETANO D BR', 'pessoal', 193.57, 1, '2026-07-30', 'ofx', 'ef69e4126b830d198961e1583467aaf8e87cd7b98f9849acaf6a9acd79a72f05', 1),
(48, 'AUTO GAS               POUSO ALEGRE  BR', 'AUTO GAS               POUSO ALEGRE  BR', 'pessoal', 420.00, 1, '2026-07-31', 'ofx', '96abbf338a011aacaee7ad2692c6db89b962da4131c4f0c39dba8e234ca98ce2', 1),
(49, 'MP*CARANGOPARTS        JUNDIAI       BR', 'MP*CARANGOPARTS        JUNDIAI       BR', 'pessoal', 691.66, 1, '2026-07-31', 'ofx', '8070d14feda35060ac48126073b1d50b7935cdb59810396fe9c1490b354d713c', 1),
(50, 'Spotify museu', 'EBN         *SPOTIFY   CURITIBA      BR', 'unica', 23.90, 1, '2026-08-03', 'ofx', 'ea98011b234a6574805db5860e0f07831f6e021e73dfca44be5fdc57c404bbd3', 1),
(51, 'MP*MERCADOLIVRE        SAO PAULO     BR', 'MP*MERCADOLIVRE        SAO PAULO     BR', 'pessoal', 249.89, 1, '2026-08-05', 'ofx', '853851080dc3828b22725cc29ee40afb1a71117ccdab5ce2ddad13af293a8d4d', 1),
(52, 'MP*MERCADOLIVRE        JUNDIAI       BR', 'MP*MERCADOLIVRE        JUNDIAI       BR', 'pessoal', 94.89, 1, '2026-08-08', 'ofx', '853794e585675eb0673eed6214b98c692fc74f27a6a7c6ade01ce0bf3e0603fa', 1),
(53, 'Meli', 'MP*MELIMAIS            OSASCO        BR', 'conjunta', 19.90, 1, '2026-08-20', 'ofx', 'a3be6bc65cd74a56155f17e6938d2800aa2008295741409be6d56704bfa4108a', 1),
(54, 'Microsoft*1 Meses de PCSao Paulo     BR', 'Microsoft*1 Meses de PCSao Paulo     BR', 'pessoal', 59.99, 1, '2026-07-26', 'ofx', 'fe7d9d27ba96a28f069a94c2abed08398fe4a7b10b8508b8e0a25378b5304f33', 1),
(55, 'MERCADOLIVRE*  SAO JOSE    BR', 'MERCADOLIVRE*  SAO JOSE    BR', 'pessoal', 2330.70, 5, '2026-07-26', 'ofx', '01c6b07f8b22b2642fe26f7250ea0622043b8a4742dbcd9b064d8d1b3555074c', 1),
(56, 'Resonancia WK', 'W K DIAGNOSE   TAUBATE     BR', 'pessoal', 750.00, 2, '2026-08-16', 'ofx', 'aa36300d2adfb061032101eddaab451c172489b4752f99d09d6676cd0867c236', 1);

--
-- Acionadores `compras`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_compras_DELETE` AFTER DELETE ON `compras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'cartao','compras',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'nome_original',OLD.`nome_original`,'categoria',OLD.`categoria`,'valor_total',OLD.`valor_total`,'total_parcelas',OLD.`total_parcelas`,'data_compra',OLD.`data_compra`,'origem',OLD.`origem`,'identificador_ofx',OLD.`identificador_ofx`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_compras_INSERT` AFTER INSERT ON `compras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'cartao','compras',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'nome_original',NEW.`nome_original`,'categoria',NEW.`categoria`,'valor_total',NEW.`valor_total`,'total_parcelas',NEW.`total_parcelas`,'data_compra',NEW.`data_compra`,'origem',NEW.`origem`,'identificador_ofx',NEW.`identificador_ofx`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_compras_UPDATE` AFTER UPDATE ON `compras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'nome_original',OLD.`nome_original`,'categoria',OLD.`categoria`,'valor_total',OLD.`valor_total`,'total_parcelas',OLD.`total_parcelas`,'data_compra',OLD.`data_compra`,'origem',OLD.`origem`,'identificador_ofx',OLD.`identificador_ofx`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'nome_original',NEW.`nome_original`,'categoria',NEW.`categoria`,'valor_total',NEW.`valor_total`,'total_parcelas',NEW.`total_parcelas`,'data_compra',NEW.`data_compra`,'origem',NEW.`origem`,'identificador_ofx',NEW.`identificador_ofx`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'cartao','compras',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'nome_original',OLD.`nome_original`,'categoria',OLD.`categoria`,'valor_total',OLD.`valor_total`,'total_parcelas',OLD.`total_parcelas`,'data_compra',OLD.`data_compra`,'origem',OLD.`origem`,'identificador_ofx',OLD.`identificador_ofx`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'nome_original',NEW.`nome_original`,'categoria',NEW.`categoria`,'valor_total',NEW.`valor_total`,'total_parcelas',NEW.`total_parcelas`,'data_compra',NEW.`data_compra`,'origem',NEW.`origem`,'identificador_ofx',NEW.`identificador_ofx`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_compras_DELETE` BEFORE DELETE ON `compras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF; INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) SELECT x.usuario_id,@mcf_ator_id,'cartao','cartoes',x.id,'excluir',JSON_OBJECT('id',x.`id`,'compra_id',x.`compra_id`,'data',x.`data`,'valor',x.`valor`,'parcela',x.`parcela`,'paga',x.`paga`,'fitid',x.`fitid`,'usuario_id',x.`usuario_id`),NULL FROM `cartoes` x WHERE x.`compra_id`=OLD.id AND x.usuario_id=OLD.usuario_id;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_compras_INSERT` BEFORE INSERT ON `compras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_compras_UPDATE` BEFORE UPDATE ON `compras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'nome_original',OLD.`nome_original`,'categoria',OLD.`categoria`,'valor_total',OLD.`valor_total`,'total_parcelas',OLD.`total_parcelas`,'data_compra',OLD.`data_compra`,'origem',OLD.`origem`,'identificador_ofx',OLD.`identificador_ofx`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'nome_original',NEW.`nome_original`,'categoria',NEW.`categoria`,'valor_total',NEW.`valor_total`,'total_parcelas',NEW.`total_parcelas`,'data_compra',NEW.`data_compra`,'origem',NEW.`origem`,'identificador_ofx',NEW.`identificador_ofx`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='cartao' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Despejando dados para a tabela `contas`
--

INSERT INTO `contas` (`id`, `nome`, `tipo`, `categoria`, `grupo_recorrencia`, `parcela_atual`, `total_parcelas`, `vencimento`, `paga`, `valor`, `criado_em`, `usuario_id`) VALUES
(1, 'teste', 'unica', 'pessoal', NULL, NULL, NULL, '2026-09-27', 0, 20.00, '2026-09-27 21:10:24', 1);

--
-- Acionadores `contas`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_contas_DELETE` AFTER DELETE ON `contas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'despesas','contas',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'tipo',OLD.`tipo`,'categoria',OLD.`categoria`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'vencimento',OLD.`vencimento`,'paga',OLD.`paga`,'valor',OLD.`valor`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_contas_INSERT` AFTER INSERT ON `contas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'despesas','contas',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'tipo',NEW.`tipo`,'categoria',NEW.`categoria`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'vencimento',NEW.`vencimento`,'paga',NEW.`paga`,'valor',NEW.`valor`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_contas_UPDATE` AFTER UPDATE ON `contas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'tipo',OLD.`tipo`,'categoria',OLD.`categoria`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'vencimento',OLD.`vencimento`,'paga',OLD.`paga`,'valor',OLD.`valor`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'tipo',NEW.`tipo`,'categoria',NEW.`categoria`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'vencimento',NEW.`vencimento`,'paga',NEW.`paga`,'valor',NEW.`valor`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'despesas','contas',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'tipo',OLD.`tipo`,'categoria',OLD.`categoria`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'vencimento',OLD.`vencimento`,'paga',OLD.`paga`,'valor',OLD.`valor`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'tipo',NEW.`tipo`,'categoria',NEW.`categoria`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'vencimento',NEW.`vencimento`,'paga',NEW.`paga`,'valor',NEW.`valor`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_contas_DELETE` BEFORE DELETE ON `contas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='despesas' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_contas_INSERT` BEFORE INSERT ON `contas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='despesas' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_contas_UPDATE` BEFORE UPDATE ON `contas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'tipo',OLD.`tipo`,'categoria',OLD.`categoria`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'vencimento',OLD.`vencimento`,'paga',OLD.`paga`,'valor',OLD.`valor`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'tipo',NEW.`tipo`,'categoria',NEW.`categoria`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'vencimento',NEW.`vencimento`,'paga',NEW.`paga`,'valor',NEW.`valor`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='despesas' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Acionadores `contas_financeiras`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_contas_financeiras_DELETE` AFTER DELETE ON `contas_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'saldos','contas_financeiras',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'nome',OLD.`nome`,'instituicao',OLD.`instituicao`,'icone',OLD.`icone`,'tipo',OLD.`tipo`,'saldo_inicial',OLD.`saldo_inicial`,'data_saldo_inicial',OLD.`data_saldo_inicial`,'ativa',OLD.`ativa`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_contas_financeiras_INSERT` AFTER INSERT ON `contas_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'saldos','contas_financeiras',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'nome',NEW.`nome`,'instituicao',NEW.`instituicao`,'icone',NEW.`icone`,'tipo',NEW.`tipo`,'saldo_inicial',NEW.`saldo_inicial`,'data_saldo_inicial',NEW.`data_saldo_inicial`,'ativa',NEW.`ativa`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_contas_financeiras_UPDATE` AFTER UPDATE ON `contas_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'nome',OLD.`nome`,'instituicao',OLD.`instituicao`,'icone',OLD.`icone`,'tipo',OLD.`tipo`,'saldo_inicial',OLD.`saldo_inicial`,'data_saldo_inicial',OLD.`data_saldo_inicial`,'ativa',OLD.`ativa`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'nome',NEW.`nome`,'instituicao',NEW.`instituicao`,'icone',NEW.`icone`,'tipo',NEW.`tipo`,'saldo_inicial',NEW.`saldo_inicial`,'data_saldo_inicial',NEW.`data_saldo_inicial`,'ativa',NEW.`ativa`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'saldos','contas_financeiras',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'nome',OLD.`nome`,'instituicao',OLD.`instituicao`,'icone',OLD.`icone`,'tipo',OLD.`tipo`,'saldo_inicial',OLD.`saldo_inicial`,'data_saldo_inicial',OLD.`data_saldo_inicial`,'ativa',OLD.`ativa`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'nome',NEW.`nome`,'instituicao',NEW.`instituicao`,'icone',NEW.`icone`,'tipo',NEW.`tipo`,'saldo_inicial',NEW.`saldo_inicial`,'data_saldo_inicial',NEW.`data_saldo_inicial`,'ativa',NEW.`ativa`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_contas_financeiras_DELETE` BEFORE DELETE ON `contas_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='saldos' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_contas_financeiras_INSERT` BEFORE INSERT ON `contas_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='saldos' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_contas_financeiras_UPDATE` BEFORE UPDATE ON `contas_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'nome',OLD.`nome`,'instituicao',OLD.`instituicao`,'icone',OLD.`icone`,'tipo',OLD.`tipo`,'saldo_inicial',OLD.`saldo_inicial`,'data_saldo_inicial',OLD.`data_saldo_inicial`,'ativa',OLD.`ativa`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'nome',NEW.`nome`,'instituicao',NEW.`instituicao`,'icone',NEW.`icone`,'tipo',NEW.`tipo`,'saldo_inicial',NEW.`saldo_inicial`,'data_saldo_inicial',NEW.`data_saldo_inicial`,'ativa',NEW.`ativa`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='saldos' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `corretoras`
--

CREATE TABLE `corretoras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `corretoras`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_corretoras_DELETE` AFTER DELETE ON `corretoras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'daytrade','corretoras',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_corretoras_INSERT` AFTER INSERT ON `corretoras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'daytrade','corretoras',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_corretoras_UPDATE` AFTER UPDATE ON `corretoras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'daytrade','corretoras',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_corretoras_DELETE` BEFORE DELETE ON `corretoras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF; INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) SELECT x.usuario_id,@mcf_ator_id,'daytrade','corretora_taxas',x.id,'excluir',JSON_OBJECT('id',x.`id`,'corretora_id',x.`corretora_id`,'nome_taxa',x.`nome_taxa`,'percentual',x.`percentual`,'usuario_id',x.`usuario_id`),NULL FROM `corretora_taxas` x WHERE x.`corretora_id`=OLD.id AND x.usuario_id=OLD.usuario_id; INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) SELECT x.usuario_id,@mcf_ator_id,'daytrade','operacoes',x.id,'excluir',JSON_OBJECT('id',x.`id`,'corretora_id',x.`corretora_id`,'data',x.`data`,'acao',x.`acao`,'quantidade',x.`quantidade`,'valor_compra',x.`valor_compra`,'valor_venda',x.`valor_venda`,'total_compra',x.`total_compra`,'total_venda',x.`total_venda`,'valor_operacao',x.`valor_operacao`,'lucro_bruto',x.`lucro_bruto`,'taxas',x.`taxas`,'deducao_1',x.`deducao_1`,'imposto_20',x.`imposto_20`,'lucro_desc',x.`lucro_desc`,'darf',x.`darf`,'lucro_final',x.`lucro_final`,'criado_em',x.`criado_em`,'usuario_id',x.`usuario_id`),NULL FROM `operacoes` x WHERE x.`corretora_id`=OLD.id AND x.usuario_id=OLD.usuario_id;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_corretoras_INSERT` BEFORE INSERT ON `corretoras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_corretoras_UPDATE` BEFORE UPDATE ON `corretoras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Acionadores `corretora_taxas`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_corretora_taxas_DELETE` AFTER DELETE ON `corretora_taxas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'daytrade','corretora_taxas',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'nome_taxa',OLD.`nome_taxa`,'percentual',OLD.`percentual`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_corretora_taxas_INSERT` AFTER INSERT ON `corretora_taxas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'daytrade','corretora_taxas',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'nome_taxa',NEW.`nome_taxa`,'percentual',NEW.`percentual`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_corretora_taxas_UPDATE` AFTER UPDATE ON `corretora_taxas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'nome_taxa',OLD.`nome_taxa`,'percentual',OLD.`percentual`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'nome_taxa',NEW.`nome_taxa`,'percentual',NEW.`percentual`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'daytrade','corretora_taxas',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'nome_taxa',OLD.`nome_taxa`,'percentual',OLD.`percentual`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'nome_taxa',NEW.`nome_taxa`,'percentual',NEW.`percentual`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_corretora_taxas_DELETE` BEFORE DELETE ON `corretora_taxas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_corretora_taxas_INSERT` BEFORE INSERT ON `corretora_taxas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_corretora_taxas_UPDATE` BEFORE UPDATE ON `corretora_taxas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'nome_taxa',OLD.`nome_taxa`,'percentual',OLD.`percentual`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'nome_taxa',NEW.`nome_taxa`,'percentual',NEW.`percentual`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Acionadores `div_datacom`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_div_datacom_DELETE` AFTER DELETE ON `div_datacom` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'investimentos','div_datacom',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'datacom',OLD.`datacom`,'datapag',OLD.`datapag`,'valor',OLD.`valor`,'tipo',OLD.`tipo`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_div_datacom_INSERT` AFTER INSERT ON `div_datacom` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'investimentos','div_datacom',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'datacom',NEW.`datacom`,'datapag',NEW.`datapag`,'valor',NEW.`valor`,'tipo',NEW.`tipo`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_div_datacom_UPDATE` AFTER UPDATE ON `div_datacom` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'datacom',OLD.`datacom`,'datapag',OLD.`datapag`,'valor',OLD.`valor`,'tipo',OLD.`tipo`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'datacom',NEW.`datacom`,'datapag',NEW.`datapag`,'valor',NEW.`valor`,'tipo',NEW.`tipo`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'investimentos','div_datacom',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'datacom',OLD.`datacom`,'datapag',OLD.`datapag`,'valor',OLD.`valor`,'tipo',OLD.`tipo`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'datacom',NEW.`datacom`,'datapag',NEW.`datapag`,'valor',NEW.`valor`,'tipo',NEW.`tipo`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_div_datacom_DELETE` BEFORE DELETE ON `div_datacom` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_div_datacom_INSERT` BEFORE INSERT ON `div_datacom` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_div_datacom_UPDATE` BEFORE UPDATE ON `div_datacom` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'datacom',OLD.`datacom`,'datapag',OLD.`datapag`,'valor',OLD.`valor`,'tipo',OLD.`tipo`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'datacom',NEW.`datacom`,'datapag',NEW.`datapag`,'valor',NEW.`valor`,'tipo',NEW.`tipo`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_alteracoes`
--

CREATE TABLE `historico_alteracoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `proprietario_id` int(11) NOT NULL,
  `ator_id` int(11) NOT NULL,
  `modulo` varchar(30) NOT NULL,
  `tabela` varchar(64) NOT NULL,
  `registro` varchar(255) NOT NULL,
  `acao` varchar(12) NOT NULL,
  `antes` longtext DEFAULT NULL,
  `depois` longtext DEFAULT NULL,
  `criado_em` datetime(6) NOT NULL DEFAULT current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `historico_alteracoes`
--

INSERT INTO `historico_alteracoes` (`id`, `proprietario_id`, `ator_id`, `modulo`, `tabela`, `registro`, `acao`, `antes`, `depois`, `criado_em`) VALUES
(1, 1, 1, 'despesas', 'contas', '1', 'cadastrar', NULL, '{\"id\": 1, \"nome\": \"teste\", \"tipo\": \"unica\", \"categoria\": \"pessoal\", \"grupo_recorrencia\": null, \"parcela_atual\": null, \"total_parcelas\": null, \"vencimento\": \"2026-09-27\", \"paga\": 0, \"valor\": 20.00, \"criado_em\": \"2026-09-27 18:10:24\", \"usuario_id\": 1}', '2026-09-27 18:10:24.697103'),
(2, 1, 1, 'cartao', 'compras', '1', 'cadastrar', NULL, '{\"id\": 1, \"nome\": \"MP*MELIMAIS            OSASCO        BR\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782\", \"usuario_id\": 1}', '2026-09-28 20:07:37.591822'),
(3, 1, 1, 'cartao', 'cartoes', '1', 'cadastrar', NULL, '{\"id\": 1, \"compra_id\": 1, \"data\": \"2026-06-22\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042248540000000090110000000001\", \"usuario_id\": 1}', '2026-09-28 20:07:37.593602'),
(4, 1, 1, 'cartao', 'compras', '2', 'cadastrar', NULL, '{\"id\": 2, \"nome\": \"POPULAR PET PETSHOP SA SAO JOSE DOS  BR\", \"nome_original\": \"POPULAR PET PETSHOP SA SAO JOSE DOS  BR\", \"categoria\": \"pessoal\", \"valor_total\": 194.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"52afd8c9758269fadefaec0ab56d235a6cebf373e7cac70fae727a0fac552b5b\", \"usuario_id\": 1}', '2026-09-28 20:07:37.599613'),
(5, 1, 1, 'cartao', 'cartoes', '2', 'cadastrar', NULL, '{\"id\": 2, \"compra_id\": 2, \"data\": \"2026-06-26\", \"valor\": 194.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042648540000000090110000000002\", \"usuario_id\": 1}', '2026-09-28 20:07:37.600235'),
(6, 1, 1, 'cartao', 'compras', '3', 'cadastrar', NULL, '{\"id\": 3, \"nome\": \"MERCADOLIVRE*MERCADOLIVSAO PAULO     BR\", \"nome_original\": \"MERCADOLIVRE*MERCADOLIVSAO PAULO     BR\", \"categoria\": \"pessoal\", \"valor_total\": 264.21, \"total_parcelas\": 1, \"data_compra\": \"2026-04-27\", \"origem\": \"ofx\", \"identificador_ofx\": \"633b607901be93f6ab2bf235a88292fda08ad8fe8f285b11a66260b7fcd0c417\", \"usuario_id\": 1}', '2026-09-28 20:07:37.603616'),
(7, 1, 1, 'cartao', 'cartoes', '3', 'cadastrar', NULL, '{\"id\": 3, \"compra_id\": 3, \"data\": \"2026-06-27\", \"valor\": 264.21, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042748540000000090110000000003\", \"usuario_id\": 1}', '2026-09-28 20:07:37.604258'),
(8, 1, 1, 'cartao', 'compras', '4', 'cadastrar', NULL, '{\"id\": 4, \"nome\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-04-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e\", \"usuario_id\": 1}', '2026-09-28 20:07:37.607895'),
(9, 1, 1, 'cartao', 'cartoes', '4', 'cadastrar', NULL, '{\"id\": 4, \"compra_id\": 4, \"data\": \"2026-06-28\", \"valor\": 31.43, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042848540000000090110000000004\", \"usuario_id\": 1}', '2026-09-28 20:07:37.609107'),
(10, 1, 1, 'cartao', 'compras', '5', 'cadastrar', NULL, '{\"id\": 5, \"nome\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261\", \"usuario_id\": 1}', '2026-09-28 20:07:37.614532'),
(11, 1, 1, 'cartao', 'cartoes', '5', 'cadastrar', NULL, '{\"id\": 5, \"compra_id\": 5, \"data\": \"2026-06-03\", \"valor\": 23.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026050348540000000090110000000005\", \"usuario_id\": 1}', '2026-09-28 20:07:37.615516'),
(12, 1, 1, 'cartao', 'compras', '6', 'cadastrar', NULL, '{\"id\": 6, \"nome\": \"MP*MERCADOLIVRE        OSASCO        BR\", \"nome_original\": \"MP*MERCADOLIVRE        OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 242.55, \"total_parcelas\": 1, \"data_compra\": \"2026-05-18\", \"origem\": \"ofx\", \"identificador_ofx\": \"25e2c047b630586a56fa2a380de906ef6f36cc26c8a904d0bdc9c3c59c9b649b\", \"usuario_id\": 1}', '2026-09-28 20:07:37.618677'),
(13, 1, 1, 'cartao', 'cartoes', '6', 'cadastrar', NULL, '{\"id\": 6, \"compra_id\": 6, \"data\": \"2026-06-18\", \"valor\": 242.55, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026051848540000000090110000000006\", \"usuario_id\": 1}', '2026-09-28 20:07:37.619494'),
(14, 1, 1, 'cartao', 'compras', '7', 'cadastrar', NULL, '{\"id\": 7, \"nome\": \"MP*MELIMAIS            OSASCO        BR\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3\", \"usuario_id\": 1}', '2026-09-28 20:07:37.622370'),
(15, 1, 1, 'cartao', 'cartoes', '7', 'cadastrar', NULL, '{\"id\": 7, \"compra_id\": 7, \"data\": \"2026-06-22\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026052248540000000090110000000007\", \"usuario_id\": 1}', '2026-09-28 20:07:37.623431'),
(16, 1, 1, 'cartao', 'compras', '8', 'cadastrar', NULL, '{\"id\": 8, \"nome\": \"MARISA 608             TAUBATE       BR\", \"nome_original\": \"MARISA 608             TAUBATE       BR\", \"categoria\": \"pessoal\", \"valor_total\": 125.97, \"total_parcelas\": 1, \"data_compra\": \"2026-04-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"54c64ccfcf7816334c3db0fd94a852855a046fe4184bfe72e4f14ee30addf4c7\", \"usuario_id\": 1}', '2026-09-28 20:07:37.626534'),
(17, 1, 1, 'cartao', 'cartoes', '8', 'cadastrar', NULL, '{\"id\": 8, \"compra_id\": 8, \"data\": \"2026-06-30\", \"valor\": 125.97, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000008\", \"usuario_id\": 1}', '2026-09-28 20:07:37.627314'),
(18, 1, 1, 'cartao', 'compras', '9', 'cadastrar', NULL, '{\"id\": 9, \"nome\": \"DM*NintendoeShop       SAO PAULO     BR\", \"nome_original\": \"DM*NintendoeShop       SAO PAULO     BR\", \"categoria\": \"pessoal\", \"valor_total\": 44.39, \"total_parcelas\": 1, \"data_compra\": \"2026-04-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"b494e1389dd853947a44925c15a74206b698fc4859b5f8de08d3f70f300cef01\", \"usuario_id\": 1}', '2026-09-28 20:07:37.629175'),
(19, 1, 1, 'cartao', 'cartoes', '9', 'cadastrar', NULL, '{\"id\": 9, \"compra_id\": 9, \"data\": \"2026-06-22\", \"valor\": 44.39, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042248540000000090110000000009\", \"usuario_id\": 1}', '2026-09-28 20:07:37.629772'),
(20, 1, 1, 'cartao', 'compras', '10', 'cadastrar', NULL, '{\"id\": 10, \"nome\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 300.03, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '2026-09-28 20:07:37.631981'),
(21, 1, 1, 'cartao', 'cartoes', '10', 'cadastrar', NULL, '{\"id\": 10, \"compra_id\": 10, \"data\": \"2026-06-25\", \"valor\": 100.01, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042548540000000090110000000010\", \"usuario_id\": 1}', '2026-09-28 20:07:37.632483'),
(22, 1, 1, 'cartao', 'cartoes', '11', 'cadastrar', NULL, '{\"id\": 11, \"compra_id\": 10, \"data\": \"2026-07-25\", \"valor\": 100.01, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.633056'),
(23, 1, 1, 'cartao', 'cartoes', '12', 'cadastrar', NULL, '{\"id\": 12, \"compra_id\": 10, \"data\": \"2026-08-25\", \"valor\": 100.01, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.634099'),
(24, 1, 1, 'cartao', 'compras', '11', 'cadastrar', NULL, '{\"id\": 11, \"nome\": \"MERCADOLIVRE*  ILHABELA    BR\", \"nome_original\": \"MERCADOLIVRE*  ILHABELA    BR\", \"categoria\": \"pessoal\", \"valor_total\": 487.98, \"total_parcelas\": 3, \"data_compra\": \"2026-03-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"12b30b569ba27f26924ee2aa3d21f21ec4cdb5675477ea6c4d98b281f08d1a7c\", \"usuario_id\": 1}', '2026-09-28 20:07:37.636381'),
(25, 1, 1, 'cartao', 'cartoes', '13', 'cadastrar', NULL, '{\"id\": 13, \"compra_id\": 11, \"data\": \"2026-05-26\", \"valor\": 162.66, \"parcela\": 1, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.636915'),
(26, 1, 1, 'cartao', 'cartoes', '14', 'cadastrar', NULL, '{\"id\": 14, \"compra_id\": 11, \"data\": \"2026-06-26\", \"valor\": 162.66, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026032648540000000090110000000011\", \"usuario_id\": 1}', '2026-09-28 20:07:37.637547'),
(27, 1, 1, 'cartao', 'cartoes', '15', 'cadastrar', NULL, '{\"id\": 15, \"compra_id\": 11, \"data\": \"2026-07-26\", \"valor\": 162.66, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.638347'),
(28, 1, 1, 'cartao', 'compras', '12', 'cadastrar', NULL, '{\"id\": 12, \"nome\": \"MLP    *KaBuM  eldorado do BR\", \"nome_original\": \"MLP    *KaBuM  eldorado do BR\", \"categoria\": \"pessoal\", \"valor_total\": 587.00, \"total_parcelas\": 4, \"data_compra\": \"2026-01-29\", \"origem\": \"ofx\", \"identificador_ofx\": \"fd7c5b69466fd5d7c969fae8479bd89fe63ed403765317d0fbe7332b492ed063\", \"usuario_id\": 1}', '2026-09-28 20:07:37.639982'),
(29, 1, 1, 'cartao', 'cartoes', '16', 'cadastrar', NULL, '{\"id\": 16, \"compra_id\": 12, \"data\": \"2026-03-29\", \"valor\": 146.75, \"parcela\": 1, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.640374'),
(30, 1, 1, 'cartao', 'cartoes', '17', 'cadastrar', NULL, '{\"id\": 17, \"compra_id\": 12, \"data\": \"2026-04-29\", \"valor\": 146.75, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.641111'),
(31, 1, 1, 'cartao', 'cartoes', '18', 'cadastrar', NULL, '{\"id\": 18, \"compra_id\": 12, \"data\": \"2026-05-29\", \"valor\": 146.75, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.641553'),
(32, 1, 1, 'cartao', 'cartoes', '19', 'cadastrar', NULL, '{\"id\": 19, \"compra_id\": 12, \"data\": \"2026-06-29\", \"valor\": 146.75, \"parcela\": 4, \"paga\": 0, \"fitid\": \"2026012948540000000090110000000012\", \"usuario_id\": 1}', '2026-09-28 20:07:37.641964'),
(33, 1, 1, 'cartao', 'compras', '13', 'cadastrar', NULL, '{\"id\": 13, \"nome\": \"IGUASPO*IGUAS  CAMPINAS    BR\", \"nome_original\": \"IGUASPO*IGUAS  CAMPINAS    BR\", \"categoria\": \"pessoal\", \"valor_total\": 259.98, \"total_parcelas\": 2, \"data_compra\": \"2026-03-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"80e6d53a02117afd7847a41b8ce5bb6d2e9bdb66b5d715b7264678053a23ef1c\", \"usuario_id\": 1}', '2026-09-28 20:07:37.643111'),
(34, 1, 1, 'cartao', 'cartoes', '20', 'cadastrar', NULL, '{\"id\": 20, \"compra_id\": 13, \"data\": \"2026-05-30\", \"valor\": 129.99, \"parcela\": 1, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.643627'),
(35, 1, 1, 'cartao', 'cartoes', '21', 'cadastrar', NULL, '{\"id\": 21, \"compra_id\": 13, \"data\": \"2026-06-30\", \"valor\": 129.99, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026033048540000000090110000000013\", \"usuario_id\": 1}', '2026-09-28 20:07:37.644096'),
(36, 1, 1, 'cartao', 'compras', '14', 'cadastrar', NULL, '{\"id\": 14, \"nome\": \"VIVARA TAB     TAUBATE     BR\", \"nome_original\": \"VIVARA TAB     TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 11990.00, \"total_parcelas\": 10, \"data_compra\": \"2026-04-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"0817bce85a3108e717d35a7c1b1379e201f0cf8400fe76fd556dc1d8e81f8dfa\", \"usuario_id\": 1}', '2026-09-28 20:07:37.645413'),
(37, 1, 1, 'cartao', 'cartoes', '22', 'cadastrar', NULL, '{\"id\": 22, \"compra_id\": 14, \"data\": \"2026-06-30\", \"valor\": 1199.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000014\", \"usuario_id\": 1}', '2026-09-28 20:07:37.645824'),
(38, 1, 1, 'cartao', 'cartoes', '23', 'cadastrar', NULL, '{\"id\": 23, \"compra_id\": 14, \"data\": \"2026-07-30\", \"valor\": 1199.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.646224'),
(39, 1, 1, 'cartao', 'cartoes', '24', 'cadastrar', NULL, '{\"id\": 24, \"compra_id\": 14, \"data\": \"2026-08-30\", \"valor\": 1199.00, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.646577'),
(40, 1, 1, 'cartao', 'cartoes', '25', 'cadastrar', NULL, '{\"id\": 25, \"compra_id\": 14, \"data\": \"2026-09-30\", \"valor\": 1199.00, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.647295'),
(41, 1, 1, 'cartao', 'cartoes', '26', 'cadastrar', NULL, '{\"id\": 26, \"compra_id\": 14, \"data\": \"2026-10-30\", \"valor\": 1199.00, \"parcela\": 5, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.647665'),
(42, 1, 1, 'cartao', 'cartoes', '27', 'cadastrar', NULL, '{\"id\": 27, \"compra_id\": 14, \"data\": \"2026-11-30\", \"valor\": 1199.00, \"parcela\": 6, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.648004'),
(43, 1, 1, 'cartao', 'cartoes', '28', 'cadastrar', NULL, '{\"id\": 28, \"compra_id\": 14, \"data\": \"2026-12-30\", \"valor\": 1199.00, \"parcela\": 7, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.648350'),
(44, 1, 1, 'cartao', 'cartoes', '29', 'cadastrar', NULL, '{\"id\": 29, \"compra_id\": 14, \"data\": \"2027-01-30\", \"valor\": 1199.00, \"parcela\": 8, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.648644'),
(45, 1, 1, 'cartao', 'cartoes', '30', 'cadastrar', NULL, '{\"id\": 30, \"compra_id\": 14, \"data\": \"2027-02-28\", \"valor\": 1199.00, \"parcela\": 9, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.649252'),
(46, 1, 1, 'cartao', 'cartoes', '31', 'cadastrar', NULL, '{\"id\": 31, \"compra_id\": 14, \"data\": \"2027-03-30\", \"valor\": 1199.00, \"parcela\": 10, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.649577'),
(47, 1, 1, 'cartao', 'compras', '15', 'cadastrar', NULL, '{\"id\": 15, \"nome\": \"A ESPORTIVA C  SANTO ANDRE BR\", \"nome_original\": \"A ESPORTIVA C  SANTO ANDRE BR\", \"categoria\": \"pessoal\", \"valor_total\": 359.70, \"total_parcelas\": 2, \"data_compra\": \"2026-04-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"8d2971f971121b18265910360bba3d4f96d706fe204bac017bccdd546bf607a8\", \"usuario_id\": 1}', '2026-09-28 20:07:37.650656'),
(48, 1, 1, 'cartao', 'cartoes', '32', 'cadastrar', NULL, '{\"id\": 32, \"compra_id\": 15, \"data\": \"2026-06-30\", \"valor\": 179.85, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000015\", \"usuario_id\": 1}', '2026-09-28 20:07:37.651088'),
(49, 1, 1, 'cartao', 'cartoes', '33', 'cadastrar', NULL, '{\"id\": 33, \"compra_id\": 15, \"data\": \"2026-07-30\", \"valor\": 179.85, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.651444'),
(50, 1, 1, 'cartao', 'compras', '16', 'cadastrar', NULL, '{\"id\": 16, \"nome\": \"PANDORA DO BR  SAO PAULO   BR\", \"nome_original\": \"PANDORA DO BR  SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 1917.99, \"total_parcelas\": 3, \"data_compra\": \"2026-03-01\", \"origem\": \"ofx\", \"identificador_ofx\": \"661ac936e7ac9b11da8ce9333899dd291d72a9921d785c5913b914c726d29db1\", \"usuario_id\": 1}', '2026-09-28 20:07:37.652881'),
(51, 1, 1, 'cartao', 'cartoes', '34', 'cadastrar', NULL, '{\"id\": 34, \"compra_id\": 16, \"data\": \"2026-04-01\", \"valor\": 639.33, \"parcela\": 1, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.653271'),
(52, 1, 1, 'cartao', 'cartoes', '35', 'cadastrar', NULL, '{\"id\": 35, \"compra_id\": 16, \"data\": \"2026-05-01\", \"valor\": 639.33, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:07:37.653559'),
(53, 1, 1, 'cartao', 'cartoes', '36', 'cadastrar', NULL, '{\"id\": 36, \"compra_id\": 16, \"data\": \"2026-06-01\", \"valor\": 639.33, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026030148540000000090110000000016\", \"usuario_id\": 1}', '2026-09-28 20:07:37.653916'),
(54, 1, 1, 'cartao', 'cartao_nomes_recorrentes', 'cdfe8737c29de9c674721b26eb8a8ffd774e96821c80fa64a3a19d5b0f1ca07e', 'cadastrar', NULL, '{\"chave_descricao\": \"cdfe8737c29de9c674721b26eb8a8ffd774e96821c80fa64a3a19d5b0f1ca07e\", \"descricao_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"nome_personalizado\": \"Spotify museu\", \"usuario_id\": 1}', '2026-09-28 20:10:18.117640'),
(55, 1, 1, 'cartao', 'compras', '5', 'editar', '{\"id\": 5, \"nome\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261\", \"usuario_id\": 1}', '{\"id\": 5, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261\", \"usuario_id\": 1}', '2026-09-28 20:10:18.120358'),
(56, 1, 1, 'cartao', 'compras', '16', 'editar', '{\"id\": 16, \"nome\": \"PANDORA DO BR  SAO PAULO   BR\", \"nome_original\": \"PANDORA DO BR  SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 1917.99, \"total_parcelas\": 3, \"data_compra\": \"2026-03-01\", \"origem\": \"ofx\", \"identificador_ofx\": \"661ac936e7ac9b11da8ce9333899dd291d72a9921d785c5913b914c726d29db1\", \"usuario_id\": 1}', '{\"id\": 16, \"nome\": \"Correntinha Pam\", \"nome_original\": \"PANDORA DO BR  SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 1917.99, \"total_parcelas\": 3, \"data_compra\": \"2026-03-01\", \"origem\": \"ofx\", \"identificador_ofx\": \"661ac936e7ac9b11da8ce9333899dd291d72a9921d785c5913b914c726d29db1\", \"usuario_id\": 1}', '2026-09-28 20:10:44.861238'),
(57, 1, 1, 'cartao', 'compras', '10', 'editar', '{\"id\": 10, \"nome\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 300.03, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '{\"id\": 10, \"nome\": \"Aquela 01\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 300.03, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '2026-09-28 20:11:07.252203'),
(58, 1, 1, 'cartao', 'compras', '14', 'editar', '{\"id\": 14, \"nome\": \"VIVARA TAB     TAUBATE     BR\", \"nome_original\": \"VIVARA TAB     TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 11990.00, \"total_parcelas\": 10, \"data_compra\": \"2026-04-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"0817bce85a3108e717d35a7c1b1379e201f0cf8400fe76fd556dc1d8e81f8dfa\", \"usuario_id\": 1}', '{\"id\": 14, \"nome\": \"Aquela 02\", \"nome_original\": \"VIVARA TAB     TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 11990.00, \"total_parcelas\": 10, \"data_compra\": \"2026-04-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"0817bce85a3108e717d35a7c1b1379e201f0cf8400fe76fd556dc1d8e81f8dfa\", \"usuario_id\": 1}', '2026-09-28 20:11:26.985723'),
(59, 1, 1, 'cartao', 'cartao_nomes_recorrentes', '4f8e028fc7eae1967aad1c70a45abfc1321d97866dbc8057099f36344f3fb92f', 'cadastrar', NULL, '{\"chave_descricao\": \"4f8e028fc7eae1967aad1c70a45abfc1321d97866dbc8057099f36344f3fb92f\", \"descricao_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"nome_personalizado\": \"HBO MAX\", \"usuario_id\": 1}', '2026-09-28 20:11:51.392003'),
(60, 1, 1, 'cartao', 'compras', '4', 'editar', '{\"id\": 4, \"nome\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-04-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e\", \"usuario_id\": 1}', '{\"id\": 4, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-04-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e\", \"usuario_id\": 1}', '2026-09-28 20:11:51.399383'),
(61, 1, 1, 'cartao', 'cartao_nomes_recorrentes', 'c00d6eb360d93e9ca99c47d772db31fb84e2140f073793e7f9a34ba8e526f9cd', 'cadastrar', NULL, '{\"chave_descricao\": \"c00d6eb360d93e9ca99c47d772db31fb84e2140f073793e7f9a34ba8e526f9cd\", \"descricao_original\": \"MP*MELIMAIS            OSASCO        BR\", \"nome_personalizado\": \"Meli\", \"usuario_id\": 1}', '2026-09-28 20:12:27.799697'),
(62, 1, 1, 'cartao', 'compras', '1', 'editar', '{\"id\": 1, \"nome\": \"MP*MELIMAIS            OSASCO        BR\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782\", \"usuario_id\": 1}', '{\"id\": 1, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782\", \"usuario_id\": 1}', '2026-09-28 20:12:27.804035'),
(63, 1, 1, 'cartao', 'compras', '7', 'editar', '{\"id\": 7, \"nome\": \"MP*MELIMAIS            OSASCO        BR\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3\", \"usuario_id\": 1}', '{\"id\": 7, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3\", \"usuario_id\": 1}', '2026-09-28 20:12:27.804898'),
(64, 1, 1, 'cartao', 'compras', '5', 'editar', '{\"id\": 5, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261\", \"usuario_id\": 1}', '{\"id\": 5, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"unica\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261\", \"usuario_id\": 1}', '2026-09-28 20:13:28.551519'),
(65, 1, 1, 'cartao', 'compras', '1', 'editar', '{\"id\": 1, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782\", \"usuario_id\": 1}', '{\"id\": 1, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782\", \"usuario_id\": 1}', '2026-09-28 20:14:12.594169'),
(66, 1, 1, 'cartao', 'compras', '7', 'editar', '{\"id\": 7, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3\", \"usuario_id\": 1}', '{\"id\": 7, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-05-22\", \"origem\": \"ofx\", \"identificador_ofx\": \"cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3\", \"usuario_id\": 1}', '2026-09-28 20:14:12.597445'),
(67, 1, 1, 'cartao', 'compras', '4', 'editar', '{\"id\": 4, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-04-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e\", \"usuario_id\": 1}', '{\"id\": 4, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-04-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e\", \"usuario_id\": 1}', '2026-09-28 20:14:12.598375'),
(68, 1, 1, 'cartao', 'compras', '2', 'editar', '{\"id\": 2, \"nome\": \"POPULAR PET PETSHOP SA SAO JOSE DOS  BR\", \"nome_original\": \"POPULAR PET PETSHOP SA SAO JOSE DOS  BR\", \"categoria\": \"pessoal\", \"valor_total\": 194.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"52afd8c9758269fadefaec0ab56d235a6cebf373e7cac70fae727a0fac552b5b\", \"usuario_id\": 1}', '{\"id\": 2, \"nome\": \"POPULAR PET PETSHOP SA SAO JOSE DOS  BR\", \"nome_original\": \"POPULAR PET PETSHOP SA SAO JOSE DOS  BR\", \"categoria\": \"conjunta\", \"valor_total\": 194.90, \"total_parcelas\": 1, \"data_compra\": \"2026-04-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"52afd8c9758269fadefaec0ab56d235a6cebf373e7cac70fae727a0fac552b5b\", \"usuario_id\": 1}', '2026-09-28 20:14:30.282632'),
(69, 1, 1, 'cartao', 'cartoes', '1', 'editar', '{\"id\": 1, \"compra_id\": 1, \"data\": \"2026-06-22\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042248540000000090110000000001\", \"usuario_id\": 1}', '{\"id\": 1, \"compra_id\": 1, \"data\": \"2026-06-22\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026042248540000000090110000000001\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(70, 1, 1, 'cartao', 'cartoes', '2', 'editar', '{\"id\": 2, \"compra_id\": 2, \"data\": \"2026-06-26\", \"valor\": 194.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042648540000000090110000000002\", \"usuario_id\": 1}', '{\"id\": 2, \"compra_id\": 2, \"data\": \"2026-06-26\", \"valor\": 194.90, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026042648540000000090110000000002\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(71, 1, 1, 'cartao', 'cartoes', '3', 'editar', '{\"id\": 3, \"compra_id\": 3, \"data\": \"2026-06-27\", \"valor\": 264.21, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042748540000000090110000000003\", \"usuario_id\": 1}', '{\"id\": 3, \"compra_id\": 3, \"data\": \"2026-06-27\", \"valor\": 264.21, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026042748540000000090110000000003\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(72, 1, 1, 'cartao', 'cartoes', '4', 'editar', '{\"id\": 4, \"compra_id\": 4, \"data\": \"2026-06-28\", \"valor\": 31.43, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042848540000000090110000000004\", \"usuario_id\": 1}', '{\"id\": 4, \"compra_id\": 4, \"data\": \"2026-06-28\", \"valor\": 31.43, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026042848540000000090110000000004\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(73, 1, 1, 'cartao', 'cartoes', '5', 'editar', '{\"id\": 5, \"compra_id\": 5, \"data\": \"2026-06-03\", \"valor\": 23.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026050348540000000090110000000005\", \"usuario_id\": 1}', '{\"id\": 5, \"compra_id\": 5, \"data\": \"2026-06-03\", \"valor\": 23.90, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026050348540000000090110000000005\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(74, 1, 1, 'cartao', 'cartoes', '6', 'editar', '{\"id\": 6, \"compra_id\": 6, \"data\": \"2026-06-18\", \"valor\": 242.55, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026051848540000000090110000000006\", \"usuario_id\": 1}', '{\"id\": 6, \"compra_id\": 6, \"data\": \"2026-06-18\", \"valor\": 242.55, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026051848540000000090110000000006\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(75, 1, 1, 'cartao', 'cartoes', '7', 'editar', '{\"id\": 7, \"compra_id\": 7, \"data\": \"2026-06-22\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026052248540000000090110000000007\", \"usuario_id\": 1}', '{\"id\": 7, \"compra_id\": 7, \"data\": \"2026-06-22\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026052248540000000090110000000007\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(76, 1, 1, 'cartao', 'cartoes', '8', 'editar', '{\"id\": 8, \"compra_id\": 8, \"data\": \"2026-06-30\", \"valor\": 125.97, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000008\", \"usuario_id\": 1}', '{\"id\": 8, \"compra_id\": 8, \"data\": \"2026-06-30\", \"valor\": 125.97, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026043048540000000090110000000008\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(77, 1, 1, 'cartao', 'cartoes', '9', 'editar', '{\"id\": 9, \"compra_id\": 9, \"data\": \"2026-06-22\", \"valor\": 44.39, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042248540000000090110000000009\", \"usuario_id\": 1}', '{\"id\": 9, \"compra_id\": 9, \"data\": \"2026-06-22\", \"valor\": 44.39, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026042248540000000090110000000009\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(78, 1, 1, 'cartao', 'cartoes', '10', 'editar', '{\"id\": 10, \"compra_id\": 10, \"data\": \"2026-06-25\", \"valor\": 100.01, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026042548540000000090110000000010\", \"usuario_id\": 1}', '{\"id\": 10, \"compra_id\": 10, \"data\": \"2026-06-25\", \"valor\": 100.01, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026042548540000000090110000000010\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(79, 1, 1, 'cartao', 'cartoes', '14', 'editar', '{\"id\": 14, \"compra_id\": 11, \"data\": \"2026-06-26\", \"valor\": 162.66, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026032648540000000090110000000011\", \"usuario_id\": 1}', '{\"id\": 14, \"compra_id\": 11, \"data\": \"2026-06-26\", \"valor\": 162.66, \"parcela\": 2, \"paga\": 1, \"fitid\": \"2026032648540000000090110000000011\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(80, 1, 1, 'cartao', 'cartoes', '19', 'editar', '{\"id\": 19, \"compra_id\": 12, \"data\": \"2026-06-29\", \"valor\": 146.75, \"parcela\": 4, \"paga\": 0, \"fitid\": \"2026012948540000000090110000000012\", \"usuario_id\": 1}', '{\"id\": 19, \"compra_id\": 12, \"data\": \"2026-06-29\", \"valor\": 146.75, \"parcela\": 4, \"paga\": 1, \"fitid\": \"2026012948540000000090110000000012\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(81, 1, 1, 'cartao', 'cartoes', '21', 'editar', '{\"id\": 21, \"compra_id\": 13, \"data\": \"2026-06-30\", \"valor\": 129.99, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026033048540000000090110000000013\", \"usuario_id\": 1}', '{\"id\": 21, \"compra_id\": 13, \"data\": \"2026-06-30\", \"valor\": 129.99, \"parcela\": 2, \"paga\": 1, \"fitid\": \"2026033048540000000090110000000013\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(82, 1, 1, 'cartao', 'cartoes', '22', 'editar', '{\"id\": 22, \"compra_id\": 14, \"data\": \"2026-06-30\", \"valor\": 1199.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000014\", \"usuario_id\": 1}', '{\"id\": 22, \"compra_id\": 14, \"data\": \"2026-06-30\", \"valor\": 1199.00, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026043048540000000090110000000014\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(83, 1, 1, 'cartao', 'cartoes', '32', 'editar', '{\"id\": 32, \"compra_id\": 15, \"data\": \"2026-06-30\", \"valor\": 179.85, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000015\", \"usuario_id\": 1}', '{\"id\": 32, \"compra_id\": 15, \"data\": \"2026-06-30\", \"valor\": 179.85, \"parcela\": 1, \"paga\": 1, \"fitid\": \"2026043048540000000090110000000015\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(84, 1, 1, 'cartao', 'cartoes', '36', 'editar', '{\"id\": 36, \"compra_id\": 16, \"data\": \"2026-06-01\", \"valor\": 639.33, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026030148540000000090110000000016\", \"usuario_id\": 1}', '{\"id\": 36, \"compra_id\": 16, \"data\": \"2026-06-01\", \"valor\": 639.33, \"parcela\": 3, \"paga\": 1, \"fitid\": \"2026030148540000000090110000000016\", \"usuario_id\": 1}', '2026-09-28 20:14:57.359052'),
(85, 1, 1, 'cartao', 'compras', '17', 'cadastrar', NULL, '{\"id\": 17, \"nome\": \"MP*GGJJK               HORTOLNDIA    BR\", \"nome_original\": \"MP*GGJJK               HORTOLNDIA    BR\", \"categoria\": \"pessoal\", \"valor_total\": -190.98, \"total_parcelas\": 1, \"data_compra\": \"2026-06-08\", \"origem\": \"ofx\", \"identificador_ofx\": \"da2b0c9b10c3e33d85f0d8106423ed805ce152caa8b666acc80433f6e5dd0885\", \"usuario_id\": 1}', '2026-09-28 20:16:08.740229'),
(86, 1, 1, 'cartao', 'cartoes', '37', 'cadastrar', NULL, '{\"id\": 37, \"compra_id\": 17, \"data\": \"2026-07-08\", \"valor\": -190.98, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060848540000000090110000000001\", \"usuario_id\": 1}', '2026-09-28 20:16:08.744759'),
(87, 1, 1, 'cartao', 'compras', '18', 'cadastrar', NULL, '{\"id\": 18, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-05-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"eaa9bc4def5f6b7ff912b46ecc793f9194aa6af4763a31c116ddef4ebeacfbf7\", \"usuario_id\": 1}', '2026-09-28 20:16:08.752506'),
(88, 1, 1, 'cartao', 'cartoes', '38', 'cadastrar', NULL, '{\"id\": 38, \"compra_id\": 18, \"data\": \"2026-07-28\", \"valor\": 31.43, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026052848540000000090110000000002\", \"usuario_id\": 1}', '2026-09-28 20:16:08.752926'),
(89, 1, 1, 'cartao', 'compras', '19', 'cadastrar', NULL, '{\"id\": 19, \"nome\": \"BRENO SILVA ROCHA      CAMPOS DO JOR BR\", \"nome_original\": \"BRENO SILVA ROCHA      CAMPOS DO JOR BR\", \"categoria\": \"pessoal\", \"valor_total\": 259.00, \"total_parcelas\": 1, \"data_compra\": \"2026-05-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"1b23cc47f95e3e4c65eb6cecfa6044e47b69e449e9994df68fd8856befafdbcc\", \"usuario_id\": 1}', '2026-09-28 20:16:08.755092'),
(90, 1, 1, 'cartao', 'cartoes', '39', 'cadastrar', NULL, '{\"id\": 39, \"compra_id\": 19, \"data\": \"2026-07-30\", \"valor\": 259.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026053048540000000090110000000003\", \"usuario_id\": 1}', '2026-09-28 20:16:08.755681'),
(91, 1, 1, 'cartao', 'compras', '20', 'cadastrar', NULL, '{\"id\": 20, \"nome\": \"MP*LOJABIKEWAY         OSASCO        BR\", \"nome_original\": \"MP*LOJABIKEWAY         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 78.00, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"b47ec78df1b30b6e8bf7d98e344cae60e2b88535850179e551c67e0c887c3bf6\", \"usuario_id\": 1}', '2026-09-28 20:16:08.757089'),
(92, 1, 1, 'cartao', 'cartoes', '40', 'cadastrar', NULL, '{\"id\": 40, \"compra_id\": 20, \"data\": \"2026-07-03\", \"valor\": 78.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060348540000000090110000000004\", \"usuario_id\": 1}', '2026-09-28 20:16:08.757443'),
(93, 1, 1, 'cartao', 'compras', '21', 'cadastrar', NULL, '{\"id\": 21, \"nome\": \"AUTO GAS               POUSO ALEGRE  BR\", \"nome_original\": \"AUTO GAS               POUSO ALEGRE  BR\", \"categoria\": \"pessoal\", \"valor_total\": 420.00, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"92d13cc5911bfd9d59743e60f3c70a3d492837cf22155631126b3de6704b98de\", \"usuario_id\": 1}', '2026-09-28 20:16:08.760501'),
(94, 1, 1, 'cartao', 'cartoes', '41', 'cadastrar', NULL, '{\"id\": 41, \"compra_id\": 21, \"data\": \"2026-07-03\", \"valor\": 420.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060348540000000090110000000005\", \"usuario_id\": 1}', '2026-09-28 20:16:08.761269'),
(95, 1, 1, 'cartao', 'compras', '22', 'cadastrar', NULL, '{\"id\": 22, \"nome\": \"MP*SUPRASERVICE        OSASCO        BR\", \"nome_original\": \"MP*SUPRASERVICE        OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 42.55, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"4c2c7f40a1fe17410dd4c5afb98fbe62edef96436f2ce3712eeb568e1b6c779d\", \"usuario_id\": 1}', '2026-09-28 20:16:08.764294'),
(96, 1, 1, 'cartao', 'cartoes', '42', 'cadastrar', NULL, '{\"id\": 42, \"compra_id\": 22, \"data\": \"2026-07-03\", \"valor\": 42.55, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060348540000000090110000000006\", \"usuario_id\": 1}', '2026-09-28 20:16:08.765472'),
(97, 1, 1, 'cartao', 'compras', '23', 'cadastrar', NULL, '{\"id\": 23, \"nome\": \"MP*GGJJK               HORTOLNDIA    BR\", \"nome_original\": \"MP*GGJJK               HORTOLNDIA    BR\", \"categoria\": \"pessoal\", \"valor_total\": 209.98, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"f409ec0f994fe82937339fefd6ab53dd8547579cd0adffe6e7ac21d11b2e27a5\", \"usuario_id\": 1}', '2026-09-28 20:16:08.768613'),
(98, 1, 1, 'cartao', 'cartoes', '43', 'cadastrar', NULL, '{\"id\": 43, \"compra_id\": 23, \"data\": \"2026-07-03\", \"valor\": 209.98, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060348540000000090110000000007\", \"usuario_id\": 1}', '2026-09-28 20:16:08.770037'),
(99, 1, 1, 'cartao', 'compras', '24', 'cadastrar', NULL, '{\"id\": 24, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"aeec41d68be97743da65cfdeec9e58d6378e5b3bea7a63c639eb2d7dc8b370eb\", \"usuario_id\": 1}', '2026-09-28 20:16:08.774317'),
(100, 1, 1, 'cartao', 'cartoes', '44', 'cadastrar', NULL, '{\"id\": 44, \"compra_id\": 24, \"data\": \"2026-07-03\", \"valor\": 23.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060348540000000090110000000008\", \"usuario_id\": 1}', '2026-09-28 20:16:08.774913'),
(101, 1, 1, 'cartao', 'compras', '25', 'cadastrar', NULL, '{\"id\": 25, \"nome\": \"MP*MERCADOLIVRE        BIRIGUI       BR\", \"nome_original\": \"MP*MERCADOLIVRE        BIRIGUI       BR\", \"categoria\": \"pessoal\", \"valor_total\": 212.00, \"total_parcelas\": 1, \"data_compra\": \"2026-06-04\", \"origem\": \"ofx\", \"identificador_ofx\": \"d753c767fbc844177a72932845fbdf922c7f6a179fb046ee6f26be4d27b81e87\", \"usuario_id\": 1}', '2026-09-28 20:16:08.777863'),
(102, 1, 1, 'cartao', 'cartoes', '45', 'cadastrar', NULL, '{\"id\": 45, \"compra_id\": 25, \"data\": \"2026-07-04\", \"valor\": 212.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060448540000000090110000000009\", \"usuario_id\": 1}', '2026-09-28 20:16:08.778450'),
(103, 1, 1, 'cartao', 'compras', '26', 'cadastrar', NULL, '{\"id\": 26, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-21\", \"origem\": \"ofx\", \"identificador_ofx\": \"54de9e6a13bee311ffcb23efbadcdbe1ce243a5d2f0cdc2a2f5754a0f5c4ba12\", \"usuario_id\": 1}', '2026-09-28 20:16:08.781856'),
(104, 1, 1, 'cartao', 'cartoes', '46', 'cadastrar', NULL, '{\"id\": 46, \"compra_id\": 26, \"data\": \"2026-07-21\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026062148540000000090110000000010\", \"usuario_id\": 1}', '2026-09-28 20:16:08.782524'),
(105, 1, 1, 'cartao', 'compras', '27', 'cadastrar', NULL, '{\"id\": 27, \"nome\": \"MP*MERCADOLIVRE        PAIANDU       BR\", \"nome_original\": \"MP*MERCADOLIVRE        PAIANDU       BR\", \"categoria\": \"pessoal\", \"valor_total\": 89.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-11\", \"origem\": \"ofx\", \"identificador_ofx\": \"d77287820e004cdbbdde5d838519f0631f98a8194bdff20fb1dce244f0442b95\", \"usuario_id\": 1}', '2026-09-28 20:16:08.784310'),
(106, 1, 1, 'cartao', 'cartoes', '47', 'cadastrar', NULL, '{\"id\": 47, \"compra_id\": 27, \"data\": \"2026-07-11\", \"valor\": 89.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026061148540000000090110000000011\", \"usuario_id\": 1}', '2026-09-28 20:16:08.785080'),
(107, 1, 1, 'cartao', 'compras', '28', 'cadastrar', NULL, '{\"id\": 28, \"nome\": \"Microsoft*Store        Sao Paulo     BR\", \"nome_original\": \"Microsoft*Store        Sao Paulo     BR\", \"categoria\": \"pessoal\", \"valor_total\": 59.99, \"total_parcelas\": 1, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"f458b16fdd3724f7d81269e58588cf1632a652b29471802041baf30dfc6a6389\", \"usuario_id\": 1}', '2026-09-28 20:16:08.786459'),
(108, 1, 1, 'cartao', 'cartoes', '48', 'cadastrar', NULL, '{\"id\": 48, \"compra_id\": 28, \"data\": \"2026-07-26\", \"valor\": 59.99, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000012\", \"usuario_id\": 1}', '2026-09-28 20:16:08.786981'),
(109, 1, 1, 'cartao', 'cartoes', '11', 'editar', '{\"id\": 11, \"compra_id\": 10, \"data\": \"2026-07-25\", \"valor\": 100.01, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 11, \"compra_id\": 10, \"data\": \"2026-07-25\", \"valor\": 99.99, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026042548540000000090110000000013\", \"usuario_id\": 1}', '2026-09-28 20:16:08.789050'),
(110, 1, 1, 'cartao', 'compras', '10', 'editar', '{\"id\": 10, \"nome\": \"Aquela 01\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 300.03, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '{\"id\": 10, \"nome\": \"Aquela 01\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 300.01, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '2026-09-28 20:16:08.789945'),
(111, 1, 1, 'cartao', 'compras', '29', 'cadastrar', NULL, '{\"id\": 29, \"nome\": \"MERCADOLIVRE*  OSASCO      BR\", \"nome_original\": \"MERCADOLIVRE*  OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 252.00, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"58123bdc542de7e8f71e9597bd276beb431f44bae9af05194d78952293717b5b\", \"usuario_id\": 1}', '2026-09-28 20:16:08.792231'),
(112, 1, 1, 'cartao', 'cartoes', '49', 'cadastrar', NULL, '{\"id\": 49, \"compra_id\": 29, \"data\": \"2026-07-26\", \"valor\": 84.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000014\", \"usuario_id\": 1}', '2026-09-28 20:16:08.792984'),
(113, 1, 1, 'cartao', 'cartoes', '50', 'cadastrar', NULL, '{\"id\": 50, \"compra_id\": 29, \"data\": \"2026-08-26\", \"valor\": 84.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.793892'),
(114, 1, 1, 'cartao', 'cartoes', '51', 'cadastrar', NULL, '{\"id\": 51, \"compra_id\": 29, \"data\": \"2026-09-26\", \"valor\": 84.00, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.794538'),
(115, 1, 1, 'cartao', 'cartoes', '15', 'editar', '{\"id\": 15, \"compra_id\": 11, \"data\": \"2026-07-26\", \"valor\": 162.66, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 15, \"compra_id\": 11, \"data\": \"2026-07-26\", \"valor\": 162.66, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026032648540000000090110000000015\", \"usuario_id\": 1}', '2026-09-28 20:16:08.798138'),
(116, 1, 1, 'cartao', 'compras', '30', 'cadastrar', NULL, '{\"id\": 30, \"nome\": \"FISIA NIKE EC  EXTREMA     BR\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.99, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '2026-09-28 20:16:08.800517'),
(117, 1, 1, 'cartao', 'cartoes', '52', 'cadastrar', NULL, '{\"id\": 52, \"compra_id\": 30, \"data\": \"2026-07-26\", \"valor\": 183.33, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000016\", \"usuario_id\": 1}', '2026-09-28 20:16:08.800927'),
(118, 1, 1, 'cartao', 'cartoes', '53', 'cadastrar', NULL, '{\"id\": 53, \"compra_id\": 30, \"data\": \"2026-08-26\", \"valor\": 183.33, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.801318'),
(119, 1, 1, 'cartao', 'cartoes', '54', 'cadastrar', NULL, '{\"id\": 54, \"compra_id\": 30, \"data\": \"2026-09-26\", \"valor\": 183.33, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.801894'),
(120, 1, 1, 'cartao', 'cartoes', '23', 'editar', '{\"id\": 23, \"compra_id\": 14, \"data\": \"2026-07-30\", \"valor\": 1199.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 23, \"compra_id\": 14, \"data\": \"2026-07-30\", \"valor\": 1199.00, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000017\", \"usuario_id\": 1}', '2026-09-28 20:16:08.804576'),
(121, 1, 1, 'cartao', 'cartoes', '33', 'editar', '{\"id\": 33, \"compra_id\": 15, \"data\": \"2026-07-30\", \"valor\": 179.85, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 33, \"compra_id\": 15, \"data\": \"2026-07-30\", \"valor\": 179.85, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000018\", \"usuario_id\": 1}', '2026-09-28 20:16:08.808020'),
(122, 1, 1, 'cartao', 'compras', '31', 'cadastrar', NULL, '{\"id\": 31, \"nome\": \"MP*FREEFORCE   OSASCO      BR\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.16, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '2026-09-28 20:16:08.811713'),
(123, 1, 1, 'cartao', 'cartoes', '55', 'cadastrar', NULL, '{\"id\": 55, \"compra_id\": 31, \"data\": \"2026-07-31\", \"valor\": 195.04, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026053148540000000090110000000019\", \"usuario_id\": 1}', '2026-09-28 20:16:08.829022'),
(124, 1, 1, 'cartao', 'cartoes', '56', 'cadastrar', NULL, '{\"id\": 56, \"compra_id\": 31, \"data\": \"2026-08-31\", \"valor\": 195.04, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.830863'),
(125, 1, 1, 'cartao', 'cartoes', '57', 'cadastrar', NULL, '{\"id\": 57, \"compra_id\": 31, \"data\": \"2026-09-30\", \"valor\": 195.04, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.831955'),
(126, 1, 1, 'cartao', 'cartoes', '58', 'cadastrar', NULL, '{\"id\": 58, \"compra_id\": 31, \"data\": \"2026-10-31\", \"valor\": 195.04, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.833029'),
(127, 1, 1, 'cartao', 'compras', '32', 'cadastrar', NULL, '{\"id\": 32, \"nome\": \"AMAZON PRIME   SAO PAULO   BR\", \"nome_original\": \"AMAZON PRIME   SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 166.80, \"total_parcelas\": 6, \"data_compra\": \"2026-06-06\", \"origem\": \"ofx\", \"identificador_ofx\": \"9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb\", \"usuario_id\": 1}', '2026-09-28 20:16:08.835495'),
(128, 1, 1, 'cartao', 'cartoes', '59', 'cadastrar', NULL, '{\"id\": 59, \"compra_id\": 32, \"data\": \"2026-07-06\", \"valor\": 27.80, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026060648540000000090110000000020\", \"usuario_id\": 1}', '2026-09-28 20:16:08.836150'),
(129, 1, 1, 'cartao', 'cartoes', '60', 'cadastrar', NULL, '{\"id\": 60, \"compra_id\": 32, \"data\": \"2026-08-06\", \"valor\": 27.80, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.838417'),
(130, 1, 1, 'cartao', 'cartoes', '61', 'cadastrar', NULL, '{\"id\": 61, \"compra_id\": 32, \"data\": \"2026-09-06\", \"valor\": 27.80, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.839231');
INSERT INTO `historico_alteracoes` (`id`, `proprietario_id`, `ator_id`, `modulo`, `tabela`, `registro`, `acao`, `antes`, `depois`, `criado_em`) VALUES
(131, 1, 1, 'cartao', 'cartoes', '62', 'cadastrar', NULL, '{\"id\": 62, \"compra_id\": 32, \"data\": \"2026-10-06\", \"valor\": 27.80, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.839738'),
(132, 1, 1, 'cartao', 'cartoes', '63', 'cadastrar', NULL, '{\"id\": 63, \"compra_id\": 32, \"data\": \"2026-11-06\", \"valor\": 27.80, \"parcela\": 5, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.840178'),
(133, 1, 1, 'cartao', 'cartoes', '64', 'cadastrar', NULL, '{\"id\": 64, \"compra_id\": 32, \"data\": \"2026-12-06\", \"valor\": 27.80, \"parcela\": 6, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.840618'),
(134, 1, 1, 'cartao', 'compras', '33', 'cadastrar', NULL, '{\"id\": 33, \"nome\": \"MERCADOLIVRE*  PAIANDU     BR\", \"nome_original\": \"MERCADOLIVRE*  PAIANDU     BR\", \"categoria\": \"pessoal\", \"valor_total\": 322.44, \"total_parcelas\": 4, \"data_compra\": \"2026-06-11\", \"origem\": \"ofx\", \"identificador_ofx\": \"bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c\", \"usuario_id\": 1}', '2026-09-28 20:16:08.841782'),
(135, 1, 1, 'cartao', 'cartoes', '65', 'cadastrar', NULL, '{\"id\": 65, \"compra_id\": 33, \"data\": \"2026-07-11\", \"valor\": 80.61, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026061148540000000090110000000021\", \"usuario_id\": 1}', '2026-09-28 20:16:08.842124'),
(136, 1, 1, 'cartao', 'cartoes', '66', 'cadastrar', NULL, '{\"id\": 66, \"compra_id\": 33, \"data\": \"2026-08-11\", \"valor\": 80.61, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.842483'),
(137, 1, 1, 'cartao', 'cartoes', '67', 'cadastrar', NULL, '{\"id\": 67, \"compra_id\": 33, \"data\": \"2026-09-11\", \"valor\": 80.61, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.842822'),
(138, 1, 1, 'cartao', 'cartoes', '68', 'cadastrar', NULL, '{\"id\": 68, \"compra_id\": 33, \"data\": \"2026-10-11\", \"valor\": 80.61, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:16:08.843143'),
(139, 1, 1, 'cartao', 'compras', '31', 'editar', '{\"id\": 31, \"nome\": \"MP*FREEFORCE   OSASCO      BR\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.16, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '{\"id\": 31, \"nome\": \"Breteles\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.16, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '2026-09-28 20:19:22.590496'),
(140, 1, 1, 'cartao', 'compras', '30', 'editar', '{\"id\": 30, \"nome\": \"FISIA NIKE EC  EXTREMA     BR\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.99, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '{\"id\": 30, \"nome\": \"Pamela camisa da seleção\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.99, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '2026-09-28 20:19:44.571496'),
(141, 1, 1, 'cartao', 'cartao_nomes_recorrentes', '2da87a9eef9df2643a5aefcca54a571bcd525f6982e9bdcbe10c74eac1d48c99', 'cadastrar', NULL, '{\"chave_descricao\": \"2da87a9eef9df2643a5aefcca54a571bcd525f6982e9bdcbe10c74eac1d48c99\", \"descricao_original\": \"Microsoft*Store        Sao Paulo     BR\", \"nome_personalizado\": \"GamePass\", \"usuario_id\": 1}', '2026-09-28 20:20:06.570591'),
(142, 1, 1, 'cartao', 'compras', '28', 'editar', '{\"id\": 28, \"nome\": \"Microsoft*Store        Sao Paulo     BR\", \"nome_original\": \"Microsoft*Store        Sao Paulo     BR\", \"categoria\": \"pessoal\", \"valor_total\": 59.99, \"total_parcelas\": 1, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"f458b16fdd3724f7d81269e58588cf1632a652b29471802041baf30dfc6a6389\", \"usuario_id\": 1}', '{\"id\": 28, \"nome\": \"GamePass\", \"nome_original\": \"Microsoft*Store        Sao Paulo     BR\", \"categoria\": \"pessoal\", \"valor_total\": 59.99, \"total_parcelas\": 1, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"f458b16fdd3724f7d81269e58588cf1632a652b29471802041baf30dfc6a6389\", \"usuario_id\": 1}', '2026-09-28 20:20:06.575678'),
(143, 1, 1, 'cartao', 'compras', '32', 'editar', '{\"id\": 32, \"nome\": \"AMAZON PRIME   SAO PAULO   BR\", \"nome_original\": \"AMAZON PRIME   SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 166.80, \"total_parcelas\": 6, \"data_compra\": \"2026-06-06\", \"origem\": \"ofx\", \"identificador_ofx\": \"9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb\", \"usuario_id\": 1}', '{\"id\": 32, \"nome\": \"Prime\", \"nome_original\": \"AMAZON PRIME   SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 166.80, \"total_parcelas\": 6, \"data_compra\": \"2026-06-06\", \"origem\": \"ofx\", \"identificador_ofx\": \"9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb\", \"usuario_id\": 1}', '2026-09-28 20:20:37.716705'),
(144, 1, 1, 'cartao', 'compras', '34', 'cadastrar', NULL, '{\"id\": 34, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-06-27\", \"origem\": \"ofx\", \"identificador_ofx\": \"8d197e34da141ea2d90f230504fcb637de5eb394c251de04cbd55b1d57e7ad02\", \"usuario_id\": 1}', '2026-09-28 20:20:58.821733'),
(145, 1, 1, 'cartao', 'cartoes', '69', 'cadastrar', NULL, '{\"id\": 69, \"compra_id\": 34, \"data\": \"2026-08-27\", \"valor\": 31.43, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026062748540000000090110000000001\", \"usuario_id\": 1}', '2026-09-28 20:20:58.827368'),
(146, 1, 1, 'cartao', 'compras', '35', 'cadastrar', NULL, '{\"id\": 35, \"nome\": \"MP*VSRMOTOS            JOINVILLE     BR\", \"nome_original\": \"MP*VSRMOTOS            JOINVILLE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 63.47, \"total_parcelas\": 1, \"data_compra\": \"2026-06-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"745b100702eee8ebe9f3237da9f16672b7c49e6a211faece3288913dec0274ff\", \"usuario_id\": 1}', '2026-09-28 20:20:58.831107'),
(147, 1, 1, 'cartao', 'cartoes', '70', 'cadastrar', NULL, '{\"id\": 70, \"compra_id\": 35, \"data\": \"2026-08-28\", \"valor\": 63.47, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026062848540000000090110000000002\", \"usuario_id\": 1}', '2026-09-28 20:20:58.832404'),
(148, 1, 1, 'cartao', 'compras', '36', 'cadastrar', NULL, '{\"id\": 36, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-07-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"7554900bcb83ca23cef94216af19a7afc7f3249571d802fed24710897ef2b49f\", \"usuario_id\": 1}', '2026-09-28 20:20:58.835878'),
(149, 1, 1, 'cartao', 'cartoes', '71', 'cadastrar', NULL, '{\"id\": 71, \"compra_id\": 36, \"data\": \"2026-08-03\", \"valor\": 23.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026070348540000000090110000000003\", \"usuario_id\": 1}', '2026-09-28 20:20:58.837335'),
(150, 1, 1, 'cartao', 'compras', '37', 'cadastrar', NULL, '{\"id\": 37, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-07-21\", \"origem\": \"ofx\", \"identificador_ofx\": \"4e8702025a5bc5e33722eb341b6265d5ecfc3f153e33f64464b6c522259181fc\", \"usuario_id\": 1}', '2026-09-28 20:20:58.841030'),
(151, 1, 1, 'cartao', 'cartoes', '72', 'cadastrar', NULL, '{\"id\": 72, \"compra_id\": 37, \"data\": \"2026-08-21\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026072148540000000090110000000004\", \"usuario_id\": 1}', '2026-09-28 20:20:58.842255'),
(152, 1, 1, 'cartao', 'compras', '38', 'cadastrar', NULL, '{\"id\": 38, \"nome\": \"Microsoft*1 Meses de PCSao Paulo     BR\", \"nome_original\": \"Microsoft*1 Meses de PCSao Paulo     BR\", \"categoria\": \"pessoal\", \"valor_total\": 59.99, \"total_parcelas\": 1, \"data_compra\": \"2026-06-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"c4118e67e9f0c444877caff76b36b7e80351b94099d40b1e67e852a3b52df4ca\", \"usuario_id\": 1}', '2026-09-28 20:20:58.845272'),
(153, 1, 1, 'cartao', 'cartoes', '73', 'cadastrar', NULL, '{\"id\": 73, \"compra_id\": 38, \"data\": \"2026-08-26\", \"valor\": 59.99, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026062648540000000090110000000005\", \"usuario_id\": 1}', '2026-09-28 20:20:58.846363'),
(154, 1, 1, 'cartao', 'cartoes', '12', 'editar', '{\"id\": 12, \"compra_id\": 10, \"data\": \"2026-08-25\", \"valor\": 100.01, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 12, \"compra_id\": 10, \"data\": \"2026-08-25\", \"valor\": 99.99, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026042548540000000090110000000006\", \"usuario_id\": 1}', '2026-09-28 20:20:58.851485'),
(155, 1, 1, 'cartao', 'compras', '10', 'editar', '{\"id\": 10, \"nome\": \"Aquela 01\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 300.01, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '{\"id\": 10, \"nome\": \"Aquela 01\", \"nome_original\": \"MP*LOJAXIAOMI  SANTA RITA DBR\", \"categoria\": \"pessoal\", \"valor_total\": 299.99, \"total_parcelas\": 3, \"data_compra\": \"2026-04-25\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf\", \"usuario_id\": 1}', '2026-09-28 20:20:58.852455'),
(156, 1, 1, 'cartao', 'cartoes', '50', 'editar', '{\"id\": 50, \"compra_id\": 29, \"data\": \"2026-08-26\", \"valor\": 84.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 50, \"compra_id\": 29, \"data\": \"2026-08-26\", \"valor\": 84.00, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000007\", \"usuario_id\": 1}', '2026-09-28 20:20:58.859250'),
(157, 1, 1, 'cartao', 'cartoes', '53', 'editar', '{\"id\": 53, \"compra_id\": 30, \"data\": \"2026-08-26\", \"valor\": 183.33, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 53, \"compra_id\": 30, \"data\": \"2026-08-26\", \"valor\": 183.32, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000008\", \"usuario_id\": 1}', '2026-09-28 20:20:58.865662'),
(158, 1, 1, 'cartao', 'compras', '30', 'editar', '{\"id\": 30, \"nome\": \"Pamela camisa da seleção\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.99, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '{\"id\": 30, \"nome\": \"Pamela camisa da seleção\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.98, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '2026-09-28 20:20:58.866909'),
(159, 1, 1, 'cartao', 'compras', '39', 'cadastrar', NULL, '{\"id\": 39, \"nome\": \"PayU        *  Barueri     BR\", \"nome_original\": \"PayU        *  Barueri     BR\", \"categoria\": \"pessoal\", \"valor_total\": 400.00, \"total_parcelas\": 2, \"data_compra\": \"2026-06-29\", \"origem\": \"ofx\", \"identificador_ofx\": \"60b4fe6caf19cbd42e40b362e5c60bb7e3e8287cb5095c0df0ec2d892c6b2ae0\", \"usuario_id\": 1}', '2026-09-28 20:20:58.869604'),
(160, 1, 1, 'cartao', 'cartoes', '74', 'cadastrar', NULL, '{\"id\": 74, \"compra_id\": 39, \"data\": \"2026-08-29\", \"valor\": 200.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026062948540000000090110000000009\", \"usuario_id\": 1}', '2026-09-28 20:20:58.870859'),
(161, 1, 1, 'cartao', 'cartoes', '75', 'cadastrar', NULL, '{\"id\": 75, \"compra_id\": 39, \"data\": \"2026-09-29\", \"valor\": 200.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:20:58.872019'),
(162, 1, 1, 'cartao', 'cartoes', '24', 'editar', '{\"id\": 24, \"compra_id\": 14, \"data\": \"2026-08-30\", \"valor\": 1199.00, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 24, \"compra_id\": 14, \"data\": \"2026-08-30\", \"valor\": 1199.00, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000010\", \"usuario_id\": 1}', '2026-09-28 20:20:58.876547'),
(163, 1, 1, 'cartao', 'cartoes', '56', 'editar', '{\"id\": 56, \"compra_id\": 31, \"data\": \"2026-08-31\", \"valor\": 195.04, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 56, \"compra_id\": 31, \"data\": \"2026-08-31\", \"valor\": 195.03, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026053148540000000090110000000011\", \"usuario_id\": 1}', '2026-09-28 20:20:58.883975'),
(164, 1, 1, 'cartao', 'compras', '31', 'editar', '{\"id\": 31, \"nome\": \"Breteles\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.16, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '{\"id\": 31, \"nome\": \"Breteles\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.15, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '2026-09-28 20:20:58.885135'),
(165, 1, 1, 'cartao', 'cartoes', '60', 'editar', '{\"id\": 60, \"compra_id\": 32, \"data\": \"2026-08-06\", \"valor\": 27.80, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 60, \"compra_id\": 32, \"data\": \"2026-08-06\", \"valor\": 27.80, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026060648540000000090110000000012\", \"usuario_id\": 1}', '2026-09-28 20:20:58.887334'),
(166, 1, 1, 'cartao', 'cartoes', '66', 'editar', '{\"id\": 66, \"compra_id\": 33, \"data\": \"2026-08-11\", \"valor\": 80.61, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 66, \"compra_id\": 33, \"data\": \"2026-08-11\", \"valor\": 80.60, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026061148540000000090110000000013\", \"usuario_id\": 1}', '2026-09-28 20:20:58.893465'),
(167, 1, 1, 'cartao', 'compras', '33', 'editar', '{\"id\": 33, \"nome\": \"MERCADOLIVRE*  PAIANDU     BR\", \"nome_original\": \"MERCADOLIVRE*  PAIANDU     BR\", \"categoria\": \"pessoal\", \"valor_total\": 322.44, \"total_parcelas\": 4, \"data_compra\": \"2026-06-11\", \"origem\": \"ofx\", \"identificador_ofx\": \"bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c\", \"usuario_id\": 1}', '{\"id\": 33, \"nome\": \"MERCADOLIVRE*  PAIANDU     BR\", \"nome_original\": \"MERCADOLIVRE*  PAIANDU     BR\", \"categoria\": \"pessoal\", \"valor_total\": 322.43, \"total_parcelas\": 4, \"data_compra\": \"2026-06-11\", \"origem\": \"ofx\", \"identificador_ofx\": \"bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c\", \"usuario_id\": 1}', '2026-09-28 20:20:58.895202'),
(168, 1, 1, 'cartao', 'compras', '40', 'cadastrar', NULL, '{\"id\": 40, \"nome\": \"DPASCHOAL 196  TAUBATE     BR\", \"nome_original\": \"DPASCHOAL 196  TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 1812.88, \"total_parcelas\": 4, \"data_compra\": \"2026-07-14\", \"origem\": \"ofx\", \"identificador_ofx\": \"2f2c0a1d3288a327972a0a39db943213f11c303f3eb13b9fd751460b06261234\", \"usuario_id\": 1}', '2026-09-28 20:20:58.897618'),
(169, 1, 1, 'cartao', 'cartoes', '76', 'cadastrar', NULL, '{\"id\": 76, \"compra_id\": 40, \"data\": \"2026-08-14\", \"valor\": 453.22, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026071448540000000090110000000014\", \"usuario_id\": 1}', '2026-09-28 20:20:58.898202'),
(170, 1, 1, 'cartao', 'cartoes', '77', 'cadastrar', NULL, '{\"id\": 77, \"compra_id\": 40, \"data\": \"2026-09-14\", \"valor\": 453.22, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:20:58.899087'),
(171, 1, 1, 'cartao', 'cartoes', '78', 'cadastrar', NULL, '{\"id\": 78, \"compra_id\": 40, \"data\": \"2026-10-14\", \"valor\": 453.22, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:20:58.900037'),
(172, 1, 1, 'cartao', 'cartoes', '79', 'cadastrar', NULL, '{\"id\": 79, \"compra_id\": 40, \"data\": \"2026-11-14\", \"valor\": 453.22, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:20:58.901084'),
(173, 1, 1, 'cartao', 'compras', '41', 'cadastrar', NULL, '{\"id\": 41, \"nome\": \"PetersonPierr  CAMPOS DO JOBR\", \"nome_original\": \"PetersonPierr  CAMPOS DO JOBR\", \"categoria\": \"pessoal\", \"valor_total\": 987.00, \"total_parcelas\": 3, \"data_compra\": \"2026-07-17\", \"origem\": \"ofx\", \"identificador_ofx\": \"ed1a0bb1163563da30e10046a0c12c921efad0c2d2f9a90ebf00e1502772c63f\", \"usuario_id\": 1}', '2026-09-28 20:20:58.904147'),
(174, 1, 1, 'cartao', 'cartoes', '80', 'cadastrar', NULL, '{\"id\": 80, \"compra_id\": 41, \"data\": \"2026-08-17\", \"valor\": 329.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026071748540000000090110000000015\", \"usuario_id\": 1}', '2026-09-28 20:20:58.905084'),
(175, 1, 1, 'cartao', 'cartoes', '81', 'cadastrar', NULL, '{\"id\": 81, \"compra_id\": 41, \"data\": \"2026-09-17\", \"valor\": 329.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:20:58.908749'),
(176, 1, 1, 'cartao', 'cartoes', '82', 'cadastrar', NULL, '{\"id\": 82, \"compra_id\": 41, \"data\": \"2026-10-17\", \"valor\": 329.00, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:20:58.909739'),
(177, 1, 1, 'cartao', 'compras', '40', 'editar', '{\"id\": 40, \"nome\": \"DPASCHOAL 196  TAUBATE     BR\", \"nome_original\": \"DPASCHOAL 196  TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 1812.88, \"total_parcelas\": 4, \"data_compra\": \"2026-07-14\", \"origem\": \"ofx\", \"identificador_ofx\": \"2f2c0a1d3288a327972a0a39db943213f11c303f3eb13b9fd751460b06261234\", \"usuario_id\": 1}', '{\"id\": 40, \"nome\": \"Gol pneus\", \"nome_original\": \"DPASCHOAL 196  TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 1812.88, \"total_parcelas\": 4, \"data_compra\": \"2026-07-14\", \"origem\": \"ofx\", \"identificador_ofx\": \"2f2c0a1d3288a327972a0a39db943213f11c303f3eb13b9fd751460b06261234\", \"usuario_id\": 1}', '2026-09-28 20:21:12.459202'),
(178, 1, 1, 'cartao', 'compras', '41', 'editar', '{\"id\": 41, \"nome\": \"PetersonPierr  CAMPOS DO JOBR\", \"nome_original\": \"PetersonPierr  CAMPOS DO JOBR\", \"categoria\": \"pessoal\", \"valor_total\": 987.00, \"total_parcelas\": 3, \"data_compra\": \"2026-07-17\", \"origem\": \"ofx\", \"identificador_ofx\": \"ed1a0bb1163563da30e10046a0c12c921efad0c2d2f9a90ebf00e1502772c63f\", \"usuario_id\": 1}', '{\"id\": 41, \"nome\": \"Biz revisão\", \"nome_original\": \"PetersonPierr  CAMPOS DO JOBR\", \"categoria\": \"pessoal\", \"valor_total\": 987.00, \"total_parcelas\": 3, \"data_compra\": \"2026-07-17\", \"origem\": \"ofx\", \"identificador_ofx\": \"ed1a0bb1163563da30e10046a0c12c921efad0c2d2f9a90ebf00e1502772c63f\", \"usuario_id\": 1}', '2026-09-28 20:21:25.410869'),
(179, 1, 1, 'cartao', 'compras', '42', 'cadastrar', NULL, '{\"id\": 42, \"nome\": \"VIVA                   CAMPOS DO JOR BR\", \"nome_original\": \"VIVA                   CAMPOS DO JOR BR\", \"categoria\": \"pessoal\", \"valor_total\": 193.00, \"total_parcelas\": 1, \"data_compra\": \"2026-08-09\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d0e436385c49e444cfd5e64e9082257aafa407e5fa35359baedea19f6df9cee\", \"usuario_id\": 1}', '2026-09-28 20:25:19.376109'),
(180, 1, 1, 'cartao', 'cartoes', '83', 'cadastrar', NULL, '{\"id\": 83, \"compra_id\": 42, \"data\": \"2026-09-09\", \"valor\": 193.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026080948540000000090110000000001\", \"usuario_id\": 1}', '2026-09-28 20:25:19.378956'),
(181, 1, 1, 'cartao', 'compras', '43', 'cadastrar', NULL, '{\"id\": 43, \"nome\": \"MP*HDSTORE             SERRA         BR\", \"nome_original\": \"MP*HDSTORE             SERRA         BR\", \"categoria\": \"pessoal\", \"valor_total\": 983.26, \"total_parcelas\": 1, \"data_compra\": \"2026-07-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"31a9db4935da12912ebf87b03d614960f63d5f939bb735fabfaf5ba9f66565db\", \"usuario_id\": 1}', '2026-09-28 20:25:19.382560'),
(182, 1, 1, 'cartao', 'cartoes', '84', 'cadastrar', NULL, '{\"id\": 84, \"compra_id\": 43, \"data\": \"2026-09-26\", \"valor\": 983.26, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026072648540000000090110000000002\", \"usuario_id\": 1}', '2026-09-28 20:25:19.384073'),
(183, 1, 1, 'cartao', 'compras', '44', 'cadastrar', NULL, '{\"id\": 44, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-07-27\", \"origem\": \"ofx\", \"identificador_ofx\": \"304b9ef13ec7cafcf0efa243ee020cbfab583c0819eefcffd3d62a6c37b20c05\", \"usuario_id\": 1}', '2026-09-28 20:25:19.387860'),
(184, 1, 1, 'cartao', 'cartoes', '85', 'cadastrar', NULL, '{\"id\": 85, \"compra_id\": 44, \"data\": \"2026-09-27\", \"valor\": 31.43, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026072748540000000090110000000003\", \"usuario_id\": 1}', '2026-09-28 20:25:19.390988'),
(185, 1, 1, 'cartao', 'compras', '45', 'cadastrar', NULL, '{\"id\": 45, \"nome\": \"MP*DUCARTUCHOS         OSVALDO CRUZ  BR\", \"nome_original\": \"MP*DUCARTUCHOS         OSVALDO CRUZ  BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.00, \"total_parcelas\": 1, \"data_compra\": \"2026-07-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"5ccd69e34e2a63d6c2f67a07a3ee0c23a988cee6273a799001273fe282379765\", \"usuario_id\": 1}', '2026-09-28 20:25:19.394801'),
(186, 1, 1, 'cartao', 'cartoes', '86', 'cadastrar', NULL, '{\"id\": 86, \"compra_id\": 45, \"data\": \"2026-09-30\", \"valor\": 19.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026073048540000000090110000000004\", \"usuario_id\": 1}', '2026-09-28 20:25:19.398712'),
(187, 1, 1, 'cartao', 'compras', '46', 'cadastrar', NULL, '{\"id\": 46, \"nome\": \"MP*SYSTEMTRACEIN       SAO PAULO     BR\", \"nome_original\": \"MP*SYSTEMTRACEIN       SAO PAULO     BR\", \"categoria\": \"pessoal\", \"valor_total\": 349.00, \"total_parcelas\": 1, \"data_compra\": \"2026-07-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"a16276f347c0a9c44e8d48720ba9ba067e5caa881a8228bb5e5f1a076600c5d1\", \"usuario_id\": 1}', '2026-09-28 20:25:19.402219'),
(188, 1, 1, 'cartao', 'cartoes', '87', 'cadastrar', NULL, '{\"id\": 87, \"compra_id\": 46, \"data\": \"2026-09-30\", \"valor\": 349.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026073048540000000090110000000005\", \"usuario_id\": 1}', '2026-09-28 20:25:19.403564'),
(189, 1, 1, 'cartao', 'compras', '47', 'cadastrar', NULL, '{\"id\": 47, \"nome\": \"MP*TURUM               SAO CAETANO D BR\", \"nome_original\": \"MP*TURUM               SAO CAETANO D BR\", \"categoria\": \"pessoal\", \"valor_total\": 193.57, \"total_parcelas\": 1, \"data_compra\": \"2026-07-30\", \"origem\": \"ofx\", \"identificador_ofx\": \"ef69e4126b830d198961e1583467aaf8e87cd7b98f9849acaf6a9acd79a72f05\", \"usuario_id\": 1}', '2026-09-28 20:25:19.406884'),
(190, 1, 1, 'cartao', 'cartoes', '88', 'cadastrar', NULL, '{\"id\": 88, \"compra_id\": 47, \"data\": \"2026-09-30\", \"valor\": 193.57, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026073048540000000090110000000006\", \"usuario_id\": 1}', '2026-09-28 20:25:19.408175'),
(191, 1, 1, 'cartao', 'compras', '48', 'cadastrar', NULL, '{\"id\": 48, \"nome\": \"AUTO GAS               POUSO ALEGRE  BR\", \"nome_original\": \"AUTO GAS               POUSO ALEGRE  BR\", \"categoria\": \"pessoal\", \"valor_total\": 420.00, \"total_parcelas\": 1, \"data_compra\": \"2026-07-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"96abbf338a011aacaee7ad2692c6db89b962da4131c4f0c39dba8e234ca98ce2\", \"usuario_id\": 1}', '2026-09-28 20:25:19.411751'),
(192, 1, 1, 'cartao', 'cartoes', '89', 'cadastrar', NULL, '{\"id\": 89, \"compra_id\": 48, \"data\": \"2026-09-30\", \"valor\": 420.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026073148540000000090110000000007\", \"usuario_id\": 1}', '2026-09-28 20:25:19.413016'),
(193, 1, 1, 'cartao', 'compras', '49', 'cadastrar', NULL, '{\"id\": 49, \"nome\": \"MP*CARANGOPARTS        JUNDIAI       BR\", \"nome_original\": \"MP*CARANGOPARTS        JUNDIAI       BR\", \"categoria\": \"pessoal\", \"valor_total\": 691.66, \"total_parcelas\": 1, \"data_compra\": \"2026-07-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"8070d14feda35060ac48126073b1d50b7935cdb59810396fe9c1490b354d713c\", \"usuario_id\": 1}', '2026-09-28 20:25:19.416309'),
(194, 1, 1, 'cartao', 'cartoes', '90', 'cadastrar', NULL, '{\"id\": 90, \"compra_id\": 49, \"data\": \"2026-09-30\", \"valor\": 691.66, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026073148540000000090110000000008\", \"usuario_id\": 1}', '2026-09-28 20:25:19.417629'),
(195, 1, 1, 'cartao', 'compras', '50', 'cadastrar', NULL, '{\"id\": 50, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-08-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"ea98011b234a6574805db5860e0f07831f6e021e73dfca44be5fdc57c404bbd3\", \"usuario_id\": 1}', '2026-09-28 20:25:19.421321'),
(196, 1, 1, 'cartao', 'cartoes', '91', 'cadastrar', NULL, '{\"id\": 91, \"compra_id\": 50, \"data\": \"2026-09-03\", \"valor\": 23.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026080348540000000090110000000009\", \"usuario_id\": 1}', '2026-09-28 20:25:19.422662'),
(197, 1, 1, 'cartao', 'compras', '51', 'cadastrar', NULL, '{\"id\": 51, \"nome\": \"MP*MERCADOLIVRE        SAO PAULO     BR\", \"nome_original\": \"MP*MERCADOLIVRE        SAO PAULO     BR\", \"categoria\": \"pessoal\", \"valor_total\": 249.89, \"total_parcelas\": 1, \"data_compra\": \"2026-08-05\", \"origem\": \"ofx\", \"identificador_ofx\": \"853851080dc3828b22725cc29ee40afb1a71117ccdab5ce2ddad13af293a8d4d\", \"usuario_id\": 1}', '2026-09-28 20:25:19.430804'),
(198, 1, 1, 'cartao', 'cartoes', '92', 'cadastrar', NULL, '{\"id\": 92, \"compra_id\": 51, \"data\": \"2026-09-05\", \"valor\": 249.89, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026080548540000000090110000000010\", \"usuario_id\": 1}', '2026-09-28 20:25:19.432459'),
(199, 1, 1, 'cartao', 'compras', '52', 'cadastrar', NULL, '{\"id\": 52, \"nome\": \"MP*MERCADOLIVRE        JUNDIAI       BR\", \"nome_original\": \"MP*MERCADOLIVRE        JUNDIAI       BR\", \"categoria\": \"pessoal\", \"valor_total\": 94.89, \"total_parcelas\": 1, \"data_compra\": \"2026-08-08\", \"origem\": \"ofx\", \"identificador_ofx\": \"853794e585675eb0673eed6214b98c692fc74f27a6a7c6ade01ce0bf3e0603fa\", \"usuario_id\": 1}', '2026-09-28 20:25:19.439573'),
(200, 1, 1, 'cartao', 'cartoes', '93', 'cadastrar', NULL, '{\"id\": 93, \"compra_id\": 52, \"data\": \"2026-09-08\", \"valor\": 94.89, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026080848540000000090110000000011\", \"usuario_id\": 1}', '2026-09-28 20:25:19.441769'),
(201, 1, 1, 'cartao', 'compras', '53', 'cadastrar', NULL, '{\"id\": 53, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-08-20\", \"origem\": \"ofx\", \"identificador_ofx\": \"a3be6bc65cd74a56155f17e6938d2800aa2008295741409be6d56704bfa4108a\", \"usuario_id\": 1}', '2026-09-28 20:25:19.446099'),
(202, 1, 1, 'cartao', 'cartoes', '94', 'cadastrar', NULL, '{\"id\": 94, \"compra_id\": 53, \"data\": \"2026-09-20\", \"valor\": 19.90, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026082048540000000090110000000012\", \"usuario_id\": 1}', '2026-09-28 20:25:19.447591'),
(203, 1, 1, 'cartao', 'compras', '54', 'cadastrar', NULL, '{\"id\": 54, \"nome\": \"Microsoft*1 Meses de PCSao Paulo     BR\", \"nome_original\": \"Microsoft*1 Meses de PCSao Paulo     BR\", \"categoria\": \"pessoal\", \"valor_total\": 59.99, \"total_parcelas\": 1, \"data_compra\": \"2026-07-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"fe7d9d27ba96a28f069a94c2abed08398fe4a7b10b8508b8e0a25378b5304f33\", \"usuario_id\": 1}', '2026-09-28 20:25:19.451874'),
(204, 1, 1, 'cartao', 'cartoes', '95', 'cadastrar', NULL, '{\"id\": 95, \"compra_id\": 54, \"data\": \"2026-09-26\", \"valor\": 59.99, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026072648540000000090110000000013\", \"usuario_id\": 1}', '2026-09-28 20:25:19.454416'),
(205, 1, 1, 'cartao', 'cartoes', '54', 'editar', '{\"id\": 54, \"compra_id\": 30, \"data\": \"2026-09-26\", \"valor\": 183.33, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 54, \"compra_id\": 30, \"data\": \"2026-09-26\", \"valor\": 183.32, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000014\", \"usuario_id\": 1}', '2026-09-28 20:25:19.478698'),
(206, 1, 1, 'cartao', 'compras', '30', 'editar', '{\"id\": 30, \"nome\": \"Pamela camisa da seleção\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.98, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '{\"id\": 30, \"nome\": \"Pamela camisa da seleção\", \"nome_original\": \"FISIA NIKE EC  EXTREMA     BR\", \"categoria\": \"pessoal\", \"valor_total\": 549.97, \"total_parcelas\": 3, \"data_compra\": \"2026-05-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2\", \"usuario_id\": 1}', '2026-09-28 20:25:19.479768'),
(207, 1, 1, 'cartao', 'cartoes', '51', 'editar', '{\"id\": 51, \"compra_id\": 29, \"data\": \"2026-09-26\", \"valor\": 84.00, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 51, \"compra_id\": 29, \"data\": \"2026-09-26\", \"valor\": 84.00, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026052648540000000090110000000015\", \"usuario_id\": 1}', '2026-09-28 20:25:19.486508'),
(208, 1, 1, 'cartao', 'compras', '55', 'cadastrar', NULL, '{\"id\": 55, \"nome\": \"MERCADOLIVRE*  SAO JOSE    BR\", \"nome_original\": \"MERCADOLIVRE*  SAO JOSE    BR\", \"categoria\": \"pessoal\", \"valor_total\": 2330.70, \"total_parcelas\": 5, \"data_compra\": \"2026-07-26\", \"origem\": \"ofx\", \"identificador_ofx\": \"01c6b07f8b22b2642fe26f7250ea0622043b8a4742dbcd9b064d8d1b3555074c\", \"usuario_id\": 1}', '2026-09-28 20:25:19.491302'),
(209, 1, 1, 'cartao', 'cartoes', '96', 'cadastrar', NULL, '{\"id\": 96, \"compra_id\": 55, \"data\": \"2026-09-26\", \"valor\": 466.14, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026072648540000000090110000000016\", \"usuario_id\": 1}', '2026-09-28 20:25:19.493185'),
(210, 1, 1, 'cartao', 'cartoes', '97', 'cadastrar', NULL, '{\"id\": 97, \"compra_id\": 55, \"data\": \"2026-10-26\", \"valor\": 466.14, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:25:19.494753'),
(211, 1, 1, 'cartao', 'cartoes', '98', 'cadastrar', NULL, '{\"id\": 98, \"compra_id\": 55, \"data\": \"2026-11-26\", \"valor\": 466.14, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:25:19.496183'),
(212, 1, 1, 'cartao', 'cartoes', '99', 'cadastrar', NULL, '{\"id\": 99, \"compra_id\": 55, \"data\": \"2026-12-26\", \"valor\": 466.14, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:25:19.497612'),
(213, 1, 1, 'cartao', 'cartoes', '100', 'cadastrar', NULL, '{\"id\": 100, \"compra_id\": 55, \"data\": \"2027-01-26\", \"valor\": 466.14, \"parcela\": 5, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:25:19.499031'),
(214, 1, 1, 'cartao', 'cartoes', '75', 'editar', '{\"id\": 75, \"compra_id\": 39, \"data\": \"2026-09-29\", \"valor\": 200.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 75, \"compra_id\": 39, \"data\": \"2026-09-29\", \"valor\": 199.99, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026062948540000000090110000000017\", \"usuario_id\": 1}', '2026-09-28 20:25:19.520595'),
(215, 1, 1, 'cartao', 'compras', '39', 'editar', '{\"id\": 39, \"nome\": \"PayU        *  Barueri     BR\", \"nome_original\": \"PayU        *  Barueri     BR\", \"categoria\": \"pessoal\", \"valor_total\": 400.00, \"total_parcelas\": 2, \"data_compra\": \"2026-06-29\", \"origem\": \"ofx\", \"identificador_ofx\": \"60b4fe6caf19cbd42e40b362e5c60bb7e3e8287cb5095c0df0ec2d892c6b2ae0\", \"usuario_id\": 1}', '{\"id\": 39, \"nome\": \"PayU        *  Barueri     BR\", \"nome_original\": \"PayU        *  Barueri     BR\", \"categoria\": \"pessoal\", \"valor_total\": 399.99, \"total_parcelas\": 2, \"data_compra\": \"2026-06-29\", \"origem\": \"ofx\", \"identificador_ofx\": \"60b4fe6caf19cbd42e40b362e5c60bb7e3e8287cb5095c0df0ec2d892c6b2ae0\", \"usuario_id\": 1}', '2026-09-28 20:25:19.521866'),
(216, 1, 1, 'cartao', 'cartoes', '25', 'editar', '{\"id\": 25, \"compra_id\": 14, \"data\": \"2026-09-30\", \"valor\": 1199.00, \"parcela\": 4, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 25, \"compra_id\": 14, \"data\": \"2026-09-30\", \"valor\": 1199.00, \"parcela\": 4, \"paga\": 0, \"fitid\": \"2026043048540000000090110000000018\", \"usuario_id\": 1}', '2026-09-28 20:25:19.542263'),
(217, 1, 1, 'cartao', 'cartoes', '57', 'editar', '{\"id\": 57, \"compra_id\": 31, \"data\": \"2026-09-30\", \"valor\": 195.04, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 57, \"compra_id\": 31, \"data\": \"2026-09-30\", \"valor\": 195.03, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026053148540000000090110000000019\", \"usuario_id\": 1}', '2026-09-28 20:25:19.554608'),
(218, 1, 1, 'cartao', 'compras', '31', 'editar', '{\"id\": 31, \"nome\": \"Breteles\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.15, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '{\"id\": 31, \"nome\": \"Breteles\", \"nome_original\": \"MP*FREEFORCE   OSASCO      BR\", \"categoria\": \"pessoal\", \"valor_total\": 780.14, \"total_parcelas\": 4, \"data_compra\": \"2026-05-31\", \"origem\": \"ofx\", \"identificador_ofx\": \"32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5\", \"usuario_id\": 1}', '2026-09-28 20:25:19.556415'),
(219, 1, 1, 'cartao', 'cartoes', '61', 'editar', '{\"id\": 61, \"compra_id\": 32, \"data\": \"2026-09-06\", \"valor\": 27.80, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 61, \"compra_id\": 32, \"data\": \"2026-09-06\", \"valor\": 27.80, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026060648540000000090110000000020\", \"usuario_id\": 1}', '2026-09-28 20:25:19.563300'),
(220, 1, 1, 'cartao', 'cartoes', '67', 'editar', '{\"id\": 67, \"compra_id\": 33, \"data\": \"2026-09-11\", \"valor\": 80.61, \"parcela\": 3, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 67, \"compra_id\": 33, \"data\": \"2026-09-11\", \"valor\": 80.60, \"parcela\": 3, \"paga\": 0, \"fitid\": \"2026061148540000000090110000000021\", \"usuario_id\": 1}', '2026-09-28 20:25:19.576370'),
(221, 1, 1, 'cartao', 'compras', '33', 'editar', '{\"id\": 33, \"nome\": \"MERCADOLIVRE*  PAIANDU     BR\", \"nome_original\": \"MERCADOLIVRE*  PAIANDU     BR\", \"categoria\": \"pessoal\", \"valor_total\": 322.43, \"total_parcelas\": 4, \"data_compra\": \"2026-06-11\", \"origem\": \"ofx\", \"identificador_ofx\": \"bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c\", \"usuario_id\": 1}', '{\"id\": 33, \"nome\": \"MERCADOLIVRE*  PAIANDU     BR\", \"nome_original\": \"MERCADOLIVRE*  PAIANDU     BR\", \"categoria\": \"pessoal\", \"valor_total\": 322.42, \"total_parcelas\": 4, \"data_compra\": \"2026-06-11\", \"origem\": \"ofx\", \"identificador_ofx\": \"bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c\", \"usuario_id\": 1}', '2026-09-28 20:25:19.579721'),
(222, 1, 1, 'cartao', 'cartoes', '77', 'editar', '{\"id\": 77, \"compra_id\": 40, \"data\": \"2026-09-14\", \"valor\": 453.22, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 77, \"compra_id\": 40, \"data\": \"2026-09-14\", \"valor\": 453.22, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026071448540000000090110000000022\", \"usuario_id\": 1}', '2026-09-28 20:25:19.587366'),
(223, 1, 1, 'cartao', 'compras', '56', 'cadastrar', NULL, '{\"id\": 56, \"nome\": \"W K DIAGNOSE   TAUBATE     BR\", \"nome_original\": \"W K DIAGNOSE   TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 750.00, \"total_parcelas\": 2, \"data_compra\": \"2026-08-16\", \"origem\": \"ofx\", \"identificador_ofx\": \"aa36300d2adfb061032101eddaab451c172489b4752f99d09d6676cd0867c236\", \"usuario_id\": 1}', '2026-09-28 20:25:19.595595'),
(224, 1, 1, 'cartao', 'cartoes', '101', 'cadastrar', NULL, '{\"id\": 101, \"compra_id\": 56, \"data\": \"2026-09-16\", \"valor\": 375.00, \"parcela\": 1, \"paga\": 0, \"fitid\": \"2026081648540000000090110000000023\", \"usuario_id\": 1}', '2026-09-28 20:25:19.597209'),
(225, 1, 1, 'cartao', 'cartoes', '102', 'cadastrar', NULL, '{\"id\": 102, \"compra_id\": 56, \"data\": \"2026-10-16\", \"valor\": 375.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '2026-09-28 20:25:19.599528'),
(226, 1, 1, 'cartao', 'cartoes', '81', 'editar', '{\"id\": 81, \"compra_id\": 41, \"data\": \"2026-09-17\", \"valor\": 329.00, \"parcela\": 2, \"paga\": 0, \"fitid\": null, \"usuario_id\": 1}', '{\"id\": 81, \"compra_id\": 41, \"data\": \"2026-09-17\", \"valor\": 329.00, \"parcela\": 2, \"paga\": 0, \"fitid\": \"2026071748540000000090110000000024\", \"usuario_id\": 1}', '2026-09-28 20:25:19.609755'),
(227, 1, 1, 'cartao', 'cartao_nomes_recorrentes', 'fc5822fa784740edc1a6d9915cdb2fd7d6329d878e3a6208d2fd067d6db1b7cb', 'cadastrar', NULL, '{\"chave_descricao\": \"fc5822fa784740edc1a6d9915cdb2fd7d6329d878e3a6208d2fd067d6db1b7cb\", \"descricao_original\": \"VIVA                   CAMPOS DO JOR BR\", \"nome_personalizado\": \"Suplemento Vivas Mais\", \"usuario_id\": 1}', '2026-09-28 20:28:53.174634'),
(228, 1, 1, 'cartao', 'compras', '42', 'editar', '{\"id\": 42, \"nome\": \"VIVA                   CAMPOS DO JOR BR\", \"nome_original\": \"VIVA                   CAMPOS DO JOR BR\", \"categoria\": \"pessoal\", \"valor_total\": 193.00, \"total_parcelas\": 1, \"data_compra\": \"2026-08-09\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d0e436385c49e444cfd5e64e9082257aafa407e5fa35359baedea19f6df9cee\", \"usuario_id\": 1}', '{\"id\": 42, \"nome\": \"Suplemento Vivas Mais\", \"nome_original\": \"VIVA                   CAMPOS DO JOR BR\", \"categoria\": \"pessoal\", \"valor_total\": 193.00, \"total_parcelas\": 1, \"data_compra\": \"2026-08-09\", \"origem\": \"ofx\", \"identificador_ofx\": \"9d0e436385c49e444cfd5e64e9082257aafa407e5fa35359baedea19f6df9cee\", \"usuario_id\": 1}', '2026-09-28 20:28:53.177407'),
(229, 1, 1, 'cartao', 'compras', '56', 'editar', '{\"id\": 56, \"nome\": \"W K DIAGNOSE   TAUBATE     BR\", \"nome_original\": \"W K DIAGNOSE   TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 750.00, \"total_parcelas\": 2, \"data_compra\": \"2026-08-16\", \"origem\": \"ofx\", \"identificador_ofx\": \"aa36300d2adfb061032101eddaab451c172489b4752f99d09d6676cd0867c236\", \"usuario_id\": 1}', '{\"id\": 56, \"nome\": \"Resonancia WK\", \"nome_original\": \"W K DIAGNOSE   TAUBATE     BR\", \"categoria\": \"pessoal\", \"valor_total\": 750.00, \"total_parcelas\": 2, \"data_compra\": \"2026-08-16\", \"origem\": \"ofx\", \"identificador_ofx\": \"aa36300d2adfb061032101eddaab451c172489b4752f99d09d6676cd0867c236\", \"usuario_id\": 1}', '2026-09-28 20:29:22.221092'),
(230, 1, 1, 'cartao', 'compras', '24', 'editar', '{\"id\": 24, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"aeec41d68be97743da65cfdeec9e58d6378e5b3bea7a63c639eb2d7dc8b370eb\", \"usuario_id\": 1}', '{\"id\": 24, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"unica\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"aeec41d68be97743da65cfdeec9e58d6378e5b3bea7a63c639eb2d7dc8b370eb\", \"usuario_id\": 1}', '2026-09-28 21:41:22.538156'),
(231, 1, 1, 'cartao', 'compras', '36', 'editar', '{\"id\": 36, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-07-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"7554900bcb83ca23cef94216af19a7afc7f3249571d802fed24710897ef2b49f\", \"usuario_id\": 1}', '{\"id\": 36, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"unica\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-07-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"7554900bcb83ca23cef94216af19a7afc7f3249571d802fed24710897ef2b49f\", \"usuario_id\": 1}', '2026-09-28 21:46:07.761932'),
(232, 1, 1, 'cartao', 'compras', '50', 'editar', '{\"id\": 50, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"pessoal\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-08-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"ea98011b234a6574805db5860e0f07831f6e021e73dfca44be5fdc57c404bbd3\", \"usuario_id\": 1}', '{\"id\": 50, \"nome\": \"Spotify museu\", \"nome_original\": \"EBN         *SPOTIFY   CURITIBA      BR\", \"categoria\": \"unica\", \"valor_total\": 23.90, \"total_parcelas\": 1, \"data_compra\": \"2026-08-03\", \"origem\": \"ofx\", \"identificador_ofx\": \"ea98011b234a6574805db5860e0f07831f6e021e73dfca44be5fdc57c404bbd3\", \"usuario_id\": 1}', '2026-09-28 21:46:07.763203'),
(233, 1, 1, 'cartao', 'compras', '26', 'editar', '{\"id\": 26, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-21\", \"origem\": \"ofx\", \"identificador_ofx\": \"54de9e6a13bee311ffcb23efbadcdbe1ce243a5d2f0cdc2a2f5754a0f5c4ba12\", \"usuario_id\": 1}', '{\"id\": 26, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-06-21\", \"origem\": \"ofx\", \"identificador_ofx\": \"54de9e6a13bee311ffcb23efbadcdbe1ce243a5d2f0cdc2a2f5754a0f5c4ba12\", \"usuario_id\": 1}', '2026-09-28 21:50:12.609255'),
(234, 1, 1, 'cartao', 'compras', '37', 'editar', '{\"id\": 37, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-07-21\", \"origem\": \"ofx\", \"identificador_ofx\": \"4e8702025a5bc5e33722eb341b6265d5ecfc3f153e33f64464b6c522259181fc\", \"usuario_id\": 1}', '{\"id\": 37, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-07-21\", \"origem\": \"ofx\", \"identificador_ofx\": \"4e8702025a5bc5e33722eb341b6265d5ecfc3f153e33f64464b6c522259181fc\", \"usuario_id\": 1}', '2026-09-28 21:50:12.609932'),
(235, 1, 1, 'cartao', 'compras', '53', 'editar', '{\"id\": 53, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-08-20\", \"origem\": \"ofx\", \"identificador_ofx\": \"a3be6bc65cd74a56155f17e6938d2800aa2008295741409be6d56704bfa4108a\", \"usuario_id\": 1}', '{\"id\": 53, \"nome\": \"Meli\", \"nome_original\": \"MP*MELIMAIS            OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 19.90, \"total_parcelas\": 1, \"data_compra\": \"2026-08-20\", \"origem\": \"ofx\", \"identificador_ofx\": \"a3be6bc65cd74a56155f17e6938d2800aa2008295741409be6d56704bfa4108a\", \"usuario_id\": 1}', '2026-09-28 21:50:12.615361'),
(236, 1, 1, 'cartao', 'compras', '32', 'editar', '{\"id\": 32, \"nome\": \"Prime\", \"nome_original\": \"AMAZON PRIME   SAO PAULO   BR\", \"categoria\": \"pessoal\", \"valor_total\": 166.80, \"total_parcelas\": 6, \"data_compra\": \"2026-06-06\", \"origem\": \"ofx\", \"identificador_ofx\": \"9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb\", \"usuario_id\": 1}', '{\"id\": 32, \"nome\": \"Prime\", \"nome_original\": \"AMAZON PRIME   SAO PAULO   BR\", \"categoria\": \"conjunta\", \"valor_total\": 166.80, \"total_parcelas\": 6, \"data_compra\": \"2026-06-06\", \"origem\": \"ofx\", \"identificador_ofx\": \"9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb\", \"usuario_id\": 1}', '2026-09-28 22:10:11.231748'),
(237, 1, 1, 'cartao', 'compras', '18', 'editar', '{\"id\": 18, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-05-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"eaa9bc4def5f6b7ff912b46ecc793f9194aa6af4763a31c116ddef4ebeacfbf7\", \"usuario_id\": 1}', '{\"id\": 18, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-05-28\", \"origem\": \"ofx\", \"identificador_ofx\": \"eaa9bc4def5f6b7ff912b46ecc793f9194aa6af4763a31c116ddef4ebeacfbf7\", \"usuario_id\": 1}', '2026-09-28 22:10:11.235970'),
(238, 1, 1, 'cartao', 'compras', '44', 'editar', '{\"id\": 44, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"pessoal\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-07-27\", \"origem\": \"ofx\", \"identificador_ofx\": \"304b9ef13ec7cafcf0efa243ee020cbfab583c0819eefcffd3d62a6c37b20c05\", \"usuario_id\": 1}', '{\"id\": 44, \"nome\": \"HBO MAX\", \"nome_original\": \"MP*HBOMAXASSIN         OSASCO        BR\", \"categoria\": \"conjunta\", \"valor_total\": 31.43, \"total_parcelas\": 1, \"data_compra\": \"2026-07-27\", \"origem\": \"ofx\", \"identificador_ofx\": \"304b9ef13ec7cafcf0efa243ee020cbfab583c0819eefcffd3d62a6c37b20c05\", \"usuario_id\": 1}', '2026-09-28 22:12:02.162998');

-- --------------------------------------------------------

--
-- Estrutura para tabela `investimentos_internacionais`
--

CREATE TABLE `investimentos_internacionais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(20) NOT NULL,
  `tipo_ativo` enum('stock','etf','reit','adr','cripto') NOT NULL,
  `quantidade` decimal(22,8) NOT NULL,
  `valor_unitario` decimal(22,8) NOT NULL,
  `valor_investido` decimal(24,8) NOT NULL,
  `data` date NOT NULL,
  `valor_mercado` decimal(22,8) DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo_operacao` enum('compra','venda') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Acionadores `investimentos_internacionais`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_investimentos_internacionais_DELETE` AFTER DELETE ON `investimentos_internacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'investimentos','investimentos_internacionais',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'valor_investido',OLD.`valor_investido`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_investimentos_internacionais_INSERT` AFTER INSERT ON `investimentos_internacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'investimentos','investimentos_internacionais',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'valor_investido',NEW.`valor_investido`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_investimentos_internacionais_UPDATE` AFTER UPDATE ON `investimentos_internacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'valor_investido',OLD.`valor_investido`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'valor_investido',NEW.`valor_investido`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'investimentos','investimentos_internacionais',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'valor_investido',OLD.`valor_investido`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'valor_investido',NEW.`valor_investido`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_investimentos_internacionais_DELETE` BEFORE DELETE ON `investimentos_internacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_investimentos_internacionais_INSERT` BEFORE INSERT ON `investimentos_internacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_investimentos_internacionais_UPDATE` BEFORE UPDATE ON `investimentos_internacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'valor_investido',OLD.`valor_investido`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'valor_investido',NEW.`valor_investido`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Acionadores `investimentos_nacionais`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_investimentos_nacionais_DELETE` AFTER DELETE ON `investimentos_nacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'investimentos','investimentos_nacionais',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_investimentos_nacionais_INSERT` AFTER INSERT ON `investimentos_nacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'investimentos','investimentos_nacionais',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_investimentos_nacionais_UPDATE` AFTER UPDATE ON `investimentos_nacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'investimentos','investimentos_nacionais',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_investimentos_nacionais_DELETE` BEFORE DELETE ON `investimentos_nacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_investimentos_nacionais_INSERT` BEFORE INSERT ON `investimentos_nacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_investimentos_nacionais_UPDATE` BEFORE UPDATE ON `investimentos_nacionais` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'ticker',OLD.`ticker`,'tipo_ativo',OLD.`tipo_ativo`,'quantidade',OLD.`quantidade`,'valor_unitario',OLD.`valor_unitario`,'data',OLD.`data`,'valor_mercado',OLD.`valor_mercado`,'logo',OLD.`logo`,'criado_em',OLD.`criado_em`,'tipo_operacao',OLD.`tipo_operacao`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'ticker',NEW.`ticker`,'tipo_ativo',NEW.`tipo_ativo`,'quantidade',NEW.`quantidade`,'valor_unitario',NEW.`valor_unitario`,'data',NEW.`data`,'valor_mercado',NEW.`valor_mercado`,'logo',NEW.`logo`,'criado_em',NEW.`criado_em`,'tipo_operacao',NEW.`tipo_operacao`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='investimentos' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas_anuais`
--

CREATE TABLE `metas_anuais` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ano` smallint(4) NOT NULL,
  `meta_mensal` decimal(15,2) NOT NULL DEFAULT 1000.00,
  `meta_basica` decimal(15,2) NOT NULL DEFAULT 12000.00,
  `referencia_pontuais` decimal(15,2) NOT NULL DEFAULT 0.00,
  `meta_sonho` decimal(15,2) NOT NULL DEFAULT 50000.00,
  `meta_desafio` decimal(15,2) NOT NULL DEFAULT 65000.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `metas_anuais`
--

INSERT INTO `metas_anuais` (`id`, `usuario_id`, `ano`, `meta_mensal`, `meta_basica`, `referencia_pontuais`, `meta_sonho`, `meta_desafio`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 2026, 1100.00, 13200.00, 0.00, 50000.00, 65000.00, '2026-09-29 23:56:21', '2026-09-29 23:56:21');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas_contribuicoes`
--

CREATE TABLE `metas_contribuicoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `tipo` enum('decimo_terceiro','ferias','restituicao_ir','servico_extra','dividendo','day_trade','outro') NOT NULL,
  `descricao` varchar(150) NOT NULL,
  `valor_recebido` decimal(15,2) NOT NULL DEFAULT 0.00,
  `valor_guardado` decimal(15,2) NOT NULL DEFAULT 0.00,
  `percentual_guardado` decimal(5,2) DEFAULT NULL,
  `origem_tabela` varchar(50) DEFAULT NULL,
  `origem_id` int(11) DEFAULT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `metas_contribuicoes`
--

INSERT INTO `metas_contribuicoes` (`id`, `usuario_id`, `data`, `tipo`, `descricao`, `valor_recebido`, `valor_guardado`, `percentual_guardado`, `origem_tabela`, `origem_id`, `observacao`, `criado_em`, `atualizado_em`) VALUES
(1, 1, '2026-09-29', 'servico_extra', 'serviço teste', 1000.00, 1000.00, 100.00, NULL, NULL, '', '2026-09-29 23:37:20', '2026-09-29 23:38:20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metas_fechamentos`
--

CREATE TABLE `metas_fechamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ano` smallint(4) NOT NULL,
  `mes` tinyint(2) NOT NULL,
  `meta` decimal(15,2) NOT NULL DEFAULT 1000.00,
  `conseguido` decimal(15,2) NOT NULL DEFAULT 0.00,
  `observacao` varchar(255) DEFAULT NULL,
  `fechado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Despejando dados para a tabela `metas_fechamentos`
--

INSERT INTO `metas_fechamentos` (`id`, `usuario_id`, `ano`, `mes`, `meta`, `conseguido`, `observacao`, `fechado_em`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 2026, 1, 1000.00, 4500.00, 'Teste do fechamento', '2026-09-29 20:28:50', '2026-09-29 23:27:28', '2026-09-29 23:28:50');

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

--
-- Acionadores `movimentacoes_financeiras`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_movimentacoes_financeiras_DELETE` AFTER DELETE ON `movimentacoes_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'saldos','movimentacoes_financeiras',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'conta_id',OLD.`conta_id`,'tipo',OLD.`tipo`,'data',OLD.`data`,'descricao',OLD.`descricao`,'valor',OLD.`valor`,'transferencia_id',OLD.`transferencia_id`,'origem_modulo',OLD.`origem_modulo`,'origem_id',OLD.`origem_id`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_movimentacoes_financeiras_INSERT` AFTER INSERT ON `movimentacoes_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'saldos','movimentacoes_financeiras',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'conta_id',NEW.`conta_id`,'tipo',NEW.`tipo`,'data',NEW.`data`,'descricao',NEW.`descricao`,'valor',NEW.`valor`,'transferencia_id',NEW.`transferencia_id`,'origem_modulo',NEW.`origem_modulo`,'origem_id',NEW.`origem_id`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_movimentacoes_financeiras_UPDATE` AFTER UPDATE ON `movimentacoes_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'conta_id',OLD.`conta_id`,'tipo',OLD.`tipo`,'data',OLD.`data`,'descricao',OLD.`descricao`,'valor',OLD.`valor`,'transferencia_id',OLD.`transferencia_id`,'origem_modulo',OLD.`origem_modulo`,'origem_id',OLD.`origem_id`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'conta_id',NEW.`conta_id`,'tipo',NEW.`tipo`,'data',NEW.`data`,'descricao',NEW.`descricao`,'valor',NEW.`valor`,'transferencia_id',NEW.`transferencia_id`,'origem_modulo',NEW.`origem_modulo`,'origem_id',NEW.`origem_id`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'saldos','movimentacoes_financeiras',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'conta_id',OLD.`conta_id`,'tipo',OLD.`tipo`,'data',OLD.`data`,'descricao',OLD.`descricao`,'valor',OLD.`valor`,'transferencia_id',OLD.`transferencia_id`,'origem_modulo',OLD.`origem_modulo`,'origem_id',OLD.`origem_id`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`),JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'conta_id',NEW.`conta_id`,'tipo',NEW.`tipo`,'data',NEW.`data`,'descricao',NEW.`descricao`,'valor',NEW.`valor`,'transferencia_id',NEW.`transferencia_id`,'origem_modulo',NEW.`origem_modulo`,'origem_id',NEW.`origem_id`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_movimentacoes_financeiras_DELETE` BEFORE DELETE ON `movimentacoes_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='saldos' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_movimentacoes_financeiras_INSERT` BEFORE INSERT ON `movimentacoes_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='saldos' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_movimentacoes_financeiras_UPDATE` BEFORE UPDATE ON `movimentacoes_financeiras` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'usuario_id',OLD.`usuario_id`,'conta_id',OLD.`conta_id`,'tipo',OLD.`tipo`,'data',OLD.`data`,'descricao',OLD.`descricao`,'valor',OLD.`valor`,'transferencia_id',OLD.`transferencia_id`,'origem_modulo',OLD.`origem_modulo`,'origem_id',OLD.`origem_id`,'criado_em',OLD.`criado_em`,'atualizado_em',OLD.`atualizado_em`) <=> JSON_OBJECT('id',NEW.`id`,'usuario_id',NEW.`usuario_id`,'conta_id',NEW.`conta_id`,'tipo',NEW.`tipo`,'data',NEW.`data`,'descricao',NEW.`descricao`,'valor',NEW.`valor`,'transferencia_id',NEW.`transferencia_id`,'origem_modulo',NEW.`origem_modulo`,'origem_id',NEW.`origem_id`,'criado_em',NEW.`criado_em`,'atualizado_em',NEW.`atualizado_em`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='saldos' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Despejando dados para a tabela `ofx_importacoes`
--

INSERT INTO `ofx_importacoes` (`id`, `nome_arquivo`, `hash_arquivo`, `periodo_inicio`, `periodo_fim`, `quantidade_transacoes`, `usuario_id`) VALUES
(1, 'OUROCARD_FACIL_VISA-Jun_26.ofx', '2ee351f691b2dca25cb09ba377c22f49a41bb3cd5409bc7371a9e6e058b0c704', '2026-01-29', '2026-05-22', 17, 1),
(2, 'OUROCARD_FACIL_VISA-Jul_26.ofx', '524aa5d9229eee5ec3e5cf1624ff374901ea58120d48787232afc300955aced6', '2026-03-26', '2026-06-21', 22, 1),
(3, 'OUROCARD_FACIL_VISA-Ago_26.ofx', '4f60ea57756ce3710429b54494ce14373fc152c00bc7b254a8a6b8bca32d38d9', '2026-04-25', '2026-07-21', 16, 1),
(4, 'OUROCARD_FACIL_VISA-Set_26.ofx', '9cea14186ff022999340a1c5619d0e56ad274d51bd9f923ad0309077f6c388f9', '2026-04-30', '2026-08-20', 25, 1);

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

--
-- Acionadores `operacoes`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_operacoes_DELETE` AFTER DELETE ON `operacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'daytrade','operacoes',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'data',OLD.`data`,'acao',OLD.`acao`,'quantidade',OLD.`quantidade`,'valor_compra',OLD.`valor_compra`,'valor_venda',OLD.`valor_venda`,'total_compra',OLD.`total_compra`,'total_venda',OLD.`total_venda`,'valor_operacao',OLD.`valor_operacao`,'lucro_bruto',OLD.`lucro_bruto`,'taxas',OLD.`taxas`,'deducao_1',OLD.`deducao_1`,'imposto_20',OLD.`imposto_20`,'lucro_desc',OLD.`lucro_desc`,'darf',OLD.`darf`,'lucro_final',OLD.`lucro_final`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_operacoes_INSERT` AFTER INSERT ON `operacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'daytrade','operacoes',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'data',NEW.`data`,'acao',NEW.`acao`,'quantidade',NEW.`quantidade`,'valor_compra',NEW.`valor_compra`,'valor_venda',NEW.`valor_venda`,'total_compra',NEW.`total_compra`,'total_venda',NEW.`total_venda`,'valor_operacao',NEW.`valor_operacao`,'lucro_bruto',NEW.`lucro_bruto`,'taxas',NEW.`taxas`,'deducao_1',NEW.`deducao_1`,'imposto_20',NEW.`imposto_20`,'lucro_desc',NEW.`lucro_desc`,'darf',NEW.`darf`,'lucro_final',NEW.`lucro_final`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_operacoes_UPDATE` AFTER UPDATE ON `operacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'data',OLD.`data`,'acao',OLD.`acao`,'quantidade',OLD.`quantidade`,'valor_compra',OLD.`valor_compra`,'valor_venda',OLD.`valor_venda`,'total_compra',OLD.`total_compra`,'total_venda',OLD.`total_venda`,'valor_operacao',OLD.`valor_operacao`,'lucro_bruto',OLD.`lucro_bruto`,'taxas',OLD.`taxas`,'deducao_1',OLD.`deducao_1`,'imposto_20',OLD.`imposto_20`,'lucro_desc',OLD.`lucro_desc`,'darf',OLD.`darf`,'lucro_final',OLD.`lucro_final`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'data',NEW.`data`,'acao',NEW.`acao`,'quantidade',NEW.`quantidade`,'valor_compra',NEW.`valor_compra`,'valor_venda',NEW.`valor_venda`,'total_compra',NEW.`total_compra`,'total_venda',NEW.`total_venda`,'valor_operacao',NEW.`valor_operacao`,'lucro_bruto',NEW.`lucro_bruto`,'taxas',NEW.`taxas`,'deducao_1',NEW.`deducao_1`,'imposto_20',NEW.`imposto_20`,'lucro_desc',NEW.`lucro_desc`,'darf',NEW.`darf`,'lucro_final',NEW.`lucro_final`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'daytrade','operacoes',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'data',OLD.`data`,'acao',OLD.`acao`,'quantidade',OLD.`quantidade`,'valor_compra',OLD.`valor_compra`,'valor_venda',OLD.`valor_venda`,'total_compra',OLD.`total_compra`,'total_venda',OLD.`total_venda`,'valor_operacao',OLD.`valor_operacao`,'lucro_bruto',OLD.`lucro_bruto`,'taxas',OLD.`taxas`,'deducao_1',OLD.`deducao_1`,'imposto_20',OLD.`imposto_20`,'lucro_desc',OLD.`lucro_desc`,'darf',OLD.`darf`,'lucro_final',OLD.`lucro_final`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'data',NEW.`data`,'acao',NEW.`acao`,'quantidade',NEW.`quantidade`,'valor_compra',NEW.`valor_compra`,'valor_venda',NEW.`valor_venda`,'total_compra',NEW.`total_compra`,'total_venda',NEW.`total_venda`,'valor_operacao',NEW.`valor_operacao`,'lucro_bruto',NEW.`lucro_bruto`,'taxas',NEW.`taxas`,'deducao_1',NEW.`deducao_1`,'imposto_20',NEW.`imposto_20`,'lucro_desc',NEW.`lucro_desc`,'darf',NEW.`darf`,'lucro_final',NEW.`lucro_final`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_operacoes_DELETE` BEFORE DELETE ON `operacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_operacoes_INSERT` BEFORE INSERT ON `operacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_operacoes_UPDATE` BEFORE UPDATE ON `operacoes` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'corretora_id',OLD.`corretora_id`,'data',OLD.`data`,'acao',OLD.`acao`,'quantidade',OLD.`quantidade`,'valor_compra',OLD.`valor_compra`,'valor_venda',OLD.`valor_venda`,'total_compra',OLD.`total_compra`,'total_venda',OLD.`total_venda`,'valor_operacao',OLD.`valor_operacao`,'lucro_bruto',OLD.`lucro_bruto`,'taxas',OLD.`taxas`,'deducao_1',OLD.`deducao_1`,'imposto_20',OLD.`imposto_20`,'lucro_desc',OLD.`lucro_desc`,'darf',OLD.`darf`,'lucro_final',OLD.`lucro_final`,'criado_em',OLD.`criado_em`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'corretora_id',NEW.`corretora_id`,'data',NEW.`data`,'acao',NEW.`acao`,'quantidade',NEW.`quantidade`,'valor_compra',NEW.`valor_compra`,'valor_venda',NEW.`valor_venda`,'total_compra',NEW.`total_compra`,'total_venda',NEW.`total_venda`,'valor_operacao',NEW.`valor_operacao`,'lucro_bruto',NEW.`lucro_bruto`,'taxas',NEW.`taxas`,'deducao_1',NEW.`deducao_1`,'imposto_20',NEW.`imposto_20`,'lucro_desc',NEW.`lucro_desc`,'darf',NEW.`darf`,'lucro_final',NEW.`lucro_final`,'criado_em',NEW.`criado_em`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='daytrade' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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

--
-- Acionadores `rendas`
--
DELIMITER $$
CREATE TRIGGER `mcf_audit_rendas_DELETE` AFTER DELETE ON `rendas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(OLD.usuario_id,@mcf_ator_id,'receitas','rendas',OLD.`id`,'excluir',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'descricao',OLD.`descricao`,'data',OLD.`data`,'valor',OLD.`valor`,'tipo',OLD.`tipo`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'recebido',OLD.`recebido`,'porcentagem',OLD.`porcentagem`,'criado_em',OLD.`criado_em`,'classificacao',OLD.`classificacao`,'usuario_id',OLD.`usuario_id`),NULL); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_rendas_INSERT` AFTER INSERT ON `rendas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND 1 THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'receitas','rendas',NEW.`id`,'cadastrar',NULL,JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'descricao',NEW.`descricao`,'data',NEW.`data`,'valor',NEW.`valor`,'tipo',NEW.`tipo`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'recebido',NEW.`recebido`,'porcentagem',NEW.`porcentagem`,'criado_em',NEW.`criado_em`,'classificacao',NEW.`classificacao`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_audit_rendas_UPDATE` AFTER UPDATE ON `rendas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'descricao',OLD.`descricao`,'data',OLD.`data`,'valor',OLD.`valor`,'tipo',OLD.`tipo`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'recebido',OLD.`recebido`,'porcentagem',OLD.`porcentagem`,'criado_em',OLD.`criado_em`,'classificacao',OLD.`classificacao`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'descricao',NEW.`descricao`,'data',NEW.`data`,'valor',NEW.`valor`,'tipo',NEW.`tipo`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'recebido',NEW.`recebido`,'porcentagem',NEW.`porcentagem`,'criado_em',NEW.`criado_em`,'classificacao',NEW.`classificacao`,'usuario_id',NEW.`usuario_id`)) THEN INSERT INTO historico_alteracoes(proprietario_id,ator_id,modulo,tabela,registro,acao,antes,depois) VALUES(NEW.usuario_id,@mcf_ator_id,'receitas','rendas',NEW.`id`,'editar',JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'descricao',OLD.`descricao`,'data',OLD.`data`,'valor',OLD.`valor`,'tipo',OLD.`tipo`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'recebido',OLD.`recebido`,'porcentagem',OLD.`porcentagem`,'criado_em',OLD.`criado_em`,'classificacao',OLD.`classificacao`,'usuario_id',OLD.`usuario_id`),JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'descricao',NEW.`descricao`,'data',NEW.`data`,'valor',NEW.`valor`,'tipo',NEW.`tipo`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'recebido',NEW.`recebido`,'porcentagem',NEW.`porcentagem`,'criado_em',NEW.`criado_em`,'classificacao',NEW.`classificacao`,'usuario_id',NEW.`usuario_id`)); END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_rendas_DELETE` BEFORE DELETE ON `rendas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF OLD.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> OLD.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=OLD.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='receitas' AND m.nivel='edicao' AND m.excluir=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_rendas_INSERT` BEFORE INSERT ON `rendas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN  IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND 1 AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='receitas' AND m.nivel='edicao' AND m.cadastrar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `mcf_guard_rendas_UPDATE` BEFORE UPDATE ON `rendas` FOR EACH ROW BEGIN IF COALESCE(@mcf_ator_id,0)>0 THEN IF NOT (OLD.usuario_id <=> NEW.usuario_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario imutavel'; END IF; IF NEW.usuario_id <> @mcf_usuario_id THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Proprietario invalido'; END IF; IF @mcf_ator_id <> NEW.usuario_id AND NOT (JSON_OBJECT('id',OLD.`id`,'nome',OLD.`nome`,'descricao',OLD.`descricao`,'data',OLD.`data`,'valor',OLD.`valor`,'tipo',OLD.`tipo`,'grupo_recorrencia',OLD.`grupo_recorrencia`,'parcela_atual',OLD.`parcela_atual`,'total_parcelas',OLD.`total_parcelas`,'recebido',OLD.`recebido`,'porcentagem',OLD.`porcentagem`,'criado_em',OLD.`criado_em`,'classificacao',OLD.`classificacao`,'usuario_id',OLD.`usuario_id`) <=> JSON_OBJECT('id',NEW.`id`,'nome',NEW.`nome`,'descricao',NEW.`descricao`,'data',NEW.`data`,'valor',NEW.`valor`,'tipo',NEW.`tipo`,'grupo_recorrencia',NEW.`grupo_recorrencia`,'parcela_atual',NEW.`parcela_atual`,'total_parcelas',NEW.`total_parcelas`,'recebido',NEW.`recebido`,'porcentagem',NEW.`porcentagem`,'criado_em',NEW.`criado_em`,'classificacao',NEW.`classificacao`,'usuario_id',NEW.`usuario_id`)) AND NOT EXISTS(SELECT 1 FROM compartilhamentos s JOIN compartilhamento_modulos m ON m.compartilhamento_id=s.id JOIN usuarios u ON u.id=s.proprietario_id WHERE s.id=@mcf_convite_id AND s.versao=@mcf_convite_versao AND s.proprietario_id=NEW.usuario_id AND s.leitor_id=@mcf_ator_id AND s.estado='ativo' AND m.modulo='receitas' AND m.nivel='edicao' AND m.editar=1 AND u.status_assinatura='ativo' AND u.data_expiracao>=CURDATE()) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Acao nao autorizada neste modulo'; END IF;  END IF; END
$$
DELIMITER ;

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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `nome` varchar(100) DEFAULT NULL,
  `email_normalizado` varchar(100) GENERATED ALWAYS AS (lcase(trim(`email`))) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `email`, `senha`, `status_assinatura`, `data_expiracao`, `criado_em`, `nome`) VALUES
(1, 'adenilson.ff@outlook.com', '$2y$10$CDMMvK.MgUxuQLcq1cLQ1ecVklRFPwXEivMWlGPVMfautxXGwOLfe', 'ativo', '2026-10-26', '2026-09-26 12:37:25', 'adenilson fernandes ferreira');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_modulos`
--

CREATE TABLE `usuario_modulos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `habilitado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_modulos`
--

INSERT INTO `usuario_modulos` (`id`, `usuario_id`, `modulo`, `habilitado`) VALUES
(1, 1, 'patrimonio', 1),
(2, 1, 'despesas', 1),
(3, 1, 'cartao', 1),
(4, 1, 'receitas', 1),
(5, 1, 'investimentos', 1),
(6, 1, 'dividendos', 1),
(7, 1, 'daytrade', 1),
(8, 1, 'analise', 1),
(9, 1, 'relatorios', 1),
(89, 1, 'reservas', 1);

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
-- Índices de tabela `cartao_categorias_recorrentes`
--
ALTER TABLE `cartao_categorias_recorrentes`
  ADD PRIMARY KEY (`usuario_id`,`chave_descricao`);

--
-- Índices de tabela `cartao_exclusoes`
--
ALTER TABLE `cartao_exclusoes`
  ADD PRIMARY KEY (`usuario_id`,`chave_importacao`);

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
-- Índices de tabela `compartilhamentos`
--
ALTER TABLE `compartilhamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_compartilhamento_pessoas` (`proprietario_id`,`leitor_id`),
  ADD KEY `ix_compartilhamento_leitor` (`leitor_id`,`estado`);

--
-- Índices de tabela `compartilhamento_modulos`
--
ALTER TABLE `compartilhamento_modulos`
  ADD PRIMARY KEY (`compartilhamento_id`,`modulo`);

--
-- Índices de tabela `compartilhamento_vistos`
--
ALTER TABLE `compartilhamento_vistos`
  ADD PRIMARY KEY (`usuario_id`,`compartilhamento_id`);

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
-- Índices de tabela `historico_alteracoes`
--
ALTER TABLE `historico_alteracoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dono_data` (`proprietario_id`,`id`);

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
-- Índices de tabela `metas_anuais`
--
ALTER TABLE `metas_anuais`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_metas_anuais_usuario_ano` (`usuario_id`,`ano`);

--
-- Índices de tabela `metas_contribuicoes`
--
ALTER TABLE `metas_contribuicoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_metas_contribuicoes_usuario_data` (`usuario_id`,`data`),
  ADD KEY `idx_metas_contribuicoes_origem` (`origem_tabela`,`origem_id`);

--
-- Índices de tabela `metas_fechamentos`
--
ALTER TABLE `metas_fechamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_metas_fechamentos_usuario_ano_mes` (`usuario_id`,`ano`,`mes`),
  ADD KEY `idx_metas_fechamentos_periodo` (`usuario_id`,`ano`,`mes`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_usuarios_email_normalizado` (`email_normalizado`);

--
-- Índices de tabela `usuario_modulos`
--
ALTER TABLE `usuario_modulos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_modulo` (`usuario_id`,`modulo`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cartoes`
--
ALTER TABLE `cartoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compartilhamentos`
--
ALTER TABLE `compartilhamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `contas_financeiras`
--
ALTER TABLE `contas_financeiras`
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
-- AUTO_INCREMENT de tabela `historico_alteracoes`
--
ALTER TABLE `historico_alteracoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT de tabela `investimentos_internacionais`
--
ALTER TABLE `investimentos_internacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `investimentos_nacionais`
--
ALTER TABLE `investimentos_nacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `metas_anuais`
--
ALTER TABLE `metas_anuais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `metas_contribuicoes`
--
ALTER TABLE `metas_contribuicoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `metas_fechamentos`
--
ALTER TABLE `metas_fechamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
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
-- AUTO_INCREMENT de tabela `usuario_modulos`
--
ALTER TABLE `usuario_modulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

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
-- Restrições para tabelas `compartilhamentos`
--
ALTER TABLE `compartilhamentos`
  ADD CONSTRAINT `fk_compartilhamento_dono` FOREIGN KEY (`proprietario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_compartilhamento_leitor` FOREIGN KEY (`leitor_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `compartilhamento_modulos`
--
ALTER TABLE `compartilhamento_modulos`
  ADD CONSTRAINT `fk_compartilhamento_modulos` FOREIGN KEY (`compartilhamento_id`) REFERENCES `compartilhamentos` (`id`) ON DELETE CASCADE;

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
-- Restrições para tabelas `metas_anuais`
--
ALTER TABLE `metas_anuais`
  ADD CONSTRAINT `fk_metas_anuais_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `metas_contribuicoes`
--
ALTER TABLE `metas_contribuicoes`
  ADD CONSTRAINT `fk_metas_contribuicoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `metas_fechamentos`
--
ALTER TABLE `metas_fechamentos`
  ADD CONSTRAINT `fk_metas_fechamentos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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

--
-- Restrições para tabelas `usuario_modulos`
--
ALTER TABLE `usuario_modulos`
  ADD CONSTRAINT `fk_usuario_modulos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
