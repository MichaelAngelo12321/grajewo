<?php

namespace App\Entity;

use App\Repository\GasStationPriceRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GasStationPriceRepository::class)]
#[ORM\Index(columns: ['date'])]
class GasStationPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gasStationPrices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GasStation $station = null;

    #[ORM\Column]
    private ?DateTimeImmutable $date = null;

    #[ORM\Column(length: 15)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStation(): ?GasStation
    {
        return $this->station;
    }

    public function setStation(?GasStation $station): static
    {
        $this->station = $station;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
