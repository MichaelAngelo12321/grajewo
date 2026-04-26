<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260412000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type to advertisement';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement ADD type VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE advertisement DROP type');
    }
}