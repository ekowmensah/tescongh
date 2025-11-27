-- Migration: Update password_reset_tokens table to use shorter tokens
-- This reduces SMS costs by shortening the reset link
-- Run this if you already have the password_reset_tokens table

-- First, clear any existing tokens (they'll be regenerated on next use)
TRUNCATE TABLE password_reset_tokens;

-- Then modify the token column to be shorter
ALTER TABLE password_reset_tokens 
MODIFY COLUMN token VARCHAR(16) NOT NULL UNIQUE;

-- Add comment for documentation
ALTER TABLE password_reset_tokens 
COMMENT = 'Password reset tokens - optimized for SMS cost-effectiveness';
