<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PromoItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PromoItemController extends AbstractController
{
    public function __construct(private PromoItemRepository $promoItemRepository)
    {
    }

    public function targetUrlRedirect(Request $request): Response
    {
        $promoItem = $this->promoItemRepository->find($request->get('id'));

        if ($promoItem) {
            $this->promoItemRepository->increaseClicks($promoItem);
        }

        return $this->redirect(base64_decode($request->get('redirectUrl')));
    }
}
