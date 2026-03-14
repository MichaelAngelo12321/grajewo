<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Poll;
use App\Form\PollType;
use App\Repository\PollRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/panel/ankiety')]
class PollController extends AbstractController
{
    public function __construct(
        private PollRepository $pollRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'panel_poll_list', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('panel/poll/index.html.twig', [
            'polls' => $this->pollRepository->findAll(),
        ]);
    }

    #[Route('/nowa', name: 'panel_poll_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $poll = new Poll();
        $form = $this->createForm(PollType::class, $poll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($poll);
            $this->entityManager->flush();

            $this->addFlash('success', 'Ankieta została dodana.');

            return $this->redirectToRoute('panel_poll_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/poll/form.html.twig', [
            'poll' => $poll,
            'form' => $form->createView(),
            'title' => 'Dodaj ankietę',
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    #[Route('/{id}/edytuj', name: 'panel_poll_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Poll $poll): Response
    {
        $form = $this->createForm(PollType::class, $poll);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Ankieta została zaktualizowana.');

            return $this->redirectToRoute('panel_poll_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/poll/form.html.twig', [
            'poll' => $poll,
            'form' => $form->createView(),
            'title' => 'Edytuj ankietę',
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    #[Route('/{id}', name: 'panel_poll_delete', methods: ['POST'])]
    public function delete(Request $request, Poll $poll): Response
    {
        if ($this->isCsrfTokenValid('delete' . $poll->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($poll);
            $this->entityManager->flush();
            $this->addFlash('success', 'Ankieta została usunięta.');
        }

        return $this->redirectToRoute('panel_poll_list', [], Response::HTTP_SEE_OTHER);
    }
}
