-- 007_moves_status.sql
SET NAMES utf8mb4;
SET time_zone = '+00:00';

ALTER TABLE moves
  ADD COLUMN status_inflict VARCHAR(16) NULL AFTER accuracy,
  ADD COLUMN status_chance TINYINT UNSIGNED NULL AFTER status_inflict;

