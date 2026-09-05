<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Repairs article excerpts that contain HTML entities (e.g. "&oacute;")
 * instead of real UTF-8 characters. Only the excerpt column is touched.
 */
final class Version20260905160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Decode HTML entities in article excerpts';
    }

    public function up(Schema $schema): void
    {
        // Data fix is done in postUp() so it can use PHP's html_entity_decode().
    }

    public function postUp(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, excerpt FROM article WHERE excerpt REGEXP '&(#[0-9]+|#x[0-9a-fA-F]+|[a-zA-Z]+);'",
        );

        foreach ($rows as $row) {
            $decoded = html_entity_decode($row['excerpt'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decoded = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $decoded));

            if ($decoded !== $row['excerpt']) {
                $this->connection->update('article', ['excerpt' => $decoded], ['id' => $row['id']]);
            }
        }

        $this->write(sprintf('Repaired %d article excerpt(s).', count($rows)));
    }

    public function down(Schema $schema): void
    {
        // Data-only fix, intentionally not reversible.
    }
}
