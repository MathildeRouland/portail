<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $homepageText = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backgroundImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rgpdFile = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $portalText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHomepageText(): ?string
    {
        return $this->homepageText;
    }

    public function setHomepageText(?string $homepageText): static
    {
        $this->homepageText = $homepageText;

        return $this;
    }

    public function getBackgroundImage(): ?string
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage(?string $backgroundImage): static
    {
        $this->backgroundImage = $backgroundImage;

        return $this;
    }

    public function getRgpdFile(): ?string
    {
        return $this->rgpdFile;
    }

    public function setRgpdFile(?string $rgpdFile): static
    {
        $this->rgpdFile = $rgpdFile;

        return $this;
    }

    public function getPortalText(): ?string
    {
        return $this->portalText;
    }

    public function setPortalText(?string $portalText): static
    {
        $this->portalText = $portalText;

        return $this;
    }

}
