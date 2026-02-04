-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 03, 2026 at 11:25 AM
-- Server version: 8.0.34-26-beget-1-1
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `karasrgd_kara`
--

-- --------------------------------------------------------

--
-- Table structure for table `abilities`
--
-- Creation: Feb 02, 2026 at 12:33 PM
-- Last update: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `abilities`;
CREATE TABLE `abilities` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `abilities`
--

INSERT INTO `abilities` (`id`, `name`, `description`) VALUES
(1, 'Overgrow', 'Grass-type moves get a boost in a pinch.'),
(2, 'Blaze', 'Fire-type moves get a boost in a pinch.'),
(3, 'Torrent', 'Water-type moves get a boost in a pinch.');

-- --------------------------------------------------------

--
-- Table structure for table `battles`
--
-- Creation: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `battles`;
CREATE TABLE `battles` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `location_id` int UNSIGNED DEFAULT NULL,
  `player_creature_id` int UNSIGNED NOT NULL,
  `opponent_creature_snapshot` json NOT NULL,
  `seed` int UNSIGNED NOT NULL,
  `turn` smallint UNSIGNED NOT NULL DEFAULT '0',
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `state` json NOT NULL,
  `result` json NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `finished_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `battle_actions`
--
-- Creation: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `battle_actions`;
CREATE TABLE `battle_actions` (
  `id` bigint UNSIGNED NOT NULL,
  `battle_id` bigint UNSIGNED NOT NULL,
  `turn` smallint UNSIGNED NOT NULL,
  `side` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` json NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `battle_logs`
--
-- Creation: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `battle_logs`;
CREATE TABLE `battle_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `battle_id` bigint UNSIGNED NOT NULL,
  `turn` smallint UNSIGNED NOT NULL,
  `seq` smallint UNSIGNED NOT NULL DEFAULT '0',
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--
-- Creation: Feb 02, 2026 at 04:41 PM
-- Last update: Feb 02, 2026 at 04:42 PM
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE `chat_messages` (
  `id` bigint UNSIGNED NOT NULL,
  `channel` enum('global','trade','dm','clan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `kind` enum('user','bot','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `bot_name` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_user_id` int UNSIGNED DEFAULT NULL,
  `to_user_id` int UNSIGNED DEFAULT NULL,
  `clan_id` int UNSIGNED DEFAULT NULL,
  `body` varchar(400) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `channel`, `kind`, `bot_name`, `from_user_id`, `to_user_id`, `clan_id`, `body`, `created_at`) VALUES
(1, 'global', 'user', NULL, 1, NULL, NULL, 'Ntcn', '2026-02-02 19:41:41'),
(2, 'trade', 'user', NULL, 1, NULL, NULL, 'Тест', '2026-02-02 19:42:05'),
(3, 'dm', 'bot', 'TradeBot', NULL, 1, NULL, 'TradeBot: лимит торгового чата: 1 сообщение / 30 сек и 30 сообщений / сутки. Используй одно сообщение с полным оффером (что продаёшь/покупаешь, цена, контакт).', '2026-02-02 19:42:07');

-- --------------------------------------------------------

--
-- Table structure for table `clans`
--
-- Creation: Feb 02, 2026 at 04:41 PM
--

DROP TABLE IF EXISTS `clans`;
CREATE TABLE `clans` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clan_members`
--
-- Creation: Feb 02, 2026 at 04:41 PM
--

DROP TABLE IF EXISTS `clan_members`;
CREATE TABLE `clan_members` (
  `clan_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `role` enum('leader','officer','member') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `creature_moves`
--
-- Creation: Feb 02, 2026 at 12:33 PM
-- Last update: Feb 02, 2026 at 12:34 PM
--

DROP TABLE IF EXISTS `creature_moves`;
CREATE TABLE `creature_moves` (
  `user_creature_id` int UNSIGNED NOT NULL,
  `slot` tinyint UNSIGNED NOT NULL,
  `move_id` int UNSIGNED NOT NULL,
  `pp_current` smallint UNSIGNED NOT NULL,
  `pp_max` smallint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `creature_moves`
--

INSERT INTO `creature_moves` (`user_creature_id`, `slot`, `move_id`, `pp_current`, `pp_max`) VALUES
(1, 1, 1, 35, 35),
(1, 2, 2, 25, 25),
(1, 3, 5, 30, 30),
(1, 4, 6, 40, 40);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--
-- Creation: Feb 01, 2026 at 11:46 PM
-- Last update: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_tradable` tinyint(1) NOT NULL DEFAULT '1',
  `is_usable` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `category`, `description`, `is_tradable`, `is_usable`) VALUES
(1, 'Малое зелье', 'potion', 'Восстанавливает немного HP (работает в бою Milestone 2).', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--
-- Creation: Feb 01, 2026 at 11:46 PM
-- Last update: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id` int UNSIGNED NOT NULL,
  `slug` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `region` int UNSIGNED NOT NULL DEFAULT '1',
  `is_pve` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `slug`, `name`, `description`, `region`, `is_pve`) VALUES
(1, 'starter-harbor', 'Стартовая Гавань', 'Тихая прибрежная стоянка с мастерской и доской поручений.', 1, 1),
(2, 'ember-trail', 'Тропа Тлеющих Камней', 'Сухие тропы и тёплый ветер. Здесь живут огненные существа.', 1, 1),
(3, 'mosswood', 'Мшистый Лес', 'Густая зелень и влажный воздух. Хорошее место для сборов.', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `moves`
--
-- Creation: Feb 02, 2026 at 12:33 PM
-- Last update: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `moves`;
CREATE TABLE `moves` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `power` smallint UNSIGNED DEFAULT NULL,
  `accuracy` smallint UNSIGNED DEFAULT NULL,
  `pp` smallint UNSIGNED NOT NULL,
  `priority` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `moves`
--

INSERT INTO `moves` (`id`, `name`, `type`, `category`, `power`, `accuracy`, `pp`, `priority`) VALUES
(1, 'Tackle', 'normal', 'physical', 40, 100, 35, 0),
(2, 'Vine Whip', 'grass', 'physical', 45, 100, 25, 0),
(3, 'Ember', 'fire', 'special', 40, 100, 25, 0),
(4, 'Water Gun', 'water', 'special', 40, 100, 25, 0),
(5, 'Quick Attack', 'normal', 'physical', 40, 100, 30, 1),
(6, 'Growl', 'normal', 'status', NULL, 100, 40, 0);

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--
-- Creation: Feb 01, 2026 at 11:46 PM
-- Last update: Feb 03, 2026 at 08:23 AM
--

DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bucket` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `k` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `window_start` int UNSIGNED NOT NULL,
  `count` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_limits`
--

INSERT INTO `rate_limits` (`id`, `bucket`, `k`, `window_start`, `count`) VALUES
('1fd51207654413c0c7851d8f66aaf6ddc8acb527dc2d2b31eb9e4541621c67e3', 'auth', '77.111.246.9|login', 1770107007, 1),
('965f7fec2ec310076a7902ce7cabcef6ce35035c2978227432d801416e833dd8', 'chat:trade:warn', '1', 1770050527, 1),
('9f880b922c144d98776773f62b0996f21e54e426435601476e30cca408398c3d', 'auth', '77.111.246.24|register', 1770021407, 1),
('ab8e5e027e6f5ce71c09c6403d2d0933ca15cd43762d608ff58013a54089a70c', 'chat:trade', '1', 1770050525, 1),
('ddfad81ac91afe473b904489a3c6b2082f20780782c2bdaf22ed0b1ffd6ee18c', 'chat:global', '1', 1770050501, 1),
('edcd162e6be904ff8bc81484e5d0ebe66418e889e102d23bd566179e668886c2', 'chat:global:day', '1', 1770050501, 1),
('f592a541c537ff6164fd69e5690e065444b4b05e0457dd9a5fd3c405ee138aff', 'chat:trade:day', '1', 1770050525, 1),
('f81c54f85bd7551d3837f4efe0b2d9986d76733c83f1c0ecb61705eb3e8470be', 'auth', '77.111.246.24|login', 1770026882, 1);

-- --------------------------------------------------------

--
-- Table structure for table `species`
--
-- Creation: Feb 01, 2026 at 11:46 PM
-- Last update: Feb 01, 2026 at 11:47 PM
--

DROP TABLE IF EXISTS `species`;
CREATE TABLE `species` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type1` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type2` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base_hp` smallint UNSIGNED NOT NULL,
  `base_atk` smallint UNSIGNED NOT NULL,
  `base_def` smallint UNSIGNED NOT NULL,
  `base_spa` smallint UNSIGNED NOT NULL,
  `base_spd` smallint UNSIGNED NOT NULL,
  `base_spe` smallint UNSIGNED NOT NULL,
  `exp_group` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `catch_rate` smallint UNSIGNED NOT NULL DEFAULT '45'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `species`
--

INSERT INTO `species` (`id`, `name`, `type1`, `type2`, `base_hp`, `base_atk`, `base_def`, `base_spa`, `base_spd`, `base_spe`, `exp_group`, `catch_rate`) VALUES
(1, 'Spriglet', 'grass', NULL, 45, 49, 49, 65, 65, 45, 1, 45),
(2, 'Embercub', 'fire', NULL, 39, 52, 43, 60, 50, 65, 1, 45),
(3, 'Aquapup', 'water', NULL, 44, 48, 65, 50, 64, 43, 1, 45);

-- --------------------------------------------------------

--
-- Table structure for table `species_abilities`
--
-- Creation: Feb 02, 2026 at 12:33 PM
-- Last update: Feb 02, 2026 at 12:33 PM
--

DROP TABLE IF EXISTS `species_abilities`;
CREATE TABLE `species_abilities` (
  `species_id` int UNSIGNED NOT NULL,
  `slot` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `ability_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `species_abilities`
--

INSERT INTO `species_abilities` (`species_id`, `slot`, `ability_id`) VALUES
(1, 0, 1),
(2, 0, 2),
(3, 0, 3);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: Feb 01, 2026 at 11:46 PM
-- Last update: Feb 03, 2026 at 08:23 AM
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_location_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `current_location_id`, `created_at`, `last_login_at`) VALUES
(1, 'Kara', 'asd@gmail.com', '$2y$10$NDLqvLR971/ILbQcmyIiguTZy5/ORbLfFOFVKJMzKlS49On9wE6Bi', 2, '2026-02-02 11:36:47', '2026-02-03 11:23:27');

-- --------------------------------------------------------

--
-- Table structure for table `user_creatures`
--
-- Creation: Feb 02, 2026 at 12:34 PM
-- Last update: Feb 02, 2026 at 12:34 PM
--

DROP TABLE IF EXISTS `user_creatures`;
CREATE TABLE `user_creatures` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `species_id` int UNSIGNED NOT NULL,
  `nickname` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `level` smallint UNSIGNED NOT NULL DEFAULT '1',
  `exp` int UNSIGNED NOT NULL DEFAULT '0',
  `iv` json NOT NULL,
  `ev` json NOT NULL,
  `stats` json NOT NULL,
  `current_hp` smallint UNSIGNED NOT NULL DEFAULT '1',
  `status` json NOT NULL,
  `created_at` datetime NOT NULL,
  `nature` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hardy',
  `happiness` smallint UNSIGNED NOT NULL DEFAULT '70',
  `ability_id` int UNSIGNED DEFAULT NULL,
  `held_item_id` int UNSIGNED DEFAULT NULL,
  `is_shiny` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_creatures`
--

INSERT INTO `user_creatures` (`id`, `user_id`, `species_id`, `nickname`, `level`, `exp`, `iv`, `ev`, `stats`, `current_hp`, `status`, `created_at`, `nature`, `happiness`, `ability_id`, `held_item_id`, `is_shiny`) VALUES
(1, 1, 1, '', 5, 0, '{\"hp\": 12, \"atk\": 12, \"def\": 12, \"spa\": 12, \"spd\": 12, \"spe\": 12}', '{\"hp\": 0, \"atk\": 0, \"def\": 0, \"spa\": 0, \"spd\": 0, \"spe\": 0}', '{\"hp\": 55, \"atk\": 49, \"def\": 49, \"spa\": 65, \"spd\": 65, \"spe\": 45}', 55, '{}', '2026-02-02 11:36:47', 'hardy', 70, 1, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_items`
--
-- Creation: Feb 01, 2026 at 11:46 PM
-- Last update: Feb 02, 2026 at 08:36 AM
--

DROP TABLE IF EXISTS `user_items`;
CREATE TABLE `user_items` (
  `user_id` int UNSIGNED NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `count` int UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_items`
--

INSERT INTO `user_items` (`user_id`, `item_id`, `count`, `updated_at`) VALUES
(1, 1, 3, '2026-02-02 11:36:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `abilities`
--
ALTER TABLE `abilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_abilities_name` (`name`);

--
-- Indexes for table `battles`
--
ALTER TABLE `battles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_battles_user` (`user_id`),
  ADD KEY `idx_battles_location` (`location_id`),
  ADD KEY `idx_battles_status` (`status`),
  ADD KEY `fk_battles_player_creature` (`player_creature_id`);

--
-- Indexes for table `battle_actions`
--
ALTER TABLE `battle_actions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_battle_actions` (`battle_id`,`turn`,`side`),
  ADD KEY `idx_battle_actions_battle` (`battle_id`);

--
-- Indexes for table `battle_logs`
--
ALTER TABLE `battle_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_battle_logs_battle` (`battle_id`),
  ADD KEY `idx_battle_logs_turn` (`battle_id`,`turn`,`seq`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chat_channel_id` (`channel`,`id`),
  ADD KEY `idx_chat_from_id` (`from_user_id`,`id`),
  ADD KEY `idx_chat_to_id` (`to_user_id`,`id`),
  ADD KEY `idx_chat_clan_id` (`clan_id`,`id`);

--
-- Indexes for table `clans`
--
ALTER TABLE `clans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clans_name` (`name`);

--
-- Indexes for table `clan_members`
--
ALTER TABLE `clan_members`
  ADD PRIMARY KEY (`clan_id`,`user_id`),
  ADD UNIQUE KEY `uq_clan_members_user` (`user_id`),
  ADD KEY `idx_clan_members_clan` (`clan_id`);

--
-- Indexes for table `creature_moves`
--
ALTER TABLE `creature_moves`
  ADD PRIMARY KEY (`user_creature_id`,`slot`),
  ADD KEY `idx_creature_moves_move` (`move_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_items_name` (`name`),
  ADD KEY `idx_items_category` (`category`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_locations_slug` (`slug`),
  ADD KEY `idx_locations_region` (`region`);

--
-- Indexes for table `moves`
--
ALTER TABLE `moves`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_moves_name` (`name`),
  ADD KEY `idx_moves_type` (`type`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rate_limits_bucket` (`bucket`),
  ADD KEY `idx_rate_limits_key` (`k`),
  ADD KEY `idx_rate_limits_window` (`window_start`);

--
-- Indexes for table `species`
--
ALTER TABLE `species`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_species_name` (`name`),
  ADD KEY `idx_species_type1` (`type1`);

--
-- Indexes for table `species_abilities`
--
ALTER TABLE `species_abilities`
  ADD PRIMARY KEY (`species_id`,`slot`),
  ADD KEY `idx_species_abilities_ability` (`ability_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_location` (`current_location_id`);

--
-- Indexes for table `user_creatures`
--
ALTER TABLE `user_creatures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_creatures_user` (`user_id`),
  ADD KEY `idx_user_creatures_species` (`species_id`),
  ADD KEY `idx_user_creatures_ability` (`ability_id`),
  ADD KEY `idx_user_creatures_held_item` (`held_item_id`);

--
-- Indexes for table `user_items`
--
ALTER TABLE `user_items`
  ADD PRIMARY KEY (`user_id`,`item_id`),
  ADD KEY `idx_user_items_item` (`item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `abilities`
--
ALTER TABLE `abilities`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `battles`
--
ALTER TABLE `battles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `battle_actions`
--
ALTER TABLE `battle_actions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `battle_logs`
--
ALTER TABLE `battle_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `clans`
--
ALTER TABLE `clans`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `moves`
--
ALTER TABLE `moves`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `species`
--
ALTER TABLE `species`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_creatures`
--
ALTER TABLE `user_creatures`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `battles`
--
ALTER TABLE `battles`
  ADD CONSTRAINT `fk_battles_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_battles_player_creature` FOREIGN KEY (`player_creature_id`) REFERENCES `user_creatures` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_battles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `battle_actions`
--
ALTER TABLE `battle_actions`
  ADD CONSTRAINT `fk_battle_actions_battle` FOREIGN KEY (`battle_id`) REFERENCES `battles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `battle_logs`
--
ALTER TABLE `battle_logs`
  ADD CONSTRAINT `fk_battle_logs_battle` FOREIGN KEY (`battle_id`) REFERENCES `battles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_chat_messages_clan` FOREIGN KEY (`clan_id`) REFERENCES `clans` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_messages_from_user` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chat_messages_to_user` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `clan_members`
--
ALTER TABLE `clan_members`
  ADD CONSTRAINT `fk_clan_members_clan` FOREIGN KEY (`clan_id`) REFERENCES `clans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clan_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `creature_moves`
--
ALTER TABLE `creature_moves`
  ADD CONSTRAINT `fk_creature_moves_creature` FOREIGN KEY (`user_creature_id`) REFERENCES `user_creatures` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_creature_moves_move` FOREIGN KEY (`move_id`) REFERENCES `moves` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `species_abilities`
--
ALTER TABLE `species_abilities`
  ADD CONSTRAINT `fk_species_abilities_ability` FOREIGN KEY (`ability_id`) REFERENCES `abilities` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_species_abilities_species` FOREIGN KEY (`species_id`) REFERENCES `species` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_location` FOREIGN KEY (`current_location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `user_creatures`
--
ALTER TABLE `user_creatures`
  ADD CONSTRAINT `fk_user_creatures_ability_id` FOREIGN KEY (`ability_id`) REFERENCES `abilities` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_creatures_held_item_id` FOREIGN KEY (`held_item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_creatures_species` FOREIGN KEY (`species_id`) REFERENCES `species` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_creatures_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_items`
--
ALTER TABLE `user_items`
  ADD CONSTRAINT `fk_user_items_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
