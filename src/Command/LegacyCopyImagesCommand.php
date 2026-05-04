<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\GalleryImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:legacy-copy-images',
    description: 'Kopiuje pobrane obrazy ze starego systemu do odpowiednich folderów w nowym (np. rocznikowych) na podstawie bazy danych.',
)]
class LegacyCopyImagesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicDirectory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('source_dir', InputArgument::REQUIRED, 'Ścieżka do folderu z pobranymi starymi zdjęciami (np. /sciezka/do/uploads)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sourceDir = rtrim($input->getArgument('source_dir'), '/');

        if (!is_dir($sourceDir)) {
            $io->error(sprintf('Katalog źródłowy "%s" nie istnieje.', $sourceDir));
            return Command::FAILURE;
        }

        $io->title('Kopiowanie starych obrazków na właściwe miejsca');

        $copied = 0;
        $missing = 0;

        // Funkcja pomocnicza do kopiowania plików
        $copyFile = function (string $targetRelativePath) use ($sourceDir, $io, &$copied, &$missing) {
            if (!$targetRelativePath) {
                return;
            }

            $fileName = basename($targetRelativePath);
            $sourceFile = $sourceDir . '/' . $fileName;
            $targetFile = $this->publicDirectory . $targetRelativePath;

            if (!file_exists($sourceFile)) {
                $missing++;
                return;
            }

            $targetDir = dirname($targetFile);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (!file_exists($targetFile)) {
                if (copy($sourceFile, $targetFile)) {
                    $copied++;
                }
            }
        };

        $io->section('Kopiowanie głównych zdjęć artykułów');
        $articles = $this->em->getRepository(Article::class)
            ->createQueryBuilder('a')
            ->where('a.imageUrl IS NOT NULL')
            ->getQuery()
            ->toIterable();

        foreach ($articles as $article) {
            $copyFile($article->getImageUrl());
        }

        $io->section('Kopiowanie zdjęć z galerii (artykuły i ogłoszenia)');
        $galleryImages = $this->em->getRepository(GalleryImage::class)
            ->createQueryBuilder('g')
            ->where('g.imageUrl IS NOT NULL')
            ->getQuery()
            ->toIterable();

        foreach ($galleryImages as $image) {
            $copyFile($image->getImageUrl());
        }

        $io->section('Kopiowanie logo firm');
        $companies = $this->em->getRepository(\App\Entity\Company::class)
            ->createQueryBuilder('c')
            ->where('c.logo IS NOT NULL')
            ->getQuery()
            ->toIterable();

        foreach ($companies as $company) {
            $copyFile($company->getLogo());
        }

        $io->section('Kopiowanie materiałów reklamowych (banery)');
        $promos = $this->em->getRepository(\App\Entity\PromoItem::class)
            ->createQueryBuilder('p')
            ->where('p.imageUrl IS NOT NULL')
            ->getQuery()
            ->toIterable();

        foreach ($promos as $promo) {
            $copyFile($promo->getImageUrl());
        }

        $io->success(sprintf('Gotowe! Skopiowano %d plików. Brakowało %d plików w folderze źródłowym.', $copied, $missing));

        return Command::SUCCESS;
    }
}
