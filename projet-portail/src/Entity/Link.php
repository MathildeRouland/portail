<?php

namespace App\Entity;

use App\Repository\LinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: LinkRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Link
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Length(max: 255)]
    #[Assert\Url]
    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(min: 2, max: 255)]
    #[Assert\Regex(
        pattern: "/^[A-Za-zÀ-ÖØ-öø-ÿ0-9\-\_ ]+$/u",
        message: "Le nom ne peut contenir que des lettres, chiffres, espaces, tirets ou underscores."
    )]
    #[ORM\Column(length: 255)]
    private ?string $customerName = null;

    #[Assert\Length(min: 4, max: 8)]
    #[Assert\Regex(
        pattern: "/^[A-Z0-9]+$/",
        message: "Les caractères aléatoires doivent être en majuscules et chiffres."
    )]
    #[ORM\Column(length: 4)]
    private ?string $fourRandomCharacters = null;

    #[Assert\Type(\DateTimeInterface::class)]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[Assert\Type(\DateTimeInterface::class)]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;
    #[Assert\Regex(
        pattern: "/^\+[1-9]\d{7,14}$/",
       groups: ['create', 'edit'],
        message: "Le numéro de téléphone doit être au format international E.164 (ex : +33123456789)."
    )]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerPhoneNumber = null;

    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    #[Assert\NotBlank(groups: ['create'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customerEmail = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(type: 'boolean')]
    private bool $permanent = false;

    #[ORM\ManyToOne(inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creator = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $updater = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): static
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getFourRandomCharacters(): ?string
    {
        return $this->fourRandomCharacters;
    }

    public function setFourRandomCharacters(string $fourRandomCharacters): static
    {
        $this->fourRandomCharacters = $fourRandomCharacters;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getCustomerPhoneNumber(): ?string
    {
        return $this->customerPhoneNumber;
    }

    public function setCustomerPhoneNumber(?string $customerPhoneNumber): static
    {
        $this->customerPhoneNumber = $customerPhoneNumber;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
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

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function setPermanent(bool $permanent): static
    {
        $this->permanent = $permanent;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function getUpdater(): ?User
    {
        return $this->updater;
    }

    public function setUpdater(?User $updater): static
    {
        $this->updater = $updater;

        return $this;
    }
    
    /**
     * Vérifie si la date de fin est passée et met à jour le statut en conséquence
     * Cette méthode est appelée automatiquement avant chaque persistance
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateStatus(): void
    {
        // Si le lien est permanent, il est toujours actif
        if ($this->permanent) {
            $this->status = true;
            return;
        }

        $now = new \DateTimeImmutable();

        // Si la date de début est dans le futur, le lien n'est pas encore actif
        if ($this->startDate !== null && $this->startDate > $now) {
            $this->status = false;
            return;
        }

        // Si la date de fin est passée, le lien est expiré
        if ($this->endDate !== null && $this->endDate < $now) {
            $this->status = false;
            return;
        }

        // Sinon, le lien est actif
        $this->status = true;
    }
    
    /**
     * Vérifie si le lien est encore valide (date de fin non passée)
     * 
     * @return bool True si le lien est valide, False sinon
     */
    public function isValid(): bool
    {
        $this->updateStatus();
        return $this->status;
    }

    /**
     * Set createdAt automatically when the entity is first persisted
     */
    #[ORM\PrePersist]
     public function onPrePersist(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    /**
     * Update updatedAt automatically on each update
     */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function isActiveAt(\DateTimeImmutable $now): bool
    {
        if ($this->permanent) {
            return true;
        }

        if ($this->startDate !== null && $this->startDate > $now) {
            return false;
        }

        if ($this->endDate !== null && $this->endDate < $now) {
            return false;
        }

        return true;
    }
}
