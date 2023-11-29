<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Helper\Paginator;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
    ) {
    }

    public function details(string $slug, int $id, Category $category): Response
    {
        $article = $this->articleRepository->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        $this->articleRepository->increaseViewsNumber($article);

        return $this->render('article/details.html.twig', [
            'article' => $article,
            'id' => $id,
            'slug' => $slug,
            'category' => $category,
        ]);
    }

    public function list(Category $category, Request $request): Response
    {
        $currentPage = $request->query->getInt('p', 1);
        $itemsPerPage = 10;
        $articles = $this->articleRepository->findLatestByCategory($category, $itemsPerPage, $currentPage - 1);
        $totalArticlesNumber = $this->articleRepository->count(['category' => $category]);

        dump($articles);
        return $this->render('article/list.html.twig', [
            'articles' => $articles,
            'category' => $category,
            'paginator' => new Paginator(
                $totalArticlesNumber,
                $itemsPerPage,
                $currentPage,
                $request->getPathInfo() . '?p=(:num)'
            ),
        ]);
    }
}
