<?php

namespace App\Entity;

use App\Repository\AdvertisementCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementCategoryRepository::class)]
class AdvertisementCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'advertisementCategory', targetEntity: Advertisement::class)]
    private Collection $advertisements;

    #[ORM\Column(length: 255)]
    private ?string $iconName = null;

    public function __construct()
    {
        $this->advertisements = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function addAdvertisement(Advertisement $advertisement): static
    {
        if (!$this->advertisements->contains($advertisement)) {
            $this->advertisements->add($advertisement);
            $advertisement->setCategory($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Advertisement>
     */
    public function getAdvertisements(): Collection
    {
        return $this->advertisements;
    }

    public function getIconName(): ?string
    {
        return $this->iconName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function removeAdvertisement(Advertisement $advertisement): static
    {
        if ($this->advertisements->removeElement($advertisement)) {
            // set the owning side to null (unless already changed)
            if ($advertisement->getCategory() === $this) {
                $advertisement->setCategory(null);
            }
        }

        return $this;
    }

    public function setIconName(string $iconName): static
    {
        $this->iconName = $iconName;

        return $this;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
