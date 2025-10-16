<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012162705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `conversation` (id INT AUTO_INCREMENT NOT NULL, initiator_id INT NOT NULL, subject VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, last_message_at DATETIME DEFAULT NULL, INDEX IDX_8A8E26E97DB3B714 (initiator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conversation_participants (conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_21821ED39AC0396 (conversation_id), INDEX IDX_21821ED3A76ED395 (user_id), PRIMARY KEY(conversation_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `message` (id INT AUTO_INCREMENT NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, content LONGTEXT NOT NULL, is_read TINYINT(1) NOT NULL, sent_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307FF624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `conversation` ADD CONSTRAINT FK_8A8E26E97DB3B714 FOREIGN KEY (initiator_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_21821ED39AC0396 FOREIGN KEY (conversation_id) REFERENCES `conversation` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_21821ED3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES `conversation` (id)');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `conversation` DROP FOREIGN KEY FK_8A8E26E97DB3B714');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED39AC0396');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED3A76ED395');
        $this->addSql('ALTER TABLE `message` DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE `message` DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('DROP TABLE `conversation`');
        $this->addSql('DROP TABLE conversation_participants');
        $this->addSql('DROP TABLE `message`');
    }
}
