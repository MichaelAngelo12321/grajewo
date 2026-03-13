<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Company;
use App\Enum\UploadDirectory;
use App\Form\CompanyType;
use App\Helper\Paginator;
use App\Repository\CompanyRepository;
use App\Service\FileCleaner;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class CompanyController extends AbstractController
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileCleaner $fileCleaner,
        private readonly FileUploader $fileUploader,
        private readonly ImageResizer $imageResizer,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function list(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 20;

        $companies = $this->companyRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage
        );

        $totalItems = $this->companyRepository->count([]);

        return $this->render('panel/company/list.html.twig', [
            'companies' => $companies,
            'paginator' => new Paginator(
                $totalItems,
                $itemsPerPage,
                $page,
                $request->getPathInfo()
            ),
        ]);
    }

    public function create(Request $request): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($company, $form);

            $this->addFlash('success', 'Firma została dodana.');
            return $this->redirectToRoute('panel_company_list');
        }

        return $this->render('panel/company/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Dodaj firmę',
        ]);
    }

    public function edit(int $id, Request $request): Response
    {
        $company = $this->companyRepository->find($id);

        if (!$company) {
            throw $this->createNotFoundException('Firma nie została znaleziona.');
        }

        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($company, $form);

            $this->addFlash('success', 'Dane firmy zostały zaktualizowane.');
            return $this->redirectToRoute('panel_company_list');
        }

        return $this->render('panel/company/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edytuj firmę',
            'company' => $company,
        ]);
    }

    public function delete(int $id, Request $request): Response
    {
        $company = $this->companyRepository->find($id);

        if ($company) {
            if ($company->getLogo()) {
                $this->fileCleaner->removeFile($company->getLogo());
            }

            $this->entityManager->remove($company);
            $this->entityManager->flush();
            $this->addFlash('success', 'Firma została usunięta.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('panel_company_list'));
    }

    private function handleForm(Company $company, $form): void
    {
        /** @var UploadedFile $logoFile */
        $logoFile = $form->get('logo')->getData();

        if ($logoFile) {
            if ($company->getLogo()) {
                $this->fileCleaner->removeFile($company->getLogo());
            }

            $newFilename = $this->fileUploader->upload($logoFile, UploadDirectory::COMPANY);
            // Optional: resize logic if needed, usually logos are kept as is or resized to specific dim
            // $this->imageResizer->resize($newFilename); 
            $company->setLogo($newFilename);
        }

        if (!$company->getSlug()) {
            $company->setSlug(
                $this->slugger->slug($company->getName())->lower()->toString()
            );
        }

        $company->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }
}
