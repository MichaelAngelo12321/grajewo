<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\Cached\ArticleCommentCachedRepository;

#[AsCommand(name: 'app:test-comments')]
class TestCommentsCommand extends Command
{
    public function __construct(private ArticleCommentCachedRepository $repo)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $comments = $this->repo->findLatest();
        foreach ($comments as $comment) {
            $output->writeln("ID: " . $comment->getId());
            $art = $comment->getArticle();
            if ($art) {
                $cat = $art->getCategory();
                if ($cat) {
                    $output->writeln("CAT: " . $cat->getSlug());
                } else {
                    $output->writeln("NO CAT");
                }
            } else {
                $output->writeln("NO ART");
            }
        }
        return Command::SUCCESS;
    }
}
