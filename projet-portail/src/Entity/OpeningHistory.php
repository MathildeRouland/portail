<?php

namespace App\Entity;

use App\Repository\OpeningHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Link;

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

    #[ORM\ManyToOne(targetEntity: Link::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Link $link = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerName = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $IpAddress = null;

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

        public function getLink(): ?Link
    {
        return $this->link;
    }

    public function setLink(?Link $link): static
    {
        $this->link = $link;
        return $this;
    }


    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }
    public function setCustomerName(?string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->IpAddress;
    }

    public function setIpAddress(?string $IpAddress): static
    {
        $this->IpAddress = $IpAddress;

        return $this;
    }
}
