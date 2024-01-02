<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\PharmacyDuty;
use App\Repository\DailyImageRepository;
use App\Repository\DailyVideoRepository;
use App\Repository\GasStationPriceRepository;
use App\Repository\PharmacyDutyRepository;
use App\Repository\SettingRepository;
use App\Service\FileCleaner;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    public function __construct(
        private DailyImageRepository $dailyImageRepository,
        private DailyVideoRepository $dailyVideoRepository,
        private EntityManagerInterface $entityManager,
        private FileCleaner $fileCleaner,
        private GasStationPriceRepository $gasStationPriceRepository,
        private PharmacyDutyRepository $pharmacyDutyRepository,
        private SettingRepository $settingRepository,
    ) {
    }

    public function index(): Response
    {
        // shopping sunday
        $daysOfWeek = [
            'Poniedziałek',
            'Wtorek',
            'Środa',
            'Czwartek',
            'Piątek',
            'Sobota',
            'Niedziela',
        ];
        $nextSunday = new DateTime('next sunday');
        $nextSundayIsShopping = $this->settingRepository
            ->findOneBy(['name' => 'nextSundayIsShopping'])
            ?->getValue() === 'true' ?? false;

        // gas station prices
        $gasStationPrices = $this->gasStationPriceRepository->findBy(
            [
                'isPublished' => false,
            ], [
                'date' => 'DESC',
            ],
        );

        // daily images and videos
        $dailyImages = $this->dailyImageRepository->findBy(['isPublished' => false], ['id' => 'DESC']);
        $dailyVideos = $this->dailyVideoRepository->findBy(['isPublished' => false], ['id' => 'DESC']);

        return $this->render('panel/dashboard/index.html.twig', [
            'dailyImages' => $dailyImages,
            'dailyVideos' => $dailyVideos,
            'daysOfWeek' => $daysOfWeek,
            'gasStationPrices' => $gasStationPrices,
            'nextSunday' => $nextSunday,
            'nextSundayIsShopping' => $nextSundayIsShopping,
            'pharmacyDuties' => $this->pharmacyDutyRepository->findAll(),
        ]);
    }

    public function publishDailyImage(int $imageId): Response
    {
        $dailyImage = $this->dailyImageRepository->find($imageId);

        if ($dailyImage === null) {
            throw $this->createNotFoundException();
        }

        $dailyImage->setIsPublished(true);

        $this->entityManager->persist($dailyImage);
        $this->entityManager->flush();

        $this->addFlash('success', 'Obrazek został opublikowany');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function publishDailyVideo(int $videoId): Response
    {
        $dailyVideo = $this->dailyVideoRepository->find($videoId);

        if ($dailyVideo === null) {
            throw $this->createNotFoundException();
        }

        $dailyVideo->setIsPublished(true);

        $this->entityManager->persist($dailyVideo);
        $this->entityManager->flush();

        $this->addFlash('success', 'Wideo zostało opublikowane');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function publishUserGasStationPrice(int $gasStationPriceId): Response
    {
        $gasStationPrice = $this->gasStationPriceRepository->find($gasStationPriceId);

        if ($gasStationPrice === null) {
            throw $this->createNotFoundException();
        }

        $gasStationPrice->setIsPublished(true);

        $this->entityManager->persist($gasStationPrice);
        $this->entityManager->flush();

        $this->addFlash('success', 'Cena została opublikowana');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function removeDailyImage(int $imageId): Response
    {
        $dailyImage = $this->dailyImageRepository->find($imageId);

        if ($dailyImage === null) {
            throw $this->createNotFoundException();
        }

        $this->fileCleaner->removeFile($dailyImage->getImageUrl());

        $this->entityManager->remove($dailyImage);
        $this->entityManager->flush();

        $this->addFlash('success', 'Obrazek został usunięty');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function removeDailyVideo(int $videoId): Response
    {
        $dailyVideo = $this->dailyVideoRepository->find($videoId);

        if ($dailyVideo === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($dailyVideo);
        $this->entityManager->flush();

        $this->addFlash('success', 'Wideo zostało usunięte');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function removeUserGasStationPrice(int $gasStationPriceId): Response
    {
        $gasStationPrice = $this->gasStationPriceRepository->find($gasStationPriceId);

        if ($gasStationPrice === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($gasStationPrice);
        $this->entityManager->flush();

        $this->addFlash('success', 'Cena została usunięta');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function saveNextSundayIsShopping(Request $request): Response
    {
        $nextSundayIsShopping = $request->get('nextSundayIsShopping', false);
        $nextSundayIsShopping = $nextSundayIsShopping === 'on' ? 'true' : 'false';

        $this->settingRepository->set('nextSundayIsShopping', $nextSundayIsShopping);
        $this->addFlash('success', 'Ustawienia niedzieli handlowej zostały zapisane');

        return $this->redirectToRoute('panel_dashboard');
    }

    public function savePharmacyDuty(Request $request): Response
    {
        $this->entityManager->createQuery('DELETE FROM App\Entity\PharmacyDuty')->execute();

        if ($request->get('pharmacyDuty')) {
            foreach ($request->get('pharmacyDuty') as $day => $pharmacyDuty) {
                $this->entityManager->persist(
                    (new PharmacyDuty())
                        ->setDay($day)
                        ->setName($pharmacyDuty['name'])
                        ->setAddress($pharmacyDuty['address']),
                );
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Dyżury aptek zostały zapisane');
        } else {
            $this->addFlash('danger', 'Brak pola pharmacyDuty w formularzu');
        }

        return $this->redirectToRoute('panel_dashboard');
    }
}
