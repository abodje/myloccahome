-- Script SQL pour ajouter manuellement les colonnes organization_id et company_id
-- À exécuter via phpMyAdmin ou ligne de commande MySQL

USE myloccahomz;

-- 1. Vérifier et ajouter organization_id et company_id à property
ALTER TABLE property ADD COLUMN IF NOT EXISTS organization_id INT DEFAULT NULL;
ALTER TABLE property ADD COLUMN IF NOT EXISTS company_id INT DEFAULT NULL;

-- 2. Vérifier et ajouter organization_id et company_id à tenant
ALTER TABLE tenant ADD COLUMN IF NOT EXISTS organization_id INT DEFAULT NULL;
ALTER TABLE tenant ADD COLUMN IF NOT EXISTS company_id INT DEFAULT NULL;

-- 3. Vérifier et ajouter organization_id et company_id à lease
ALTER TABLE lease ADD COLUMN IF NOT EXISTS organization_id INT DEFAULT NULL;
ALTER TABLE lease ADD COLUMN IF NOT EXISTS company_id INT DEFAULT NULL;

-- 4. Vérifier et ajouter organization_id et company_id à payment
ALTER TABLE payment ADD COLUMN IF NOT EXISTS organization_id INT DEFAULT NULL;
ALTER TABLE payment ADD COLUMN IF NOT EXISTS company_id INT DEFAULT NULL;

-- 5. Vérifier et ajouter company_id à user (organization_id existe déjà)
ALTER TABLE user ADD COLUMN IF NOT EXISTS company_id INT DEFAULT NULL;

-- 6. Vérifier et ajouter organization_id et company_id à expense
ALTER TABLE expense ADD COLUMN IF NOT EXISTS organization_id INT DEFAULT NULL;
ALTER TABLE expense ADD COLUMN IF NOT EXISTS company_id INT DEFAULT NULL;

-- 7. Ajouter les index (ignorer les erreurs si existent déjà)
CREATE INDEX IF NOT EXISTS IDX_property_organization ON property(organization_id);
CREATE INDEX IF NOT EXISTS IDX_property_company ON property(company_id);
CREATE INDEX IF NOT EXISTS IDX_tenant_organization ON tenant(organization_id);
CREATE INDEX IF NOT EXISTS IDX_tenant_company ON tenant(company_id);
CREATE INDEX IF NOT EXISTS IDX_lease_organization ON lease(organization_id);
CREATE INDEX IF NOT EXISTS IDX_lease_company ON lease(company_id);
CREATE INDEX IF NOT EXISTS IDX_payment_organization ON payment(organization_id);
CREATE INDEX IF NOT EXISTS IDX_payment_company ON payment(company_id);
CREATE INDEX IF NOT EXISTS IDX_user_company ON user(company_id);
CREATE INDEX IF NOT EXISTS IDX_expense_organization ON expense(organization_id);
CREATE INDEX IF NOT EXISTS IDX_expense_company ON expense(company_id);

-- TERMINÉ ! Les colonnes sont créées, l'application peut maintenant fonctionner.
-- Les contraintes FK seront ajoutées plus tard si nécessaire.


