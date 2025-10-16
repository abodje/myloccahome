<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012171752 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_conversation_initiator');
        $this->addSql('ALTER TABLE conversation ADD is_encrypted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED39AC0396');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_conversation_participants_user');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_message_conversation');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message ADD is_encrypted TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `conversation` DROP is_encrypted');
        $this->addSql('ALTER TABLE `message` DROP is_encrypted');
        $this->addSql('ALTER TABLE `message` ADD CONSTRAINT FK_message_conversation FOREIGN KEY (conversation_id) REFERENCES conversation (id) ON DELETE CASCADE');
    }
}
