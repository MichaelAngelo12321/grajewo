<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PromoItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PromoController extends AbstractController
{
    public function __construct(
        private readonly PromoItemRepository $promoItemRepository,
    ) {
    }

    #[Route('/promo/r/{id}', name: 'promo_redirect')]
    public function redirectAction(int $id): Response
    {
        $promoItem = $this->promoItemRepository->find($id);

        if (!$promoItem) {
            return $this->redirectToRoute('home');
        }

        $this->promoItemRepository->increaseClicks($promoItem);

        if ($promoItem->getTargetUrl()) {
            return new RedirectResponse($promoItem->getTargetUrl());
        }

        return $this->redirectToRoute('home');
    }
}
