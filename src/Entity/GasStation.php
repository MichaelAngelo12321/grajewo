<?php

namespace App\Entity;

use App\Repository\GasStationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GasStationRepository::class)]
class GasStation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\OneToMany(mappedBy: 'station', targetEntity: GasStationPrice::class, orphanRemoval: true)]
    private Collection $gasStationPrices;

    #[ORM\Column]
    private int $positionOrder = 0;

    #[ORM\Column]
    private ?bool $hasDiesel = null;

    #[ORM\Column]
    private ?bool $hasUnleaded = null;

    #[ORM\Column]
    private ?bool $hasSuperUnleaded = null;

    #[ORM\Column]
    private ?bool $hasLiquidGas = null;

    public function __construct()
    {
        $this->gasStationPrices = new ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection<int, GasStationPrice>
     */
    public function getGasStationPrices(): Collection
    {
        return $this->gasStationPrices;
    }

    public function addGasStationPrice(GasStationPrice $gasStationPrice): static
    {
        if (!$this->gasStationPrices->contains($gasStationPrice)) {
            $this->gasStationPrices->add($gasStationPrice);
            $gasStationPrice->setStation($this);
        }

        return $this;
    }

    public function removeGasStationPrice(GasStationPrice $gasStationPrice): static
    {
        if ($this->gasStationPrices->removeElement($gasStationPrice)) {
            // set the owning side to null (unless already changed)
            if ($gasStationPrice->getStation() === $this) {
                $gasStationPrice->setStation(null);
            }
        }

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

    public function isHasDiesel(): ?bool
    {
        return $this->hasDiesel;
    }

    public function setHasDiesel(bool $hasDiesel): static
    {
        $this->hasDiesel = $hasDiesel;

        return $this;
    }

    public function isHasUnleaded(): ?bool
    {
        return $this->hasUnleaded;
    }

    public function setHasUnleaded(bool $hasUnleaded): static
    {
        $this->hasUnleaded = $hasUnleaded;

        return $this;
    }

    public function isHasSuperUnleaded(): ?bool
    {
        return $this->hasSuperUnleaded;
    }

    public function setHasSuperUnleaded(bool $hasSuperUnleaded): static
    {
        $this->hasSuperUnleaded = $hasSuperUnleaded;

        return $this;
    }

    public function isHasLiquidGas(): ?bool
    {
        return $this->hasLiquidGas;
    }

    public function setHasLiquidGas(bool $hasLiquidGas): static
    {
        $this->hasLiquidGas = $hasLiquidGas;

        return $this;
    }
}
