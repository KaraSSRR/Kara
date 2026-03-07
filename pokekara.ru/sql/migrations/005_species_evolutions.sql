-- 005_species_evolutions.sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';

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

