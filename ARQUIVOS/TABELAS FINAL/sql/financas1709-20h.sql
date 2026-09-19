-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/09/2026 às 01:17
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
(82, 41, '2026-10-17', 329.00, 3, 0, NULL),
(83, 42, '2026-09-04', -10391.46, 1, 0, '20260504010391460'),
(84, 43, '2026-09-04', -100.00, 1, 0, '202605040100000'),
(85, 44, '2026-09-04', -500.00, 1, 0, '202605040500000'),
(86, 45, '2026-09-04', 1514.53, 1, 0, '2026050411514530'),
(87, 46, '2026-09-04', 8.92, 1, 0, '2026050418920'),
(88, 47, '2026-09-04', 7.99, 1, 0, '2026050417990'),
(89, 48, '2026-09-04', 80.79, 1, 0, '20260504180790'),
(90, 49, '2026-09-04', 59.00, 1, 0, '20260504159000'),
(91, 50, '2026-09-04', 500.00, 1, 0, '202605041500000'),
(92, 51, '2026-09-05', -180.00, 1, 0, '202605050180000'),
(93, 52, '2026-09-06', -10.06, 1, 0, '20260506010060'),
(94, 53, '2026-09-06', 180.00, 1, 0, '202605061180000'),
(95, 54, '2026-09-06', 2819.00, 1, 0, '2026050612819000'),
(96, 55, '2026-09-06', 155.00, 1, 0, '202605061155000'),
(97, 56, '2026-09-06', 76.35, 1, 0, '20260506176350'),
(98, 57, '2026-09-06', 94.00, 1, 0, '20260506194000'),
(99, 58, '2026-09-06', 77.67, 1, 0, '20260506177670'),
(100, 59, '2026-09-06', 54.00, 1, 0, '20260506154000'),
(101, 60, '2026-09-06', 175.21, 1, 0, '202605061175210'),
(102, 61, '2026-09-07', 73.83, 1, 0, '20260507173830'),
(103, 62, '2026-09-07', 5400.00, 1, 0, '2026050715400000'),
(104, 63, '2026-09-12', -200.00, 1, 0, '202605120200000'),
(105, 64, '2026-09-12', 200.00, 1, 0, '202605121200000'),
(106, 65, '2026-09-13', -1000.00, 1, 0, '2026051301000000'),
(107, 66, '2026-09-13', -465.00, 1, 0, '202605130465000'),
(108, 67, '2026-09-13', -500.00, 1, 0, '202605130500000'),
(109, 68, '2026-09-13', 190.61, 1, 0, '202605131190610'),
(110, 69, '2026-09-13', 124.35, 1, 0, '202605131124350'),
(111, 70, '2026-09-13', 121.30, 1, 0, '202605131121300'),
(112, 71, '2026-09-13', 207.70, 1, 0, '202605131207700'),
(113, 72, '2026-09-13', 207.70, 1, 0, '202605131207701'),
(114, 73, '2026-09-13', 465.00, 1, 0, '202605131465000'),
(115, 74, '2026-09-14', 500.00, 1, 0, '202605141500000'),
(116, 75, '2026-09-18', -165.00, 1, 0, '202605180165000'),
(117, 76, '2026-09-18', -270.00, 1, 0, '202605180270000'),
(118, 77, '2026-09-18', 81.00, 1, 0, '20260518181000'),
(119, 78, '2026-09-18', 50.00, 1, 0, '20260518150000'),
(120, 79, '2026-09-18', 165.00, 1, 0, '202605181165000'),
(121, 80, '2026-09-18', 270.00, 1, 0, '202605181270000'),
(122, 81, '2026-09-19', 13.99, 1, 0, '20260519113990'),
(123, 82, '2026-09-25', -30.00, 1, 0, '20260525030000'),
(124, 83, '2026-09-25', 5.74, 1, 0, '2026052515740'),
(125, 84, '2026-09-25', 21.89, 1, 0, '20260525121890'),
(126, 85, '2026-09-25', 7.00, 1, 0, '2026052517000'),
(127, 86, '2026-09-27', -200.00, 1, 0, '202605270200000'),
(128, 87, '2026-09-27', 200.00, 1, 0, '202605271200000'),
(129, 88, '2026-09-28', -330.00, 1, 0, '202605280330000'),
(130, 89, '2026-09-28', 7.00, 1, 0, '2026052817000'),
(131, 90, '2026-09-28', 330.00, 1, 0, '202605281330000');

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
(41, 'PetersonPierr  CAMPOS DO JOBR', 'PetersonPierr  CAMPOS DO JOBR', 'pessoal', 987.00, 3, '2026-07-17', 'ofx', 'ed1a0bb1163563da30e10046a0c12c921efad0c2d2f9a90ebf00e1502772c63f'),
(42, 'Recebimento de Proventos - 10.882.594/0001-65 INSTITUTO FEDERAL D', 'Recebimento de Proventos - 10.882.594/0001-65 INSTITUTO FEDERAL D', 'pessoal', -10391.46, 1, '2026-05-04', 'ofx', '0fe28c2d3f77326e9055c2373aee53e21a0c1e8c9dacff3b309463ca5b1e45ef'),
(43, 'Pix - Recebido - 02/05 14:24 00027888980880 FABIO VENTU', 'Pix - Recebido - 02/05 14:24 00027888980880 FABIO VENTU', 'pessoal', -100.00, 1, '2026-05-04', 'ofx', '63675a48fa942213b4edb52f38d221f5b03ae62edf5031aa2f1681cb8448ee1b'),
(44, 'Pix - Recebido - 04/05 17:06 41732199892 Pércila Cristi', 'Pix - Recebido - 04/05 17:06 41732199892 Pércila Cristi', 'pessoal', -500.00, 1, '2026-05-04', 'ofx', '3b612e7fa3d5ce21c1cea5be93097f245c6444e26598698d09efccfff5f7d219'),
(45, 'Pagto cartão crédito', 'Pagto cartão crédito', 'pessoal', 1514.53, 1, '2026-05-04', 'ofx', 'fb2f54f000abe084edd5e471fcef88c2a50b5ef5840d4659a1dc40457e2c174c'),
(46, 'Pix - Enviado - 02/05 06:58 REDE DE POSTOS SETE ESTRE', 'Pix - Enviado - 02/05 06:58 REDE DE POSTOS SETE ESTRE', 'pessoal', 8.92, 1, '2026-05-04', 'ofx', '5974d79430f890686474fbcad5dd6ced4a21e2d3cb5f9780b7068feb59ecfbd3'),
(47, 'Pix - Enviado - 02/05 17:11 MERCADO BACANA', 'Pix - Enviado - 02/05 17:11 MERCADO BACANA', 'pessoal', 7.99, 1, '2026-05-04', 'ofx', 'c7e9906b1565b9a4f36bc437a4d0d0b85b9460429e39a44fb5531f8e14cb0b3c'),
(48, 'Pix - Enviado - 02/05 18:31 FARMA CONDE S/A', 'Pix - Enviado - 02/05 18:31 FARMA CONDE S/A', 'pessoal', 80.79, 1, '2026-05-04', 'ofx', 'da58bb6b3eeaeb4b4ce9fecadb600a04b1f493f98c0cd1f6267579c48c360554'),
(49, 'Pix - Enviado - 03/05 16:05 CPQ BRASIL S/A - EM RECUP', 'Pix - Enviado - 03/05 16:05 CPQ BRASIL S/A - EM RECUP', 'pessoal', 59.00, 1, '2026-05-04', 'ofx', '4358e6ed68785b09b1d4057fc292c4402d6bf01a9a0605d4110cbe6fe9ea78f2'),
(50, 'Pix - Enviado - 04/05 19:25 Adenilson Fernandes Ferre', 'Pix - Enviado - 04/05 19:25 Adenilson Fernandes Ferre', 'pessoal', 500.00, 1, '2026-05-04', 'ofx', '2a18ae87dab0524b7abb58b0991d9d81189c8e942c5bcc23dda7a56f077e5c5b'),
(51, 'Pix - Recebido - 05/05 13:47 04047664804 HOMERO GODLIAU', 'Pix - Recebido - 05/05 13:47 04047664804 HOMERO GODLIAU', 'pessoal', -180.00, 1, '2026-05-05', 'ofx', 'c37dffb38c2277bcfdf0c9b01e6c07c471f8b43f62fdb4ff11fb88b31e8e09ce'),
(52, 'Devolução NF Paulista - 46.377.222/0001-29 SECRETARIA DA FAZEN', 'Devolução NF Paulista - 46.377.222/0001-29 SECRETARIA DA FAZEN', 'pessoal', -10.06, 1, '2026-05-06', 'ofx', 'ae475c88b419392f2364717b06842fb5315542e8f8f98eec4288b33b78096c58'),
(53, 'Pix - Enviado - 06/05 18:39 Adenilson Fernandes Ferre', 'Pix - Enviado - 06/05 18:39 Adenilson Fernandes Ferre', 'pessoal', 180.00, 1, '2026-05-06', 'ofx', 'a9c56de85c968cf559f713aff086aca176fef5919bcb18099eca6d5bacc7b741'),
(54, 'Pix - Enviado - 06/05 18:44 AVENUE INVESTMENT BANK AV', 'Pix - Enviado - 06/05 18:44 AVENUE INVESTMENT BANK AV', 'pessoal', 2819.00, 1, '2026-05-06', 'ofx', 'f09f8dd430df79a7604611735f9dd636f86af36fc56764b2014ed6d76c96295d'),
(55, 'Pix - Enviado - 06/05 18:46 EDSON CAETANO DA SILVA JU', 'Pix - Enviado - 06/05 18:46 EDSON CAETANO DA SILVA JU', 'pessoal', 155.00, 1, '2026-05-06', 'ofx', '6e341b419ff0d264fd2f74fa0766209fabf828e1a1048b9568ea3d609e7c8c67'),
(56, 'Pix - Enviado - 06/05 18:49 VIVAS TELECOMUNICACOES LT', 'Pix - Enviado - 06/05 18:49 VIVAS TELECOMUNICACOES LT', 'pessoal', 76.35, 1, '2026-05-06', 'ofx', '9df19cd774db4eb5d929d8c9996a71a01ea665307625ab1878b2706c3a71c83d'),
(57, 'Pix - Enviado - 06/05 18:51 VIVAS TELECOMUNICACOES LT', 'Pix - Enviado - 06/05 18:51 VIVAS TELECOMUNICACOES LT', 'pessoal', 94.00, 1, '2026-05-06', 'ofx', '2e09e5d5aec14381b9cf9e60680ab4161196c5aa298c40e9b6c08fdf650e1a94'),
(58, 'Pix - Enviado - 06/05 18:54 CLARO', 'Pix - Enviado - 06/05 18:54 CLARO', 'pessoal', 77.67, 1, '2026-05-06', 'ofx', '83a1fceafe1ae78a6a9a610e24b431239971e12d9545d3205936ebab9f72c996'),
(59, 'Pix - Enviado - 06/05 19:00 TELEFONICA BRAS', 'Pix - Enviado - 06/05 19:00 TELEFONICA BRAS', 'pessoal', 54.00, 1, '2026-05-06', 'ofx', 'a1db9bbc632229e40f077f3266aff61ae05df72894125cbcb438db28f931c7dd'),
(60, 'Pix - Enviado - 06/05 19:09 PJBANK', 'Pix - Enviado - 06/05 19:09 PJBANK', 'pessoal', 175.21, 1, '2026-05-06', 'ofx', '203a4e380ad66457c5f1f66fe43ed8165c0ac6e7e4e1c6b59898646d0bd5ed21'),
(61, 'Pix - Enviado - 07/05 19:17 Adenilson Fernandes Ferre', 'Pix - Enviado - 07/05 19:17 Adenilson Fernandes Ferre', 'pessoal', 73.83, 1, '2026-05-07', 'ofx', '1f73877b91d08865f46a51a2eb56dea9b6054e90ca069c833119468f6dc5b6a8'),
(62, 'Pix - Enviado - 07/05 19:21 Adenilson Fernandes Ferre', 'Pix - Enviado - 07/05 19:21 Adenilson Fernandes Ferre', 'pessoal', 5400.00, 1, '2026-05-07', 'ofx', 'a267f89f79b5608f2d588497ffe5c7d1db07bb27b5af6897bb1784958098a2cb'),
(63, 'Pix - Recebido - 12/05 10:16 00037627462819 ANA PAULA A', 'Pix - Recebido - 12/05 10:16 00037627462819 ANA PAULA A', 'pessoal', -200.00, 1, '2026-05-12', 'ofx', 'c8254c281d77d99fc6e3aa129bd67636ed5282eaeeda2ec2fa687b2f84200262'),
(64, 'Pix - Enviado - 12/05 10:20 AVENUE INVESTMENT BANK AV', 'Pix - Enviado - 12/05 10:20 AVENUE INVESTMENT BANK AV', 'pessoal', 200.00, 1, '2026-05-12', 'ofx', '239d76034b8924ea68acca88c6e2639cf8f7bf6041ee5f5a62d70bdc3ae35017'),
(65, 'Pix - Recebido - 13/05 20:24 31175576875 Adenilson Fern', 'Pix - Recebido - 13/05 20:24 31175576875 Adenilson Fern', 'pessoal', -1000.00, 1, '2026-05-13', 'ofx', '9dcf6bf431a1d4ba4288c8d26bbef7e1ddfc25d06021cf54404894dffaa9cbe8'),
(66, 'Pix - Recebido - 13/05 20:43 31175576875 Adenilson Fern', 'Pix - Recebido - 13/05 20:43 31175576875 Adenilson Fern', 'pessoal', -465.00, 1, '2026-05-13', 'ofx', 'dab7e0971b1de6b9f8761156572d3443c27360b07804292b22250aafc0fa9b48'),
(67, 'Pix - Recebido - 13/05 20:59 31175576875 Adenilson Fern', 'Pix - Recebido - 13/05 20:59 31175576875 Adenilson Fern', 'pessoal', -500.00, 1, '2026-05-13', 'ofx', 'b53b0c8d9f602b50c6a346539c24da406cd5c75de3d73796131cffabbe9c0f89'),
(68, 'Pix - Enviado - 13/05 20:33 SKY BANDA LARGA', 'Pix - Enviado - 13/05 20:33 SKY BANDA LARGA', 'pessoal', 190.61, 1, '2026-05-13', 'ofx', '445752b151f7208967fa5e83719ad58911c235058bdf1a58d7986d155c1f10e8'),
(69, 'Pix - Enviado - 13/05 20:35 SABESP', 'Pix - Enviado - 13/05 20:35 SABESP', 'pessoal', 124.35, 1, '2026-05-13', 'ofx', 'cb6e9af53f31256db8dafecdf12ebaa7199cae862b2460510458a7f80f3295e4'),
(70, 'Pix - Enviado - 13/05 20:36 NEOENERGIA ELEKTRO', 'Pix - Enviado - 13/05 20:36 NEOENERGIA ELEKTRO', 'pessoal', 121.30, 1, '2026-05-13', 'ofx', 'db2b9747d0ee47410365e5e03f819168980bbe1affe4aa9836c103c6773f79b8'),
(71, 'Pagamento de Impostos - PREFEITURA C.JORDAO-IPTU', 'Pagamento de Impostos - PREFEITURA C.JORDAO-IPTU', 'pessoal', 207.70, 1, '2026-05-13', 'ofx', '20d10a3e2bb318044aa632d4dba658384294dfb3e1081db04cf88d04446d5fdf'),
(72, 'Pagamento de Impostos - PREFEITURA C.JORDAO-IPTU', 'Pagamento de Impostos - PREFEITURA C.JORDAO-IPTU', 'pessoal', 207.70, 1, '2026-05-13', 'ofx', '0609ef715ce193d1169a61a430771e105e102b81106fdac89336e7e18e09aded'),
(73, 'Pix - Enviado - 13/05 20:45 AVENUE INVESTMENT BANK AV', 'Pix - Enviado - 13/05 20:45 AVENUE INVESTMENT BANK AV', 'pessoal', 465.00, 1, '2026-05-13', 'ofx', '6e6cbe8fe84787193266d872f40f223f83419b7f1a3a350b5eb833da3e23621e'),
(74, 'Saque dinheiro ATM cartao - 14/05 09:50 SAA-CAMPOS DO JORDAO', 'Saque dinheiro ATM cartao - 14/05 09:50 SAA-CAMPOS DO JORDAO', 'pessoal', 500.00, 1, '2026-05-14', 'ofx', '8b0b9cfaf7c0a9902178dfd5301fe3bbdd2f9619cc442cc10c250cb563b1bf3a'),
(75, 'Pix - Recebido - 17/05 17:40 40900551801 PAMELA ALVES D', 'Pix - Recebido - 17/05 17:40 40900551801 PAMELA ALVES D', 'pessoal', -165.00, 1, '2026-05-18', 'ofx', 'dd2dd92586cffbe7b649a0ec77c10456444cc981658c7cc61b120999f1f50cd1'),
(76, 'Pix - Recebido - 18/05 17:06 41732199892 Pércila Cristi', 'Pix - Recebido - 18/05 17:06 41732199892 Pércila Cristi', 'pessoal', -270.00, 1, '2026-05-18', 'ofx', 'b18d66a87a3540c576c93f11c363deb56375e5b1aa1d7b5e0c21106dfee66621'),
(77, 'Pix - Enviado - 16/05 19:02 PRO SPORT CAMPINAS', 'Pix - Enviado - 16/05 19:02 PRO SPORT CAMPINAS', 'pessoal', 81.00, 1, '2026-05-18', 'ofx', '1bd25bca7ca8f363f19c6793391b08ec12c9da472831544f47448adc6bfddc4e'),
(78, 'Pix - Enviado - 16/05 19:04 CREPERIA SANTA CATARINA L', 'Pix - Enviado - 16/05 19:04 CREPERIA SANTA CATARINA L', 'pessoal', 50.00, 1, '2026-05-18', 'ofx', '39ccd2a651bb4617ed4d63ddda90100c5693563a4c2d3ad48b2a2dbf36df851f'),
(79, 'Pix - Enviado - 17/05 17:52 61.650.315 SANDRO JOSE PE', 'Pix - Enviado - 17/05 17:52 61.650.315 SANDRO JOSE PE', 'pessoal', 165.00, 1, '2026-05-18', 'ofx', '3f8b368d8cae276116ecc0ae340141d1a6978c11ca1e99a398ee42c211b35c98'),
(80, 'Pix - Enviado - 18/05 21:47 Adenilson Fernandes Ferre', 'Pix - Enviado - 18/05 21:47 Adenilson Fernandes Ferre', 'pessoal', 270.00, 1, '2026-05-18', 'ofx', '62fc6d288668245dc386f79193ecaf34461b630cd1b6e11beecbf5710970bc20'),
(81, 'Pix - Enviado - 19/05 17:09 FARMA CONDE S/A', 'Pix - Enviado - 19/05 17:09 FARMA CONDE S/A', 'pessoal', 13.99, 1, '2026-05-19', 'ofx', 'b0040cc7ae9389840275e62e5a2e942ddfed91737d883ad32943b3c4cadee205'),
(82, 'Dep dinheiro ATM - 23/05 19:27 SAA-CAMPOS DO JORDAO', 'Dep dinheiro ATM - 23/05 19:27 SAA-CAMPOS DO JORDAO', 'pessoal', -30.00, 1, '2026-05-25', 'ofx', 'e1f52ca032a8b19551d30bb833e502bc8a1a486e506f009215cfe860a4d50795'),
(83, 'Pix - Enviado - 23/05 07:51 PADARIA E MERCADINHO JARD', 'Pix - Enviado - 23/05 07:51 PADARIA E MERCADINHO JARD', 'pessoal', 5.74, 1, '2026-05-25', 'ofx', '3b1fb9a6cb9b9b01f35a0568a8e0ea350ee1156c5d63866bbe6127c4a0ebb846'),
(84, 'Pix - Enviado - 24/05 17:14 Adenilson Fernandes Ferre', 'Pix - Enviado - 24/05 17:14 Adenilson Fernandes Ferre', 'pessoal', 21.89, 1, '2026-05-25', 'ofx', 'c0f00780c94229ffb126da864226faa326916c63feb6143d371099dab69790b4'),
(85, 'Pix - Enviado - 25/05 11:12 DN COMERCIO E SERVICOS DE', 'Pix - Enviado - 25/05 11:12 DN COMERCIO E SERVICOS DE', 'pessoal', 7.00, 1, '2026-05-25', 'ofx', '143445d99a8e06de7eb4418769d871df696a1f8b8cc5e60145ee2b5b883d7502'),
(86, 'Pix - Recebido - 27/05 16:42 21321570813 MAGDA MARIA CA', 'Pix - Recebido - 27/05 16:42 21321570813 MAGDA MARIA CA', 'pessoal', -200.00, 1, '2026-05-27', 'ofx', 'b7c67d35d2c165ded0afc0757bf07e30233c2feffbd838c8c15f80e30ddfb630'),
(87, 'Pix - Enviado - 27/05 19:52 Adenilson Fernandes Ferre', 'Pix - Enviado - 27/05 19:52 Adenilson Fernandes Ferre', 'pessoal', 200.00, 1, '2026-05-27', 'ofx', '7e4132ab5c94921eb88063799b6420abbbcad143740b9aadbd260b52a98934dd'),
(88, 'Pix - Recebido - 28/05 17:15 15963746833 LUIS ANTONIO P', 'Pix - Recebido - 28/05 17:15 15963746833 LUIS ANTONIO P', 'pessoal', -330.00, 1, '2026-05-28', 'ofx', '07d3feea9cd924b132077d529977048b21641dab1720d9adf17e6f18104f10a8'),
(89, 'Pix - Enviado - 28/05 10:15 DN COMERCIO E SERVICOS DE', 'Pix - Enviado - 28/05 10:15 DN COMERCIO E SERVICOS DE', 'pessoal', 7.00, 1, '2026-05-28', 'ofx', 'ece6f44036d8f37a23f09277e059fb89277c11cf43271bf102d2d1093f0b75f7'),
(90, 'Pix - Enviado - 28/05 19:35 Adenilson Fernandes Ferre', 'Pix - Enviado - 28/05 19:35 Adenilson Fernandes Ferre', 'pessoal', 330.00, 1, '2026-05-28', 'ofx', 'b1ce070767d5ca2077aae2a3baea2c77b54466adf9550a118c21b09898cb5e0d');

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
-- Despejando dados para a tabela `contas_financeiras`
--

INSERT INTO `contas_financeiras` (`id`, `usuario_id`, `nome`, `instituicao`, `icone`, `tipo`, `saldo_inicial`, `data_saldo_inicial`, `ativa`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 'Banco do Brasil', 'Banco do Brasil', 'banco-do-brasil.png', 'corrente', 1500.00, '2026-09-01', 1, '2026-09-15 17:34:51', '2026-09-15 19:14:42'),
(2, 1, 'Bradesco', 'Bradesco', 'bradesco.png', 'poupanca', 328.00, '2026-07-31', 1, '2026-09-15 17:35:19', '2026-09-15 19:15:31'),
(3, 1, 'Mercado Pago', 'Mercado Pago', 'mercado-pago.png', 'corrente', 8714.30, '2026-09-01', 1, '2026-09-15 17:39:39', '2026-09-15 19:15:45'),
(4, 1, 'Avenue', NULL, NULL, 'corretora', 20100.00, '2026-09-01', 1, '2026-09-15 17:40:50', '2026-09-15 17:40:50'),
(5, 1, 'XP investimentos', 'XP', 'xp.png', 'corretora', 300.00, '2026-09-01', 1, '2026-09-15 17:41:30', '2026-09-15 19:15:53');

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
  `usuario_id` int(11) NOT NULL,
  `ticker` varchar(10) NOT NULL,
  `tipo_ativo` enum('acao','fii','etf','bdr') NOT NULL,
  `datacom` date NOT NULL,
  `datapag` date DEFAULT NULL,
  `valor` decimal(15,8) NOT NULL,
  `tipo` enum('DIV','JCP','REND') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `div_datacom`
--

INSERT INTO `div_datacom` (`id`, `usuario_id`, `ticker`, `tipo_ativo`, `datacom`, `datapag`, `valor`, `tipo`) VALUES
(2, 1, 'VBBR3', 'acao', '2026-09-21', '2026-10-16', 0.41736422, 'DIV'),
(3, 1, 'BBDC4', 'acao', '2026-10-01', '2026-11-03', 0.01897481, 'DIV'),
(4, 1, 'PETR4', 'acao', '2026-07-01', '2026-09-21', 0.35048636, 'JCP'),
(5, 1, 'GOAU4', 'acao', '2026-08-19', '2026-09-14', 0.11000000, 'DIV'),
(6, 1, 'BBAS3', 'acao', '2026-09-01', '2026-09-11', 0.10245644, 'JCP'),
(7, 1, 'PETR4', 'acao', '2026-09-13', '2026-09-24', 1.00000000, 'DIV'),
(8, 1, 'VALE3', 'acao', '2026-09-03', NULL, 2.56000000, 'DIV'),
(9, 1, 'XPLG', 'fii', '2026-08-30', '2026-09-05', 1.52000000, 'DIV'),
(10, 1, 'TIMS3', 'acao', '2026-09-14', '2026-10-14', 0.25300000, 'DIV'),
(11, 1, 'HSLG11', 'fii', '2026-09-30', '2026-10-05', 1.23000000, 'REND'),
(12, 1, 'BOVA11', 'etf', '2026-09-17', '2026-10-22', 0.53200000, 'REND'),
(13, 1, 'AAPL34', 'bdr', '2026-07-08', '2026-10-07', 2.40000000, 'DIV'),
(14, 1, 'HGLG', 'fii', '2026-09-30', '2026-09-04', 2.57000000, 'REND');

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

--
-- Despejando dados para a tabela `investimentos_internacionais`
--

INSERT INTO `investimentos_internacionais` (`id`, `usuario_id`, `ticker`, `tipo_ativo`, `quantidade`, `valor_unitario`, `valor_investido`, `data`, `valor_mercado`, `logo`, `criado_em`, `tipo_operacao`) VALUES
(1, 1, 'AAPL', 'stock', 2.00000000, 100.0000, 200.00000000, '2026-05-14', 332.2700, 'https://icons.brapi.dev/icons/AAPL.svg', '2026-09-13 19:49:43', 'compra'),
(3, 1, 'AAPL', 'stock', 1.00000000, 130.0000, 130.00000000, '2026-08-13', 332.2700, 'https://icons.brapi.dev/icons/AAPL.svg', '2026-09-13 19:53:43', 'compra'),
(4, 1, 'AAPL', 'stock', 0.50000000, 200.0000, 100.00000000, '2026-09-13', 332.2700, 'https://icons.brapi.dev/icons/AAPL.svg', '2026-09-13 19:56:16', 'compra'),
(5, 1, 'AAPL', 'stock', -1.00000000, 300.0000, -300.00000000, '2026-09-13', 332.2700, 'https://icons.brapi.dev/icons/AAPL.svg', '2026-09-13 19:59:12', 'venda'),
(6, 1, 'VOO', 'etf', 4.00000000, 600.0000, 2400.00000000, '2026-04-09', 702.5600, 'https://icons.brapi.dev/icons/VOO.svg', '2026-09-13 20:00:36', 'compra'),
(7, 1, 'O', 'reit', 30.00000000, 40.0000, 1200.00000000, '2026-05-21', 59.5000, 'https://icons.brapi.dev/icons/O.svg', '2026-09-13 20:01:31', 'compra'),
(8, 1, 'BABA', 'adr', 30.00000000, 100.0000, 3000.00000000, '2026-06-25', 109.3000, 'https://icons.brapi.dev/icons/BABA.svg', '2026-09-13 20:02:15', 'compra');

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
-- Despejando dados para a tabela `investimentos_nacionais`
--

INSERT INTO `investimentos_nacionais` (`id`, `usuario_id`, `ticker`, `tipo_ativo`, `quantidade`, `valor_unitario`, `data`, `valor_mercado`, `logo`, `criado_em`, `tipo_operacao`) VALUES
(1, 1, 'PETR4', 'acao', 10, 30.00, '2026-09-13', 49.00, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-13 19:37:03', 'compra'),
(2, 1, 'PETR4', 'acao', 10, 40.00, '2026-09-10', 49.00, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-13 19:38:55', 'compra'),
(3, 1, 'PETR4', 'acao', -5, 50.00, '2026-09-13', 49.00, 'https://icons.brapi.dev/icons/PETR4.svg', '2026-09-13 19:40:07', 'venda'),
(4, 1, 'HGLG', 'fii', 10, 150.00, '2026-09-13', NULL, NULL, '2026-09-13 19:42:30', 'compra'),
(5, 1, 'WRLD11', 'etf', 3, 140.00, '2026-05-14', 148.39, 'https://icons.brapi.dev/icons/WRLD11.svg', '2026-09-13 19:44:43', 'compra'),
(6, 1, 'AAPL34', 'bdr', 20, 80.00, '2026-04-16', 85.00, 'https://icons.brapi.dev/icons/AAPL34.svg', '2026-09-13 19:45:30', 'compra'),
(7, 1, 'HGLG', 'fii', -10, 150.00, '2026-09-14', NULL, NULL, '2026-09-15 23:11:41', 'venda');

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
  `quantidade_transacoes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ofx_importacoes`
--

INSERT INTO `ofx_importacoes` (`id`, `nome_arquivo`, `hash_arquivo`, `periodo_inicio`, `periodo_fim`, `quantidade_transacoes`) VALUES
(1, 'OUROCARD_FACIL_VISA-Jun_26.ofx', '2ee351f691b2dca25cb09ba377c22f49a41bb3cd5409bc7371a9e6e058b0c704', '2026-01-29', '2026-05-22', 17),
(2, 'OUROCARD_FACIL_VISA-Jul_26.ofx', '524aa5d9229eee5ec3e5cf1624ff374901ea58120d48787232afc300955aced6', '2026-03-26', '2026-06-21', 22),
(3, 'OUROCARD_FACIL_VISA-Ago_26.ofx', '4f60ea57756ce3710429b54494ce14373fc152c00bc7b254a8a6b8bca32d38d9', '2026-04-25', '2026-07-21', 16),
(4, 'extrato_maio.ofx', '1eac96507ee47ebe9fee74c2b11d9627eae7dad82d70407e5e101048d40261b8', '2026-05-04', '2026-05-28', 49);

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
(9, 1, '2026-09-03', 'BB.VA', 7000, 47.75, 47.86, 334257.00, 335048.00, 669305.00, 791.00, 153.93, 6.37, 123.24, 637.07, 121.04, 509.66, '2026-09-07 14:20:58'),
(10, 1, '2026-09-14', 'PETR4', 1000, 46.00, 48.50, 46000.00, 48600.00, 94600.00, 2600.00, 21.72, 25.78, 515.66, 2578.28, 489.88, 2088.40, '2026-09-14 23:14:58');

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

--
-- Despejando dados para a tabela `recuperacao_senha`
--

INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `data_expiracao`, `utilizado`, `criado_em`) VALUES
(1, 2, 'cd30f43edfaab7451ba5e9119e1d61830870d096e556793c94e77ff662ad76aa', '2026-09-11 16:42:38', 1, '2026-09-11 13:42:38'),
(2, 2, '1f50a43563957ce9ef6de542c6a86148b57abfabfe8d77b6e92825d342c20997', '2026-09-11 11:49:42', 0, '2026-09-11 13:49:42'),
(3, 1, '4cd0de759db6d9cabb9dec9ea723659fdee6c3a9285deb3a7f57111d518fb718', '2026-09-13 11:31:30', 1, '2026-09-13 13:31:30');

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
(1, 'IFSP', 'salario do if', '2026-09-01', 6956.00, 'mensal', '3712077baa418eff3c25b7707a67b205', 0, 0.00, '2026-09-07 19:43:28'),
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
(1, 'adenilson.ff@outlook.com', '$2y$10$gJG75NYicED.mv1ZmjcrI.gjUe7NRNsoQAd.eN15aUwLDs658QF5y', 'ativo', '2026-12-31', '2026-04-27 22:37:04'),
(2, 'pamela.amleal@outlook.com', '$2y$10$.E.m.56qNZQW43R760YFgOeaIIKeTBzIiahVDULdC9ux03GTlAXFW', 'ativo', '2026-10-11', '2026-09-11 13:22:15');

--
-- Índices para tabelas despejadas
--

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
-- Índices de tabela `contas_financeiras`
--
ALTER TABLE `contas_financeiras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cf_usuario` (`usuario_id`),
  ADD KEY `idx_cf_usuario_ativa` (`usuario_id`,`ativa`);

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
  ADD KEY `idx_mf_origem` (`usuario_id`,`origem_modulo`,`origem_id`);

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
-- Índices de tabela `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `rendas`
--
ALTER TABLE `rendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Restrições para tabelas `contas_financeiras`
--
ALTER TABLE `contas_financeiras`
  ADD CONSTRAINT `fk_cf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `corretora_taxas`
--
ALTER TABLE `corretora_taxas`
  ADD CONSTRAINT `corretora_taxas_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_mf_conta` FOREIGN KEY (`conta_id`) REFERENCES `contas_financeiras` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mf_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `operacoes`
--
ALTER TABLE `operacoes`
  ADD CONSTRAINT `operacoes_ibfk_1` FOREIGN KEY (`corretora_id`) REFERENCES `corretoras` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `recuperacao_senha`
--
ALTER TABLE `recuperacao_senha`
  ADD CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
