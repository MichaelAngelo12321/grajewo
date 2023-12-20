<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231220001413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gas_station (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, position_order INT NOT NULL, has_diesel TINYINT(1) NOT NULL, has_unleaded TINYINT(1) NOT NULL, has_super_unleaded TINYINT(1) NOT NULL, has_liquid_gas TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gas_station_price (id INT AUTO_INCREMENT NOT NULL, station_id INT NOT NULL, date DATE NOT NULL, diesel DOUBLE PRECISION DEFAULT NULL, unleaded DOUBLE PRECISION DEFAULT NULL, super_unleaded DOUBLE PRECISION DEFAULT NULL, liquid_gas DOUBLE PRECISION DEFAULT NULL, INDEX IDX_F635123D21BDB235 (station_id), INDEX IDX_F635123DAA9E377A (date), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE gas_station_price ADD CONSTRAINT FK_F635123D21BDB235 FOREIGN KEY (station_id) REFERENCES gas_station (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gas_station_price DROP FOREIGN KEY FK_F635123D21BDB235');
        $this->addSql('DROP TABLE gas_station');
        $this->addSql('DROP TABLE gas_station_price');
    }
}
