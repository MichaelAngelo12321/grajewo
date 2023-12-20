<?php

namespace App\Entity;

use App\Repository\GasStationPriceRepository;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(nullable: true)]
    private ?float $diesel = null;

    #[ORM\Column(nullable: true)]
    private ?float $unleaded = null;

    #[ORM\Column(nullable: true)]
    private ?float $superUnleaded = null;

    #[ORM\Column(nullable: true)]
    private ?float $liquidGas = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDiesel(): ?float
    {
        return $this->diesel;
    }

    public function setDiesel(?float $diesel): static
    {
        $this->diesel = $diesel;

        return $this;
    }

    public function getUnleaded(): ?float
    {
        return $this->unleaded;
    }

    public function setUnleaded(?float $unleaded): static
    {
        $this->unleaded = $unleaded;

        return $this;
    }

    public function getSuperUnleaded(): ?float
    {
        return $this->superUnleaded;
    }

    public function setSuperUnleaded(?float $superUnleaded): static
    {
        $this->superUnleaded = $superUnleaded;

        return $this;
    }

    public function getLiquidGas(): ?float
    {
        return $this->liquidGas;
    }

    public function setLiquidGas(?float $liquidGas): static
    {
        $this->liquidGas = $liquidGas;

        return $this;
    }
}
