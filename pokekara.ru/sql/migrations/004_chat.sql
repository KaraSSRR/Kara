-- 004_chat.sql
-- Global chat + trade + DM + clan chat (idempotent, MySQL 5.7-safe)

-- 1) Core clan tables (minimal)
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

-- 2) Chat messages table
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

-- 3) Guarded foreign keys (avoid duplicate constraint names across imports)

-- clan_members.clan_id -> clans.id
SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'clan_members'
    AND COLUMN_NAME = 'clan_id'
    AND REFERENCED_TABLE_NAME = 'clans'
    AND REFERENCED_COLUMN_NAME = 'id'
);
SET @sql := IF(@has_fk = 0,
  'ALTER TABLE clan_members
     ADD CONSTRAINT fk_clan_members_clan
     FOREIGN KEY (clan_id) REFERENCES clans(id)
     ON DELETE CASCADE ON UPDATE CASCADE',
  'SELECT "OK: FK clan_members.clan_id exists" AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- clan_members.user_id -> users.id
SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'clan_members'
    AND COLUMN_NAME = 'user_id'
    AND REFERENCED_TABLE_NAME = 'users'
    AND REFERENCED_COLUMN_NAME = 'id'
);
SET @sql := IF(@has_fk = 0,
  'ALTER TABLE clan_members
     ADD CONSTRAINT fk_clan_members_user
     FOREIGN KEY (user_id) REFERENCES users(id)
     ON DELETE CASCADE ON UPDATE CASCADE',
  'SELECT "OK: FK clan_members.user_id exists" AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- chat_messages.from_user_id -> users.id (SET NULL, because bots use NULL and users can be deleted)
SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chat_messages'
    AND COLUMN_NAME = 'from_user_id'
    AND REFERENCED_TABLE_NAME = 'users'
    AND REFERENCED_COLUMN_NAME = 'id'
);
SET @sql := IF(@has_fk = 0,
  'ALTER TABLE chat_messages
     ADD CONSTRAINT fk_chat_messages_from_user
     FOREIGN KEY (from_user_id) REFERENCES users(id)
     ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "OK: FK chat_messages.from_user_id exists" AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- chat_messages.to_user_id -> users.id (SET NULL)
SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chat_messages'
    AND COLUMN_NAME = 'to_user_id'
    AND REFERENCED_TABLE_NAME = 'users'
    AND REFERENCED_COLUMN_NAME = 'id'
);
SET @sql := IF(@has_fk = 0,
  'ALTER TABLE chat_messages
     ADD CONSTRAINT fk_chat_messages_to_user
     FOREIGN KEY (to_user_id) REFERENCES users(id)
     ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "OK: FK chat_messages.to_user_id exists" AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- chat_messages.clan_id -> clans.id (SET NULL)
SET @has_fk := (
  SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'chat_messages'
    AND COLUMN_NAME = 'clan_id'
    AND REFERENCED_TABLE_NAME = 'clans'
    AND REFERENCED_COLUMN_NAME = 'id'
);
SET @sql := IF(@has_fk = 0,
  'ALTER TABLE chat_messages
     ADD CONSTRAINT fk_chat_messages_clan
     FOREIGN KEY (clan_id) REFERENCES clans(id)
     ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT "OK: FK chat_messages.clan_id exists" AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Seed TradeBot DM (optional welcome in trade rules via DM, no table seeds required)
