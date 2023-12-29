<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DailyImage;
use App\Entity\DailyVideo;
use App\Enum\UploadDirectory;
use App\Form\DailyImageType;
use App\Form\DailyVideoType;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserContentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileUploader $fileUploader,
        private ImageResizer $imageResizer,
    ) {
    }

    public function addGasStationPrices(): Response
    {
        return $this->render('app/user_content/add_gas_station_prices.html.twig');
    }

    public function addImage(Request $request): Response
    {
        $image = new DailyImage();
        $form = $this->createForm(DailyImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image->setIpAddress($request->getClientIp());
            $image->setCreatedAt(new DateTimeImmutable());
            $image->setIsPublished(false);

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::USER_REPORT);
                $this->imageResizer->resize($imageFileName);

                $image->setImageUrl($imageFileName);
            }

            $this->entityManager->persist($image);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('user_content_thank_you'));
        }

        return $this->render('app/user_content/add_image.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function addVideo(Request $request): Response
    {
        $video = new DailyVideo();
        $form = $this->createForm(DailyVideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $video->setIpAddress($request->getClientIp());
            $video->setCreatedAt(new DateTimeImmutable());
            $video->setIsPublished(false);

            $this->entityManager->persist($video);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('user_content_thank_you'));
        }

        return $this->render('app/user_content/add_video.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function thankYouPage(): Response
    {
        return $this->render('app/user_content/thank_you_page.html.twig');
    }
}
