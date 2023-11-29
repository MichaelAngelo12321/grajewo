<?php

declare(strict_types=1);

namespace App\Helper;

use InvalidArgumentException;

class Paginator
{
    public const NUM_PLACEHOLDER = '(:num)';

    protected int $totalItems;
    protected int $numPages;
    protected int $itemsPerPage;
    protected int $currentPage;
    protected string $urlPattern;
    protected int $maxPagesToShow = 8;
    protected string $previousText = '';
    protected string $nextText = '';

    public function __construct(int $totalItems, int $itemsPerPage, int $currentPage, string $urlPattern = '')
    {
        $this->totalItems = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->currentPage = $currentPage;
        $this->urlPattern = $urlPattern;

        $this->updateNumPages();
    }

    protected function updateNumPages(): void
    {
        $this->numPages = ($this->itemsPerPage == 0 ? 0 : (int)ceil($this->totalItems / $this->itemsPerPage));
    }

    public function __toString()
    {
        return $this->toHtml();
    }

    public function toHtml(): string
    {
        if ($this->numPages <= 1) {
            return '';
        }

        $html = '<ul class="pagination">';
        if ($this->getPrevUrl()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->getPrevUrl() . '">&laquo; ' . $this->previousText . '</a></li>';
        }

        foreach ($this->getPages() as $page) {
            if ($page['url']) {
                $html .= '<li class="' . ($page['isCurrent'] ? 'page-item active' : 'page-item') . '"><a class="page-link" href="' .
                    $page['url']
                    . '">' . $page['num'] . '</a></li>';
            } else {
                $html .= '<li class="page-item disabled"><span class="page-link">' . $page['num'] . '</span></li>';
            }
        }

        if ($this->getNextUrl()) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $this->getNextUrl() . '">' . $this->nextText . ' &raquo;</a></li>';
        }
        $html .= '</ul>';

        return $html;
    }

    public function getPrevUrl(): ?string
    {
        if (!$this->getPrevPage()) {
            return null;
        }

        return $this->getPageUrl($this->getPrevPage());
    }

    public function getPrevPage(): ?int
    {
        if ($this->currentPage > 1) {
            return $this->currentPage - 1;
        }

        return null;
    }

    public function getPageUrl(int $pageNum): array|string
    {
        return str_replace(self::NUM_PLACEHOLDER, (string)$pageNum, $this->urlPattern);
    }

    public function getPages(): array
    {
        $pages = array();

        if ($this->numPages <= 1) {
            return array();
        }

        if ($this->numPages <= $this->maxPagesToShow) {
            for ($i = 1; $i <= $this->numPages; $i++) {
                $pages[] = $this->createPage($i, $i == $this->currentPage);
            }
        } else {
            // Determine the sliding range, centered around the current page.
            $numAdjacents = (int)floor(($this->maxPagesToShow - 3) / 2);

            if ($this->currentPage + $numAdjacents > $this->numPages) {
                $slidingStart = $this->numPages - $this->maxPagesToShow + 2;
            } else {
                $slidingStart = $this->currentPage - $numAdjacents;
            }
            if ($slidingStart < 2) {
                $slidingStart = 2;
            }

            $slidingEnd = $slidingStart + $this->maxPagesToShow - 3;
            if ($slidingEnd >= $this->numPages) {
                $slidingEnd = $this->numPages - 1;
            }

            // Build the list of pages.
            $pages[] = $this->createPage(1, $this->currentPage == 1);
            if ($slidingStart > 2) {
                $pages[] = $this->createPageEllipsis();
            }
            for ($i = $slidingStart; $i <= $slidingEnd; $i++) {
                $pages[] = $this->createPage($i, $i == $this->currentPage);
            }
            if ($slidingEnd < $this->numPages - 1) {
                $pages[] = $this->createPageEllipsis();
            }
            $pages[] = $this->createPage($this->numPages, $this->currentPage == $this->numPages);
        }


        return $pages;
    }

    protected function createPage(int $pageNum, bool $isCurrent = false): array
    {
        return array(
            'num' => $pageNum,
            'url' => $this->getPageUrl($pageNum),
            'isCurrent' => $isCurrent,
        );
    }

    protected function createPageEllipsis(): array
    {
        return array(
            'num' => '...',
            'url' => null,
            'isCurrent' => false,
        );
    }

    public function getNextUrl(): array|string|null
    {
        if (!$this->getNextPage()) {
            return null;
        }

        return $this->getPageUrl($this->getNextPage());
    }

    public function getNextPage(): ?int
    {
        if ($this->currentPage < $this->numPages) {
            return $this->currentPage + 1;
        }

        return null;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): void
    {
        $this->currentPage = $currentPage;
    }

    public function getCurrentPageLastItem(): float|int|null
    {
        $first = $this->getCurrentPageFirstItem();
        if ($first === null) {
            return null;
        }

        $last = $first + $this->itemsPerPage - 1;
        if ($last > $this->totalItems) {
            return $this->totalItems;
        }

        return $last;
    }

    public function getCurrentPageFirstItem(): float|int|null
    {
        $first = ($this->currentPage - 1) * $this->itemsPerPage + 1;

        if ($first > $this->totalItems) {
            return null;
        }

        return $first;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
        $this->updateNumPages();
    }

    public function getMaxPagesToShow(): int
    {
        return $this->maxPagesToShow;
    }

    public function setMaxPagesToShow(int $maxPagesToShow): void
    {
        if ($maxPagesToShow < 3) {
            throw new InvalidArgumentException('maxPagesToShow cannot be less than 3.');
        }
        $this->maxPagesToShow = $maxPagesToShow;
    }

    public function getNumPages(): int
    {
        return $this->numPages;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
        $this->updateNumPages();
    }

    public function getUrlPattern(): string
    {
        return $this->urlPattern;
    }

    public function setUrlPattern(string $urlPattern): void
    {
        $this->urlPattern = $urlPattern;
    }

    public function setNextText(string $text): static
    {
        $this->nextText = $text;
        return $this;
    }

    public function setPreviousText(string $text): static
    {
        $this->previousText = $text;
        return $this;
    }
}
