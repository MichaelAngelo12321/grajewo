<?php

namespace App\Entity;

use App\Repository\NameDayRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NameDayRepository::class)]
class NameDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $day = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $month = null;

    #[ORM\Column(length: 255)]
    private ?string $names = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): static
    {
        $this->day = $day;

        return $this;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(int $month): static
    {
        $this->month = $month;

        return $this;
    }

    public function getNames(): ?string
    {
        return $this->names;
    }

    public function setNames(string $names): static
    {
        $this->names = $names;

        return $this;
    }
}
