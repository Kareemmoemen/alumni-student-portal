-- Resize location column to 255 characters as requested
ALTER TABLE profiles MODIFY COLUMN location VARCHAR(255) DEFAULT NULL;
