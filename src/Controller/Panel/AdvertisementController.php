<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdvertisementController extends AbstractController
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function remove(int $advertisementId, Request $request): Response
    {
        $advertisement = $this->advertisementRepository->find($advertisementId);

        if ($advertisement === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($advertisement);
        $this->entityManager->flush();

        $this->addFlash('success', 'Ogłoszenie zostało usunięte');

        return $this->redirect($request->headers->get('referer'));
    }
}
