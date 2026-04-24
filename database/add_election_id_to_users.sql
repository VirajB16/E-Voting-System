-- Add election_id column to users table for candidate registration
-- This allows candidates to be associated with specific elections

-- Check if column exists first
SET @dbname = 'evoting_system';
SET @tablename = 'users';
SET @columnname = 'election_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 'Column already exists' AS message;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NULL AFTER photo;")
));

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key constraint if column was added
SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname
      AND CONSTRAINT_NAME = 'fk_users_election'
  ) > 0,
  "SELECT 'Foreign key already exists' AS message;",
  CONCAT("ALTER TABLE ", @tablename, " ADD CONSTRAINT fk_users_election FOREIGN KEY (", @columnname, ") REFERENCES election_settings(id) ON DELETE SET NULL;")
));

PREPARE alterIfNotExists2 FROM @preparedStatement2;
EXECUTE alterIfNotExists2;
DEALLOCATE PREPARE alterIfNotExists2;

-- Show final structure
DESCRIBE users;

SELECT 'Migration completed successfully!' AS status;
