<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-legacy-gallery-paths',
    description: 'Przywraca ścieżki zdjęć (article, gallery_image, promo_item) do struktury /galeria/... zgodnej ze starym serwerem, dla rekordów od podanej daty.',
)]
class FixLegacyGalleryPathsCommand extends Command
{
    public function __construct(private Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Tylko rekordy utworzone od tej daty (YYYY-MM-DD)', '2020-01-01')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Pokaż ile rekordów zostałoby zmienionych, bez zapisywania zmian');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $since = $input->getOption('since');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!\DateTimeImmutable::createFromFormat('Y-m-d', $since)) {
            $io->error('Nieprawidłowa wartość --since. Oczekiwany format: YYYY-MM-DD');
            return Command::FAILURE;
        }

        $io->title('Naprawa ścieżek zdjęć: /public/images/... → /galeria/...');
        $io->note(sprintf('Zakres: rekordy od %s%s', $since, $dryRun ? ' [DRY RUN — bez zapisu]' : ''));

        $io->section('Artykuły (article.image_url)');
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM article WHERE created_at >= :since AND image_url LIKE '/public/images/%'",
            ['since' => $since]
        );
        if (!$dryRun && $count > 0) {
            $this->connection->executeStatement(
                "UPDATE article
                 SET image_url = CONCAT(REPLACE(image_url, '/public/images/', '/galeria/'), 'd.jpg')
                 WHERE created_at >= :since AND image_url LIKE '/public/images/%'",
                ['since' => $since]
            );
        }
        $io->text(sprintf('%d rekordów %s', $count, $dryRun ? 'do zmiany' : 'zaktualizowanych'));

        $io->section('Zdjęcia galerii (gallery_image.image_url)');
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM gallery_image gi
             JOIN gallery g ON g.id = gi.gallery_id
             WHERE g.created_at >= :since AND gi.image_url LIKE '/public/images/%'",
            ['since' => $since]
        );
        if (!$dryRun && $count > 0) {
            $this->connection->executeStatement(
                "UPDATE gallery_image gi
                 JOIN gallery g ON g.id = gi.gallery_id
                 SET gi.image_url = REPLACE(gi.image_url, '/public/images/', '/galeria/')
                 WHERE g.created_at >= :since AND gi.image_url LIKE '/public/images/%'",
                ['since' => $since]
            );
        }
        $io->text(sprintf('%d rekordów %s', $count, $dryRun ? 'do zmiany' : 'zaktualizowanych'));

        $io->section('Promo (promo_item.image_url)');
        $count = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM promo_item WHERE created_at >= :since AND image_url LIKE '/public/images/%'",
            ['since' => $since]
        );
        if (!$dryRun && $count > 0) {
            $this->connection->executeStatement(
                "UPDATE promo_item
                 SET image_url = REPLACE(image_url, '/public/images/', '/galeria/reklama/')
                 WHERE created_at >= :since AND image_url LIKE '/public/images/%'",
                ['since' => $since]
            );
        }
        $io->text(sprintf('%d rekordów %s', $count, $dryRun ? 'do zmiany' : 'zaktualizowanych'));

        $io->success($dryRun ? 'Dry-run zakończony, nic nie zostało zmienione.' : 'Gotowe!');

        return Command::SUCCESS;
    }
}
