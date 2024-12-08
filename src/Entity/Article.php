<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Index(columns: ['updated_at'])]
#[ORM\Index(columns: ['is_event'])]
#[ORM\Index(columns: ['event_date_time'])]
#[ORM\Index(columns: ['status'])]
#[ORM\Index(columns: ['name', 'content'], flags: ['fulltext'])]
class Article
{
    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $commentsNumber = 0;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 300)]
    private ?string $excerpt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column]
    private ?int $viewsNumber = 0;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $updateAuthor = null;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ArticleComment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $status = 0;

    #[ORM\Column]
    private ?bool $hasCommentsDisabled = false;

    #[ORM\Column]
    private ?bool $isEvent = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $eventDateTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $eventPlace = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageCaption = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    private ?Gallery $gallery = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function addComment(ArticleComment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArticle($this);
        }

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, ArticleComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getCommentsNumber(): ?int
    {
        return $this->commentsNumber;
    }

    public function setCommentsNumber(int $commentsNumber): static
    {
        $this->commentsNumber = $commentsNumber;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEventDateTime(): ?\DateTimeInterface
    {
        return $this->eventDateTime;
    }

    public function setEventDateTime(?\DateTimeInterface $eventDateTime): static
    {
        $this->eventDateTime = $eventDateTime;

        return $this;
    }

    public function getEventPlace(): ?string
    {
        return $this->eventPlace;
    }

    public function setEventPlace(?string $eventPlace): static
    {
        $this->eventPlace = $eventPlace;

        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): static
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImageCaption(): ?string
    {
        return $this->imageCaption;
    }

    public function setImageCaption(?string $imageCaption): static
    {
        $this->imageCaption = $imageCaption;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
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

    public function getStatus(): ArticleStatus
    {
        return ArticleStatus::from($this->status);
    }

    public function setStatus(ArticleStatus $status): static
    {
        $this->status = $status->value;

        return $this;
    }

    public function getUpdateAuthor(): ?User
    {
        return $this->updateAuthor;
    }

    public function setUpdateAuthor(?User $updateAuthor): static
    {
        $this->updateAuthor = $updateAuthor;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getViewsNumber(): ?int
    {
        return $this->viewsNumber;
    }

    public function setViewsNumber(int $viewsNumber): static
    {
        $this->viewsNumber = $viewsNumber;

        return $this;
    }

    public function isHasCommentsDisabled(): ?bool
    {
        return $this->hasCommentsDisabled;
    }

    public function isIsEvent(): ?bool
    {
        return $this->isEvent;
    }

    public function removeComment(ArticleComment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getArticle() === $this) {
                $comment->setArticle(null);
            }
        }

        return $this;
    }

    public function setHasCommentsDisabled(bool $hasCommentsDisabled): static
    {
        $this->hasCommentsDisabled = $hasCommentsDisabled;

        return $this;
    }

    public function setIsEvent(bool $isEvent): static
    {
        $this->isEvent = $isEvent;

        return $this;
    }

    public function getGallery(): ?Gallery
    {
        return $this->gallery;
    }

    public function setGallery(?Gallery $gallery): static
    {
        $this->gallery = $gallery;

        return $this;
    }
}
