<?php

namespace App\Entity;

use App\Repository\OpeningHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpeningHistoryRepository::class)]
class OpeningHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $openingDate = null;

    #[ORM\Column]
    private ?int $IpAdress = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOpeningDate(): ?\DateTimeInterface
    {
        return $this->openingDate;
    }

    public function setOpeningDate(\DateTimeInterface $openingDate): static
    {
        $this->openingDate = $openingDate;

        return $this;
    }

    public function getIpAdress(): ?int
    {
        return $this->IpAdress;
    }

    public function setIpAdress(int $IpAdress): static
    {
        $this->IpAdress = $IpAdress;

        return $this;
    }
}
