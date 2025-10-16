<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012121837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE online_payment (id INT AUTO_INCREMENT NOT NULL, lease_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, advance_payment_id INT DEFAULT NULL, transaction_id VARCHAR(100) NOT NULL, payment_type VARCHAR(20) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(10) NOT NULL, provider VARCHAR(50) NOT NULL, payment_method VARCHAR(50) NOT NULL, status VARCHAR(30) NOT NULL, customer_name VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(50) DEFAULT NULL, customer_email VARCHAR(255) DEFAULT NULL, payment_url LONGTEXT DEFAULT NULL, cinetpay_response LONGTEXT DEFAULT NULL, notification_data LONGTEXT DEFAULT NULL, paid_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5CBE57F32FC0CB0F (transaction_id), INDEX IDX_5CBE57F3D3CA542C (lease_id), INDEX IDX_5CBE57F34C3A3BB (payment_id), INDEX IDX_5CBE57F350DC10C5 (advance_payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE online_payment ADD CONSTRAINT FK_5CBE57F3D3CA542C FOREIGN KEY (lease_id) REFERENCES lease (id)');
        $this->addSql('ALTER TABLE online_payment ADD CONSTRAINT FK_5CBE57F34C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE online_payment ADD CONSTRAINT FK_5CBE57F350DC10C5 FOREIGN KEY (advance_payment_id) REFERENCES advance_payment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE online_payment DROP FOREIGN KEY FK_5CBE57F3D3CA542C');
        $this->addSql('ALTER TABLE online_payment DROP FOREIGN KEY FK_5CBE57F34C3A3BB');
        $this->addSql('ALTER TABLE online_payment DROP FOREIGN KEY FK_5CBE57F350DC10C5');
        $this->addSql('DROP TABLE online_payment');
    }
}
