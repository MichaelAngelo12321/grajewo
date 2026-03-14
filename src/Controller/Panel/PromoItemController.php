<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\PromoItem;
use App\Enum\UploadDirectory;
use App\Form\PromoItemType;
use App\Helper\Paginator;
use App\Repository\PromoItemRepository;
use App\Service\FileCleaner;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PromoItemController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileCleaner $fileCleaner,
        private FileUploader $fileUploader,
        private ImageResizer $imageResizer,
        private PromoItemRepository $promoItemRepository,
    ) {
    }

    public function create(Request $request): Response
    {
        $promoItem = new PromoItem();
        $promoForm = $this->createForm(PromoItemType::class, $promoItem);
        $promoForm->handleRequest($request);

        if ($promoForm->isSubmitted() && $promoForm->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $promoForm->get('imageUrl')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::PROMO_ITEM);
                $this->imageResizer->resize($imageFileName);

                $promoItem->setImageUrl($imageFileName);
            }

            $promoItem->setClicksCount(0);
            $promoItem->setViewsCount(0);
            $promoItem->setCreatedAt(new DateTimeImmutable());
            $promoItem->setUpdatedAt(new DateTimeImmutable());

            $this->entityManager->persist($promoItem);
            $this->entityManager->flush();

            $this->addFlash('success', 'Reklama została dodana');

            return $this->redirectToRoute('panel_ad_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/promo/item/create.html.twig', [
            'form' => $promoForm->createView(),
        ], $promoForm->isSubmitted() && !$promoForm->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function edit(int $id, Request $request): Response
    {
        $promoItem = $this->promoItemRepository->find($id);

        if ($promoItem === null) {
            throw $this->createNotFoundException();
        }

        $promoItemImage = $promoItem->getImageUrl();
        $promoItemForm = $this->createForm(PromoItemType::class, $promoItem);
        $promoItemForm->handleRequest($request);

        if ($promoItemForm->isSubmitted() && $promoItemForm->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $promoItemForm->get('imageUrl')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::PROMO_ITEM);
                $this->imageResizer->resize($imageFileName);
                $promoItem->setImageUrl($imageFileName);
                $this->fileCleaner->removeFile($promoItemImage);
            }

            $promoItem->setUpdatedAt(new DateTimeImmutable());

            $this->entityManager->persist($promoItem);
            $this->entityManager->flush();

            $this->addFlash('success', 'Reklama została zaktualizowana');

            return $this->redirectToRoute('panel_ad_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/promo/item/edit.html.twig', [
            'promoItem' => $promoItem,
            'form' => $promoItemForm->createView(),
        ], $promoItemForm->isSubmitted() && !$promoItemForm->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function delete(int $id, Request $request): Response
    {
        $promoItem = $this->promoItemRepository->find($id);

        if ($promoItem === null) {
            throw $this->createNotFoundException();
        }

        if ($promoItem->getImageUrl()) {
            $this->fileCleaner->removeFile($promoItem->getImageUrl());
        }

        $this->entityManager->remove($promoItem);
        $this->entityManager->flush();

        $this->addFlash('success', 'Reklama została usunięta');

        return $request->headers->has('referer')
            ? $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER)
            : $this->redirectToRoute('panel_ad_list', [], Response::HTTP_SEE_OTHER);
    }

    public function list(Request $request): Response
    {
        $itemsPerPage = (int)$request->get('number', 15);
        $page = (int)$request->get('page', 1);
        $criteria = [];
        $promoItems = $this->promoItemRepository->findBy($criteria, ['createdAt' => 'DESC'], $itemsPerPage, ($page - 1) * $itemsPerPage);
        $slotCounts = $this->promoItemRepository->countActivePerSlot();

        return $this->render('panel/promo/item/list.html.twig', [
            'promoItems' => $promoItems,
            'slotCounts' => $slotCounts,
            'paginator' => new Paginator(
                $this->promoItemRepository->count($criteria),
                $itemsPerPage,
                $page,
                $request->getUri(),
            ),
        ]);
    }
}
