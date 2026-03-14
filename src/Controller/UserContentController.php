<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\DailyImage;
use App\Entity\DailyVideo;
use App\Entity\GasStationPrice;
use App\Enum\UploadDirectory;
use App\Form\DailyImageType;
use App\Form\DailyVideoType;
use App\Repository\Cached\GasStationCachedRepository;
use App\Repository\GasStationRepository;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use App\Service\UserActivity;
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
        private GasStationRepository $gasStationRepository,
        private GasStationCachedRepository $gasStationCachedRepository,
        private ImageResizer $imageResizer,
        private UserActivity $userActivity,
    ) {
    }

    public function addGasStationPrices(Request $request): Response
    {
        $gasStations = $this->gasStationCachedRepository->findStations();

        if ($request->isMethod(Request::METHOD_POST)) {
            $csrfToken = $request->request->get('_csrf_token');

            if (!$this->isCsrfTokenValid('gas_station_prices_add', $csrfToken)) {
                $this->addFlash('danger', 'Nieprawidłowy token CSRF');

                return $this->redirectToRoute('user_content_add_gas_station_prices_form', [], Response::HTTP_SEE_OTHER);
            }

            if (!$this->userActivity->canUserPerformAction(
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            )) {
                $this->addFlash('danger', 'Musisz poczekać 2 minuty przed dodaniem kolejnej treści');

                return $this->redirectToRoute('user_content_add_gas_station_prices_form', [], Response::HTTP_SEE_OTHER);
            }

            $prices = $request->get('prices');
            $emptyPrices = array_filter($prices, fn ($price) => $price === '');

            if (count($emptyPrices) === count($prices)) {
                $this->addFlash('danger', 'Nie podano żadnej ceny');

                return $this->redirectToRoute('user_content_add_gas_station_prices_form', [], Response::HTTP_SEE_OTHER);
            }

            $station = $this->gasStationRepository->find($request->get('station'));

            if ($station === null) {
                $this->addFlash('danger', 'Nie znaleziono stacji');

                return $this->redirectToRoute('user_content_add_gas_station_prices_form', [], Response::HTTP_SEE_OTHER);
            }

            foreach ($prices as $type => $price) {
                if ($price === '') {
                    continue;
                }

                $gasStationPrice = new GasStationPrice();
                $gasStationPrice->setStation($station);
                $gasStationPrice->setType($type);
                $gasStationPrice->setPrice((float) $price);
                $gasStationPrice->setIsPublished(false);
                $gasStationPrice->setDate(new DateTimeImmutable());
                $gasStationPrice->setIpAddress($request->getClientIp());

                $this->entityManager->persist($gasStationPrice);
            }

            $this->userActivity->recordUserActivity($request->getClientIp(), $request->headers->get('User-Agent'));

            $this->entityManager->flush();

            return $this->redirectToRoute('user_content_thank_you', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('app/user_content/add_gas_station_prices.html.twig', [
            'gasStations' => $gasStations,
        ]);
    }

    public function addImage(Request $request): Response
    {
        $image = new DailyImage();
        $form = $this->createForm(DailyImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->userActivity->canUserPerformAction(
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            )) {
                $this->addFlash('danger', 'Musisz poczekać 2 minuty przed dodaniem kolejnej treści');

                return $this->redirectToRoute('user_content_add_image_form', [], Response::HTTP_SEE_OTHER);
            }

            $image->setIpAddress($request->getClientIp());
            $image->setCreatedAt(new DateTimeImmutable());
            $image->setIsPublished(false);

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::DAILY_IMAGE);
                $this->imageResizer->resize($imageFileName);

                $image->setImageUrl($imageFileName);
            }

            $this->userActivity->recordUserActivity($request->getClientIp(), $request->headers->get('User-Agent'));

            $this->entityManager->persist($image);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('user_content_thank_you'), Response::HTTP_SEE_OTHER);
        }

        return $this->render('app/user_content/add_image.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    public function addVideo(Request $request): Response
    {
        $video = new DailyVideo();
        $form = $this->createForm(DailyVideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->userActivity->canUserPerformAction(
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            )) {
                $this->addFlash('danger', 'Musisz poczekać 2 minuty przed dodaniem kolejnej treści');

                return $this->redirectToRoute('user_content_add_video_form', [], Response::HTTP_SEE_OTHER);
            }

            $video->setIpAddress($request->getClientIp());
            $video->setCreatedAt(new DateTimeImmutable());
            $video->setIsPublished(false);

            $this->userActivity->recordUserActivity($request->getClientIp(), $request->headers->get('User-Agent'));

            $this->entityManager->persist($video);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('user_content_thank_you'), Response::HTTP_SEE_OTHER);
        }

        return $this->render('app/user_content/add_video.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $form->isSubmitted() && !$form->isValid() ? 422 : 200));
    }

    public function thankYouPage(): Response
    {
        return $this->render('app/user_content/thank_you_page.html.twig');
    }
}
