<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\ArticleComment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-comments',
    description: 'Imports article comments from the legacy database.',
)]
class ImportCommentsCommand extends Command
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

        $io->info('Fetching comments from legacy database...');
        
        // Zależnie od tego, jak id artykułów przeniosły się z legacy DB do nowej,
        // musimy mieć pewność, że id posta w komentarzach zgadza się z nowym ID artykułu.
        // Ponieważ nie zmienialiśmy ID artykułów podczas ich importu (auto-increment generuje te same ID 
        // lub jeśli idki nie są tożsame, musielibyśmy zapisać legacy_id w Article), 
        // założymy, że id artykułów w nowej bazie pokrywa się z `postId` ze starej.
        
        // Wyłącz middlewares, aby uniknąć memory leak
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);
        $legacyDb->getConfiguration()->setMiddlewares([]);

        $limit = 500;
        $offset = 0;
        $totalMigrated = 0;
        $missingArticlesCount = 0;

        while (true) {
            // Pobieramy komentarze złączone z postami, żeby od razu mieć tytuł posta
            $sql = "
                SELECT c.*, p.title as post_title 
                FROM comments c
                LEFT JOIN posts p ON c.postId = p.id
                ORDER BY c.id ASC 
                LIMIT $limit OFFSET $offset
            ";
            $commentsData = $legacyDb->fetchAllAssociative($sql);
            
            if (empty($commentsData)) {
                break;
            }

            foreach ($commentsData as $row) {
                // Skoro ID artykułów w nowej bazie różni się od starego (ze względu na czyszczenie i brak starych brakujących numerów),
                // musimy wyszukać artykuł po tytule, który zmigrowaliśmy wcześniej.
                $postTitle = $row['post_title'];
                
                if (!$postTitle) {
                    $missingArticlesCount++;
                    continue;
                }

                // Wyszukiwanie artykułu w nowej bazie
                // Tytuł w starej bazie mógł być długi, a my w nowej obcięliśmy go do 255 znaków (zgodnie ze skryptem importu)
                $searchTitle = mb_substr($postTitle, 0, 255);
                
                $article = $this->entityManager->getRepository(Article::class)->findOneBy(['name' => $searchTitle]);
                
                if (!$article) {
                    $missingArticlesCount++;
                    continue;
                }

                $comment = new ArticleComment();
                $comment->setArticle($article);
                $comment->setAuthor(mb_substr($row['nickname'] ?? 'Anonim', 0, 255));
                $comment->setContent($row['content'] ?? '');
                
                if (method_exists($comment, 'setIpAddress')) {
                    $comment->setIpAddress($row['ip']);
                }
                
                // Sprawdzanie czy ma właściwość createdAt
                if (method_exists($comment, 'setCreatedAt')) {
                    $createdAt = !empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable();
                    $comment->setCreatedAt($createdAt);
                }
                
                if (method_exists($comment, 'setIsActive')) {
                    // Jeżeli w starej bazie nie ma pola is_active, zakładamy, że opublikowane są aktywne
                    $comment->setIsActive(!empty($row['published_at']));
                }

                // Uwaga: obsługa zagnieżdżonych komentarzy (parent/responding_to) 
                // wymagałaby wcześniejszego zapisania rodziców i zmapowania ich nowych ID.
                // Aby to uprościć, najpierw wgrywamy wszystkie płaskie (pierwszy przelot),
                // ale jako że sortujemy po `id ASC`, to rodzice powinni pojawić się pierwsi.
                if (!empty($row['parent']) && method_exists($comment, 'setParent')) {
                    // Znajdź zmigrowanego rodzica
                    // Uwaga: Jeśli id starych komentarzy odpowiada nowym, możemy zrobić find().
                    // Ponieważ to migracja, prawdopodobnie ID komentarzy też się zgadzają, 
                    // bo zachowujemy strukturę przy autoincrement.
                    $parentComment = $this->entityManager->getRepository(ArticleComment::class)->find((int)$row['parent']);
                    if ($parentComment) {
                        $comment->setParent($parentComment);
                    }
                }

                $this->entityManager->persist($comment);
                $totalMigrated++;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            
            $offset += $limit;
            $io->text("Migrated $totalMigrated comments...");
        }

        $io->success(sprintf('Successfully imported %d comments! (Skipped %d comments due to missing articles)', $totalMigrated, $missingArticlesCount));

        return Command::SUCCESS;
    }
}