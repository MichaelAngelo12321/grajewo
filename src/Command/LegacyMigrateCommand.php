<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\ArticleComment;
use App\Entity\Category;
use App\Entity\Company;
use App\Entity\CompanyCategory;
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:legacy-migrate',
    description: 'Migrates data from the legacy database to the new structure.',
)]
class LegacyMigrateCommand extends Command
{
    public function __construct(
        private ManagerRegistry $registry,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear existing data before migrating')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        $io = new SymfonyStyle($input, $output);
        
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $this->registry->getManager('default');
        $legacyConnection = $this->registry->getConnection('legacy');

        if ($input->getOption('clear')) {
            $io->note('Clearing existing data...');
            $connection = $em->getConnection();
            $platform = $connection->getDatabasePlatform();
            
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
            
            $tablesToClear = [
                'article_comment',
                'gallery_image',
                'gallery',
                'article',
                'advertisement',
                'advertisement_category',
                'category',
                'company',
                'company_category',
                'user'
            ];
            
            foreach ($tablesToClear as $table) {
                $connection->executeStatement($platform->getTruncateTableSQL($table, true));
            }
            
            $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
            $io->success('Existing data cleared.');
        }

        $io->title('Starting Migration');

        $this->migrateUsers($legacyConnection, $em, $io);
        $this->migrateCategories($legacyConnection, $em, $io);
        $this->migrateArticles($legacyConnection, $em, $io);
        $this->migrateComments($legacyConnection, $em, $io);
        $this->migrateCompanyCategories($legacyConnection, $em, $io);
        $this->migrateCompanies($legacyConnection, $em, $io);
        $this->migrateAdvertisementCategories($legacyConnection, $em, $io);
        $this->migrateAdvertisements($legacyConnection, $em, $io);
        $this->migrateFiles($legacyConnection, $em, $io);

        $io->success('Migration completed successfully.');

        return Command::SUCCESS;
    }

    private function migrateUsers($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Users');
        $users = $legacyDb->fetchAllAssociative('SELECT * FROM strapi_administrator');
        
        foreach ($users as $legacyUser) {
            $user = new User();
            $user->setEmail($legacyUser['email'] ?? 'user_'.$legacyUser['id'].'@elk24.pl');
            
            $user->setPassword($legacyUser['password'] ?? '');
            
            $user->setFullName(($legacyUser['firstname'] ?? '') . ' ' . ($legacyUser['lastname'] ?? ''));
            if (empty(trim($user->getFullName()))) {
                $user->setFullName('Admin ' . $legacyUser['id']);
            }
            
            $user->setPosition('Redaktor');
            $user->setIsActive(empty($legacyUser['blocked']));
            
            $createdAt = isset($legacyUser['created_at']) ? new \DateTimeImmutable($legacyUser['created_at']) : new \DateTimeImmutable();
            $user->setCreatedAt($createdAt);
            
            $user->setRoles(['ROLE_ADMIN']);
            
            $metadata = $em->getClassMetadata(User::class);
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            
            $reflection = new \ReflectionClass($user);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($user, $legacyUser['id']);
            
            $em->persist($user);
        }
        
        $em->flush();
        $em->clear();
        $io->text(count($users) . ' users migrated.');
    }

    private function migrateCategories($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Categories');
        $categories = $legacyDb->fetchAllAssociative('SELECT * FROM posts_categories');
        
        foreach ($categories as $legacyCategory) {
            $category = new Category();
            $category->setName($legacyCategory['name'] ?? 'Kategoria ' . $legacyCategory['id']);
            $category->setSlug($legacyCategory['slug'] ?? 'kategoria-' . $legacyCategory['id']);
            $category->setPositionOrder($legacyCategory['order'] ?? 0);
            $category->setIsRoot(false);
            
            $metadata = $em->getClassMetadata(Category::class);
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            
            $reflection = new \ReflectionClass($category);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($category, $legacyCategory['id']);
            
            $em->persist($category);
        }
        
        $em->flush();
        $em->clear();
        $io->text(count($categories) . ' categories migrated.');
    }

    private function migrateArticles($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Articles');
        
        $offset = 0;
        $limit = 500; // Zmniejszono limit
        $totalMigrated = 0;
        
        // Find default user for fallback
        $defaultUser = $em->getRepository(User::class)->findOneBy([]);
        $defaultUserId = $defaultUser ? $defaultUser->getId() : 1;
        
        // Find default category for fallback
        $defaultCategory = $em->getRepository(Category::class)->findOneBy([]);
        $defaultCategoryId = $defaultCategory ? $defaultCategory->getId() : 1;
        
        // Get all valid user IDs to prevent foreign key constraint fails
        $validUserIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM user');
        $validUserIds = array_map('intval', $validUserIds);
        
        // Get all valid category IDs to prevent foreign key constraint fails
        $validCategoryIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM category');
        $validCategoryIds = array_map('intval', $validCategoryIds);
        
        while (true) {
            $posts = $legacyDb->fetchAllAssociative("SELECT * FROM posts LIMIT $limit OFFSET $offset");
            if (empty($posts)) {
                break;
            }
            
            foreach ($posts as $legacyPost) {
                $article = new Article();
                $article->setName(mb_substr($legacyPost['title'] ?? 'Brak tytułu', 0, 255));
                $article->setContent($legacyPost['content'] ?? '');
                
                $excerpt = $legacyPost['excerpt'] ?? '';
                $article->setExcerpt(mb_substr($excerpt, 0, 300));
                
                $article->setIsEvent((bool)($legacyPost['isEvent'] ?? 0));
                
                if (!empty($legacyPost['eventDate'])) {
                    $article->setEventDateTime(new \DateTimeImmutable($legacyPost['eventDate']));
                }
                
                $createdAt = !empty($legacyPost['created_at']) ? new \DateTimeImmutable($legacyPost['created_at']) : clone $article->getCreatedAt();
                $article->setCreatedAt($createdAt);
                
                $updatedAt = !empty($legacyPost['updated_at']) ? new \DateTimeImmutable($legacyPost['updated_at']) : clone $article->getUpdatedAt();
                $article->setUpdatedAt($updatedAt);
                
                $status = !empty($legacyPost['published_at']) ? \App\Enum\ArticleStatus::PUBLISHED : \App\Enum\ArticleStatus::DRAFT;
                $article->setStatus($status);
                
                $categoryId = $legacyPost['category'] ?? $defaultCategoryId;
                if (!in_array((int)$categoryId, $validCategoryIds, true)) {
                    $categoryId = $defaultCategoryId;
                }
                $categoryRef = $em->getReference(Category::class, $categoryId);
                $article->setCategory($categoryRef);
                
                $authorId = $legacyPost['created_by'] ?? $defaultUserId;
                if (!in_array((int)$authorId, $validUserIds, true)) {
                    $authorId = $defaultUserId;
                }
                $authorRef = $em->getReference(User::class, $authorId);
                $article->setAuthor($authorRef);
                
                $updaterId = $legacyPost['updated_by'] ?? $authorId;
                if (!in_array((int)$updaterId, $validUserIds, true)) {
                    $updaterId = $authorId;
                }
                $updaterRef = $em->getReference(User::class, $updaterId);
                $article->setUpdateAuthor($updaterRef);
                
                // Force ID
                $metadata = $em->getClassMetadata(Article::class);
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                
                $reflection = new \ReflectionClass($article);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($article, $legacyPost['id']);
                
                $em->persist($article);
                $totalMigrated++;
            }
            
            $em->flush();
            $em->clear(); // To usuwa referencje Doctrine do wczytanych obiektów
            gc_collect_cycles(); // Zbiera śmieci PHP żeby zredukować ram
            
            $offset += $limit;
            $io->text("Migrated $totalMigrated articles...");
        }
        
        $io->text("Total $totalMigrated articles migrated.");
    }

    private function migrateComments($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Comments');
        
        $offset = 0;
        $limit = 500;
        $totalMigrated = 0;
        
        // Get valid article IDs
        $validArticleIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM article');
        $validArticleIds = array_map('intval', $validArticleIds);
        
        while (true) {
            $comments = $legacyDb->fetchAllAssociative("SELECT * FROM comments LIMIT $limit OFFSET $offset");
            if (empty($comments)) {
                break;
            }
            
            foreach ($comments as $legacyComment) {
                if (empty($legacyComment['postId']) || !in_array((int)$legacyComment['postId'], $validArticleIds, true)) {
                    continue; // Skip if no post attached or article doesn't exist
                }
                
                $articleRef = $em->getReference(Article::class, $legacyComment['postId']);
                
                $comment = new ArticleComment();
                $comment->setArticle($articleRef);
                $comment->setAuthor(mb_substr($legacyComment['nickname'] ?? 'Anonim', 0, 50));
                $comment->setContent($legacyComment['content'] ?? '');
                $comment->setIpAddress($legacyComment['ip'] ?? '127.0.0.1');
                
                $createdAt = !empty($legacyComment['created_at']) ? new \DateTimeImmutable($legacyComment['created_at']) : new \DateTimeImmutable();
                $comment->setCreatedAt($createdAt);
                
                $comment->setIsHidden(empty($legacyComment['published_at']));
                
                // Force ID
                $metadata = $em->getClassMetadata(ArticleComment::class);
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                
                $reflection = new \ReflectionClass($comment);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($comment, $legacyComment['id']);
                
                $em->persist($comment);
                $totalMigrated++;
            }
            
            $em->flush();
            $em->clear();
            gc_collect_cycles();
            
            $offset += $limit;
            $io->text("Migrated $totalMigrated comments...");
        }
        
        $io->text("Total $totalMigrated comments migrated.");
    }

    private function migrateCompanyCategories($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Company Categories');
        
        $categories = $legacyDb->fetchAllAssociative('SELECT * FROM companies_categoies');
        
        foreach ($categories as $legacyCat) {
            $category = new CompanyCategory();
            $category->setName($legacyCat['title'] ?? 'Kategoria ' . $legacyCat['id']);
            $category->setSlug($legacyCat['slug'] ?? 'firma-kat-' . $legacyCat['id']);
            
            // Force ID
            $metadata = $em->getClassMetadata(CompanyCategory::class);
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            
            $reflection = new \ReflectionClass($category);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($category, $legacyCat['id']);
            
            $em->persist($category);
        }
        
        $em->flush();
        $em->clear();
        $io->text(count($categories) . ' company categories migrated.');
    }

    private function migrateCompanies($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Companies');
        
        $offset = 0;
        $limit = 500;
        $totalMigrated = 0;
        
        // Find default category for fallback
        $defaultCategory = $em->getRepository(CompanyCategory::class)->findOneBy([]);
        $defaultCategoryId = $defaultCategory ? $defaultCategory->getId() : 1;
        
        // Get valid company category IDs
        $validCompanyCategoryIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM company_category');
        $validCompanyCategoryIds = array_map('intval', $validCompanyCategoryIds);
        
        while (true) {
            $companies = $legacyDb->fetchAllAssociative("SELECT * FROM companies LIMIT $limit OFFSET $offset");
            if (empty($companies)) {
                break;
            }
            
            foreach ($companies as $legacyCompany) {
                $company = new Company();
                $company->setName(mb_substr($legacyCompany['title'] ?? 'Firma ' . $legacyCompany['id'], 0, 255));
                // Generate simple slug as legacy doesn't have one
                $slug = mb_strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $company->getName())) . '-' . $legacyCompany['id'];
                $company->setSlug($slug);
                
                $company->setDescription($legacyCompany['content'] ?? '');
                $company->setAddress(mb_substr($legacyCompany['location'] ?? '', 0, 255));
                $company->setPhone(mb_substr($legacyCompany['phone'] ?? '', 0, 50));
                $company->setEmail(mb_substr($legacyCompany['email'] ?? '', 0, 255));
                $company->setWebsite(mb_substr($legacyCompany['url'] ?? '', 0, 255));
                
                $createdAt = !empty($legacyCompany['created_at']) ? new \DateTimeImmutable($legacyCompany['created_at']) : new \DateTimeImmutable();
                
                $reflection = new \ReflectionClass($company);
                $propCreatedAt = $reflection->getProperty('createdAt');
                $propCreatedAt->setAccessible(true);
                $propCreatedAt->setValue($company, $createdAt);
                
                $updatedAt = !empty($legacyCompany['updated_at']) ? new \DateTimeImmutable($legacyCompany['updated_at']) : new \DateTimeImmutable();
                $propUpdatedAt = $reflection->getProperty('updatedAt');
                $propUpdatedAt->setAccessible(true);
                $propUpdatedAt->setValue($company, $updatedAt);
                
                $categoryId = $legacyCompany['category'] ?? $legacyCompany['categoy'] ?? $defaultCategoryId;
                if (!in_array((int)$categoryId, $validCompanyCategoryIds, true)) {
                    $categoryId = $defaultCategoryId;
                }
                $categoryRef = $em->getReference(CompanyCategory::class, $categoryId);
                $company->setCategory($categoryRef);
                
                // Force ID
                $metadata = $em->getClassMetadata(Company::class);
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($company, $legacyCompany['id']);
                
                $em->persist($company);
                $totalMigrated++;
            }
            
            $em->flush();
            $em->clear();
            gc_collect_cycles();
            
            $offset += $limit;
            $io->text("Migrated $totalMigrated companies...");
        }
        
        $io->text("Total $totalMigrated companies migrated.");
    }

    private function migrateAdvertisementCategories($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Advertisement Categories');
        
        $categories = $legacyDb->fetchAllAssociative('SELECT * FROM offers_categories');
        
        foreach ($categories as $legacyCat) {
            $category = new AdvertisementCategory();
            $category->setName($legacyCat['title'] ?? 'Kategoria ' . $legacyCat['id']);
            $category->setSlug($legacyCat['slug'] ?? 'ogloszenie-kat-' . $legacyCat['id']);
            $category->setIconName('ti-category'); // Default icon
            
            // Force ID
            $metadata = $em->getClassMetadata(AdvertisementCategory::class);
            $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
            
            $reflection = new \ReflectionClass($category);
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($category, $legacyCat['id']);
            
            $em->persist($category);
        }
        
        $em->flush();
        $em->clear();
        $io->text(count($categories) . ' advertisement categories migrated.');
    }

    private function migrateAdvertisements($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Advertisements');
        
        $offset = 0;
        $limit = 500;
        $totalMigrated = 0;
        
        // Find default category for fallback
        $defaultCategory = $em->getRepository(AdvertisementCategory::class)->findOneBy([]);
        $defaultCategoryId = $defaultCategory ? $defaultCategory->getId() : 1;
        
        // Get valid advertisement category IDs
        $validCategoryIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM advertisement_category');
        $validCategoryIds = array_map('intval', $validCategoryIds);
        
        while (true) {
            $offers = $legacyDb->fetchAllAssociative("SELECT * FROM offers LIMIT $limit OFFSET $offset");
            if (empty($offers)) {
                break;
            }
            
            foreach ($offers as $legacyOffer) {
                $advertisement = new Advertisement();
                $advertisement->setTitle(mb_substr($legacyOffer['title'] ?? 'Ogłoszenie ' . $legacyOffer['id'], 0, 255));
                $advertisement->setContent($legacyOffer['content'] ?? 'Brak treści');
                
                // Truncate email and phone if too long
                $email = $legacyOffer['email'] ?? null;
                if ($email && mb_strlen($email) > 255) {
                    $email = mb_substr($email, 0, 255);
                }
                if ($email) {
                    $advertisement->setEmail($email);
                }
                
                $phone = $legacyOffer['phoneNumber'] ?? null;
                if ($phone) {
                    // Oczyść telefon z dziwnych znaków
                    $phone = preg_replace('/[^0-9\+\-\s]/', '', $phone);
                    if (mb_strlen($phone) > 15) {
                        $phone = mb_substr($phone, 0, 15);
                    }
                    if (mb_strlen($phone) >= 9) {
                        $advertisement->setPhone($phone);
                    }
                }
                
                $advertisement->setAuthor('Anonim');
                $advertisement->setIpAddress($legacyOffer['ip'] ?? '127.0.0.1');
                
                $createdAt = !empty($legacyOffer['created_at']) ? new \DateTimeImmutable($legacyOffer['created_at']) : new \DateTimeImmutable();
                $advertisement->setCreatedAt($createdAt);
                
                $advertisement->setIsActive(!empty($legacyOffer['published_at']));
                $advertisement->setViews((int)($legacyOffer['clickCounter'] ?? 0));
                
                $categoryId = $legacyOffer['category'] ?? $defaultCategoryId;
                if (!in_array((int)$categoryId, $validCategoryIds, true)) {
                    $categoryId = $defaultCategoryId;
                }
                $categoryRef = $em->getReference(AdvertisementCategory::class, $categoryId);
                $advertisement->setCategory($categoryRef);
                
                // Force ID
                $metadata = $em->getClassMetadata(Advertisement::class);
                $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
                
                $reflection = new \ReflectionClass($advertisement);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($advertisement, $legacyOffer['id']);
                
                $em->persist($advertisement);
                $totalMigrated++;
            }
            
            $em->flush();
            $em->clear();
            gc_collect_cycles();
            
            $offset += $limit;
            $io->text("Migrated $totalMigrated advertisements...");
        }
        
        $io->text("Total $totalMigrated advertisements migrated.");
    }

    private function migrateFiles($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Files (Images)');
        
        // Zmigrujemy tylko pliki dla tabel które znamy: posts i offers
        $this->migrateArticleFiles($legacyDb, $em, $io);
        $this->migrateAdvertisementFiles($legacyDb, $em, $io);
    }

    private function migrateAdvertisementFiles($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->text('Migrating Advertisement Images...');
        
        $offset = 0;
        $limit = 1000;
        $totalMigrated = 0;
        
        // Get valid advertisement IDs
        $validAdIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM advertisement');
        $validAdIds = array_map('intval', $validAdIds);
        
        // Default user for gallery author
        $defaultUser = $em->getRepository(User::class)->findOneBy([]);
        
        while (true) {
            $files = $legacyDb->fetchAllAssociative("
                SELECT u.id, u.url, u.name, m.related_id, m.field 
                FROM upload_file u
                JOIN upload_file_morph m ON u.id = m.upload_file_id
                WHERE m.related_type = 'offers'
                LIMIT $limit OFFSET $offset
            ");
            
            if (empty($files)) {
                break;
            }
            
            $galleriesMap = []; // cache galerii dla ogłoszeń
            
            // Reload defaultUser in the current EntityManager context
            if ($defaultUser) {
                $defaultUser = $em->getRepository(User::class)->find($defaultUser->getId());
            }

            foreach ($files as $file) {
                $adId = (int)$file['related_id'];
                if (!in_array($adId, $validAdIds, true)) {
                    continue;
                }
                
                $ad = $em->getRepository(Advertisement::class)->find($adId);
                if (!$ad) {
                    continue;
                }
                
                // Ekstrakcja tylko nazwy pliku z URL
                $fileName = basename($file['url'] ?? $file['name']);
                
                // Ustalenie ścieżki w nowym systemie
                $createdAt = $ad->getCreatedAt();
                if (!$createdAt) {
                    $createdAt = new \DateTimeImmutable();
                }
                $year = $createdAt->format('Y');
                $month = $createdAt->format('m');
                
                $galleryPath = sprintf('/media/upload/gallery/%s/%s/%s', $year, $month, $fileName);
                
                if ($file['field'] === 'images') {
                    if (!isset($galleriesMap[$adId])) {
                        $gallery = $ad->getGallery();
                        if (!$gallery) {
                            $gallery = new Gallery();
                            $gallery->setName('Galeria ogłoszenia: ' . mb_substr($ad->getTitle(), 0, 200));
                            $gallery->setCreatedAt(new \DateTimeImmutable());
                            $gallery->setUpdatedAt(new \DateTimeImmutable());
                            if ($defaultUser) {
                                $gallery->setAuthor($defaultUser);
                                $gallery->setUpdateAuthor($defaultUser);
                            }
                            $em->persist($gallery);
                            $ad->setGallery($gallery);
                        }
                        $galleriesMap[$adId] = $gallery;
                    }
                    
                    $gallery = $galleriesMap[$adId];
                    $galleryImage = new GalleryImage();
                    $galleryImage->setGallery($gallery);
                    $galleryImage->setImageUrl($galleryPath);
                    $galleryImage->setPositionOrder($gallery->getGalleryImages()->count()); 
                    
                    $em->persist($galleryImage);
                }
                
                $totalMigrated++;
            }
            
            $em->flush();
            $em->clear();
            gc_collect_cycles();
            
            $offset += $limit;
            $io->text("Processed $totalMigrated advertisement images...");
        }
        
        $io->success("Finished migrating advertisement images.");
    }

    private function migrateArticleFiles($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->text('Migrating Article Images...');
        
        $offset = 0;
        $limit = 1000;
        $totalMigrated = 0;
        
        // Get valid article IDs
        $validArticleIds = $em->getConnection()->fetchFirstColumn('SELECT id FROM article');
        $validArticleIds = array_map('intval', $validArticleIds);
        
        // Default user for gallery author
        $defaultUser = $em->getRepository(User::class)->findOneBy([]);
        
        while (true) {
            $files = $legacyDb->fetchAllAssociative("
                SELECT u.id, u.url, u.name, m.related_id, m.field 
                FROM upload_file u
                JOIN upload_file_morph m ON u.id = m.upload_file_id
                WHERE m.related_type = 'posts'
                LIMIT $limit OFFSET $offset
            ");
            
            if (empty($files)) {
                break;
            }
            
            $galleriesMap = []; // cache galerii dla artykułów
            
            // Reload defaultUser in the current EntityManager context
            if ($defaultUser) {
                $defaultUser = $em->getRepository(User::class)->find($defaultUser->getId());
            }

            foreach ($files as $file) {
                $articleId = (int)$file['related_id'];
                if (!in_array($articleId, $validArticleIds, true)) {
                    continue;
                }
                
                $article = $em->getRepository(Article::class)->find($articleId);
                if (!$article) {
                    continue;
                }
                
                // Ekstrakcja tylko nazwy pliku z URL (np. /uploads/image.jpg -> image.jpg)
                $fileName = basename($file['url'] ?? $file['name']);
                
                // Ustalenie ścieżki w nowym systemie
                $createdAt = $article->getCreatedAt();
                if (!$createdAt) {
                    $createdAt = new \DateTimeImmutable();
                }
                $year = $createdAt->format('Y');
                $month = $createdAt->format('m');
                
                $articlePath = sprintf('/media/upload/article/%s/%s/%s', $year, $month, $fileName);
                $galleryPath = sprintf('/media/upload/gallery/%s/%s/%s', $year, $month, $fileName);
                
                if ($file['field'] === 'promoImage') {
                    // Obrazek główny artykułu
                    $article->setImageUrl($articlePath);
                } elseif ($file['field'] === 'images') {
                    // Obrazki do galerii
                    if (!isset($galleriesMap[$articleId])) {
                        $gallery = $article->getGallery();
                        if (!$gallery) {
                            $gallery = new Gallery();
                            $gallery->setName('Galeria artykułu: ' . mb_substr($article->getName(), 0, 200));
                            $gallery->setCreatedAt(new \DateTimeImmutable());
                            $gallery->setUpdatedAt(new \DateTimeImmutable());
                            if ($defaultUser) {
                                $gallery->setAuthor($defaultUser);
                                $gallery->setUpdateAuthor($defaultUser);
                            }
                            $em->persist($gallery);
                            $article->setGallery($gallery);
                        }
                        $galleriesMap[$articleId] = $gallery;
                    }
                    
                    $gallery = $galleriesMap[$articleId];
                    $galleryImage = new GalleryImage();
                    $galleryImage->setGallery($gallery);
                    $galleryImage->setImageUrl($galleryPath);
                    // Ustalamy order na podst. obecnej liczby zdjec (przybliżenie)
                    $galleryImage->setPositionOrder($gallery->getGalleryImages()->count()); 
                    
                    $em->persist($galleryImage);
                }
                
                $totalMigrated++;
            }
            
            $em->flush();
            $em->clear();
            gc_collect_cycles();
            
            $offset += $limit;
            $io->text("Processed $totalMigrated article images...");
        }
        
        $io->success("Finished migrating article images.");
    }
}
