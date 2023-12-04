<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Column]
    private int $articlesNumber = 0;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Article::class)]
    private Collection $articles;

    #[ORM\Column]
    private int $positionOrder = 0;

    #[ORM\Column]
    private ?bool $isRoot = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function addArticle(Article $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setCategory($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function getArticlesNumber(): ?int
    {
        return $this->articlesNumber;
    }

    public function setArticlesNumber(int $articlesNumber): static
    {
        $this->articlesNumber = $articlesNumber;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPositionOrder(): int
    {
        return $this->positionOrder;
    }

    public function setPositionOrder(int $positionOrder): static
    {
        $this->positionOrder = $positionOrder;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function isIsRoot(): ?bool
    {
        return $this->isRoot;
    }

    public function removeArticle(Article $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getCategory() === $this) {
                $article->setCategory(null);
            }
        }

        return $this;
    }

    public function setIsRoot(bool $isRoot): static
    {
        $this->isRoot = $isRoot;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
