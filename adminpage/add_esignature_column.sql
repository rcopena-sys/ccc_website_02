-- SQL Query to add e-signature column to signin_db table
-- This column will store the filename of the uploaded e-signature

ALTER TABLE signin_db 
ADD COLUMN esignature VARCHAR(255) DEFAULT NULL COMMENT 'Stores the filename of the uploaded e-signature image';

-- Optional: Add index for better performance if searching by signature files
-- CREATE INDEX idx_esignature ON signin_db(esignature);

-- Optional: Update existing records to have a default signature (if needed)
-- UPDATE signin_db SET esignature = NULL WHERE esignature IS NULL;

-- Verify the column was added successfully
-- DESCRIBE signin_db;
