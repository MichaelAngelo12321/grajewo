<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Gallery;
use App\Entity\GalleryImage;
use App\Enum\UploadDirectory;
use App\Form\GalleryType;
use App\Helper\Paginator;
use App\Repository\GalleryImageRepository;
use App\Repository\GalleryRepository;
use App\Service\FileCleaner;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GalleryController extends AbstractController
{
    public function __construct(
        private readonly GalleryRepository $galleryRepository,
        private readonly GalleryImageRepository $galleryImageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileCleaner $fileCleaner,
        private readonly FileUploader $fileUploader,
        private readonly ImageResizer $imageResizer,
    ) {
    }

    public function create(Request $request): Response
    {
        $gallery = new Gallery();
        $galleryForm = $this->createForm(GalleryType::class, $gallery);
        $galleryForm->handleRequest($request);

        if ($galleryForm->isSubmitted() && $galleryForm->isValid()) {
            $gallery->setCreatedAt(new DateTimeImmutable());
            $gallery->setUpdatedAt(new DateTimeImmutable());
            $gallery->setAuthor($this->getUser());
            $gallery->setUpdateAuthor($this->getUser());

            $files = $galleryForm->get('images')->getData();
            $descriptions = $request->request->all('image_descriptions') ?? [];

            if (!empty($files)) {
                foreach ($files as $index => $file) {
                    $imageFileName = $this->fileUploader->upload($file, UploadDirectory::GALLERY);
                    $this->imageResizer->resize($imageFileName);

                    $galleryImage = new GalleryImage();
                    $galleryImage->setImageUrl($imageFileName);
                    $galleryImage->setPositionOrder($index);
                    $galleryImage->setDescription($descriptions[$index] ?? null);

                    $galleryImage->setGallery($gallery);
                    $this->entityManager->persist($galleryImage);
                }
            }

            $this->entityManager->persist($gallery);
            $this->entityManager->flush();

            $this->addFlash('success', 'Galeria zdjęć została dodana');

            return $this->redirectToRoute('panel_gallery_list');
        }

        return $this->render('panel/gallery/create.html.twig', [
            'form' => $galleryForm->createView(),
        ]);
    }

    public function delete($id): Response
    {
        $gallery = $this->galleryRepository->find($id);

        if ($gallery === null) {
            throw $this->createNotFoundException();
        }

        foreach ($gallery->getGalleryImages() as $image) {
            $this->fileCleaner->removeFile($image->getImageUrl());
            $this->entityManager->remove($image);
        }

        $this->entityManager->remove($gallery);
        $this->entityManager->flush();

        $this->addFlash('success', 'Galeria zdjęć została usunięta');

        return $this->redirectToRoute('panel_gallery_list');
    }

    public function edit(int $id, Request $request): Response
    {
        $gallery = $this->galleryRepository->findWithSortedImages($id);

        if ($gallery === null) {
            throw $this->createNotFoundException();
        }

        $galleryForm = $this->createForm(GalleryType::class, $gallery);
        $galleryForm->handleRequest($request);

        if ($galleryForm->isSubmitted() && $galleryForm->isValid()) {
            $gallery->setUpdatedAt(new DateTimeImmutable());
            $gallery->setUpdateAuthor($this->getUser());

            $removedImageIds = array_filter(
                explode(',', $galleryForm->get('removedImages')->getData() ?? ''),
            );

            foreach ($removedImageIds as $imageId) {
                $image = $this->galleryImageRepository->find($imageId);
                if ($image && $image->getGallery() === $gallery) {
                    $this->fileCleaner->removeFile($image->getImageUrl());
                    $this->entityManager->remove($image);
                }
            }

            $imageOrder = array_filter(
                explode(',', $galleryForm->get('imageOrder')->getData() ?? ''),
            );
            $files = $galleryForm->get('images')->getData();

            $position = 0;
            foreach ($imageOrder as $order) {
                if (str_starts_with($order, 'e')) {
                    $imageId = (int)substr($order, 1);
                    $image = $this->galleryImageRepository->find($imageId);

                    if ($image && $image->getGallery() === $gallery) {
                        $image->setPositionOrder($position);
                        $this->entityManager->persist($image);
                    }
                    $position++;
                } elseif (str_starts_with($order, 'n') && !empty($files)) {
                    $fileIndex = (int)substr($order, 1);
                    if (!isset($files[$fileIndex])) {
                        continue;
                    }

                    $file = $files[$fileIndex];
                    $imageFileName = $this->fileUploader->upload($file, UploadDirectory::GALLERY);
                    $this->imageResizer->resize($imageFileName);

                    $galleryImage = new GalleryImage();
                    $galleryImage->setImageUrl($imageFileName);
                    $galleryImage->setPositionOrder($position);
                    $galleryImage->setGallery($gallery);

                    $this->entityManager->persist($galleryImage);
                    $position++;
                }
            }

            $descriptions = $request->request->all('image_descriptions') ?? [];
            foreach ($descriptions as $imageId => $description) {
                $image = $this->galleryImageRepository->find($imageId);
                if ($image && $image->getGallery() === $gallery) {
                    $image->setDescription($description);
                    $this->entityManager->persist($image);
                }
            }

            $this->entityManager->persist($gallery);
            $this->entityManager->flush();

            $this->addFlash('success', 'Galeria zdjęć została zaktualizowana');

            return $this->redirectToRoute('panel_gallery_list');
        }

        $images = $gallery->getGalleryImages()->toArray();
        usort($images, function ($a, $b) {
            return $a->getPositionOrder() <=> $b->getPositionOrder();
        });

        return $this->render('panel/gallery/edit.html.twig', [
            'form' => $galleryForm->createView(),
            'gallery' => $gallery,
            'sortedImages' => $images,
        ]);
    }

    public function list(Request $request): Response
    {
        $itemsPerPage = (int)$request->get('number', 15);
        $page = (int)$request->get('page', 1);
        $criteria = [];
        $galleries = $this->galleryRepository->findAllWithFirstImage(
            $criteria,
            ['createdAt' => 'DESC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage,
        );

        return $this->render('panel/gallery/list.html.twig', [
            'galleries' => $galleries,
            'paginator' => new Paginator(
                $this->galleryRepository->count($criteria),
                $itemsPerPage,
                $page,
                $request->getUri(),
            ),
        ]);
    }
}
