<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241011000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for property rental management system';
    }

    public function up(Schema $schema): void
    {
        // Create property table
        $this->addSql('CREATE TABLE property (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            postal_code VARCHAR(10) NOT NULL,
            property_type VARCHAR(50) NOT NULL,
            surface DOUBLE PRECISION NOT NULL,
            rooms INTEGER NOT NULL,
            monthly_rent NUMERIC(10, 2) NOT NULL,
            charges NUMERIC(10, 2) DEFAULT NULL,
            deposit NUMERIC(10, 2) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        )');

        // Create tenant table
        $this->addSql('CREATE TABLE tenant (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            phone VARCHAR(20) DEFAULT NULL,
            birth_date DATE DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            postal_code VARCHAR(10) DEFAULT NULL,
            profession VARCHAR(100) DEFAULT NULL,
            monthly_income NUMERIC(10, 2) DEFAULT NULL,
            emergency_contact_name VARCHAR(255) DEFAULT NULL,
            emergency_contact_phone VARCHAR(20) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        )');

        // Create lease table
        $this->addSql('CREATE TABLE lease (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            property_id INTEGER NOT NULL,
            tenant_id INTEGER NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            monthly_rent NUMERIC(10, 2) NOT NULL,
            charges NUMERIC(10, 2) DEFAULT NULL,
            deposit NUMERIC(10, 2) DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            terms TEXT DEFAULT NULL,
            rent_due_day INTEGER DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (property_id) REFERENCES property (id),
            FOREIGN KEY (tenant_id) REFERENCES tenant (id)
        )');

        // Create payment table
        $this->addSql('CREATE TABLE payment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            lease_id INTEGER NOT NULL,
            due_date DATE NOT NULL,
            paid_date DATE DEFAULT NULL,
            amount NUMERIC(10, 2) NOT NULL,
            type VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL,
            payment_method VARCHAR(100) DEFAULT NULL,
            reference VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (lease_id) REFERENCES lease (id)
        )');

        // Create expense table
        $this->addSql('CREATE TABLE expense (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            property_id INTEGER DEFAULT NULL,
            description VARCHAR(255) NOT NULL,
            amount NUMERIC(10, 2) NOT NULL,
            category VARCHAR(100) NOT NULL,
            expense_date DATE NOT NULL,
            supplier VARCHAR(255) DEFAULT NULL,
            invoice_number VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (property_id) REFERENCES property (id)
        )');

        // Create indexes
        $this->addSql('CREATE INDEX IDX_lease_property_id ON lease (property_id)');
        $this->addSql('CREATE INDEX IDX_lease_tenant_id ON lease (tenant_id)');
        $this->addSql('CREATE INDEX IDX_payment_lease_id ON payment (lease_id)');
        $this->addSql('CREATE INDEX IDX_expense_property_id ON expense (property_id)');
        $this->addSql('CREATE INDEX IDX_tenant_email ON tenant (email)');
        $this->addSql('CREATE INDEX IDX_property_status ON property (status)');
        $this->addSql('CREATE INDEX IDX_lease_status ON lease (status)');
        $this->addSql('CREATE INDEX IDX_payment_status ON payment (status)');
        $this->addSql('CREATE INDEX IDX_payment_due_date ON payment (due_date)');
        $this->addSql('CREATE INDEX IDX_expense_date ON expense (expense_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE expense');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE lease');
        $this->addSql('DROP TABLE tenant');
        $this->addSql('DROP TABLE property');
    }
}