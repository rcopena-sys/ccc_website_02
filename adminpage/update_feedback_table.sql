-- Add is_read column to feedback_db table if it doesn't exist
ALTER TABLE `feedback_db` 
ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD INDEX `idx_is_read` (`is_read`);

-- Update existing records to be marked as read
UPDATE `feedback_db` SET `is_read` = 1 WHERE `is_read` = 0;
