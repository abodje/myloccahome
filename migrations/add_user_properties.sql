-- Migration pour ajouter les nouvelles propriétés à l'entité User
-- Date: 2025-01-15

-- Ajouter les nouvelles colonnes à la table user
ALTER TABLE `user` ADD COLUMN country VARCHAR(100) DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN marital_status VARCHAR(50) DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN preferred_payment_method VARCHAR(50) DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN consents JSON DEFAULT NULL;

-- Ajouter des index pour améliorer les performances
CREATE INDEX idx_user_country ON `user` (country);
CREATE INDEX idx_user_marital_status ON `user` (marital_status);
CREATE INDEX idx_user_preferred_payment_method ON `user` (preferred_payment_method);

