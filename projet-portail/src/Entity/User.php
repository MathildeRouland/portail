<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

     #[Assert\NotBlank]
     #[Assert\Email]
     #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[Assert\All([
        new Assert\Regex(pattern: '/^ROLE_[A-Z_]+$/', groups: ['create'])
    ])]
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[Assert\Length(min: 8, groups: ['create'])]
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_\- ]+$/u',
        message: 'Le nom d’utilisateur ne peut contenir que des lettres, chiffres, espaces, tirets et underscores.'
    )]
    #[Assert\Length(min: 3, max: 50)]
    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Link::class, orphanRemoval: true)]
    private Collection $links;


    public function __construct()
    {
        $this->links = new ArrayCollection();
    }

    /**
     * @return Collection|\App\Entity\Link[]
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        //$this->plainPassword = null;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

}
