-- Migration pour créer la table audit_log
-- À exécuter après création de l'entité ou via Doctrine

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    organization_id INT DEFAULT NULL,
    company_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT DEFAULT NULL,
    description LONGTEXT DEFAULT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_audit_created_at (created_at),
    INDEX IDX_audit_user (user_id),
    INDEX IDX_audit_entity_type (entity_type),
    INDEX IDX_audit_action (action),
    INDEX IDX_F6E1AA52A76ED395 (user_id),
    INDEX IDX_F6E1AA5232C8A3DE (organization_id),
    INDEX IDX_F6E1AA52979B1AD6 (company_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

-- Clés étrangères
ALTER TABLE audit_log
    ADD CONSTRAINT FK_F6E1AA52A76ED395
    FOREIGN KEY (user_id)
    REFERENCES user (id)
    ON DELETE SET NULL;

ALTER TABLE audit_log
    ADD CONSTRAINT FK_F6E1AA5232C8A3DE
    FOREIGN KEY (organization_id)
    REFERENCES organization (id)
    ON DELETE SET NULL;

ALTER TABLE audit_log
    ADD CONSTRAINT FK_F6E1AA52979B1AD6
    FOREIGN KEY (company_id)
    REFERENCES company (id)
    ON DELETE SET NULL;

-- Note : Adapter les noms de tables (user, organization, company) selon votre schéma

