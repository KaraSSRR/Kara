-- schema.sql (MySQL 5.7)
SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(20) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  current_location_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  last_login_at DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_location (current_location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug VARCHAR(64) NOT NULL,
  name VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  region INT UNSIGNED NOT NULL DEFAULT 1,
  is_pve TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uq_locations_slug (slug),
  KEY idx_locations_region (region)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS location_encounters (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  location_id INT UNSIGNED NOT NULL,
  species_id INT UNSIGNED NOT NULL,
  weight SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  min_level SMALLINT UNSIGNED NOT NULL DEFAULT 2,
  max_level SMALLINT UNSIGNED NOT NULL DEFAULT 6,
  time_slot ENUM('any','day','night') NOT NULL DEFAULT 'any',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_location_encounters_location (location_id),
  KEY idx_location_encounters_species (species_id),
  KEY idx_location_encounters_active (location_id, is_active),
  CONSTRAINT fk_location_encounters_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_location_encounters_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure users.current_location_id references locations(id) (idempotent)
-- MySQL 5.7 does NOT support "ADD CONSTRAINT IF NOT EXISTS", so we guard via INFORMATION_SCHEMA.

-- Ensure index exists (required for FK)
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'idx_users_location'
);

SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE `users` ADD KEY `idx_users_location` (`current_location_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure FK relationship exists (guard by column+reference, not by FK name)
SET @fk_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
  WHERE kcu.TABLE_SCHEMA = DATABASE()
    AND kcu.TABLE_NAME = 'users'
    AND kcu.COLUMN_NAME = 'current_location_id'
    AND kcu.REFERENCED_TABLE_NAME = 'locations'
    AND kcu.REFERENCED_COLUMN_NAME = 'id'
);

SET @sql := IF(
  @fk_exists = 0,
  'ALTER TABLE `users` \
     ADD CONSTRAINT `fk_users_current_location` \
     FOREIGN KEY (`current_location_id`) REFERENCES `locations`(`id`) \
     ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(32) NOT NULL DEFAULT 'other',
  description TEXT NOT NULL,
  is_tradable TINYINT(1) NOT NULL DEFAULT 1,
  is_usable TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_items_name (name),
  KEY idx_items_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS species (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  type1 VARCHAR(16) NOT NULL,
  type2 VARCHAR(16) NULL,
  base_hp SMALLINT UNSIGNED NOT NULL,
  base_atk SMALLINT UNSIGNED NOT NULL,
  base_def SMALLINT UNSIGNED NOT NULL,
  base_spa SMALLINT UNSIGNED NOT NULL,
  base_spd SMALLINT UNSIGNED NOT NULL,
  base_spe SMALLINT UNSIGNED NOT NULL,
  exp_group TINYINT UNSIGNED NOT NULL DEFAULT 1,
  catch_rate SMALLINT UNSIGNED NOT NULL DEFAULT 45,
  PRIMARY KEY (id),
  UNIQUE KEY uq_species_name (name),
  KEY idx_species_type1 (type1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS species_evolutions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  from_species_id INT UNSIGNED NOT NULL,
  to_species_id INT UNSIGNED NOT NULL,
  method ENUM('level','item','quest','trade','friendship','special') NOT NULL DEFAULT 'level',
  min_level SMALLINT UNSIGNED NULL,
  item_id INT UNSIGNED NULL,
  condition_text VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_species_evo_from (from_species_id),
  KEY idx_species_evo_to (to_species_id),
  KEY idx_species_evo_method (method),
  CONSTRAINT fk_species_evo_from FOREIGN KEY (from_species_id) REFERENCES species(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_species_evo_to FOREIGN KEY (to_species_id) REFERENCES species(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_species_evo_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS abilities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_abilities_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS moves (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  type VARCHAR(16) NOT NULL,
  category VARCHAR(16) NOT NULL,
  power SMALLINT UNSIGNED NULL,
  accuracy SMALLINT UNSIGNED NULL,
  status_inflict VARCHAR(16) NULL,
  status_chance TINYINT UNSIGNED NULL,
  pp SMALLINT UNSIGNED NOT NULL,
  priority TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_moves_name (name),
  KEY idx_moves_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS species_abilities (
  species_id INT UNSIGNED NOT NULL,
  slot TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ability_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (species_id, slot),
  KEY idx_species_abilities_ability (ability_id),
  CONSTRAINT fk_species_abilities_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_species_abilities_ability FOREIGN KEY (ability_id) REFERENCES abilities(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_creatures (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  species_id INT UNSIGNED NOT NULL,
  nickname VARCHAR(120) NOT NULL DEFAULT '',
  level SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  exp INT UNSIGNED NOT NULL DEFAULT 0,
  iv JSON NOT NULL,
  ev JSON NOT NULL,
  stats JSON NOT NULL,
  nature VARCHAR(16) NOT NULL DEFAULT 'hardy',
  happiness SMALLINT UNSIGNED NOT NULL DEFAULT 70,
  ability_id INT UNSIGNED NULL,
  held_item_id INT UNSIGNED NULL,
  is_shiny TINYINT(1) NOT NULL DEFAULT 0,
  current_hp SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status JSON NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_user_creatures_user (user_id),
  KEY idx_user_creatures_species (species_id),
  KEY idx_user_creatures_ability (ability_id),
  KEY idx_user_creatures_held_item (held_item_id),
  CONSTRAINT fk_user_creatures_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_creatures_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_user_creatures_ability FOREIGN KEY (ability_id) REFERENCES abilities(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_user_creatures_held_item FOREIGN KEY (held_item_id) REFERENCES items(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_items (
  user_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  count INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (user_id, item_id),
  KEY idx_user_items_item (item_id),
  CONSTRAINT fk_user_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_items_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
  id CHAR(64) NOT NULL,
  bucket VARCHAR(32) NOT NULL,
  k VARCHAR(128) NOT NULL,
  window_start INT UNSIGNED NOT NULL,
  count INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_rate_limits_bucket (bucket),
  KEY idx_rate_limits_key (k),
  KEY idx_rate_limits_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creature_moves (
  user_creature_id INT UNSIGNED NOT NULL,
  slot TINYINT UNSIGNED NOT NULL,
  move_id INT UNSIGNED NOT NULL,
  pp_current SMALLINT UNSIGNED NOT NULL,
  pp_max SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_creature_id, slot),
  KEY idx_creature_moves_move (move_id),
  CONSTRAINT fk_creature_moves_creature FOREIGN KEY (user_creature_id) REFERENCES user_creatures(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_creature_moves_move FOREIGN KEY (move_id) REFERENCES moves(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS battles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  location_id INT UNSIGNED NULL,
  player_creature_id INT UNSIGNED NOT NULL,
  opponent_creature_snapshot JSON NOT NULL,
  seed INT UNSIGNED NOT NULL,
  turn SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(16) NOT NULL DEFAULT 'active',
  state JSON NOT NULL,
  result JSON NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_battles_user (user_id),
  KEY idx_battles_location (location_id),
  KEY idx_battles_status (status),
  CONSTRAINT fk_battles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_battles_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_battles_player_creature FOREIGN KEY (player_creature_id) REFERENCES user_creatures(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS battle_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  turn SMALLINT UNSIGNED NOT NULL,
  side VARCHAR(2) NOT NULL,
  action JSON NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_battle_actions (battle_id, turn, side),
  KEY idx_battle_actions_battle (battle_id),
  CONSTRAINT fk_battle_actions_battle FOREIGN KEY (battle_id) REFERENCES battles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS battle_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  turn SMALLINT UNSIGNED NOT NULL,
  seq SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  message VARCHAR(255) NOT NULL,
  payload JSON NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_battle_logs_battle (battle_id),
  KEY idx_battle_logs_turn (battle_id, turn, seq),
  CONSTRAINT fk_battle_logs_battle FOREIGN KEY (battle_id) REFERENCES battles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS battle_snapshots (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  turn SMALLINT UNSIGNED NOT NULL,
  state JSON NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_battle_snapshots (battle_id, turn),
  KEY idx_battle_snapshots_battle (battle_id),
  CONSTRAINT fk_battle_snapshots_battle FOREIGN KEY (battle_id) REFERENCES battles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat (Milestone 2+) 
CREATE TABLE IF NOT EXISTS clans (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(32) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clans_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clan_members (
  clan_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('leader','officer','member') NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL,
  PRIMARY KEY (clan_id, user_id),
  UNIQUE KEY uq_clan_members_user (user_id),
  KEY idx_clan_members_clan (clan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  channel ENUM('global','trade','dm','clan') NOT NULL,
  kind ENUM('user','bot','system') NOT NULL DEFAULT 'user',
  bot_name VARCHAR(32) NULL,
  from_user_id INT UNSIGNED NULL,
  to_user_id INT UNSIGNED NULL,
  clan_id INT UNSIGNED NULL,
  body VARCHAR(400) NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_chat_channel_id (channel, id),
  KEY idx_chat_from_id (from_user_id, id),
  KEY idx_chat_to_id (to_user_id, id),
  KEY idx_chat_clan_id (clan_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
