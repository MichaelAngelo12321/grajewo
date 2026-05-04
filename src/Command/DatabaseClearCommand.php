<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:database:clear',
    description: 'Clears all data from the database and clears the application cache.',
)]
class DatabaseClearCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private CacheItemPoolInterface $cache;

    public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cache)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->cache = $cache;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<error>UWAGA!</error> Ta operacja całkowicie usunie WSZYSTKIE dane z bazy. Czy na pewno chcesz kontynuować? (y/N) ', false);

        if (!$helper->ask($input, $output, $question)) {
            $io->note('Operacja anulowana.');
            return Command::SUCCESS;
        }

        $io->info('Rozpoczynam czyszczenie bazy danych...');

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Pobieramy wszystkie nazwy tabel z bazy danych
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        // Tabela z migracjami, której nie chcemy usuwać
        $tablesToIgnore = ['doctrine_migration_versions'];

        // Wyłączamy sprawdzanie kluczy obcych na czas truncate
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        $count = 0;
        foreach ($tables as $table) {
            if (in_array($table, $tablesToIgnore, true)) {
                continue;
            }

            // Generujemy odpowiednie zapytanie TRUNCATE dla danego silnika bazy danych
            $truncateSql = $platform->getTruncateTableSQL($table, true);
            $connection->executeStatement($truncateSql);
            
            $io->text("Wyczyszczono tabelę: <info>$table</info>");
            $count++;
        }

        // Włączamy z powrotem sprawdzanie kluczy obcych
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $io->info('Czyszczenie pamięci podręcznej (cache)...');
        $this->cache->clear();

        $io->success(sprintf('Gotowe! Pomyślnie wyczyszczono %d tabel w bazie danych oraz usunięto cache.', $count));

        return Command::SUCCESS;
    }
}