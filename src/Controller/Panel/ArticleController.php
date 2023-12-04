<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Enum\ArticleStatus;
use App\Helper\Paginator;
use App\Repository\ArticleRepository;
use App\Repository\Cached\CacheKeyPrefix;
use App\Repository\Cached\CategoryCachedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CacheItemPoolInterface $cachePool,
        private CategoryCachedRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function changeStatus(int $id, int $status): Response
    {
        $article = $this->articleRepository->find($id);

        if ($article === null) {
            throw $this->createNotFoundException();
        }

        $article->setStatus(ArticleStatus::from($status));

        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->clearCache();

        $this->addFlash('success', 'Status został zmieniony');

        return $this->redirectToRoute('panel_article_list');
    }

    private function clearCache(): void
    {
        $this->cachePool->clear(CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY);
        $this->cachePool->clear(CacheKeyPrefix::ARTICLE_MOST_POPULAR);
    }

    public function list(Request $request): Response
    {
        $articlesNumber = (int) $request->get('number', 15);
        $page = (int) $request->get('page', 1);
        $criteria = [];

        if ($request->get('category', '') !== '') {
            $criteria['category'] = $request->get('category');
        }

        if ($request->get('search_query', '') !== '') {
            $criteria['name'] = ['FULLTEXT', $request->get('search_query')];
        }

        $articles = $this->articleRepository->findBy(
            $criteria,
            ['id' => 'DESC'],
            $articlesNumber,
            $page - 1,
        );
        $categories = $this->categoryRepository->findAll();

        return $this->render('panel/article/list.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'paginator' => new Paginator(
                $this->articleRepository->count($criteria),
                $articlesNumber,
                $page,
                $request->getUri()
            ),
        ]);
    }

}
