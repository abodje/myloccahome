<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241011000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rental management system tables';
    }

    public function up(Schema $schema): void
    {
        // Property table
        $this->addSql('CREATE TABLE property (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            description TEXT DEFAULT NULL, 
            address VARCHAR(255) NOT NULL, 
            city VARCHAR(100) NOT NULL, 
            postal_code VARCHAR(20) NOT NULL, 
            country VARCHAR(100) NOT NULL, 
            type VARCHAR(50) NOT NULL, 
            surface INTEGER NOT NULL, 
            rooms INTEGER NOT NULL, 
            bedrooms INTEGER NOT NULL, 
            bathrooms INTEGER NOT NULL, 
            monthly_rent NUMERIC(10, 2) NOT NULL, 
            charges NUMERIC(10, 2) NOT NULL, 
            deposit NUMERIC(10, 2) NOT NULL, 
            furnished BOOLEAN NOT NULL, 
            available BOOLEAN NOT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL
        )');

        // Tenant table
        $this->addSql('CREATE TABLE tenant (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            first_name VARCHAR(255) NOT NULL, 
            last_name VARCHAR(255) NOT NULL, 
            email VARCHAR(255) NOT NULL, 
            phone VARCHAR(20) DEFAULT NULL, 
            birth_date DATE DEFAULT NULL, 
            address VARCHAR(255) DEFAULT NULL, 
            city VARCHAR(100) DEFAULT NULL, 
            postal_code VARCHAR(20) DEFAULT NULL, 
            country VARCHAR(100) DEFAULT NULL, 
            profession VARCHAR(255) DEFAULT NULL, 
            monthly_income NUMERIC(10, 2) DEFAULT NULL, 
            emergency_contact_name VARCHAR(255) DEFAULT NULL, 
            emergency_contact_phone VARCHAR(20) DEFAULT NULL, 
            notes TEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL
        )');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_4E59C462E7927C74 ON tenant (email)');

        // Rental Contract table
        $this->addSql('CREATE TABLE rental_contract (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            property_id INTEGER NOT NULL, 
            tenant_id INTEGER NOT NULL, 
            start_date DATE NOT NULL, 
            end_date DATE DEFAULT NULL, 
            monthly_rent NUMERIC(10, 2) NOT NULL, 
            charges NUMERIC(10, 2) NOT NULL, 
            deposit NUMERIC(10, 2) NOT NULL, 
            status VARCHAR(50) NOT NULL, 
            conditions TEXT DEFAULT NULL, 
            notes TEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL, 
            CONSTRAINT FK_4EC28FAA549213EC FOREIGN KEY (property_id) REFERENCES property (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
            CONSTRAINT FK_4EC28FAA9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');

        $this->addSql('CREATE INDEX IDX_4EC28FAA549213EC ON rental_contract (property_id)');
        $this->addSql('CREATE INDEX IDX_4EC28FAA9033212A ON rental_contract (tenant_id)');

        // Payment table
        $this->addSql('CREATE TABLE payment (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            rental_contract_id INTEGER NOT NULL, 
            tenant_id INTEGER NOT NULL, 
            amount NUMERIC(10, 2) NOT NULL, 
            due_date DATE NOT NULL, 
            paid_date DATE DEFAULT NULL, 
            status VARCHAR(50) NOT NULL, 
            type VARCHAR(100) NOT NULL, 
            payment_method VARCHAR(100) DEFAULT NULL, 
            reference VARCHAR(255) DEFAULT NULL, 
            notes TEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL, 
            CONSTRAINT FK_6D28840D7A53E532 FOREIGN KEY (rental_contract_id) REFERENCES rental_contract (id) NOT DEFERRABLE INITIALLY IMMEDIATE, 
            CONSTRAINT FK_6D28840D9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');

        $this->addSql('CREATE INDEX IDX_6D28840D7A53E532 ON payment (rental_contract_id)');
        $this->addSql('CREATE INDEX IDX_6D28840D9033212A ON payment (tenant_id)');

        // Maintenance Request table
        $this->addSql('CREATE TABLE maintenance_request (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 
            property_id INTEGER NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            description TEXT NOT NULL, 
            priority VARCHAR(50) NOT NULL, 
            status VARCHAR(50) NOT NULL, 
            category VARCHAR(100) NOT NULL, 
            estimated_cost NUMERIC(10, 2) DEFAULT NULL, 
            actual_cost NUMERIC(10, 2) DEFAULT NULL, 
            assigned_to VARCHAR(255) DEFAULT NULL, 
            scheduled_date DATETIME DEFAULT NULL, 
            completed_date DATETIME DEFAULT NULL, 
            resolution TEXT DEFAULT NULL, 
            notes TEXT DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            updated_at DATETIME NOT NULL, 
            CONSTRAINT FK_9D7E8B1D549213EC FOREIGN KEY (property_id) REFERENCES property (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');

        $this->addSql('CREATE INDEX IDX_9D7E8B1D549213EC ON maintenance_request (property_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE maintenance_request');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE rental_contract');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE property');
    }
}