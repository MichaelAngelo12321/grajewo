<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add phone to user_report';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_report ADD phone VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_report DROP phone');
    }
}