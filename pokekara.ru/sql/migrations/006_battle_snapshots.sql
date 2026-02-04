-- 006_battle_snapshots.sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';

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

