-- 003_m2_battle_location.sql (MySQL 5.7+)
-- Attach battles to locations (so battles can happen "на локации").
SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Add location_id column if missing
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'battles'
    AND COLUMN_NAME = 'location_id'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE battles ADD COLUMN location_id INT UNSIGNED NULL AFTER user_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index if missing
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'battles'
    AND INDEX_NAME = 'idx_battles_location'
);

SET @sql := IF(
  @idx_exists = 0,
  'ALTER TABLE battles ADD KEY idx_battles_location (location_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add FK constraint if missing
SET @fk_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'battles'
    AND CONSTRAINT_NAME = 'fk_battles_location'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @sql := IF(
  @fk_exists = 0,
  'ALTER TABLE battles ADD CONSTRAINT fk_battles_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill location_id for existing battles (best effort)
UPDATE battles b
JOIN users u ON u.id = b.user_id
SET b.location_id = u.current_location_id
WHERE b.location_id IS NULL;
