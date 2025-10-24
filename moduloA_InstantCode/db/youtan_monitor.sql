-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 24-Out-2025 às 20:58
-- Versão do servidor: 10.4.27-MariaDB
-- versão do PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `youtan_monitor`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alertas`
--

CREATE TABLE `alertas` (
  `id` int(11) NOT NULL,
  `tipo` enum('manutencao','vencimento','critico') NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `id_ativo` int(11) DEFAULT NULL,
  `id_manutencao` int(11) DEFAULT NULL,
  `severidade` enum('baixa','media','alta','critica') DEFAULT 'media',
  `status` enum('pendente','visto','resolvido') DEFAULT 'pendente',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_resolucao` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `ativos`
--

CREATE TABLE `ativos` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `valor` decimal(15,2) DEFAULT NULL,
  `data_aquisicao` date DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `fabricante` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `status` enum('operacional','manutencao','descartado') NOT NULL DEFAULT 'operacional',
  `localizacao` varchar(255) NOT NULL,
  `id_usuario_responsavel` int(11) DEFAULT NULL,
  `data_garantia` date DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `ativos`
--

INSERT INTO `ativos` (`id`, `nome`, `id_categoria`, `valor`, `data_aquisicao`, `numero_serie`, `fabricante`, `modelo`, `status`, `localizacao`, `id_usuario_responsavel`, `data_garantia`, `observacoes`, `data_criacao`) VALUES
(2, 'APP', 2, '210.00', '2025-10-09', '1', 'You', '57', 'manutencao', 'Caçapava', 3, '2025-11-07', 'Pra hoje.', '2025-10-24 17:51:09'),
(3, 'Jump', 5, '500.00', '2025-10-09', '2', 'You', '56', 'manutencao', 'Caçapava', 7, '2025-10-31', 'jogue', '2025-10-24 18:03:50'),
(4, 'jogo', 3, '300.00', '2025-10-07', '3', 'You', '578', 'descartado', 'Caçapava', 7, '2025-11-08', 'Já foi', '2025-10-24 18:09:14');

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias_ativos`
--

CREATE TABLE `categorias_ativos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `categorias_ativos`
--

INSERT INTO `categorias_ativos` (`id`, `nome`, `descricao`, `data_criacao`) VALUES
(1, 'Hardware', 'Equipamentos de informática e periféricos', '2025-10-24 12:51:43'),
(2, 'Software', 'Licenças e aplicativos', '2025-10-24 12:51:43'),
(3, 'Móveis', 'Mobiliário corporativo', '2025-10-24 12:51:43'),
(4, 'Veículos', 'Frota corporativa', '2025-10-24 12:51:43'),
(5, 'Equipamentos', 'Maquinários e equipamentos especiais', '2025-10-24 12:51:43');

-- --------------------------------------------------------

--
-- Estrutura da tabela `manutencoes`
--

CREATE TABLE `manutencoes` (
  `id` int(11) NOT NULL,
  `id_ativo` int(11) NOT NULL,
  `tipo` enum('preventiva','corretiva','preditiva') NOT NULL,
  `data_manutencao` date NOT NULL,
  `data_agendamento` date DEFAULT NULL,
  `custo` decimal(15,2) DEFAULT NULL,
  `tecnico_responsavel` varchar(100) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('agendada','em_andamento','concluida','cancelada') DEFAULT 'agendada',
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `manutencoes`
--

INSERT INTO `manutencoes` (`id`, `id_ativo`, `tipo`, `data_manutencao`, `data_agendamento`, `custo`, `tecnico_responsavel`, `descricao`, `status`, `data_criacao`) VALUES
(1, 2, 'corretiva', '2025-10-30', '2025-10-28', '20.00', 'José', 'Carlos', 'agendada', '2025-10-24 18:45:25'),
(4, 2, 'corretiva', '2025-10-30', '2025-10-28', '20.00', 'José', 'Carlos', 'agendada', '2025-10-24 18:46:42'),
(5, 2, 'corretiva', '2025-10-30', '2025-10-28', '20.00', 'José', 'Carlos', 'agendada', '2025-10-24 18:49:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `transferencias`
--

CREATE TABLE `transferencias` (
  `id` int(11) NOT NULL,
  `id_ativo` int(11) NOT NULL,
  `id_usuario_origem` int(11) DEFAULT NULL,
  `id_usuario_destino` int(11) NOT NULL,
  `data_transferencia` timestamp NOT NULL DEFAULT current_timestamp(),
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_permissao` enum('admin','colaborador') NOT NULL DEFAULT 'colaborador',
  `setor` varchar(100) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_permissao`, `setor`, `data_criacao`, `ativo`) VALUES
(1, 'Administrador', 'admin@youtan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'TI', '2025-10-24 12:51:43', 1),
(2, 'João Silva', 'joao@youtan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'colaborador', 'Vendas', '2025-10-24 12:51:43', 1),
(3, 'Samuel', 'samuel@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$dC5paVlLTXBWR2pGZEtOeA$Agg/spcF8VPvBa8aAepdND0YH6fyg4F3hW3vWYXcxgA', 'admin', NULL, '2025-10-24 12:58:53', 1),
(7, 'Nicolle', 'nicolle@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SENDWDJNalpBbnlnR3RKbA$r5iicrVhSa2p+ffZ8HqrHoybZlVhkqu0F06nDDXWzZM', 'colaborador', NULL, '2025-10-24 16:44:39', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ativo` (`id_ativo`),
  ADD KEY `id_manutencao` (`id_manutencao`);

--
-- Índices para tabela `ativos`
--
ALTER TABLE `ativos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_serie` (`numero_serie`),
  ADD KEY `id_categoria` (`id_categoria`),
  ADD KEY `id_usuario_responsavel` (`id_usuario_responsavel`);

--
-- Índices para tabela `categorias_ativos`
--
ALTER TABLE `categorias_ativos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ativo` (`id_ativo`);

--
-- Índices para tabela `transferencias`
--
ALTER TABLE `transferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ativo` (`id_ativo`),
  ADD KEY `id_usuario_origem` (`id_usuario_origem`),
  ADD KEY `id_usuario_destino` (`id_usuario_destino`);

--
-- Índices para tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ativos`
--
ALTER TABLE `ativos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `categorias_ativos`
--
ALTER TABLE `categorias_ativos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `transferencias`
--
ALTER TABLE `transferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`id_ativo`) REFERENCES `ativos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertas_ibfk_2` FOREIGN KEY (`id_manutencao`) REFERENCES `manutencoes` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `ativos`
--
ALTER TABLE `ativos`
  ADD CONSTRAINT `ativos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_ativos` (`id`),
  ADD CONSTRAINT `ativos_ibfk_2` FOREIGN KEY (`id_usuario_responsavel`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD CONSTRAINT `manutencoes_ibfk_1` FOREIGN KEY (`id_ativo`) REFERENCES `ativos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `transferencias`
--
ALTER TABLE `transferencias`
  ADD CONSTRAINT `transferencias_ibfk_1` FOREIGN KEY (`id_ativo`) REFERENCES `ativos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transferencias_ibfk_2` FOREIGN KEY (`id_usuario_origem`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transferencias_ibfk_3` FOREIGN KEY (`id_usuario_destino`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
