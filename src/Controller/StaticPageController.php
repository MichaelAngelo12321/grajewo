<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\StaticPageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class StaticPageController extends AbstractController
{
    public function __construct(
        private StaticPageRepository $staticPageRepository,
    ) {
    }

    public function show(string $slug): Response
    {
        $staticPage = $this->staticPageRepository->findOneBy(['slug' => $slug]);

        if (!$staticPage) {
            throw $this->createNotFoundException('Strona nie została znaleziona');
        }

        return $this->render('app/static_page/show.html.twig', [
            'staticPage' => $staticPage,
        ]);
    }
}

