<?php

namespace App\Command;

use App\Entity\Advertisement;
use App\Entity\AdvertisementCategory;
use App\Entity\Gallery;
use App\Entity\GalleryImage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:import-advertisements',
    description: 'Imports advertisements (ogłoszenia) and their categories from the legacy database.',
)]
class ImportAdvertisementsCommand extends Command
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
        ini_set('memory_limit', '-1');
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        
        $io = new SymfonyStyle($input, $output);
        $legacyDb = $this->registry->getConnection('legacy');
        $legacyDb->getConfiguration()->setSQLLogger(null);
        
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);
        $legacyDb->getConfiguration()->setMiddlewares([]);

        $slugger = new AsciiSlugger();

        $io->info('Fetching advertisement subcategories (as categories) from legacy database...');
        
        // W legacy bazie "offers_categories" to tylko główne zakładki, a prawdziwe kategorie ogłoszeń 
        // znajdują się zazwyczaj w "offers_subcategories". Przenosimy offers_subcategories jako AdvertisementCategory
        $categoriesData = $legacyDb->fetchAllAssociative('SELECT * FROM offers_subcategories');
        $categoriesMap = []; // old_id => AdvertisementCategory entity
        
        foreach ($categoriesData as $catRow) {
            $catId = (int)$catRow['id'];
            $title = $catRow['title'] ?? 'Kategoria Ogłoszeń ' . $catId;
            $slug = $catRow['slug'] ?? strtolower($slugger->slug($title)->toString());
            
            $category = $this->entityManager->getRepository(AdvertisementCategory::class)->findOneBy(['slug' => $slug]);
            
            if (!$category) {
                $category = new AdvertisementCategory();
                $category->setName($title);
                $category->setSlug($slug);
                if (method_exists($category, 'setPositionOrder')) {
                    $category->setPositionOrder($catId);
                }
                if (method_exists($category, 'setIconName')) {
                    $category->setIconName('ti-folder'); // Domyślna ikona
                }
                $this->entityManager->persist($category);
            }
            $categoriesMap[$catId] = $category;
        }
        $this->entityManager->flush();

        $io->info('Fetching image relations for advertisements (offers)...');
        $morphData = $legacyDb->fetchAllAssociative("SELECT * FROM upload_file_morph WHERE related_type = 'offers' AND field = 'images'");
        $adImages = []; // ad_id => array of file_ids
        $allFileIds = [];
        
        foreach ($morphData as $row) {
            $adId = (int)$row['related_id'];
            $fileId = (int)$row['upload_file_id'];
            $allFileIds[] = $fileId;
            $adImages[$adId][] = $fileId;
        }

        $io->info('Fetching image URLs...');
        $filesUrlMap = [];
        $allFileIds = array_unique($allFileIds);
        
        if (!empty($allFileIds)) {
            $chunks = array_chunk($allFileIds, 1000);
            foreach ($chunks as $chunk) {
                $idsList = implode(',', $chunk);
                $fileData = $legacyDb->fetchAllAssociative("SELECT id, url, name FROM upload_file WHERE id IN ($idsList)");
                foreach ($fileData as $row) {
                    $fileName = basename($row['url'] ?? $row['name']);
                    $filesUrlMap[(int)$row['id']] = $fileName;
                }
            }
        }

        $io->info('Fetching advertisements from legacy database...');

        $defaultUser = $this->entityManager->getRepository(User::class)->findOneBy([]);

        $limit = 500;
        $offset = 0;
        $totalMigrated = 0;

        while (true) {
            $adsData = $legacyDb->fetchAllAssociative("SELECT * FROM offers LIMIT $limit OFFSET $offset");
            
            if (empty($adsData)) {
                break;
            }

            foreach ($adsData as $row) {
                $adId = (int)$row['id'];
                
                $title = $row['title'] ?? 'Ogłoszenie ' . $adId;
                
                $ad = new Advertisement();
                $ad->setTitle(mb_substr($title, 0, 255));
                $ad->setContent($row['content'] ?? '');
                
                $slug = strtolower($slugger->slug($title)->toString());
                
                // Szukamy, czy ogłoszenie o danym slugu nie istnieje (jeśli pole slug w ogóle jest mapowane)
                if (property_exists(Advertisement::class, 'slug')) {
                    $existingSlug = $this->entityManager->getRepository(Advertisement::class)->findOneBy(['slug' => $slug]);
                    if ($existingSlug) {
                        $slug = $slug . '-' . uniqid();
                    }
                    if (method_exists($ad, 'setSlug')) {
                        $ad->setSlug($slug);
                    }
                }
                
                // Contact info
                $ad->setEmail(mb_substr($row['email'] ?? '', 0, 255));
                $ad->setPhone(mb_substr($row['phoneNumber'] ?? '', 0, 255));
                
                if (method_exists($ad, 'setPrice')) {
                    $ad->setPrice((string)($row['price'] ?? ''));
                }

                // Dates
                $createdAt = !empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable();
                $ad->setCreatedAt($createdAt);
                
                if (method_exists($ad, 'setUpdatedAt')) {
                    $updatedAt = !empty($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : new \DateTimeImmutable();
                    $ad->setUpdatedAt($updatedAt);
                }
                
                if (method_exists($ad, 'setExpiresAt')) {
                    if (!empty($row['expirationDate'])) {
                        $ad->setExpiresAt(new \DateTimeImmutable($row['expirationDate']));
                    } else {
                        $ad->setExpiresAt($createdAt->modify('+30 days'));
                    }
                }
                
                if (method_exists($ad, 'setIpAddress')) {
                    $ad->setIpAddress('127.0.0.1'); // Domyślne IP dla zmigrowanych ogłoszeń
                }
                
                $ad->setIsActive(!empty($row['published_at']));
                $ad->setIsPromoted($row['level'] === 'premium'); // zakladam, ze level=premium to promowane

                // Author
                if ($defaultUser) {
                    if (method_exists($ad, 'setAuthor')) {
                        $ad->setAuthor($defaultUser->getFullName() ?? $defaultUser->getEmail());
                    }
                    if (method_exists($ad, 'setUpdateAuthor')) {
                        $ad->setUpdateAuthor($defaultUser);
                    }
                }

                // Category
                $oldCatId = (int)($row['category'] ?? 0);
                if (isset($categoriesMap[$oldCatId])) {
                    $ad->setCategory($categoriesMap[$oldCatId]);
                }

                // Images mapping (Ogłoszenia wspierają galerie zdjęć - relacja OneToOne z Gallery)
                if (!empty($adImages[$adId])) {
                    $gallery = new Gallery();
                    $gallery->setName('Galeria ogłoszenia: ' . mb_substr($title, 0, 200));
                    $gallery->setCreatedAt(new \DateTimeImmutable());
                    $gallery->setUpdatedAt(new \DateTimeImmutable());
                    
                    if ($defaultUser) {
                        $gallery->setAuthor($defaultUser);
                        $gallery->setUpdateAuthor($defaultUser);
                    }
                    
                    $year = $createdAt->format('Y');
                    $month = $createdAt->format('m');
                    $position = 0;
                    
                    foreach ($adImages[$adId] as $fileId) {
                        if (isset($filesUrlMap[$fileId])) {
                            $galleryPath = sprintf('/media/upload/gallery/%s/%s/%s', $year, $month, $filesUrlMap[$fileId]);
                            $galleryImage = new GalleryImage();
                            $galleryImage->setGallery($gallery);
                            $galleryImage->setImageUrl($galleryPath);
                            $galleryImage->setPositionOrder($position++);
                            $this->entityManager->persist($galleryImage);
                        }
                    }
                    
                    $this->entityManager->persist($gallery);
                    if (method_exists($ad, 'setGallery')) {
                        $ad->setGallery($gallery);
                    }
                }

                $this->entityManager->persist($ad);
                $totalMigrated++;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            
            // Odtwarzanie odpiętych encji po clear()
            $defaultUser = $defaultUser ? $this->entityManager->getRepository(User::class)->find($defaultUser->getId()) : null;
            
            $categoriesMap = []; 
            $allCategoriesData = $legacyDb->fetchAllAssociative('SELECT * FROM offers_subcategories');
            foreach ($allCategoriesData as $catRow) {
                $catId = (int)$catRow['id'];
                $category = $this->entityManager->getRepository(AdvertisementCategory::class)->findOneBy(['slug' => $catRow['slug']]);
                if ($category) {
                    $categoriesMap[$catId] = $category;
                }
            }

            $offset += $limit;
            $io->text("Migrated $totalMigrated advertisements...");
        }

        $io->success(sprintf('Successfully imported %d advertisements and their categories!', $totalMigrated));

        return Command::SUCCESS;
    }
}