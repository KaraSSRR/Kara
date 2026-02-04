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
(1, 'Verdin', 'grass', NULL, 46, 50, 48, 63, 66, 46, 1, 45),
(2, 'Pyrel', 'fire', NULL, 41, 54, 42, 61, 49, 66, 1, 45),
(3, 'Rivli', 'water', NULL, 45, 49, 66, 49, 63, 44, 1, 45),
(4, 'Verdaro', 'grass', NULL, 60, 62, 60, 78, 78, 60, 1, 45),
(5, 'Pyronox', 'fire', NULL, 58, 70, 55, 82, 64, 82, 1, 45),
(6, 'Rivonar', 'water', NULL, 59, 63, 80, 64, 78, 60, 1, 45)
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
(1, 'Verdant Surge', 'Grass-type moves gain a small boost at low HP.'),
(2, 'Cinder Pulse', 'Fire-type moves gain a small boost at low HP.'),
(3, 'Tideflow', 'Water-type moves gain a small boost at low HP.')
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);

-- ---------------------------------------------------------------------------
-- Moves (MVP)
-- ---------------------------------------------------------------------------
INSERT INTO moves (id, name, type, category, power, accuracy, status_inflict, status_chance, pp, priority) VALUES
(1, 'Force Tap', 'normal', 'physical', 40, 100, NULL, NULL, 35, 0),
(2, 'Thorn Lash', 'grass', 'physical', 45, 100, NULL, NULL, 25, 0),
(3, 'Cinder Flick', 'fire', 'special', 40, 100, 'burn', 10, 25, 0),
(4, 'Stream Bolt', 'water', 'special', 40, 100, NULL, NULL, 25, 0),
(5, 'Quickstep', 'normal', 'physical', 40, 100, NULL, NULL, 30, 1),
(6, 'Low Call', 'normal', 'status', NULL, 100, NULL, NULL, 40, 0),
(7, 'Venom Drip', 'poison', 'special', 50, 100, 'poison', 20, 20, 0),
(8, 'Shock Pulse', 'electric', 'special', 40, 100, 'paralysis', 20, 25, 0),
(9, 'Dream Dust', 'psychic', 'status', NULL, 95, 'sleep', 100, 15, 0),
(10, 'Frost Pin', 'ice', 'special', 55, 100, 'freeze', 10, 15, 0)
ON DUPLICATE KEY UPDATE
  type=VALUES(type), category=VALUES(category), power=VALUES(power), accuracy=VALUES(accuracy),
  status_inflict=VALUES(status_inflict), status_chance=VALUES(status_chance),
  pp=VALUES(pp), priority=VALUES(priority);

-- ---------------------------------------------------------------------------
-- Species -> abilities (slot 0)
-- ---------------------------------------------------------------------------
INSERT INTO species_abilities (species_id, slot, ability_id) VALUES
(1, 0, 1),
(2, 0, 2),
(3, 0, 3)
ON DUPLICATE KEY UPDATE ability_id=VALUES(ability_id);

-- ---------------------------------------------------------------------------
-- Species evolutions (starter lines)
-- ---------------------------------------------------------------------------
INSERT INTO species_evolutions (id, from_species_id, to_species_id, method, min_level, item_id, condition_text) VALUES
(1, 1, 4, 'level', 16, NULL, 'Достигнуть уровня 16'),
(2, 2, 5, 'level', 16, NULL, 'Достигнуть уровня 16'),
(3, 3, 6, 'level', 16, NULL, 'Достигнуть уровня 16')
ON DUPLICATE KEY UPDATE
  from_species_id=VALUES(from_species_id),
  to_species_id=VALUES(to_species_id),
  method=VALUES(method),
  min_level=VALUES(min_level),
  item_id=VALUES(item_id),
  condition_text=VALUES(condition_text);

-- ---------------------------------------------------------------------------
-- Location encounters (starter MVP)
-- ---------------------------------------------------------------------------
INSERT INTO location_encounters (id, location_id, species_id, weight, min_level, max_level, time_slot, is_active) VALUES
(1, 1, 1, 60, 2, 6, 'any', 1),
(2, 1, 2, 20, 2, 5, 'day', 1),
(3, 1, 3, 20, 2, 5, 'night', 1),
(4, 2, 2, 70, 3, 7, 'any', 1),
(5, 2, 5, 30, 5, 8, 'any', 1),
(6, 3, 1, 50, 2, 6, 'any', 1),
(7, 3, 4, 20, 4, 7, 'day', 1),
(8, 3, 3, 30, 2, 6, 'night', 1)
ON DUPLICATE KEY UPDATE
  location_id=VALUES(location_id),
  species_id=VALUES(species_id),
  weight=VALUES(weight),
  min_level=VALUES(min_level),
  max_level=VALUES(max_level),
  time_slot=VALUES(time_slot),
  is_active=VALUES(is_active);
