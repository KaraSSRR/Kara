-- 008_location_encounters.sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';

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

