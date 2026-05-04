<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-image-paths',
    description: 'Zmienia ścieżki w bazie na /media/upload/uploads/nazwa_pliku.',
)]
class FixImagePathsCommand extends Command
{
    public function __construct(
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Aktualizacja ścieżek obrazków do /media/upload/uploads/nazwa_pliku');

        $tables = [
            'article' => 'image_url',
            'gallery_image' => 'image_url',
            'company' => 'logo',
            'promo_item' => 'image_url',
            'daily_image' => 'image_url',
            'user_report' => 'image_url'
        ];

        $totalUpdated = 0;

        foreach ($tables as $table => $column) {
            $io->section("Przetwarzanie tabeli: $table");
            
            $rows = $this->connection->fetchAllAssociative("SELECT id, $column FROM $table WHERE $column IS NOT NULL AND $column != ''");
            $tableUpdated = 0;

            foreach ($rows as $row) {
                $url = $row[$column];
                $basename = basename($url);
                $newUrl = '/media/upload/uploads/' . $basename;
                
                // Aktualizujemy tylko jeśli obecny URL jest inny
                if ($url !== $newUrl) {
                    $this->connection->executeStatement(
                        "UPDATE $table SET $column = :newUrl WHERE id = :id",
                        ['newUrl' => $newUrl, 'id' => $row['id']]
                    );
                    $tableUpdated++;
                }
            }
            
            $totalUpdated += $tableUpdated;
            $io->text(sprintf('Zaktualizowano rekordów: %d', $tableUpdated));
        }

        $io->section("Przetwarzanie treści artykułów");
        $articles = $this->connection->fetchAllAssociative("SELECT id, content FROM article WHERE content LIKE '%/media/upload/%'");
        $contentUpdated = 0;

        foreach ($articles as $article) {
            $content = $article['content'];
            $originalContent = $content;
            
            // Zamieniamy /media/upload/nazwa_pliku.jpg na /media/upload/uploads/nazwa_pliku.jpg
            // Omijamy te, które już mają /uploads/ w ścieżce
            $content = preg_replace('/\/media\/upload\/(?!uploads\/)([^\/"]+\.[a-zA-Z0-9]+)/', '/media/upload/uploads/$1', $content);
            
            if ($content !== $originalContent) {
                $this->connection->executeStatement(
                    "UPDATE article SET content = :content WHERE id = :id",
                    ['content' => $content, 'id' => $article['id']]
                );
                $contentUpdated++;
            }
        }
        $io->text(sprintf('Zaktualizowano treści artykułów: %d', $contentUpdated));

        $io->success([
            'Gotowe! Podsumowanie:',
            sprintf('Zaktualizowano ścieżki w kolumnach: %d', $totalUpdated),
            sprintf('Zaktualizowano wpisy w treści artykułów: %d', $contentUpdated)
        ]);

        return Command::SUCCESS;
    }
}
