<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260426155944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename promo item positions to be more descriptive';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE promo_item SET position = 'NAGLOWEK_STRONY' WHERE position = 'TOP_1'");
        $this->addSql("UPDATE promo_item SET position = 'STRONA_GLOWNA_POD_KARUZELA' WHERE position = 'GLOWNA_1'");
        $this->addSql("UPDATE promo_item SET position = 'STRONA_GLOWNA_SRODEK_LISTY' WHERE position = 'GLOWNA_2'");
        $this->addSql("UPDATE promo_item SET position = 'ZAWARTOSC_GLOWNA_1' WHERE position = 'SRODEK_1'");
        $this->addSql("UPDATE promo_item SET position = 'ZAWARTOSC_GLOWNA_2' WHERE position = 'SRODEK_2'");
        $this->addSql("UPDATE promo_item SET position = 'PANEL_BOCZNY_GORA' WHERE position = 'PRAWA_1'");
        $this->addSql("UPDATE promo_item SET position = 'PANEL_BOCZNY_SRODEK_1' WHERE position = 'PRAWA_2'");
        $this->addSql("UPDATE promo_item SET position = 'PANEL_BOCZNY_SRODEK_2' WHERE position = 'PRAWA_3'");
        $this->addSql("UPDATE promo_item SET position = 'PANEL_BOCZNY_DOL' WHERE position = 'PRAWA_4'");
        $this->addSql("UPDATE promo_item SET position = 'NAD_STOPKA' WHERE position = 'STOPKA_1'");
        $this->addSql("UPDATE promo_item SET position = 'WYSKAKUJACE_OKIENKO_POPUP' WHERE position = 'POPUP'");
        $this->addSql("UPDATE promo_item SET position = 'W_TRESCI_ARTYKULU_SRODEK' WHERE position = 'ARTYKUL_SRODEK'");
        $this->addSql("UPDATE promo_item SET position = 'W_TRESCI_ARTYKULU_LEWA' WHERE position = 'ARTYKUL_LEWA'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE promo_item SET position = 'TOP_1' WHERE position = 'NAGLOWEK_STRONY'");
        $this->addSql("UPDATE promo_item SET position = 'GLOWNA_1' WHERE position = 'STRONA_GLOWNA_POD_KARUZELA'");
        $this->addSql("UPDATE promo_item SET position = 'GLOWNA_2' WHERE position = 'STRONA_GLOWNA_SRODEK_LISTY'");
        $this->addSql("UPDATE promo_item SET position = 'SRODEK_1' WHERE position = 'ZAWARTOSC_GLOWNA_1'");
        $this->addSql("UPDATE promo_item SET position = 'SRODEK_2' WHERE position = 'ZAWARTOSC_GLOWNA_2'");
        $this->addSql("UPDATE promo_item SET position = 'PRAWA_1' WHERE position = 'PANEL_BOCZNY_GORA'");
        $this->addSql("UPDATE promo_item SET position = 'PRAWA_2' WHERE position = 'PANEL_BOCZNY_SRODEK_1'");
        $this->addSql("UPDATE promo_item SET position = 'PRAWA_3' WHERE position = 'PANEL_BOCZNY_SRODEK_2'");
        $this->addSql("UPDATE promo_item SET position = 'PRAWA_4' WHERE position = 'PANEL_BOCZNY_DOL'");
        $this->addSql("UPDATE promo_item SET position = 'STOPKA_1' WHERE position = 'NAD_STOPKA'");
        $this->addSql("UPDATE promo_item SET position = 'POPUP' WHERE position = 'WYSKAKUJACE_OKIENKO_POPUP'");
        $this->addSql("UPDATE promo_item SET position = 'ARTYKUL_SRODEK' WHERE position = 'W_TRESCI_ARTYKULU_SRODEK'");
        $this->addSql("UPDATE promo_item SET position = 'ARTYKUL_LEWA' WHERE position = 'W_TRESCI_ARTYKULU_LEWA'");
    }
}
