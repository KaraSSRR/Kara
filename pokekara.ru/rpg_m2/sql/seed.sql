-- seed.sql
-- Base reference data for local/dev installs.
-- MySQL 5.7+
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------------
-- Locations
-- ---------------------------------------------------------------------------
INSERT INTO locations (id, slug, name, description, region, is_pve) VALUES
(1, 'starter-harbor', 'Стартовая Гавань', 'Тихая прибрежная стоянка с мастерской и доской поручений.', 1, 1),
(2, 'ember-trail', 'Тропа Тлеющих Камней', 'Сухие тропы и тёплый ветер. Здесь живут огненные существа.', 1, 1),
(3, 'mosswood', 'Мшистый Лес', 'Густая зелень и влажный воздух. Хорошее место для сборов.', 1, 1)
ON DUPLICATE KEY UPDATE slug=VALUES(slug), name=VALUES(name), description=VALUES(description), region=VALUES(region), is_pve=VALUES(is_pve);

-- ---------------------------------------------------------------------------
-- Species (starter set for MVP)
-- ---------------------------------------------------------------------------
INSERT INTO species (id, name, type1, type2, base_hp, base_atk, base_def, base_spa, base_spd, base_spe, exp_group, catch_rate) VALUES
(1, 'Spriglet', 'grass', NULL, 45, 49, 49, 65, 65, 45, 1, 45),
(2, 'Embercub', 'fire', NULL, 39, 52, 43, 60, 50, 65, 1, 45),
(3, 'Aquapup', 'water', NULL, 44, 48, 65, 50, 64, 43, 1, 45)
ON DUPLICATE KEY UPDATE
  name=VALUES(name), type1=VALUES(type1), type2=VALUES(type2),
  base_hp=VALUES(base_hp), base_atk=VALUES(base_atk), base_def=VALUES(base_def),
  base_spa=VALUES(base_spa), base_spd=VALUES(base_spd), base_spe=VALUES(base_spe),
  exp_group=VALUES(exp_group), catch_rate=VALUES(catch_rate);

-- ---------------------------------------------------------------------------
-- Items
-- ---------------------------------------------------------------------------
INSERT INTO items (id, name, category, description, is_tradable, is_usable) VALUES
(1, 'Малое зелье', 'potion', 'Восстанавливает немного HP (работает в бою Milestone 2).', 1, 1)
ON DUPLICATE KEY UPDATE
  name=VALUES(name), category=VALUES(category), description=VALUES(description),
  is_tradable=VALUES(is_tradable), is_usable=VALUES(is_usable);

-- ---------------------------------------------------------------------------
-- Abilities (MVP)
-- ---------------------------------------------------------------------------
INSERT INTO abilities (id, name, description) VALUES
(1, 'Overgrow', 'Grass-type moves get a boost in a pinch.'),
(2, 'Blaze', 'Fire-type moves get a boost in a pinch.'),
(3, 'Torrent', 'Water-type moves get a boost in a pinch.')
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- ---------------------------------------------------------------------------
-- Moves (MVP)
-- ---------------------------------------------------------------------------
INSERT INTO moves (id, name, type, category, power, accuracy, pp, priority) VALUES
(1, 'Tackle', 'normal', 'physical', 40, 100, 35, 0),
(2, 'Vine Whip', 'grass', 'physical', 45, 100, 25, 0),
(3, 'Ember', 'fire', 'special', 40, 100, 25, 0),
(4, 'Water Gun', 'water', 'special', 40, 100, 25, 0),
(5, 'Quick Attack', 'normal', 'physical', 40, 100, 30, 1),
(6, 'Growl', 'normal', 'status', NULL, 100, 40, 0)
ON DUPLICATE KEY UPDATE
  type=VALUES(type), category=VALUES(category), power=VALUES(power), accuracy=VALUES(accuracy),
  pp=VALUES(pp), priority=VALUES(priority);

-- ---------------------------------------------------------------------------
-- Species -> abilities (slot 0)
-- ---------------------------------------------------------------------------
INSERT INTO species_abilities (species_id, slot, ability_id) VALUES
(1, 0, 1),
(2, 0, 2),
(3, 0, 3)
ON DUPLICATE KEY UPDATE ability_id=VALUES(ability_id);
