-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 22/03/2025 às 18:39
-- Versão do servidor: 8.4.3
-- Versão do PHP: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `badoo_clone`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `admin_id`, `action`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, NULL, 'login', 'Login realizado com sucesso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 17:14:57'),
(2, 1, NULL, 'profile_update', 'Perfil atualizado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 17:19:01'),
(3, 1, NULL, 'profile_update', 'Perfil atualizado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 17:38:14'),
(4, 1, NULL, 'logout', 'Logout realizado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 18:01:58'),
(5, 3, NULL, 'register', 'Novo usuário registrado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 18:15:36'),
(6, 3, NULL, 'login', 'Login realizado com sucesso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 18:19:14'),
(7, 3, NULL, 'login', 'Login realizado com sucesso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 18:28:03'),
(8, 3, NULL, 'logout', 'Logout realizado', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 18:37:57'),
(9, 1, NULL, 'login', 'Login realizado com sucesso', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', '2025-03-22 18:38:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('super_admin','admin','moderator') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','blocked') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_attempts` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `name`, `role`, `status`, `last_login`, `failed_attempts`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'super_admin', 'active', NULL, 0, '2025-03-22 15:32:25', '2025-03-22 15:32:25');

-- --------------------------------------------------------

--
-- Estrutura para tabela `blocks`
--

CREATE TABLE `blocks` (
  `id` int NOT NULL,
  `blocker_id` int NOT NULL,
  `blocked_id` int NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `matches`
--

CREATE TABLE `matches` (
  `id` int NOT NULL,
  `user1_id` int NOT NULL,
  `user2_id` int NOT NULL,
  `status` enum('pending','matched','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `matched_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `match_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` enum('match','message','like','visit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `reports`
--

CREATE TABLE `reports` (
  `id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reported_id` int NOT NULL,
  `reason` enum('fake','inappropriate','harassment','spam','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','reviewing','resolved') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` enum('M','F','O') COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default.jpg',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `last_active` timestamp NULL DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT '0',
  `is_verified` tinyint(1) DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `status` enum('active','blocked','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `failed_attempts` int DEFAULT '0',
  `reset_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `gender`, `birth_date`, `bio`, `avatar`, `city`, `country`, `latitude`, `longitude`, `last_active`, `is_online`, `is_verified`, `is_featured`, `status`, `failed_attempts`, `reset_token`, `reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'hosttimer', 'brunokayte@gmail.com', '$2y$10$5KucuA7Un7W7bos7x8hzVeCHSDMHkRng.8rWArxlYnqXyQOfmOFn6', 'bruno silva', 'M', '1978-10-20', 'verdadeiro', 'avatar_67def586a352b.png', 'anapolis', 'hosttimer', NULL, NULL, '2025-03-22 18:38:57', 1, 0, 0, 'active', 0, NULL, NULL, '2025-03-22 17:02:16', '2025-03-22 18:38:57'),
(3, 'mariasfadinha', 'hosttimercapcut@gmail.com', '$2y$10$F6RkwKyuEtINvi7MnLeBPuWpQ7wcyd408HpCgha/5RTob81Qz/pQC', 'maria bonita', 'F', '1989-10-20', '', 'default.jpg', 'anapolis', 'BR', NULL, NULL, '2025-03-22 18:37:57', 0, 0, 0, 'active', 0, NULL, NULL, '2025-03-22 18:15:36', '2025-03-22 18:37:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_photos`
--

CREATE TABLE `user_photos` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `photo_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `order_position` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` int NOT NULL,
  `interested_in` enum('M','F','B') COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_age` int DEFAULT '18',
  `max_age` int DEFAULT '99',
  `max_distance` int DEFAULT '50',
  `show_online` tinyint(1) DEFAULT '1',
  `show_distance` tinyint(1) DEFAULT '1',
  `notifications_enabled` tinyint(1) DEFAULT '1',
  `email_notifications` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `user_preferences`
--

INSERT INTO `user_preferences` (`user_id`, `interested_in`, `min_age`, `max_age`, `max_distance`, `show_online`, `show_distance`, `notifications_enabled`, `email_notifications`, `created_at`, `updated_at`) VALUES
(1, 'F', 18, 99, 50, 1, 1, 1, 1, '2025-03-22 17:02:16', '2025-03-22 17:02:16'),
(3, 'M', 18, 99, 50, 1, 1, 1, 1, '2025-03-22 18:15:36', '2025-03-22 18:15:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `visits`
--

CREATE TABLE `visits` (
  `id` int NOT NULL,
  `visitor_id` int NOT NULL,
  `visited_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`),
  ADD KEY `idx_admin_activity` (`admin_id`),
  ADD KEY `idx_action` (`action`);

--
-- Índices de tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_admin_username` (`username`),
  ADD UNIQUE KEY `unique_admin_email` (`email`);

--
-- Índices de tabela `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_block` (`blocker_id`,`blocked_id`),
  ADD KEY `idx_blocker` (`blocker_id`),
  ADD KEY `idx_blocked` (`blocked_id`);

--
-- Índices de tabela `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_match` (`user1_id`,`user2_id`),
  ADD KEY `idx_user1` (`user1_id`),
  ADD KEY `idx_user2` (`user2_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_match` (`match_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_read_status` (`is_read`);

--
-- Índices de tabela `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_notifications` (`user_id`),
  ADD KEY `idx_notification_type` (`type`),
  ADD KEY `idx_notification_read` (`is_read`);

--
-- Índices de tabela `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reporter` (`reporter_id`),
  ADD KEY `idx_reported` (`reported_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Índices de tabela `user_photos`
--
ALTER TABLE `user_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_photos` (`user_id`);

--
-- Índices de tabela `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- Índices de tabela `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visitor` (`visitor_id`),
  ADD KEY `idx_visited` (`visited_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `blocks`
--
ALTER TABLE `blocks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `user_photos`
--
ALTER TABLE `user_photos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `blocks`
--
ALTER TABLE `blocks`
  ADD CONSTRAINT `fk_block_blocked` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_block_blocker` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_match_user1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_match_user2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_reported` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_report_reporter` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_photos`
--
ALTER TABLE `user_photos`
  ADD CONSTRAINT `fk_user_photos` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_user_preferences` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `fk_visit_visited` FOREIGN KEY (`visited_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_visit_visitor` FOREIGN KEY (`visitor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
