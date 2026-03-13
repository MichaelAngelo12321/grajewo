<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\Paginator;
use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/szukaj', name: 'search')]
    public function index(Request $request, ArticleRepository $articleRepository): Response
    {
        $query = $request->query->get('search_query');
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 10;

        if (!$query) {
            return $this->render('app/search/index.html.twig', [
                'pagination' => null,
                'articles' => [],
                'query' => null,
            ]);
        }

        $criteria = ['name' => ['FULLTEXT', $query]];

        $totalItems = $articleRepository->count($criteria);

        $articles = $articleRepository->findBy(
            $criteria,
            null,
            $itemsPerPage,
            ($page - 1) * $itemsPerPage
        );

        return $this->render('app/search/index.html.twig', [
            'articles' => $articles,
            'query' => $query,
            'paginator' => new Paginator(
                $totalItems,
                $itemsPerPage,
                $page,
                $request->getRequestUri()
            ),
        ]);
    }
}
