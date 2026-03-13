<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\GasStation;
use App\Entity\GasStationPrice;
use App\Form\GasStationType;
use App\Repository\GasStationPriceRepository;
use App\Repository\GasStationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GasPriceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GasStationRepository $gasStationRepository,
        private GasStationPriceRepository $gasStationPriceRepository,
    ) {
    }

    public function changeStationOrder(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $elementsOrder = $requestData['elementsOrder'];

        foreach ($elementsOrder as $elementId => $order) {
            $category = $this->gasStationRepository->find($elementId);
            $category->setPositionOrder($order);
            $this->entityManager->persist($category);
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    public function createStation(Request $request): Response
    {
        $station = new GasStation();
        $form = $this->createForm(GasStationType::class, $station);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lastStation = $this->gasStationRepository->findOneBy([], ['positionOrder' => 'DESC']);

            $station->setPositionOrder($lastStation ? $lastStation->getPositionOrder() + 1 : 1);

            $this->entityManager->persist($station);
            $this->entityManager->flush();

            $this->addFlash('success', 'Stacja paliw została dodana');

            return $this->redirectToRoute('panel_gas_station');
        }

        return $this->render('panel/gas_price/create_station.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function deleteStation(int $id): Response
    {
        $station = $this->gasStationRepository->find($id);

        if (!$station) {
            throw $this->createNotFoundException('Gas station not found');
        }

        $this->entityManager->remove($station);

        $stations = $this->gasStationRepository->findBy(['positionOrder' => 'ASC']);
        $positionOrder = 0;

        foreach ($stations as $station) {
            $station->setPositionOrder($positionOrder);
            $this->entityManager->persist($station);
            $positionOrder++;
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Stacja paliw została usunięta');

        return $this->redirectToRoute('panel_gas_station');
    }

    public function editStation(int $id, Request $request): Response
    {
        $station = $this->gasStationRepository->find($id);

        if (!$station) {
            throw $this->createNotFoundException('Gas station not found');
        }

        $form = $this->createForm(GasStationType::class, $station);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($station);
            $this->entityManager->flush();

            $this->addFlash('success', 'Stacja paliw została zaktualizowana');

            return $this->redirectToRoute('panel_gas_station');
        }

        return $this->render('panel/gas_price/edit_station.html.twig', [
            'station' => $station,
            'form' => $form->createView(),
        ]);
    }

    public function setPrices(Request $request): Response
    {
        $prices = $request->get('prices');

        foreach ($prices as $stationId => $stationPrices) {
            $station = $this->gasStationRepository->find($stationId);

            if (!$station) {
                throw $this->createNotFoundException('Gas station not found');
            }

            $types = ['diesel', 'unleaded', 'superUnleaded', 'liquidGas'];

            foreach ($types as $type) {
                if (isset($stationPrices[$type])) {
                    $currentPrice = $this->gasStationPriceRepository->findTodayStationPrices($station, $type);

                    if (!$currentPrice) {
                        $currentPrice = new GasStationPrice();
                        $currentPrice->setStation($station);
                        $currentPrice->setDate(new DateTimeImmutable());
                        $currentPrice->setType($type);
                        $currentPrice->setIsPublished(true);
                    }

                    $currentPrice->setPrice($stationPrices[$type] > 0 ? (float)$stationPrices[$type] : null);

                    $this->entityManager->persist($currentPrice);
                }
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Ceny zostały zaktualizowane');

        return $this->redirectToRoute('panel_gas_station');
    }

    public function stationList(): Response
    {
        $stations = $this->gasStationRepository->findBy([], ['positionOrder' => 'ASC']);
        $stationPrices = [];

        foreach ($stations as $station) {
            $todayStationPrices = $this->gasStationPriceRepository->findLatestStationPrice($station);

            if (!empty($todayStationPrices)) {
                foreach ($todayStationPrices as $todayStationPrice) {
                    $stationPrices[$station->getId()][$todayStationPrice->getType()] = $todayStationPrice->getPrice();
                }
            }
        }

        return $this->render('panel/gas_price/station_list.html.twig', [
            'stations' => $stations,
            'stationPrices' => $stationPrices,
        ]);
    }
}
