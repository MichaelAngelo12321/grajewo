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
    private const NEXT_SUNDAY_IS_SHOPPING = 'nextSundayIsShopping';

    public function __construct(
        private readonly \App\Repository\AdvertisementRepository $advertisementRepository,
        private readonly \App\Repository\CompanyRepository $companyRepository,
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
            ->findOneBy(['name' => self::NEXT_SUNDAY_IS_SHOPPING])
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

        // advertisements
        $advertisements = $this->advertisementRepository->findBy(['isActive' => false], ['createdAt' => 'DESC']);

        // companies
        $companies = $this->companyRepository->findBy(['isActive' => false], ['createdAt' => 'DESC']);

        return $this->render('panel/dashboard/index.html.twig', [
            'advertisements' => $advertisements,
            'companies' => $companies,
            'dailyImages' => $dailyImages,
            'dailyVideos' => $dailyVideos,
            'daysOfWeek' => $daysOfWeek,
            'gasStationPrices' => $gasStationPrices,
            'nextSunday' => $nextSunday,
            'nextSundayIsShopping' => $nextSundayIsShopping,
            'pharmacyDuties' => $this->pharmacyDutyRepository->findAll(),
        ]);
    }

    public function publishAdvertisement(int $advertisementId): Response
    {
        $advertisement = $this->advertisementRepository->find($advertisementId);

        if ($advertisement === null) {
            throw $this->createNotFoundException();
        }

        $advertisement->setIsActive(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Ogłoszenie zostało opublikowane');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function publishDailyImage(int $imageId): Response
    {
        $this->publishEntity($this->dailyImageRepository->find($imageId));
        $this->addFlash('success', 'Obrazek został opublikowany');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function publishDailyVideo(int $videoId): Response
    {
        $this->publishEntity($this->dailyVideoRepository->find($videoId));
        $this->addFlash('success', 'Wideo zostało opublikowane');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
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

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function removeDailyImage(int $imageId): Response
    {
        $dailyImage = $this->dailyImageRepository->find($imageId);

        $this->removeEntity($dailyImage, $dailyImage->getImageUrl());
        $this->addFlash('success', 'Obrazek został usunięty');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function removeDailyVideo(int $videoId): Response
    {
        $dailyVideo = $this->dailyVideoRepository->find($videoId);

        $this->removeEntity($dailyVideo);
        $this->addFlash('success', 'Wideo zostało usunięte');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
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

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function saveNextSundayIsShopping(Request $request): Response
    {
        $nextSundayIsShopping = $request->get(self::NEXT_SUNDAY_IS_SHOPPING, false);
        $nextSundayIsShopping = $nextSundayIsShopping === 'on' ? 'true' : 'false';

        $this->settingRepository->set(self::NEXT_SUNDAY_IS_SHOPPING, $nextSundayIsShopping);
        $this->addFlash('success', 'Ustawienia niedzieli handlowej zostały zapisane');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function publishCompany(int $companyId): Response
    {
        $company = $this->companyRepository->find($companyId);

        if ($company === null) {
            throw $this->createNotFoundException();
        }

        $company->setIsActive(true);
        $this->entityManager->flush();

        $this->addFlash('success', 'Firma została zaakceptowana');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    public function removeCompany(int $companyId): Response
    {
        $company = $this->companyRepository->find($companyId);

        if ($company === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($company);
        $this->entityManager->flush();

        $this->addFlash('success', 'Firma została usunięta');

        return $this->redirectToRoute('panel_dashboard', [], Response::HTTP_SEE_OTHER);
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

    private function publishEntity($entity): void
    {
        if ($entity === null) {
            throw $this->createNotFoundException();
        }

        $entity->setIsPublished(true);

        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    private function removeEntity($entity, ?string $fileUrl = null): void
    {
        if ($entity === null) {
            throw $this->createNotFoundException();
        }

        if ($fileUrl !== null) {
            $this->fileCleaner->removeFile($fileUrl);
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
