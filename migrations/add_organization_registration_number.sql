-- Add registrationNumber column to Organization table
-- This column will store RCCM, SIRET, SIREN, etc.

ALTER TABLE `organization`
ADD COLUMN `registration_number` VARCHAR(100) NULL
COMMENT 'RCCM, SIRET, SIREN, etc.'
AFTER `email`;
