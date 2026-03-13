<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\AdvertisementRepository;
use App\Repository\Cached\ArticleCachedRepository;
use App\Repository\Cached\CategoryCachedRepository;
use App\Repository\CompanyRepository;
use App\Service\PolishCalendarEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly ArticleCachedRepository $articleRepository,
        private readonly CategoryCachedRepository $categoryRepository,
        private readonly CompanyRepository $companyRepository,
        private readonly PolishCalendarEvent $polishCalendarEvent,
    ) {
    }

    public function index(): Response
    {
        $this->polishCalendarEvent->getHolidays();

        $topCategory = $this->categoryRepository->findTopCategory();
        $topCategoryArticles = $this->articleRepository->findLatestArticlesFromCategory($topCategory, 7);
        $mostPopularArticles = $this->articleRepository->findMostPopularArticles(4);
        $promotedAdvertisements = $this->advertisementRepository->findPromotedAdvertisements(4);
        $promotedCompanies = $this->companyRepository->findBy(['isActive' => true, 'isPromoted' => true], ['name' => 'ASC'], 6);

        $categories = $this->categoryRepository->findAll();
        $articles = [];

        foreach ($categories as $category) {
            $articles[$category->getId()] = $this->articleRepository->findLatestArticlesFromCategory($category, 4);
        }

        return $this->render(
            'app/home/index.html.twig',
            [
                'articles' => $articles,
                'categories' => $categories,
                'promotedAdvertisements' => $promotedAdvertisements,
                'promotedCompanies' => $promotedCompanies,
                'mostPopularArticles' => $mostPopularArticles,
                'topCategory' => $topCategory,
                'topCategoryArticles' => $topCategoryArticles,
            ],
        );
    }
}
