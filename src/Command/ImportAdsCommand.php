<?php

namespace App\Command;

use App\Entity\PromoItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-ads',
    description: 'Imports advertisements from a legacy database.',
)]
class ImportAdsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    public function __construct(EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->registry = $registry;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $legacyDb = $this->registry->getConnection('legacy');

        $io->info('Fetching ads from legacy database...');
        
        // 1. Pobierz reklamy
        $adsData = $legacyDb->fetchAllAssociative('SELECT * FROM components_site_components_simplified_ads');
        
        $ads = [];
        foreach ($adsData as $row) {
            $ads[$row['id']] = [
                'name' => $row['name'],
                'url' => $row['url'],
                'type' => $row['type'],
                'clicks' => !empty($row['clicks']) ? (int)$row['clicks'] : 0,
                'enabled' => $row['enabled'] ?? true,
                'file_id' => null,
                'file_url' => null,
            ];
        }

        // 2. Pobierz relacje obrazków dla reklam
        $morphData = $legacyDb->fetchAllAssociative("SELECT * FROM upload_file_morph WHERE related_type = 'components_site_components_simplified_ads' AND field = 'image'");
        foreach ($morphData as $row) {
            $fileId = (int)$row['upload_file_id'];
            $relatedId = (int)$row['related_id'];
            if (isset($ads[$relatedId])) {
                $ads[$relatedId]['file_id'] = $fileId;
            }
        }

        // 3. Pobierz URL obrazków
        $fileIds = array_filter(array_column($ads, 'file_id'));
        $files = [];
        
        if (!empty($fileIds)) {
            $idsList = implode(',', $fileIds);
            $fileData = $legacyDb->fetchAllAssociative("SELECT id, url FROM upload_file WHERE id IN ($idsList)");
            foreach ($fileData as $row) {
                $files[(int)$row['id']] = $row['url'];
            }
        }

        // Mapowanie typów na nowe pozycje z .env
        $typeMapping = [
            'banner' => 'NAGLOWEK_STRONY',
            'baner_1' => 'NAGLOWEK_STRONY',
            'baner_2' => 'NAGLOWEK_STRONY',
            'separator' => 'STRONA_GLOWNA_POD_KARUZELA',
            'square' => 'ZAWARTOSC_GLOWNA_1',
            'left_1' => 'PANEL_BOCZNY_SRODEK_1',
            'right_1' => 'PANEL_BOCZNY_SRODEK_2',
            'popup' => 'WYSKAKUJACE_OKIENKO_POPUP',
        ];

        $io->info(sprintf('Found %d ads to import.', count($ads)));

        $count = 0;
        foreach ($ads as $id => $adData) {
            $promo = new PromoItem();
            $promo->setTitle($adData['name'] ?? 'Brak tytułu');
            $promo->setTargetUrl($adData['url']);
            
            $position = $typeMapping[$adData['type']] ?? 'NAGLOWEK_STRONY';
            $promo->setPosition($position);
            
            // Fix for missing clicksCount column value - handle null values and cast properly
            $clicksCount = $adData['clicks'];
            if ($clicksCount === null) {
                $clicksCount = 0;
            }
            $promo->setViewsCount((int)$clicksCount);
            
            // Check if PromoItem has setClicksCount method (just in case viewsCount is different from clicksCount)
            if (method_exists($promo, 'setClicksCount')) {
                $promo->setClicksCount((int)$clicksCount);
            }
            
            $promo->setIsActive($adData['enabled']);
            $promo->setCreatedAt(new \DateTimeImmutable());
            $promo->setUpdatedAt(new \DateTimeImmutable());
            
            // Przypisz URL obrazka
            $imageUrl = '';
            if ($adData['file_id'] && isset($files[$adData['file_id']])) {
                $imageUrl = $files[$adData['file_id']];
            } else {
                $io->warning(sprintf('Ad "%s" (ID: %d) has no image found. Setting placeholder.', $adData['name'], $id));
                $imageUrl = '/build/images/placeholder.jpg';
            }
            $promo->setImageUrl($imageUrl);

            $this->entityManager->persist($promo);
            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Successfully imported %d ads from legacy database!', $count));

        return Command::SUCCESS;
    }
}