<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Gallery;
use App\Entity\GalleryImage;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-articles',
    description: 'Imports articles from a legacy database for a specific category.',
)]
class ImportArticlesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    public function __construct(EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->registry = $registry;
    }

    protected function configure(): void
    {
        $this
            ->addOption('category-id', 'c', InputOption::VALUE_REQUIRED, 'ID of the legacy category to import', 1); // Domyślnie 1 = Aktualności
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Zdejmujemy limit pamięci dla skryptu migracyjnego
        ini_set('memory_limit', '-1');

        // Wyłączamy logowanie zapytań SQL, żeby nie zapchać RAM-u przy tysiącach rekordów
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        $legacyDb = $this->registry->getConnection('legacy');
        $legacyDb->getConfiguration()->setSQLLogger(null);

        // Dodatkowo w Symfony 6/7 Doctrine może profilować zapytania przez Middlewares,
        // co powoduje memory leak (jak BacktraceDebugDataHolder). Wymuszamy ominięcie tego:
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);
        $legacyDb->getConfiguration()->setMiddlewares([]);

        $io = new SymfonyStyle($input, $output);
        $categoryId = (int) $input->getOption('category-id');

        $io->info('Fetching all categories from legacy database...');
        $allCategoriesData = $legacyDb->fetchAllAssociative('SELECT * FROM posts_categories');
        
        $categoriesMap = []; // old_id => Category entity
        foreach ($allCategoriesData as $catRow) {
            // Sprawdzamy czy kategoria już istnieje, by nie duplikować
            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $catRow['slug']]);
            if (!$category) {
                $category = new Category();
                $category->setName($catRow['title'] ?? 'Kategoria ' . $catRow['id']);
                $category->setSlug($catRow['slug'] ?? 'kategoria-' . $catRow['id']);
                $category->setPositionOrder($catRow['order'] ?? 0);
                $category->setIsRoot(false);
                $this->entityManager->persist($category);
            }
            $categoriesMap[(int)$catRow['id']] = $category;
        }
        $this->entityManager->flush();

        // Upewniamy się, że kategoria docelowa dla artykułów istnieje
        if (!isset($categoriesMap[$categoryId])) {
            $io->error(sprintf('Category with ID %d not found in legacy database.', $categoryId));
            return Command::FAILURE;
        }

        $io->info('Fetching images relations for articles...');
        // Pobieramy wszystkie powiązania obrazków dla postów w jednej paczce
        $morphData = $legacyDb->fetchAllAssociative("SELECT * FROM upload_file_morph WHERE related_type = 'posts'");
        $postImages = []; // post_id => ['promo' => file_id, 'gallery' => [file_ids]]
        $allFileIds = [];
        
        foreach ($morphData as $row) {
            $postId = (int)$row['related_id'];
            $fileId = (int)$row['upload_file_id'];
            $allFileIds[] = $fileId;
            
            if (!isset($postImages[$postId])) {
                $postImages[$postId] = ['promo' => null, 'gallery' => []];
            }
            
            if ($row['field'] === 'promoImage') {
                $postImages[$postId]['promo'] = $fileId;
            } elseif ($row['field'] === 'images') {
                $postImages[$postId]['gallery'][] = $fileId;
            }
        }

        $io->info('Fetching image URLs...');
        $filesUrlMap = []; // file_id => url
        $allFileIds = array_unique($allFileIds);
        
        if (!empty($allFileIds)) {
            // Ze względu na to, że idków może być dużo, dzielimy je na paczki po 1000
            $chunks = array_chunk($allFileIds, 1000);
            foreach ($chunks as $chunk) {
                $idsList = implode(',', $chunk);
                $fileData = $legacyDb->fetchAllAssociative("SELECT id, url, name FROM upload_file WHERE id IN ($idsList)");
                foreach ($fileData as $row) {
                    $fileName = basename($row['url'] ?? $row['name']);
                    $filesUrlMap[(int)$row['id']] = $fileName; // Przechowujemy samą nazwę pliku, ścieżkę zbudujemy rocznikowo
                }
            }
        }

        $io->info('Fetching articles from legacy database (in batches)...');
        
        // Zbudujmy mapę użytkowników: legacy_id => User entity
        $legacyUsersData = $legacyDb->fetchAllAssociative('SELECT id, email FROM strapi_administrator');
        $usersMap = [];
        foreach ($legacyUsersData as $uRow) {
            $userEntity = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $uRow['email']]);
            if ($userEntity) {
                $usersMap[(int)$uRow['id']] = $userEntity;
            }
        }
        
        // Zabezpieczenie przed brakiem przypisanego użytkownika (fallback na pierwszego lepszego admina)
        $fallbackUser = $this->entityManager->getRepository(User::class)->findOneBy([]);
        
        $defaultCategory = !empty($categoriesMap) ? reset($categoriesMap) : null;

        $offset = 0;
        $limit = 500;
        $totalMigrated = 0;

        while (true) {
            $postsData = $legacyDb->fetchAllAssociative("SELECT * FROM posts LIMIT $limit OFFSET $offset");
            
            if (empty($postsData)) {
                break;
            }

            foreach ($postsData as $postRow) {
                $article = new Article();
                $article->setName(mb_substr($postRow['title'] ?? 'Brak tytułu', 0, 255));
                $article->setContent($postRow['content'] ?? '');
                
                $excerpt = $postRow['excerpt'] ?? '';
                $article->setExcerpt(mb_substr($excerpt, 0, 300));
                
                $article->setIsEvent((bool)($postRow['isEvent'] ?? 0));
                if (!empty($postRow['eventDate'])) {
                    $article->setEventDateTime(new \DateTimeImmutable($postRow['eventDate']));
                }
                
                $createdAt = !empty($postRow['created_at']) ? new \DateTimeImmutable($postRow['created_at']) : clone $article->getCreatedAt();
                $article->setCreatedAt($createdAt);
                
                $updatedAt = !empty($postRow['updated_at']) ? new \DateTimeImmutable($postRow['updated_at']) : clone $article->getUpdatedAt();
                $article->setUpdatedAt($updatedAt);
                
                $status = !empty($postRow['published_at']) ? \App\Enum\ArticleStatus::PUBLISHED : \App\Enum\ArticleStatus::DRAFT;
                $article->setStatus($status);

                $oldCatId = (int)($postRow['category'] ?? 0);
                $article->setCategory($categoriesMap[$oldCatId] ?? $defaultCategory);
                
                // Przypisanie odpowiedniego autora z mapy lub fallback
                $authorId = (int)($postRow['created_by'] ?? 0);
                $articleAuthor = $usersMap[$authorId] ?? $fallbackUser;
                
                if (!$articleAuthor) {
                    throw new \Exception('Nie znaleziono żadnego użytkownika w bazie! Najpierw uruchom app:import-users');
                }
                
                $article->setAuthor($articleAuthor);
                $article->setUpdateAuthor($articleAuthor);

                // Ustalanie obrazków
                $postId = (int)$postRow['id'];
                $year = $createdAt->format('Y');
                $month = $createdAt->format('m');

                if (isset($postImages[$postId])) {
                    // Obrazek główny
                    if ($postImages[$postId]['promo']) {
                        $promoFileId = $postImages[$postId]['promo'];
                        if (isset($filesUrlMap[$promoFileId])) {
                            $articlePath = sprintf('/media/upload/article/%s/%s/%s', $year, $month, $filesUrlMap[$promoFileId]);
                            $article->setImageUrl($articlePath);
                        }
                    }

                    // Galeria
                    if (!empty($postImages[$postId]['gallery'])) {
                        $gallery = new Gallery();
                        $gallery->setName('Galeria artykułu: ' . mb_substr($article->getName(), 0, 200));
                        $gallery->setCreatedAt(new \DateTimeImmutable());
                        $gallery->setUpdatedAt(new \DateTimeImmutable());
                        if ($articleAuthor) {
                            $gallery->setAuthor($articleAuthor);
                            $gallery->setUpdateAuthor($articleAuthor);
                        }
                        
                        $position = 0;
                        foreach ($postImages[$postId]['gallery'] as $galleryFileId) {
                            if (isset($filesUrlMap[$galleryFileId])) {
                                $galleryPath = sprintf('/media/upload/gallery/%s/%s/%s', $year, $month, $filesUrlMap[$galleryFileId]);
                                $galleryImage = new GalleryImage();
                                $galleryImage->setGallery($gallery);
                                $galleryImage->setImageUrl($galleryPath);
                                $galleryImage->setPositionOrder($position++);
                                $this->entityManager->persist($galleryImage);
                            }
                        }
                        $this->entityManager->persist($gallery);
                        $article->setGallery($gallery);
                    }
                }

                $this->entityManager->persist($article);
                $totalMigrated++;
            }

            $this->entityManager->flush();
            $this->entityManager->clear(); // Czyści pamięć po każdej paczce
            
            // Ponowne wczytanie fallback po clear()
            $fallbackUser = $fallbackUser ? $this->entityManager->getRepository(User::class)->find($fallbackUser->getId()) : null;
            
            // Odświeżamy mapy po clear()
            $categoriesMap = []; // Odbudowujemy całą mapę żeby odświeżyć referencje
            $allCategoriesData = $legacyDb->fetchAllAssociative('SELECT * FROM posts_categories');
            foreach ($allCategoriesData as $catRow) {
                $catId = (int)$catRow['id'];
                $category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $catRow['slug']]);
                if ($category) {
                    $categoriesMap[$catId] = $category;
                }
            }
            
            $defaultCategory = !empty($categoriesMap) ? reset($categoriesMap) : null;
            
            foreach ($usersMap as $oldId => $usr) {
                $usersMap[$oldId] = $this->entityManager->getRepository(User::class)->find($usr->getId());
            }

            gc_collect_cycles();
            $offset += $limit;
            $io->text("Migrated $totalMigrated articles...");
        }

        $io->success(sprintf('Successfully imported %d articles along with their images and categories!', $totalMigrated));

        return Command::SUCCESS;
    }
}