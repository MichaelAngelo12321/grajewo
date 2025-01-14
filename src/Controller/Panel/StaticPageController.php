<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\StaticPage;
use App\Form\StaticPageType;
use App\Helper\Paginator;
use App\Repository\StaticPageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StaticPageController extends AbstractController
{
    public function __construct(
        private StaticPageRepository $staticPageRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(Request $request): Response
    {
        $staticPage = new StaticPage();
        $form = $this->createForm(StaticPageType::class, $staticPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $this->staticPageRepository->generateUniqueSlug($staticPage->getTitle());
            $staticPage->setSlug($slug);

            $this->entityManager->persist($staticPage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Strona statyczna została dodana');

            return $this->redirectToRoute('panel_static_page_list');
        }

        return $this->render('panel/static_page/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function delete(int $id, Request $request): Response
    {
        $staticPage = $this->staticPageRepository->find($id);

        if ($staticPage === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($staticPage);
        $this->entityManager->flush();

        $this->addFlash('success', 'Strona statyczna została usunięta');

        return $request->headers->has('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('panel_static_page_list');
    }

    public function edit(int $id, Request $request): Response
    {
        $staticPage = $this->staticPageRepository->find($id);

        if ($staticPage === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(StaticPageType::class, $staticPage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($staticPage);
            $this->entityManager->flush();

            $this->addFlash('success', 'Strona statyczna została zaktualizowana');

            return $this->redirectToRoute('panel_static_page_list');
        }

        return $this->render('panel/static_page/edit.html.twig', [
            'staticPage' => $staticPage,
            'form' => $form->createView(),
        ]);
    }

    public function list(Request $request): Response
    {
        $pagesNumber = (int)$request->get('number', 15);
        $page = (int)$request->get('page', 1);
        $criteria = [];

        if ($request->get('search_query', '') !== '') {
            $criteria['title'] = ['LIKE', '%' . $request->get('search_query') . '%'];
        }

        $staticPages = $this->staticPageRepository->findBy(
            $criteria,
            ['id' => 'DESC'],
            $pagesNumber,
            ($page - 1) * $pagesNumber,
        );

        return $this->render('panel/static_page/list.html.twig', [
            'staticPages' => $staticPages,
            'paginator' => new Paginator(
                $this->staticPageRepository->count($criteria),
                $pagesNumber,
                $page,
                $request->getUri(),
            ),
        ]);
    }
}
