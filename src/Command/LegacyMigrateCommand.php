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
use App\Entity\Poll;
use App\Entity\PollOption;
use App\Entity\PromoItem;
use App\Entity\User;
use App\Enum\ArticleStatus;
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
    description: 'Migrates data from legacy_db to app, mapping to existing categories.',
)]
class LegacyMigrateCommand extends Command
{
    // Legacy firmy_kat.id → app company_category.id
    private const COMPANY_CAT_MAP = [
        6  => 2,  // Antyki, sztuka i książki
        8  => 4,  // Biuro i reklama
        10 => 5,  // Budownictwo
        11 => 7,  // Edukacja
        13 => 12, // Gastronomia i żywność
        15 => 9,  // Foto, video i media
        16 => 10, // Informatyka
        22 => 18, // Prawo
        28 => 22, // Sport i rekreacja
        31 => 23, // Telekomunikacja
        39 => 27, // Zwierzęta
        41 => 13, // Kościoły i dewocjonalia
        42 => 3,  // Architektura i geodezja
        43 => 6,  // Dom i ogród
        44 => 8,  // Finanse i ubezpieczenia
        45 => 11, // Instytucje i urzędy
        46 => 15, // Motoryzacja i transport
        47 => 16, // Odzież, biżuteria i zegarki
        48 => 19, // Przemysł i energetyka
        49 => 20, // Rolnictwo i leśnictwo
        50 => 21, // Rozrywka i muzyka
        51 => 24, // Turystyka i noclegi
        52 => 26, // Zdrowie i uroda
        53 => 1,  // AGD / RTV
        54 => 17, // Pozostałe
        55 => 25, // Wyposażenie wnętrz
        56 => 14, // Kwiaty, upominki i zabawki
    ];

    // Legacy ogloszenia_kat.id → app advertisement_category.id
    private const AD_CAT_MAP = [
        6  => 1,  // AGD / RTV / FOTO → AGD
        7  => 4,  // Edukacja
        8  => 6,  // Informatyka i biuro → Informatyka
        9  => 8,  // Motoryzacja → Samochody osobowe
        10 => 14, // Nieruchomości
        11 => 25, // Pozostałe
        12 => 16, // Praca
        13 => 20, // Telekomunikacja → Telefony i tablety
        14 => 24, // Zwierzęta
        15 => 22, // Usługi
        16 => 21, // Towarzyskie
        17 => 15, // Odzież i biżuteria
        18 => 17, // Wyposażenie wnętrz → Dom i ogród
        19 => 23, // Zabawki, upominki, gadżety
        20 => 5,  // Gastronomia
        21 => 12, // Rolnictwo i ogrodnictwo → Maszyny rolnicze
        22 => 18, // Sport i rekreacja
    ];

    // Legacy informacje.nr_dzialu → app category.slug
    private const ARTICLE_CAT_MAP = [
        'wydarzenia' => 'wydarzenia', // Aktualności
        'kultura'    => 'kultura',
        'sport'      => 'sport',
        'artykuly'   => 'artykuly',   // Artykuły i felietony
        'oferty'     => 'oferty',     // Oferty i promocje
        'konkursy'   => 'wydarzenia', // brak odpowiednika → Aktualności
        'imprezy'    => 'wydarzenia', // brak odpowiednika → Aktualności
    ];

    public function __construct(
        private ManagerRegistry $registry,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    private const STEPS = ['users', 'galleries', 'articles', 'comments', 'companies', 'advertisements', 'polls', 'promo'];

    protected function configure(): void
    {
        $this
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear migrated tables before running')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Start from step: ' . implode(', ', self::STEPS))
            ->addOption('since', null, InputOption::VALUE_REQUIRED, 'Only migrate records created after this date (YYYY-MM-DD), skips existing IDs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $io = new SymfonyStyle($input, $output);

        /** @var EntityManagerInterface $em */
        $em = $this->registry->getManager('default');
        $legacyDb = $this->registry->getConnection('legacy');

        $em->getConnection()->getConfiguration()->setMiddlewares([]);
        $legacyDb->getConfiguration()->setMiddlewares([]);

        $fromStep = $input->getOption('from');
        if ($fromStep !== null && !in_array($fromStep, self::STEPS, true)) {
            $io->error('Invalid --from value. Valid steps: ' . implode(', ', self::STEPS));
            return Command::FAILURE;
        }

        $since = $input->getOption('since');
        if ($since !== null && !\DateTimeImmutable::createFromFormat('Y-m-d', $since)) {
            $io->error('Invalid --since value. Expected format: YYYY-MM-DD');
            return Command::FAILURE;
        }

        $skip = $fromStep !== null
            ? array_flip(array_slice(self::STEPS, 0, array_search($fromStep, self::STEPS, true)))
            : [];

        if ($input->getOption('clear')) {
            $this->clearData($em, $io);
        }

        $io->title('Legacy Migration (legacy_db → app)');
        if ($since) {
            $io->note("Incremental mode: only records created after $since, skipping existing IDs.");
        }

        $adminUser = isset($skip['users'])
            ? $em->getRepository(User::class)->findOneBy([])
            : $this->migrateUsers($legacyDb, $em, $io);

        $articleCatMap = (isset($skip['galleries']) && isset($skip['articles']))
            ? null
            : $this->buildArticleCategoryMap($em, $io);

        if (!isset($skip['galleries'])) {
            $this->migrateGalleries($legacyDb, $em, $adminUser, $io, $since);
        }
        if (!isset($skip['articles'])) {
            $this->migrateArticles($legacyDb, $em, $adminUser, $articleCatMap, $io, $since);
        }
        if (!isset($skip['comments'])) {
            $this->migrateComments($legacyDb, $em, $io, $since);
        }
        if (!isset($skip['companies'])) {
            $this->migrateCompanies($legacyDb, $em, $io, $since);
        }
        if (!isset($skip['advertisements'])) {
            $this->migrateAdvertisements($legacyDb, $em, $io, $since);
        }
        if (!isset($skip['polls'])) {
            $this->migratePolls($legacyDb, $em, $io);
        }
        if (!isset($skip['promo'])) {
            $this->migratePromoItems($legacyDb, $em, $io);
        }

        $io->success('Migration completed!');
        return Command::SUCCESS;
    }

    private function clearData(EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->note('Clearing migrated tables...');
        $conn = $em->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['poll_vote', 'poll_option', 'poll', 'article_comment', 'gallery_image', 'gallery', 'article', 'company', 'advertisement', 'promo_item'] as $table) {
            $conn->executeStatement("TRUNCATE TABLE `$table`");
        }
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        $io->success('Tables cleared (categories and users untouched).');
    }

    // uzytkownicy → user (skip if email already exists)
    private function migrateUsers($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): User
    {
        $io->section('Migrating Users (uzytkownicy)');

        $rows = $legacyDb->fetchAllAssociative('SELECT * FROM uzytkownicy');
        $created = 0;

        foreach ($rows as $row) {
            $login = $row['login'] ?? '';
            $email = str_contains($login, '@') ? $login : $login . '@info24.pl';
            $email = mb_substr($email, 0, 180);

            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword($row['haslo'] ?? '');
            $user->setFullName(mb_substr($row['nazwa'] ?? $login, 0, 255));
            $user->setPosition('Redaktor');
            $user->setIsActive(true);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setCreatedAt(new \DateTimeImmutable());

            $em->persist($user);
            $created++;
        }

        $em->flush();
        $em->clear();

        $io->text("$created users created from legacy.");
        return $em->getRepository(User::class)->findOneBy([]);
    }

    // Buduje mapę: nr_dzialu string → app category entity id
    private function buildArticleCategoryMap(EntityManagerInterface $em, SymfonyStyle $io): array
    {
        $io->section('Building Article Category Map');

        $appCategories = $em->getConnection()->fetchAllKeyValue('SELECT slug, id FROM category');
        $map = [];

        foreach (self::ARTICLE_CAT_MAP as $legacySlug => $appSlug) {
            if (isset($appCategories[$appSlug])) {
                $map[$legacySlug] = (int)$appCategories[$appSlug];
            }
        }

        $fallback = reset($appCategories);
        $io->text('Category map: ' . json_encode($map));

        return ['map' => $map, 'fallback' => (int)$fallback];
    }

    // galerie_opis → gallery, galerie → gallery_image
    private function migrateGalleries($legacyDb, EntityManagerInterface $em, User $adminUser, SymfonyStyle $io, ?string $since = null): void
    {
        $io->section('Migrating Galleries');

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM gallery'))
        );

        $dateFilter = $since ? "WHERE data > '$since'" : '';
        $offset = 0;
        $limit = 500;
        $totalGalleries = 0;

        while (true) {
            $rows = $legacyDb->fetchAllAssociative("SELECT * FROM galerie_opis $dateFilter LIMIT $limit OFFSET $offset");
            if (empty($rows)) break;

            $adminRef = $em->getReference(User::class, $adminUser->getId());

            foreach ($rows as $row) {
                if (isset($existingIds[(int)$row['id']])) continue;

                $gallery = new Gallery();
                $gallery->setName(mb_substr($this->cleanText($row['tytul'] ?? 'Galeria ' . $row['id']), 0, 255));
                $createdAt = $this->parseDateSafe($row['data'] ?? null);
                $updatedAt = $this->parseDateSafe($row['ak_czas'] ?? null) ?: $createdAt;
                $gallery->setCreatedAt($createdAt);
                $gallery->setUpdatedAt($updatedAt);
                $gallery->setAuthor($adminRef);
                $gallery->setUpdateAuthor($adminRef);

                $this->forceId($em, $gallery, (int)$row['id']);
                $em->persist($gallery);
                $existingIds[(int)$row['id']] = true;
                $totalGalleries++;
            }

            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $offset += $limit;
        }

        $io->text("$totalGalleries galleries migrated.");

        $existingImageIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM gallery_image'))
        );

        $offset = 0;
        $totalImages = 0;
        $positionCounters = [];

        while (true) {
            $rows = $legacyDb->fetchAllAssociative("SELECT * FROM galerie ORDER BY id LIMIT $limit OFFSET $offset");
            if (empty($rows)) break;

            $validGalleryIds = array_flip(
                array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM gallery'))
            );

            foreach ($rows as $row) {
                if (isset($existingImageIds[(int)$row['id']])) continue;

                $galleryId = (int)$row['grupa'];
                if (!isset($validGalleryIds[$galleryId])) continue;

                $positionCounters[$galleryId] = $positionCounters[$galleryId] ?? 0;

                $image = new GalleryImage();
                $image->setGallery($em->getReference(Gallery::class, $galleryId));
                $image->setImageUrl('/public/images/galeria/' . $row['plik']);
                $image->setDescription(mb_substr($this->cleanText($row['opis'] ?? ''), 0, 255) ?: null);
                $image->setPositionOrder($positionCounters[$galleryId]++);

                $this->forceId($em, $image, (int)$row['id']);
                $em->persist($image);
                $existingImageIds[(int)$row['id']] = true;
                $totalImages++;
            }

            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $offset += $limit;
            $io->text("Gallery images: $totalImages...");
        }

        $io->text("$totalImages gallery images migrated.");
    }

    // informacje → article
    private function migrateArticles($legacyDb, EntityManagerInterface $em, User $adminUser, array $catData, SymfonyStyle $io, ?string $since = null): void
    {
        $io->section('Migrating Articles (informacje)');

        $catMap = $catData['map'];
        $fallbackCatId = $catData['fallback'];
        $adminId = $adminUser->getId();
        $offset = 0;
        $limit = 500;
        $total = 0;

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM article'))
        );

        $validGalleryIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM gallery'))
        );

        $dateFilter = $since ? "WHERE czas > '$since'" : '';

        while (true) {
            $rows = $legacyDb->fetchAllAssociative("SELECT * FROM informacje $dateFilter LIMIT $limit OFFSET $offset");
            if (empty($rows)) break;

            $adminRef = $em->getReference(User::class, $adminId);

            foreach ($rows as $row) {
                if (isset($existingIds[(int)$row['id']])) continue;

                $article = new Article();
                $article->setName(mb_substr($this->cleanText($row['tytul'] ?? 'Artykuł ' . $row['id']), 0, 255));
                $article->setContent($this->cleanText($row['tresc'] ?? '', allowHtml: true));
                $article->setExcerpt(mb_substr($this->cleanText($row['tresc'] ?? ''), 0, 300));

                $createdAt = $this->parseDateSafe($row['czas'] ?? null);
                $updatedAt = $this->parseDateSafe($row['ak_czas'] ?? null) ?: $createdAt;
                $article->setCreatedAt($createdAt);
                $article->setUpdatedAt($updatedAt);
                $article->setBumpedAt($createdAt);

                $article->setStatus(ArticleStatus::PUBLISHED);
                $article->setIsEvent(false);
                $article->setHasCommentsDisabled((int)($row['komentarze_stan'] ?? 1) === 0);
                $article->setViewsNumber(0);
                $article->setCommentsNumber(0);
                $article->setAuthor($adminRef);
                $article->setUpdateAuthor($adminRef);

                $nr = $row['nr_dzialu'] ?? '';
                $catId = $catMap[$nr] ?? $fallbackCatId;
                $article->setCategory($em->getReference(Category::class, $catId));

                if (!empty($row['foto'])) {
                    $article->setImageUrl('/public/images/galeria/' . $row['foto']);
                }

                $galleryId = (int)($row['nr_galerii'] ?? 0);
                if ($galleryId > 0 && isset($validGalleryIds[$galleryId])) {
                    $article->setGallery($em->getReference(Gallery::class, $galleryId));
                }

                $this->forceId($em, $article, (int)$row['id']);
                $em->persist($article);
                $existingIds[(int)$row['id']] = true;
                $total++;
            }

            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $offset += $limit;
            $io->text("Articles: $total...");
        }

        $io->text("Total $total articles migrated.");
    }

    // komentarze → article_comment
    private function migrateComments($legacyDb, EntityManagerInterface $em, SymfonyStyle $io, ?string $since = null): void
    {
        $io->section('Migrating Comments (komentarze)');

        $offset = 0;
        $limit = 500;
        $total = 0;

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM article_comment'))
        );

        $validArticleIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM article'))
        );

        $dateFilter = $since ? "WHERE czas > '$since'" : '';

        while (true) {
            $rows = $legacyDb->fetchAllAssociative("SELECT * FROM komentarze $dateFilter LIMIT $limit OFFSET $offset");
            if (empty($rows)) break;

            foreach ($rows as $row) {
                if (isset($existingIds[(int)$row['id']])) continue;

                $articleId = (int)$row['info_id'];
                if (!isset($validArticleIds[$articleId])) continue;

                $comment = new ArticleComment();
                $comment->setArticle($em->getReference(Article::class, $articleId));
                $comment->setAuthor(mb_substr($this->cleanText($row['autor'] ?? 'Anonim'), 0, 50));
                $comment->setContent($this->cleanText($row['txt'] ?? ''));
                $comment->setIpAddress(mb_substr($row['ip'] ?? '127.0.0.1', 0, 255));
                $comment->setCreatedAt(new \DateTimeImmutable($row['czas']));
                $comment->setIsHidden(false);

                $this->forceId($em, $comment, (int)$row['id']);
                $em->persist($comment);
                $existingIds[(int)$row['id']] = true;
                $total++;
            }

            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $offset += $limit;
            $io->text("Comments: $total...");
        }

        $io->text("Total $total comments migrated.");
    }

    // firmy_lista → company (mapowanie przez COMPANY_CAT_MAP)
    private function migrateCompanies($legacyDb, EntityManagerInterface $em, SymfonyStyle $io, ?string $since = null): void
    {
        $io->section('Migrating Companies (firmy_lista)');

        $validCatIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM company_category'))
        );
        $defaultCatId = (int)$em->getConnection()->fetchOne('SELECT id FROM company_category ORDER BY id LIMIT 1');

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM company'))
        );

        $dateFilter = $since ? "WHERE data > '$since'" : '';
        $offset = 0;
        $limit = 500;
        $total = 0;

        while (true) {
            $rows = $legacyDb->fetchAllAssociative("SELECT * FROM firmy_lista $dateFilter LIMIT $limit OFFSET $offset");
            if (empty($rows)) break;

            foreach ($rows as $row) {
                if (isset($existingIds[(int)$row['id']])) continue;
                $legacyCatId = (int)($row['kategoria'] ?? 0);
                $appCatId = self::COMPANY_CAT_MAP[$legacyCatId] ?? null;

                if ($appCatId === null || !isset($validCatIds[$appCatId])) {
                    $appCatId = $defaultCatId;
                }

                $company = new Company();
                $name = $this->cleanText($row['nazwa'] ?? 'Firma ' . $row['id']);
                $company->setName(mb_substr($name, 0, 255));
                $company->setSlug($this->slugify($name) . '-' . $row['id']);
                $company->setDescription($this->cleanText($row['opis'] ?? ''));
                $company->setIsActive(true);
                $company->setIsPromoted(false);
                $company->setViews(0);

                $createdAt = $this->parseDateSafe($row['data'] ?? null);
                $company->setCreatedAt($createdAt);
                $company->setUpdatedAt($createdAt);

                if (!empty($row['banner'])) {
                    $company->setLogo('/public/images/' . $row['banner']);
                }

                $company->setCategory($em->getReference(CompanyCategory::class, $appCatId));

                $this->forceId($em, $company, (int)$row['id']);
                $em->persist($company);
                $existingIds[(int)$row['id']] = true;
                $total++;
            }

            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $offset += $limit;
        }

        $io->text("$total companies migrated.");
    }

    // ogloszenia_lista → advertisement (mapowanie przez AD_CAT_MAP)
    private function migrateAdvertisements($legacyDb, EntityManagerInterface $em, SymfonyStyle $io, ?string $since = null): void
    {
        $io->section('Migrating Advertisements (ogloszenia_lista)');

        $validCatIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM advertisement_category'))
        );
        $defaultCatId = (int)$em->getConnection()->fetchOne('SELECT id FROM advertisement_category ORDER BY id LIMIT 1');

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM advertisement'))
        );

        $dateFilter = $since ? "WHERE data > '$since'" : '';
        $offset = 0;
        $limit = 500;
        $total = 0;

        while (true) {
            $rows = $legacyDb->fetchAllAssociative("SELECT * FROM ogloszenia_lista $dateFilter LIMIT $limit OFFSET $offset");
            if (empty($rows)) break;

            foreach ($rows as $row) {
                if (isset($existingIds[(int)$row['id']])) continue;
                $legacyCatId = (int)($row['kategoria'] ?? 0);
                $appCatId = self::AD_CAT_MAP[$legacyCatId] ?? null;

                if ($appCatId === null || !isset($validCatIds[$appCatId])) {
                    $appCatId = $defaultCatId;
                }

                $ad = new Advertisement();
                $ad->setTitle(mb_substr($this->cleanText($row['nazwa'] ?? 'Ogłoszenie ' . $row['id']), 0, 255));
                $ad->setContent($this->cleanText($row['opis'] ?? ''));
                $ad->setAuthor('Anonim');
                $ad->setIpAddress(mb_substr($row['ip'] ?? '127.0.0.1', 0, 255));
                $ad->setIsActive(true);
                $ad->setIsPromoted(false);
                $ad->setViews(0);
                $ad->setClicks(0);

                $createdAt = $this->parseDateSafe($row['data'] ?? null);
                $ad->setCreatedAt($createdAt);

                if (!empty($row['banner'])) {
                    $gallery = new Gallery();
                    $gallery->setName('Ogłoszenie ' . $row['id']);
                    $gallery->setCreatedAt($createdAt);
                    $gallery->setUpdatedAt($createdAt);
                    $em->persist($gallery);
                    $em->flush();

                    $img = new GalleryImage();
                    $img->setGallery($gallery);
                    $img->setImageUrl('/public/images/' . $row['banner']);
                    $img->setPositionOrder(0);
                    $em->persist($img);
                    $em->flush();

                    $ad->setGallery($gallery);
                }

                $ad->setCategory($em->getReference(AdvertisementCategory::class, $appCatId));

                $this->forceId($em, $ad, (int)$row['id']);
                $em->persist($ad);
                $existingIds[(int)$row['id']] = true;
                $total++;
            }

            $em->flush();
            $em->clear();
            gc_collect_cycles();
            $offset += $limit;
        }

        $io->text("$total advertisements migrated.");
    }

    // sonda → poll + poll_option
    private function migratePolls($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Polls (sonda)');

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM poll'))
        );

        $rows = $legacyDb->fetchAllAssociative('SELECT * FROM sonda');

        foreach ($rows as $row) {
            if (isset($existingIds[(int)$row['id']])) continue;

            $poll = new Poll();
            $poll->setQuestion(mb_substr($this->cleanText($row['pytanie'] ?? 'Ankieta ' . $row['id']), 0, 255));
            $poll->setIsActive(($row['aktywna'] ?? 'n') === 't');
            $poll->setCreatedAt(new \DateTimeImmutable());

            $this->forceId($em, $poll, (int)$row['id']);
            $em->persist($poll);
            $em->flush();

            for ($i = 1; $i <= 7; $i++) {
                $optionText = trim($row['o' . $i] ?? '');
                if ($optionText === '') continue;

                $option = new PollOption();
                $option->setPoll($poll);
                $option->setTitle(mb_substr($this->cleanText($optionText), 0, 255));
                $option->setVotesCount((int)($row['o' . $i . 'w'] ?? 0));
                $em->persist($option);
            }

            $em->flush();
        }

        $em->clear();
        $io->text(count($rows) . ' polls migrated.');
    }

    // reklamy → promo_item (position=LEWA_1, is_active=false — do ręcznego ustawienia w panelu)
    private function migratePromoItems($legacyDb, EntityManagerInterface $em, SymfonyStyle $io): void
    {
        $io->section('Migrating Promo Items (reklamy)');

        $existingIds = array_flip(
            array_map('intval', $em->getConnection()->fetchFirstColumn('SELECT id FROM promo_item'))
        );

        $rows = $legacyDb->fetchAllAssociative('SELECT * FROM reklamy');
        $total = 0;

        foreach ($rows as $row) {
            if (isset($existingIds[(int)$row['id']])) continue;

            $promo = new PromoItem();
            $promo->setTitle(mb_substr($this->cleanText($row['opis'] ?? 'Reklama ' . $row['id']), 0, 255));
            $promo->setTargetUrl($row['link'] ?: null);
            $promo->setPosition('LEWA_1');
            $promo->setIsActive(false);
            $promo->setViewsCount(0);
            $promo->setClicksCount((int)($row['klikniec'] ?? 0));
            $promo->setCreatedAt(new \DateTimeImmutable());
            $promo->setUpdatedAt(new \DateTimeImmutable());

            $ext = ltrim($row['typ'] ?? '.jpg', '.');
            $promo->setImageUrl('/public/images/galeria/reklama/' . $row['plik'] . '.' . $ext);

            $em->persist($promo);
            $total++;
        }

        $em->flush();
        $em->clear();

        $io->text("$total promo items migrated (all inactive, position=LEWA_1).");
    }

    private function cleanText(?string $value, bool $allowHtml = false): string
    {
        $text = $value ?? '';
        if (!$allowHtml) {
            $text = strip_tags($text);
        }
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'ISO-8859-2');
        }
        return $text;
    }

    private function parseDateSafe(?string $value): \DateTimeImmutable
    {
        if (empty($value) || str_starts_with($value, '0000-00-00')) {
            return new \DateTimeImmutable();
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            return new \DateTimeImmutable();
        }
    }

    private function forceId(EntityManagerInterface $em, object $entity, int $id): void
    {
        $metadata = $em->getClassMetadata(get_class($entity));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = strtr($text, [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ]);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text ?? '', '-');
    }
}
