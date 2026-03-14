<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Setting;
use App\Form\SettingType;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panel/ustawienia')]
class SettingController extends AbstractController
{
    public function __construct(
        private SettingRepository $settingRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'panel_setting_list', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('panel/setting/index.html.twig', [
            'settings' => $this->settingRepository->findAll(),
        ]);
    }

    #[Route('/nowe', name: 'panel_setting_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $setting = new Setting();
        $form = $this->createForm(SettingType::class, $setting, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingSetting = $this->settingRepository->find($setting->getName());
            if ($existingSetting) {
                $this->addFlash('danger', 'Ustawienie o podanej nazwie już istnieje.');
                return $this->redirectToRoute('panel_setting_new', [], Response::HTTP_SEE_OTHER);
            }

            $this->entityManager->persist($setting);
            $this->entityManager->flush();

            $this->addFlash('success', 'Ustawienie zostało dodane.');

            return $this->redirectToRoute('panel_setting_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/setting/form.html.twig', [
            'setting' => $setting,
            'form' => $form->createView(),
            'title' => 'Dodaj ustawienie',
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    #[Route('/{name}/edytuj', name: 'panel_setting_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $name): Response
    {
        $setting = $this->settingRepository->find($name);

        if (!$setting) {
            throw $this->createNotFoundException('Ustawienie nie istnieje.');
        }

        $form = $this->createForm(SettingType::class, $setting, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Ustawienie zostało zaktualizowane.');

            return $this->redirectToRoute('panel_setting_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/setting/form.html.twig', [
            'setting' => $setting,
            'form' => $form->createView(),
            'title' => 'Edytuj ustawienie',
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    #[Route('/{name}', name: 'panel_setting_delete', methods: ['POST'])]
    public function delete(Request $request, string $name): Response
    {
        $setting = $this->settingRepository->find($name);

        if ($this->isCsrfTokenValid('delete' . $setting->getName(), $request->request->get('_token'))) {
            $this->entityManager->remove($setting);
            $this->entityManager->flush();
            $this->addFlash('success', 'Ustawienie zostało usunięte.');
        }

        return $this->redirectToRoute('panel_setting_list', [], Response::HTTP_SEE_OTHER);
    }
}
