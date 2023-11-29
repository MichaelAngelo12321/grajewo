<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\CategoryCachedRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    public function __construct(private CategoryCachedRepository $categoryRepository)
    {
    }

    public function index(): Response
    {
        $topCategory = $this->categoryRepository->findTopCategory();
        $categories = $this->categoryRepository->findAll();

        return $this->render(
            'home/index.html.twig',
            [
                'topCategory' => $topCategory,
                'categories' => $categories,
            ],
        );
    }
}
