-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 10/09/2026 às 11:57
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

--
-- Despejando dados para a tabela `acoes_internacionais`
--

INSERT INTO `acoes_internacionais` (`id`, `ticker`, `quantidade`, `valor_unitario`, `valor_investido`, `data`, `valor_mercado`, `logo`, `tipo`) VALUES
(1, 'NVDA', 2.56000000, 100.0000, 256.00000000, '0000-00-00', 223.6700, 'https://icons.brapi.dev/icons/NVDA.svg', 'compra'),
(2, 'AAPL', 0.02236000, 100.0000, 2.23600000, '2026-09-03', 315.3400, 'https://icons.brapi.dev/icons/AAPL.svg', 'compra'),
(3, 'AAPL', -0.02000000, 105.0000, -2.10000000, '2026-09-09', 315.3400, 'https://icons.brapi.dev/icons/AAPL.svg', 'venda');

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

--
-- Despejando dados para a tabela `acoes_nacionais`
--

INSERT INTO `acoes_nacionais` (`id`, `ticker`, `quantidade`, `valor_unitario`, `data`, `valor_mercado`, `logo`, `criado_em`, `tipo`) VALUES
(1, 'PETR4', 160, 42.22, '2026-08-05', 48.42, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-09 20:50:36', 'compra'),
(2, 'PETR4', 636, 44.34, '2026-08-27', 48.42, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-09 20:51:03', 'compra'),
(3, 'PETR4', 900, 42.70, '2026-08-31', 48.42, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-09 20:51:28', 'compra'),
(4, 'PETR4', -40, 47.28, '2026-09-04', 48.42, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-09 20:56:12', 'venda'),
(5, 'PETR4', 1550, 41.60, '2026-04-16', 48.42, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-10 00:49:32', 'compra');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cartao_nomes_recorrentes`
--

CREATE TABLE `cartao_nomes_recorrentes` (
  `chave_descricao` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `descricao_original` varchar(255) NOT NULL,
  `nome_personalizado` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cartao_nomes_recorrentes`
--

INSERT INTO `cartao_nomes_recorrentes` (`chave_descricao`, `descricao_original`, `nome_personalizado`) VALUES
('cdfe8737c29de9c674721b26eb8a8ffd774e96821c80fa64a3a19d5b0f1ca07e', 'EBN         *SPOTIFY   CURITIBA      BR', 'Spotify pam');

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

--
-- Despejando dados para a tabela `cartoes`
--

INSERT INTO `cartoes` (`id`, `compra_id`, `data`, `valor`, `parcela`, `paga`, `fitid`) VALUES
(1, 1, '2026-06-22', 19.90, 1, 1, '2026042248540000000090110000000001'),
(2, 2, '2026-06-26', 194.90, 1, 1, '2026042648540000000090110000000002'),
(3, 3, '2026-06-27', 264.21, 1, 1, '2026042748540000000090110000000003'),
(4, 4, '2026-06-28', 31.43, 1, 1, '2026042848540000000090110000000004'),
(5, 5, '2026-06-03', 23.90, 1, 1, '2026050348540000000090110000000005'),
(6, 6, '2026-06-18', 242.55, 1, 1, '2026051848540000000090110000000006'),
(7, 7, '2026-06-22', 19.90, 1, 1, '2026052248540000000090110000000007'),
(8, 8, '2026-06-30', 125.97, 1, 1, '2026043048540000000090110000000008'),
(9, 9, '2026-06-22', 44.39, 1, 1, '2026042248540000000090110000000009'),
(10, 10, '2026-06-25', 100.01, 1, 1, '2026042548540000000090110000000010'),
(11, 10, '2026-07-25', 99.99, 2, 1, '2026042548540000000090110000000013'),
(12, 10, '2026-08-25', 99.99, 3, 0, '2026042548540000000090110000000006'),
(13, 11, '2026-05-26', 162.66, 1, 0, NULL),
(14, 11, '2026-06-26', 162.66, 2, 1, '2026032648540000000090110000000011'),
(15, 11, '2026-07-26', 162.66, 3, 1, '2026032648540000000090110000000015'),
(16, 12, '2026-03-29', 146.75, 1, 0, NULL),
(17, 12, '2026-04-29', 146.75, 2, 0, NULL),
(18, 12, '2026-05-29', 146.75, 3, 0, NULL),
(19, 12, '2026-06-29', 146.75, 4, 1, '2026012948540000000090110000000012'),
(20, 13, '2026-05-30', 129.99, 1, 0, NULL),
(21, 13, '2026-06-30', 129.99, 2, 1, '2026033048540000000090110000000013'),
(22, 14, '2026-06-30', 1199.00, 1, 1, '2026043048540000000090110000000014'),
(23, 14, '2026-07-30', 1199.00, 2, 1, '2026043048540000000090110000000017'),
(24, 14, '2026-08-30', 1199.00, 3, 0, '2026043048540000000090110000000010'),
(25, 14, '2026-09-30', 1199.00, 4, 0, NULL),
(26, 14, '2026-10-30', 1199.00, 5, 0, NULL),
(27, 14, '2026-11-30', 1199.00, 6, 0, NULL),
(28, 14, '2026-12-30', 1199.00, 7, 0, NULL),
(29, 14, '2027-01-30', 1199.00, 8, 0, NULL),
(30, 14, '2027-02-28', 1199.00, 9, 0, NULL),
(31, 14, '2027-03-30', 1199.00, 10, 0, NULL),
(32, 15, '2026-06-30', 179.85, 1, 1, '2026043048540000000090110000000015'),
(33, 15, '2026-07-30', 179.85, 2, 1, '2026043048540000000090110000000018'),
(34, 16, '2026-04-01', 639.33, 1, 0, NULL),
(35, 16, '2026-05-01', 639.33, 2, 0, NULL),
(36, 16, '2026-06-01', 639.33, 3, 1, '2026030148540000000090110000000016'),
(37, 17, '2026-07-08', -190.98, 1, 1, '2026060848540000000090110000000001'),
(38, 18, '2026-07-28', 31.43, 1, 1, '2026052848540000000090110000000002'),
(39, 19, '2026-07-30', 259.00, 1, 1, '2026053048540000000090110000000003'),
(40, 20, '2026-07-03', 78.00, 1, 1, '2026060348540000000090110000000004'),
(41, 21, '2026-07-03', 420.00, 1, 1, '2026060348540000000090110000000005'),
(42, 22, '2026-07-03', 42.55, 1, 1, '2026060348540000000090110000000006'),
(43, 23, '2026-07-03', 209.98, 1, 1, '2026060348540000000090110000000007'),
(44, 24, '2026-07-03', 23.90, 1, 1, '2026060348540000000090110000000008'),
(45, 25, '2026-07-04', 212.00, 1, 1, '2026060448540000000090110000000009'),
(46, 26, '2026-07-21', 19.90, 1, 1, '2026062148540000000090110000000010'),
(47, 27, '2026-07-11', 89.90, 1, 1, '2026061148540000000090110000000011'),
(48, 28, '2026-07-26', 59.99, 1, 1, '2026052648540000000090110000000012'),
(49, 29, '2026-07-26', 84.00, 1, 1, '2026052648540000000090110000000014'),
(50, 29, '2026-08-26', 84.00, 2, 0, '2026052648540000000090110000000007'),
(51, 29, '2026-09-26', 84.00, 3, 0, NULL),
(52, 30, '2026-07-26', 183.33, 1, 1, '2026052648540000000090110000000016'),
(53, 30, '2026-08-26', 183.32, 2, 0, '2026052648540000000090110000000008'),
(54, 30, '2026-09-26', 183.33, 3, 0, NULL),
(55, 31, '2026-07-31', 195.04, 1, 1, '2026053148540000000090110000000019'),
(56, 31, '2026-08-31', 195.03, 2, 0, '2026053148540000000090110000000011'),
(57, 31, '2026-09-30', 195.04, 3, 0, NULL),
(58, 31, '2026-10-31', 195.04, 4, 0, NULL),
(59, 32, '2026-07-06', 27.80, 1, 1, '2026060648540000000090110000000020'),
(60, 32, '2026-08-06', 27.80, 2, 0, '2026060648540000000090110000000012'),
(61, 32, '2026-09-06', 27.80, 3, 0, NULL),
(62, 32, '2026-10-06', 27.80, 4, 0, NULL),
(63, 32, '2026-11-06', 27.80, 5, 0, NULL),
(64, 32, '2026-12-06', 27.80, 6, 0, NULL),
(65, 33, '2026-07-11', 80.61, 1, 1, '2026061148540000000090110000000021'),
(66, 33, '2026-08-11', 80.60, 2, 0, '2026061148540000000090110000000013'),
(67, 33, '2026-09-11', 80.61, 3, 0, NULL),
(68, 33, '2026-10-11', 80.61, 4, 0, NULL),
(69, 34, '2026-08-27', 31.43, 1, 0, '2026062748540000000090110000000001'),
(70, 35, '2026-08-28', 63.47, 1, 0, '2026062848540000000090110000000002'),
(71, 36, '2026-08-03', 23.90, 1, 0, '2026070348540000000090110000000003'),
(72, 37, '2026-08-21', 19.90, 1, 0, '2026072148540000000090110000000004'),
(73, 38, '2026-08-26', 59.99, 1, 0, '2026062648540000000090110000000005'),
(74, 39, '2026-08-29', 200.00, 1, 0, '2026062948540000000090110000000009'),
(75, 39, '2026-09-29', 200.00, 2, 0, NULL),
(76, 40, '2026-08-14', 453.22, 1, 0, '2026071448540000000090110000000014'),
(77, 40, '2026-09-14', 453.22, 2, 0, NULL),
(78, 40, '2026-10-14', 453.22, 3, 0, NULL),
(79, 40, '2026-11-14', 453.22, 4, 0, NULL),
(80, 41, '2026-08-17', 329.00, 1, 0, '2026071748540000000090110000000015'),
(81, 41, '2026-09-17', 329.00, 2, 0, NULL),
(82, 41, '2026-10-17', 329.00, 3, 0, NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `compras`
--

INSERT INTO `compras` (`id`, `nome`, `nome_original`, `categoria`, `valor_total`, `total_parcelas`, `data_compra`, `origem`, `identificador_ofx`) VALUES
(1, 'MP*MELIMAIS            OSASCO        BR', 'MP*MELIMAIS            OSASCO        BR', 'pessoal', 19.90, 1, '2026-04-22', 'ofx', '955c85cc76eb9395d2275f6175b6e4a1ffefb71024ff25a39063553e6051f782'),
(2, 'POPULAR PET PETSHOP SA SAO JOSE DOS  BR', 'POPULAR PET PETSHOP SA SAO JOSE DOS  BR', 'conjunta', 194.90, 1, '2026-04-26', 'ofx', '52afd8c9758269fadefaec0ab56d235a6cebf373e7cac70fae727a0fac552b5b'),
(3, 'MERCADOLIVRE*MERCADOLIVSAO PAULO     BR', 'MERCADOLIVRE*MERCADOLIVSAO PAULO     BR', 'pessoal', 264.21, 1, '2026-04-27', 'ofx', '633b607901be93f6ab2bf235a88292fda08ad8fe8f285b11a66260b7fcd0c417'),
(4, 'HBO MAX', 'MP*HBOMAXASSIN         OSASCO        BR', 'conjunta', 31.43, 1, '2026-04-28', 'ofx', 'b2748f307dc468e0d1dbbe1d73f53d943868e5e76a0d5ea4061342658cc66a1e'),
(5, 'Spotify pam', 'EBN         *SPOTIFY   CURITIBA      BR', 'unica', 23.90, 1, '2026-05-03', 'ofx', '4eb4d62ec687d8ad73b965757e439311a1ffedc800b7f41eac2bb2d6bc5c7261'),
(6, 'MP*MERCADOLIVRE        OSASCO        BR', 'MP*MERCADOLIVRE        OSASCO        BR', 'pessoal', 242.55, 1, '2026-05-18', 'ofx', '25e2c047b630586a56fa2a380de906ef6f36cc26c8a904d0bdc9c3c59c9b649b'),
(7, 'MP*MELIMAIS            OSASCO        BR', 'MP*MELIMAIS            OSASCO        BR', 'pessoal', 19.90, 1, '2026-05-22', 'ofx', 'cc57a6e7fcf1c8cad8283d11ed58450985eac00326b4e57282f605dc28a021a3'),
(8, 'MARISA 608             TAUBATE       BR', 'MARISA 608             TAUBATE       BR', 'pessoal', 125.97, 1, '2026-04-30', 'ofx', '54c64ccfcf7816334c3db0fd94a852855a046fe4184bfe72e4f14ee30addf4c7'),
(9, 'DM*NintendoeShop       SAO PAULO     BR', 'DM*NintendoeShop       SAO PAULO     BR', 'pessoal', 44.39, 1, '2026-04-22', 'ofx', 'b494e1389dd853947a44925c15a74206b698fc4859b5f8de08d3f70f300cef01'),
(10, 'Aquela 01', 'MP*LOJAXIAOMI  SANTA RITA DBR', 'pessoal', 299.99, 3, '2026-04-25', 'ofx', '9d558779e8a73606674b0c6b0c67eb8ba756878e6a4e26fdc1b43cfab04f60cf'),
(11, 'MERCADOLIVRE*  ILHABELA    BR', 'MERCADOLIVRE*  ILHABELA    BR', 'pessoal', 487.98, 3, '2026-03-26', 'ofx', '12b30b569ba27f26924ee2aa3d21f21ec4cdb5675477ea6c4d98b281f08d1a7c'),
(12, 'MLP    *KaBuM  eldorado do BR', 'MLP    *KaBuM  eldorado do BR', 'pessoal', 587.00, 4, '2026-01-29', 'ofx', 'fd7c5b69466fd5d7c969fae8479bd89fe63ed403765317d0fbe7332b492ed063'),
(13, 'IGUASPO*IGUAS  CAMPINAS    BR', 'IGUASPO*IGUAS  CAMPINAS    BR', 'pessoal', 259.98, 2, '2026-03-30', 'ofx', '80e6d53a02117afd7847a41b8ce5bb6d2e9bdb66b5d715b7264678053a23ef1c'),
(14, 'Aquela 2', 'VIVARA TAB     TAUBATE     BR', 'pessoal', 11990.00, 10, '2026-04-30', 'ofx', '0817bce85a3108e717d35a7c1b1379e201f0cf8400fe76fd556dc1d8e81f8dfa'),
(15, 'Roupas Viajem', 'A ESPORTIVA C  SANTO ANDRE BR', 'pessoal', 359.70, 2, '2026-04-30', 'ofx', '8d2971f971121b18265910360bba3d4f96d706fe204bac017bccdd546bf607a8'),
(16, 'PANDORA DO BR  SAO PAULO   BR', 'PANDORA DO BR  SAO PAULO   BR', 'pessoal', 1917.99, 3, '2026-03-01', 'ofx', '661ac936e7ac9b11da8ce9333899dd291d72a9921d785c5913b914c726d29db1'),
(17, 'MP*GGJJK               HORTOLNDIA    BR', 'MP*GGJJK               HORTOLNDIA    BR', 'pessoal', -190.98, 1, '2026-06-08', 'ofx', 'da2b0c9b10c3e33d85f0d8106423ed805ce152caa8b666acc80433f6e5dd0885'),
(18, 'MP*HBOMAXASSIN         OSASCO        BR', 'MP*HBOMAXASSIN         OSASCO        BR', 'pessoal', 31.43, 1, '2026-05-28', 'ofx', 'eaa9bc4def5f6b7ff912b46ecc793f9194aa6af4763a31c116ddef4ebeacfbf7'),
(19, 'BRENO SILVA ROCHA      CAMPOS DO JOR BR', 'BRENO SILVA ROCHA      CAMPOS DO JOR BR', 'pessoal', 259.00, 1, '2026-05-30', 'ofx', '1b23cc47f95e3e4c65eb6cecfa6044e47b69e449e9994df68fd8856befafdbcc'),
(20, 'MP*LOJABIKEWAY         OSASCO        BR', 'MP*LOJABIKEWAY         OSASCO        BR', 'pessoal', 78.00, 1, '2026-06-03', 'ofx', 'b47ec78df1b30b6e8bf7d98e344cae60e2b88535850179e551c67e0c887c3bf6'),
(21, 'AUTO GAS               POUSO ALEGRE  BR', 'AUTO GAS               POUSO ALEGRE  BR', 'pessoal', 420.00, 1, '2026-06-03', 'ofx', '92d13cc5911bfd9d59743e60f3c70a3d492837cf22155631126b3de6704b98de'),
(22, 'MP*SUPRASERVICE        OSASCO        BR', 'MP*SUPRASERVICE        OSASCO        BR', 'pessoal', 42.55, 1, '2026-06-03', 'ofx', '4c2c7f40a1fe17410dd4c5afb98fbe62edef96436f2ce3712eeb568e1b6c779d'),
(23, 'MP*GGJJK               HORTOLNDIA    BR', 'MP*GGJJK               HORTOLNDIA    BR', 'pessoal', 209.98, 1, '2026-06-03', 'ofx', 'f409ec0f994fe82937339fefd6ab53dd8547579cd0adffe6e7ac21d11b2e27a5'),
(24, 'Spotify pam', 'EBN         *SPOTIFY   CURITIBA      BR', 'pessoal', 23.90, 1, '2026-06-03', 'ofx', 'aeec41d68be97743da65cfdeec9e58d6378e5b3bea7a63c639eb2d7dc8b370eb'),
(25, 'MP*MERCADOLIVRE        BIRIGUI       BR', 'MP*MERCADOLIVRE        BIRIGUI       BR', 'pessoal', 212.00, 1, '2026-06-04', 'ofx', 'd753c767fbc844177a72932845fbdf922c7f6a179fb046ee6f26be4d27b81e87'),
(26, 'MP*MELIMAIS            OSASCO        BR', 'MP*MELIMAIS            OSASCO        BR', 'pessoal', 19.90, 1, '2026-06-21', 'ofx', '54de9e6a13bee311ffcb23efbadcdbe1ce243a5d2f0cdc2a2f5754a0f5c4ba12'),
(27, 'MP*MERCADOLIVRE        PAIANDU       BR', 'MP*MERCADOLIVRE        PAIANDU       BR', 'pessoal', 89.90, 1, '2026-06-11', 'ofx', 'd77287820e004cdbbdde5d838519f0631f98a8194bdff20fb1dce244f0442b95'),
(28, 'Microsoft*Store        Sao Paulo     BR', 'Microsoft*Store        Sao Paulo     BR', 'pessoal', 59.99, 1, '2026-05-26', 'ofx', 'f458b16fdd3724f7d81269e58588cf1632a652b29471802041baf30dfc6a6389'),
(29, 'MERCADOLIVRE*  OSASCO      BR', 'MERCADOLIVRE*  OSASCO      BR', 'pessoal', 252.00, 3, '2026-05-26', 'ofx', '58123bdc542de7e8f71e9597bd276beb431f44bae9af05194d78952293717b5b'),
(30, 'FISIA NIKE EC  EXTREMA     BR', 'FISIA NIKE EC  EXTREMA     BR', 'pessoal', 549.98, 3, '2026-05-26', 'ofx', '84dcd63a05b09a2469bce3b25365cbcda0db51a1b762f8c6d3b76c9dadc0a9b2'),
(31, 'Breteles', 'MP*FREEFORCE   OSASCO      BR', 'pessoal', 780.15, 4, '2026-05-31', 'ofx', '32108206f2cb4ed67f6ad42d77c56f25b3142cc9ee4fef762e8989b85ef4daf5'),
(32, 'AMAZON PRIME   SAO PAULO   BR', 'AMAZON PRIME   SAO PAULO   BR', 'conjunta', 166.80, 6, '2026-06-06', 'ofx', '9daf7546a404a1687501f7e5940e79c1dfe6db649373bba5406b227e70dc55cb'),
(33, 'MERCADOLIVRE*  PAIANDU     BR', 'MERCADOLIVRE*  PAIANDU     BR', 'pessoal', 322.43, 4, '2026-06-11', 'ofx', 'bda0c1295b4055394aba3f3ee765606a5ea196426a3935c956f6a324f3af920c'),
(34, 'MP*HBOMAXASSIN         OSASCO        BR', 'MP*HBOMAXASSIN         OSASCO        BR', 'pessoal', 31.43, 1, '2026-06-27', 'ofx', '8d197e34da141ea2d90f230504fcb637de5eb394c251de04cbd55b1d57e7ad02'),
(35, 'MP*VSRMOTOS            JOINVILLE     BR', 'MP*VSRMOTOS            JOINVILLE     BR', 'pessoal', 63.47, 1, '2026-06-28', 'ofx', '745b100702eee8ebe9f3237da9f16672b7c49e6a211faece3288913dec0274ff'),
(36, 'Spotify pam', 'EBN         *SPOTIFY   CURITIBA      BR', 'pessoal', 23.90, 1, '2026-07-03', 'ofx', '7554900bcb83ca23cef94216af19a7afc7f3249571d802fed24710897ef2b49f'),
(37, 'MP*MELIMAIS            OSASCO        BR', 'MP*MELIMAIS            OSASCO        BR', 'pessoal', 19.90, 1, '2026-07-21', 'ofx', '4e8702025a5bc5e33722eb341b6265d5ecfc3f153e33f64464b6c522259181fc'),
(38, 'Microsoft*1 Meses de PCSao Paulo     BR', 'Microsoft*1 Meses de PCSao Paulo     BR', 'pessoal', 59.99, 1, '2026-06-26', 'ofx', 'c4118e67e9f0c444877caff76b36b7e80351b94099d40b1e67e852a3b52df4ca'),
(39, 'PayU        *  Barueri     BR', 'PayU        *  Barueri     BR', 'pessoal', 400.00, 2, '2026-06-29', 'ofx', '60b4fe6caf19cbd42e40b362e5c60bb7e3e8287cb5095c0df0ec2d892c6b2ae0'),
(40, 'DPASCHOAL 196  TAUBATE     BR', 'DPASCHOAL 196  TAUBATE     BR', 'pessoal', 1812.88, 4, '2026-07-14', 'ofx', '2f2c0a1d3288a327972a0a39db943213f11c303f3eb13b9fd751460b06261234'),
(41, 'PetersonPierr  CAMPOS DO JOBR', 'PetersonPierr  CAMPOS DO JOBR', 'pessoal', 987.00, 3, '2026-07-17', 'ofx', 'ed1a0bb1163563da30e10046a0c12c921efad0c2d2f9a90ebf00e1502772c63f');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contas`
--

CREATE TABLE `contas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('unica','mensal') NOT NULL,
  `categoria` enum('pessoal','conjunta') NOT NULL,
  `grupo_recorrencia` varchar(36) DEFAULT NULL,
  `vencimento` date NOT NULL,
  `paga` tinyint(1) NOT NULL DEFAULT 0,
  `valor` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `contas`
--

INSERT INTO `contas` (`id`, `nome`, `tipo`, `categoria`, `grupo_recorrencia`, `vencimento`, `paga`, `valor`, `criado_em`) VALUES
(1, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2026-09-05', 1, 54.00, '2026-09-07 15:48:18'),
(2, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2026-10-05', 0, 54.00, '2026-09-07 15:48:18'),
(3, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2026-11-05', 0, 54.00, '2026-09-07 15:48:18'),
(4, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2026-12-05', 0, 54.00, '2026-09-07 15:48:18'),
(5, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-01-05', 0, 54.00, '2026-09-07 15:48:18'),
(6, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-02-05', 0, 54.00, '2026-09-07 15:48:18'),
(7, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-03-05', 0, 54.00, '2026-09-07 15:48:18'),
(8, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-04-05', 0, 54.00, '2026-09-07 15:48:18'),
(9, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-05-05', 0, 54.00, '2026-09-07 15:48:18'),
(10, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-06-05', 0, 54.00, '2026-09-07 15:48:18'),
(11, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-07-05', 0, 54.00, '2026-09-07 15:48:18'),
(12, 'Celular', 'mensal', 'pessoal', 'celular-2026-09', '2027-08-05', 0, 54.00, '2026-09-07 15:48:18'),
(13, 'IRPF', 'unica', 'pessoal', NULL, '2026-09-30', 0, 555.00, '2026-09-07 15:48:43'),
(14, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2026-09-10', 0, 120.00, '2026-09-07 15:49:25'),
(15, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2026-10-10', 0, 120.00, '2026-09-07 15:49:25'),
(16, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2026-11-10', 0, 120.00, '2026-09-07 15:49:25'),
(17, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2026-12-10', 0, 120.00, '2026-09-07 15:49:25'),
(18, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-01-10', 0, 120.00, '2026-09-07 15:49:25'),
(19, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-02-10', 0, 120.00, '2026-09-07 15:49:25'),
(20, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-03-10', 0, 120.00, '2026-09-07 15:49:25'),
(21, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-04-10', 0, 120.00, '2026-09-07 15:49:25'),
(22, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-05-10', 0, 120.00, '2026-09-07 15:49:25'),
(23, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-06-10', 0, 120.00, '2026-09-07 15:49:25'),
(24, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-07-10', 0, 120.00, '2026-09-07 15:49:25'),
(25, 'Luz', 'mensal', 'conjunta', 'luz-2026-09', '2027-08-10', 0, 120.00, '2026-09-07 15:49:25'),
(26, 'Tio da pam', 'unica', 'conjunta', NULL, '2026-09-03', 1, 3500.00, '2026-09-07 19:30:21'),
(27, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2026-09-05', 1, 4456.00, '2026-09-07 19:31:00'),
(28, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2026-10-05', 0, 4456.00, '2026-09-07 19:31:00'),
(29, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2026-11-05', 0, 4456.00, '2026-09-07 19:31:00'),
(30, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2026-12-05', 0, 4456.00, '2026-09-07 19:31:00'),
(31, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-01-05', 0, 4456.00, '2026-09-07 19:31:00'),
(32, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-02-05', 0, 4456.00, '2026-09-07 19:31:00'),
(33, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-03-05', 0, 4456.00, '2026-09-07 19:31:00'),
(34, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-04-05', 0, 4456.00, '2026-09-07 19:31:00'),
(35, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-05-05', 0, 4456.00, '2026-09-07 19:31:00'),
(36, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-06-05', 0, 4456.00, '2026-09-07 19:31:00'),
(37, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-07-05', 0, 4456.00, '2026-09-07 19:31:00'),
(38, 'Portão', 'mensal', 'conjunta', 'c6f1a3558952ac8465744d64ef1d6ca5', '2027-08-05', 0, 4456.00, '2026-09-07 19:31:00');

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
  `ticker` varchar(10) NOT NULL,
  `datacom` date NOT NULL,
  `datapag` date DEFAULT NULL,
  `valor` decimal(15,8) NOT NULL,
  `tipo` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `div_datacom`
--

INSERT INTO `div_datacom` (`id`, `ticker`, `datacom`, `datapag`, `valor`, `tipo`) VALUES
(1, 'AB', '2026-09-21', NULL, 0.07130000, 'JCP'),
(2, 'VBBR3', '2026-09-21', '2026-10-16', 0.41736422, 'DIV'),
(3, 'BBDC4', '2026-10-01', '2026-11-03', 0.01897481, 'DIV'),
(4, 'PETR4', '2026-07-01', '2026-09-21', 0.35048636, 'JCP'),
(5, 'GOAU4', '2026-08-19', '2026-09-14', 0.11000000, 'DIV'),
(6, 'BBAS3', '2026-09-01', '2026-09-11', 0.10245644, 'JCP');

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

--
-- Despejando dados para a tabela `ofx_importacoes`
--

INSERT INTO `ofx_importacoes` (`id`, `nome_arquivo`, `hash_arquivo`, `periodo_inicio`, `periodo_fim`, `quantidade_transacoes`) VALUES
(1, 'OUROCARD_FACIL_VISA-Jun_26.ofx', '2ee351f691b2dca25cb09ba377c22f49a41bb3cd5409bc7371a9e6e058b0c704', '2026-01-29', '2026-05-22', 17),
(2, 'OUROCARD_FACIL_VISA-Jul_26.ofx', '524aa5d9229eee5ec3e5cf1624ff374901ea58120d48787232afc300955aced6', '2026-03-26', '2026-06-21', 22),
(3, 'OUROCARD_FACIL_VISA-Ago_26.ofx', '4f60ea57756ce3710429b54494ce14373fc152c00bc7b254a8a6b8bca32d38d9', '2026-04-25', '2026-07-21', 16);

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
  `tipo` enum('unica','mensal') NOT NULL DEFAULT 'unica',
  `grupo_recorrencia` varchar(50) DEFAULT NULL,
  `recebido` tinyint(1) NOT NULL DEFAULT 0,
  `porcentagem` decimal(5,2) NOT NULL DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `rendas`
--

INSERT INTO `rendas` (`id`, `nome`, `descricao`, `data`, `valor`, `tipo`, `grupo_recorrencia`, `recebido`, `porcentagem`, `criado_em`) VALUES
(1, 'IFSP', 'salario do if', '2026-09-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 1, 0.00, '2026-09-07 19:43:28'),
(2, 'IFSP', 'salario do if', '2026-10-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(3, 'IFSP', 'salario do if', '2026-11-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(4, 'IFSP', 'salario do if', '2026-12-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(5, 'IFSP', 'salario do if', '2027-01-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(6, 'IFSP', 'salario do if', '2027-02-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(7, 'IFSP', 'salario do if', '2027-03-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(8, 'IFSP', 'salario do if', '2027-04-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(9, 'IFSP', 'salario do if', '2027-05-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(10, 'IFSP', 'salario do if', '2027-06-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(11, 'IFSP', 'salario do if', '2027-07-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(12, 'IFSP', 'salario do if', '2027-08-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
(13, 'IFSP - coordenação', 'função gratificada 2', '2026-09-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 1, 0.00, '2026-09-07 19:44:06'),
(14, 'IFSP - coordenação', 'função gratificada 2', '2026-10-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(15, 'IFSP - coordenação', 'função gratificada 2', '2026-11-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(16, 'IFSP - coordenação', 'função gratificada 2', '2026-12-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(17, 'IFSP - coordenação', 'função gratificada 2', '2027-01-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(18, 'IFSP - coordenação', 'função gratificada 2', '2027-02-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(19, 'IFSP - coordenação', 'função gratificada 2', '2027-03-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(20, 'IFSP - coordenação', 'função gratificada 2', '2027-04-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(21, 'IFSP - coordenação', 'função gratificada 2', '2027-05-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(22, 'IFSP - coordenação', 'função gratificada 2', '2027-06-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(23, 'IFSP - coordenação', 'função gratificada 2', '2027-07-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(24, 'IFSP - coordenação', 'função gratificada 2', '2027-08-01', 616.00, 'mensal', '006e20308a4eafa214e1ec3a18970779', 0, 0.00, '2026-09-07 19:44:06'),
(25, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2026-09-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 1, 0.00, '2026-09-07 19:44:49'),
(26, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2026-10-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(27, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2026-11-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(28, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2026-12-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(29, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-01-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(30, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-02-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(31, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-03-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(32, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-04-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(33, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-05-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(34, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-06-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(35, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-07-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(36, 'Reserva mensal', 'valor referente ao somatorio dos dividendos', '2027-08-01', 903.00, 'mensal', '9877fb19b96df5e4bdf6308e1d3ba42a', 0, 0.00, '2026-09-07 19:44:49'),
(37, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2026-09-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 1, 0.00, '2026-09-07 19:45:39'),
(38, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2026-10-05', 1056.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(39, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2026-11-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(40, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2026-12-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(41, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-01-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(42, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-02-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(43, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-03-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(44, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-04-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(45, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-05-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(46, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-06-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(47, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-07-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39'),
(48, 'FLMA - Escola de restauração', 'monitor em funilaria e eletrica', '2027-08-05', 852.00, 'mensal', '9be4da98f3e7d243fa9de99c9092dd67', 0, 0.00, '2026-09-07 19:45:39');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `acoes_nacionais`
--
ALTER TABLE `acoes_nacionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `cartoes`
--
ALTER TABLE `cartoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de tabela `contas`
--
ALTER TABLE `contas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `ofx_importacoes`
--
ALTER TABLE `ofx_importacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `operacoes`
--
ALTER TABLE `operacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `rendas`
--
ALTER TABLE `rendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

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
