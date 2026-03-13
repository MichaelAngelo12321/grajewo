<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PromoItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

class PromoItemController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PromoItemRepository $promoItemRepository
    ) {
    }

    #[Route('/promo/redirect', name: 'promo_redirect')]
    public function targetUrlRedirect(Request $request): Response
    {
        $promoItem = $this->promoItemRepository->find($request->get('id'));

        if ($promoItem && $promoItem->isIsActive()) {
            $this->promoItemRepository->increaseClicks($promoItem);
            
            if ($promoItem->getTargetUrl()) {
                return $this->redirect($promoItem->getTargetUrl());
            }
        }

        return $this->redirectToRoute('homepage');
    }

    #[Route('/promo/render/{slot}', name: 'promo_render_slot')]
    public function renderSlot(string $slot): Response
    {
        $promoItem = $this->promoItemRepository->findBySlot($slot);

        if ($promoItem) {
            $this->promoItemRepository->increaseViews($promoItem);
            $this->entityManager->flush();
        }

        $response = $this->render('app/widgets/_promo_space_content.html.twig', [
            'promoItem' => $promoItem,
        ]);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
