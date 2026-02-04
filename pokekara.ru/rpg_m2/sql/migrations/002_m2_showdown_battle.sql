-- 002_m2_showdown_battle.sql (MySQL 5.7+)
-- Adds Showdown-like creature fields + moves/abilities + 1v1 battle storage.
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Abilities
CREATE TABLE IF NOT EXISTS abilities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  description TEXT NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_abilities_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Moves (minimal subset for MVP; expand later)
CREATE TABLE IF NOT EXISTS moves (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(64) NOT NULL,
  type VARCHAR(16) NOT NULL,
  category VARCHAR(16) NOT NULL, -- physical|special|status
  power SMALLINT UNSIGNED NULL,
  accuracy SMALLINT UNSIGNED NULL, -- 1..100, NULL = always hits
  pp SMALLINT UNSIGNED NOT NULL,
  priority TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_moves_name (name),
  KEY idx_moves_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Species abilities mapping (slot 0/1, slot 2 = hidden)
CREATE TABLE IF NOT EXISTS species_abilities (
  species_id INT UNSIGNED NOT NULL,
  slot TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ability_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (species_id, slot),
  KEY idx_species_abilities_ability (ability_id),
  CONSTRAINT fk_species_abilities_species FOREIGN KEY (species_id) REFERENCES species(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_species_abilities_ability FOREIGN KEY (ability_id) REFERENCES abilities(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extend user_creatures with Showdown-like fields
ALTER TABLE user_creatures
  ADD COLUMN nature VARCHAR(16) NOT NULL DEFAULT 'hardy',
  ADD COLUMN happiness SMALLINT UNSIGNED NOT NULL DEFAULT 70,
  ADD COLUMN ability_id INT UNSIGNED NULL,
  ADD COLUMN held_item_id INT UNSIGNED NULL,
  ADD COLUMN is_shiny TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE user_creatures
  ADD KEY idx_user_creatures_ability (ability_id),
  ADD KEY idx_user_creatures_held_item (held_item_id);

ALTER TABLE user_creatures
  ADD CONSTRAINT fk_user_creatures_ability FOREIGN KEY (ability_id) REFERENCES abilities(id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_user_creatures_held_item FOREIGN KEY (held_item_id) REFERENCES items(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Moveset for user creatures (up to 4 slots)
CREATE TABLE IF NOT EXISTS creature_moves (
  user_creature_id INT UNSIGNED NOT NULL,
  slot TINYINT UNSIGNED NOT NULL, -- 1..4
  move_id INT UNSIGNED NOT NULL,
  pp_current SMALLINT UNSIGNED NOT NULL,
  pp_max SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (user_creature_id, slot),
  KEY idx_creature_moves_move (move_id),
  CONSTRAINT fk_creature_moves_creature FOREIGN KEY (user_creature_id) REFERENCES user_creatures(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_creature_moves_move FOREIGN KEY (move_id) REFERENCES moves(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Battles (1v1)
CREATE TABLE IF NOT EXISTS battles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  player_creature_id INT UNSIGNED NOT NULL,
  opponent_creature_snapshot JSON NOT NULL,
  seed INT UNSIGNED NOT NULL,
  turn SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(16) NOT NULL DEFAULT 'active', -- active|finished
  state JSON NOT NULL,
  result JSON NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_battles_user (user_id),
  KEY idx_battles_status (status),
  CONSTRAINT fk_battles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_battles_player_creature FOREIGN KEY (player_creature_id) REFERENCES user_creatures(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actions (for replay)
CREATE TABLE IF NOT EXISTS battle_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  battle_id BIGINT UNSIGNED NOT NULL,
  turn SMALLINT UNSIGNED NOT NULL,
  side VARCHAR(2) NOT NULL, -- p1|p2
  action JSON NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_battle_actions (battle_id, turn, side),
  KEY idx_battle_actions_battle (battle_id),
  CONSTRAINT fk_battle_actions_battle FOREIGN KEY (battle_id) REFERENCES battles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs (for UI/debugging). Replay uses actions + seed.
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

-- ---------------------------------------------------------------------------
-- MVP reference data (INSERT IGNORE so migration can run on existing DB)
-- ---------------------------------------------------------------------------

INSERT IGNORE INTO abilities (id, name, description) VALUES
  (1, 'Overgrow', 'Grass-type moves get a boost in a pinch.'),
  (2, 'Blaze', 'Fire-type moves get a boost in a pinch.'),
  (3, 'Torrent', 'Water-type moves get a boost in a pinch.');

INSERT IGNORE INTO moves (id, name, type, category, power, accuracy, pp, priority) VALUES
  (1, 'Tackle', 'normal', 'physical', 40, 100, 35, 0),
  (2, 'Vine Whip', 'grass', 'physical', 45, 100, 25, 0),
  (3, 'Ember', 'fire', 'special', 40, 100, 25, 0),
  (4, 'Water Gun', 'water', 'special', 40, 100, 25, 0),
  (5, 'Quick Attack', 'normal', 'physical', 40, 100, 30, 1),
  (6, 'Growl', 'normal', 'status', NULL, 100, 40, 0);

-- Species -> abilities
INSERT IGNORE INTO species_abilities (species_id, slot, ability_id) VALUES
  (1, 0, 1),
  (2, 0, 2),
  (3, 0, 3);

-- Backfill: set default ability_id from species_abilities(slot=0) when NULL
UPDATE user_creatures uc
JOIN species_abilities sa ON sa.species_id = uc.species_id AND sa.slot = 0
SET uc.ability_id = sa.ability_id
WHERE uc.ability_id IS NULL;

-- Backfill: add default moves to existing creatures that have none.
-- Slot 1: Tackle for all
INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
SELECT uc.id, 1, 1, 35, 35 FROM user_creatures uc;

-- Slot 2: species signature (Spriglet/Vine Whip, Embercub/Ember, Aquapup/Water Gun)
INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
SELECT uc.id,
       2,
       CASE uc.species_id WHEN 1 THEN 2 WHEN 2 THEN 3 WHEN 3 THEN 4 ELSE 5 END,
       25,
       25
FROM user_creatures uc;

-- Slot 3: Quick Attack
INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
SELECT uc.id, 3, 5, 30, 30 FROM user_creatures uc;

-- Slot 4: Growl
INSERT IGNORE INTO creature_moves (user_creature_id, slot, move_id, pp_current, pp_max)
SELECT uc.id, 4, 6, 40, 40 FROM user_creatures uc;
