<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231217213859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_23A0E667752E9D7 ON article (is_event)');
        $this->addSql('CREATE INDEX IDX_23A0E667B00651C ON article (status)');
        $this->addSql('CREATE INDEX IDX_64C19C179E97745 ON category (position_order)');
        $this->addSql('CREATE INDEX IDX_A17D6CB98B8E8428 ON user_report (created_at)');
        $this->addSql('CREATE INDEX IDX_A17D6CB9D8146462 ON user_report (is_hidden)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_23A0E667752E9D7 ON article');
        $this->addSql('DROP INDEX IDX_23A0E667B00651C ON article');
        $this->addSql('DROP INDEX IDX_A17D6CB98B8E8428 ON user_report');
        $this->addSql('DROP INDEX IDX_A17D6CB9D8146462 ON user_report');
        $this->addSql('DROP INDEX IDX_64C19C179E97745 ON category');
    }
}
