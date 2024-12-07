<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240623155306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gallery (id INT AUTO_INCREMENT NOT NULL, author_id INT DEFAULT NULL, update_author_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_472B783AF675F31B (author_id), INDEX IDX_472B783A5760469E (update_author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gallery_image (id INT AUTO_INCREMENT NOT NULL, gallery_id INT NOT NULL, description VARCHAR(255) DEFAULT NULL, image_url VARCHAR(255) NOT NULL, position_order SMALLINT NOT NULL, INDEX IDX_21A0D47C4E7AF8F (gallery_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783AF675F31B FOREIGN KEY (author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783A5760469E FOREIGN KEY (update_author_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE gallery_image ADD CONSTRAINT FK_21A0D47C4E7AF8F FOREIGN KEY (gallery_id) REFERENCES gallery (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery DROP FOREIGN KEY FK_472B783AF675F31B');
        $this->addSql('ALTER TABLE gallery DROP FOREIGN KEY FK_472B783A5760469E');
        $this->addSql('ALTER TABLE gallery_image DROP FOREIGN KEY FK_21A0D47C4E7AF8F');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('DROP TABLE gallery_image');
    }
}
