<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GalleryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends AbstractController
{
    public function __construct(
        private readonly GalleryRepository $galleryRepository
    ) {
    }

    public function details(int $id): Response
    {
        $gallery = $this->galleryRepository->find($id);

        if (!$gallery) {
            throw $this->createNotFoundException('Gallery not found');
        }

        return $this->render('app/gallery/details.html.twig', [
            'gallery' => $gallery,
        ]);
    }

    public function list(): Response
    {
        $galleries = $this->galleryRepository->findBy([], ['id' => 'DESC']);

        return $this->render('app/gallery/list.html.twig', [
            'galleries' => $galleries,
        ]);
    }
}
