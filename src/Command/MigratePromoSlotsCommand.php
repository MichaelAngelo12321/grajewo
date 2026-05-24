<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-promo-slots',
    description: 'Migruje stare nazwy pozycji reklam na nowe.',
)]
class MigratePromoSlotsCommand extends Command
{
    private const RENAMES = [
        'WYSKAKUJACE_OKIENKO_POPUP'  => 'WYSKAKUJACA',
        'NAGLOWEK_STRONY'            => 'NAGLOWKOWA',
        'PANEL_BOCZNY_GORA'          => 'PRAWY_1',
        'PANEL_BOCZNY_SRODEK_1'      => 'PRAWY_4',
        'PANEL_BOCZNY_SRODEK_2'      => 'PRAWY_5',
        'PANEL_BOCZNY_DOL'           => 'PRAWY_6',
        'STRONA_GLOWNA_POD_KARUZELA' => 'LEWA_1',
        'STRONA_GLOWNA_SRODEK_LISTY' => 'LEWA_4',
        'NAD_STOPKA'                 => 'LEWA_5',
        'ZAWARTOSC_GLOWNA_1'         => 'LEWA_1',
        'ZAWARTOSC_GLOWNA_2'         => 'LEWA_2',
    ];

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->entityManager->getConnection();
        $total = 0;

        foreach (self::RENAMES as $old => $new) {
            $affected = $conn->executeStatement(
                'UPDATE promo_item SET position = :new WHERE position = :old',
                ['new' => $new, 'old' => $old]
            );

            if ($affected > 0) {
                $io->writeln(sprintf('  %s → %s (%d rekordów)', $old, $new, $affected));
                $total += $affected;
            }
        }

        $io->success(sprintf('Zaktualizowano %d rekordów.', $total));

        return Command::SUCCESS;
    }
}
