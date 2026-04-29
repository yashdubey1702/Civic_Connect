-- Public report status tracking support.
-- Safe to import more than once in phpMyAdmin.

SET @tracking_column_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reports'
    AND COLUMN_NAME = 'tracking_token'
);

SET @tracking_column_sql := IF(
  @tracking_column_exists = 0,
  'ALTER TABLE reports ADD COLUMN tracking_token VARCHAR(32) DEFAULT NULL AFTER status',
  'SELECT ''tracking_token column already exists'''
);

PREPARE tracking_column_stmt FROM @tracking_column_sql;
EXECUTE tracking_column_stmt;
DEALLOCATE PREPARE tracking_column_stmt;

SET @tracking_index_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reports'
    AND INDEX_NAME = 'idx_reports_tracking_token'
);

SET @tracking_index_sql := IF(
  @tracking_index_exists = 0,
  'CREATE UNIQUE INDEX idx_reports_tracking_token ON reports (tracking_token)',
  'SELECT ''tracking token index already exists'''
);

PREPARE tracking_index_stmt FROM @tracking_index_sql;
EXECUTE tracking_index_stmt;
DEALLOCATE PREPARE tracking_index_stmt;
