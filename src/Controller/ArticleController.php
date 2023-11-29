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
        $articles = $this->articleRepository->findLatestByCategory($category, 10);
        $totalArticlesNumber = $this->articleRepository->count(['category' => $category]);

        return $this->render('article/list.html.twig', [
            'articles' => $articles,
            'category' => $category,
            'paginator' => new Paginator(
                $totalArticlesNumber,
                10,
                $request->query->getInt('p', 1),
                $request->getPathInfo().'?p=(:num)'
            ),
        ]);
    }
}
