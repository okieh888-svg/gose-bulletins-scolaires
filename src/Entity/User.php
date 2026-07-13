<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Compte de connexion GOSE.
 *
 * Porte un unique rôle métier parmi ROLE_ADMIN / ROLE_PROVISEUR / ROLE_ENSEIGNANT / ROLE_ELEVE.
 * ROLE_ADMIN est le seul rôle sans établissement de rattachement (gestion multi-établissements) ;
 * les trois autres sont TOUJOURS rattachés à un établissement, ce qui sert de base au
 * cloisonnement vérifié par les Voters (voir App\Security\Voter\*).
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    /**
     * Établissement de rattachement. Nullable uniquement pour ROLE_ADMIN.
     * C'est LE champ pivot du cloisonnement inter-établissements.
     */
    #[ORM\ManyToOne(targetEntity: Etablissement::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Etablissement $etablissement = null;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $derniereConnexion = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Enseignant::class, cascade: ['persist', 'remove'])]
    private ?Enseignant $profilEnseignant = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Eleve::class, cascade: ['persist', 'remove'])]
    private ?Eleve $profilEleve = null;

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

    /** Identifiant unique utilisé par le composant Security. */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // ROLE_USER garanti pour tout compte authentifié
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /** Rôle métier principal (le premier rôle explicite, hors ROLE_USER). */
    public function getRolePrincipal(): ?string
    {
        return $this->roles[0] ?? null;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Aucune donnée sensible temporaire à effacer ici.
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNomComplet(): string
    {
        return trim(sprintf('%s %s', $this->prenom, $this->nom));
    }

    public function getEtablissement(): ?Etablissement
    {
        return $this->etablissement;
    }

    public function setEtablissement(?Etablissement $etablissement): static
    {
        $this->etablissement = $etablissement;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getDerniereConnexion(): ?\DateTimeImmutable
    {
        return $this->derniereConnexion;
    }

    public function setDerniereConnexion(?\DateTimeImmutable $derniereConnexion): static
    {
        $this->derniereConnexion = $derniereConnexion;

        return $this;
    }

    public function getProfilEnseignant(): ?Enseignant
    {
        return $this->profilEnseignant;
    }

    public function getProfilEleve(): ?Eleve
    {
        return $this->profilEleve;
    }

    /**
     * Réservé à Enseignant::setUser() pour maintenir la cohérence des deux côtés
     * de l'association bidirectionnelle en mémoire (sans cela, getProfilEnseignant()
     * resterait `null` tant que ce User n'a pas été rechargé depuis la base).
     */
    public function setProfilEnseignant(?Enseignant $profilEnseignant): static
    {
        $this->profilEnseignant = $profilEnseignant;

        return $this;
    }

    /** Réservé à Eleve::setUser() — voir setProfilEnseignant(). */
    public function setProfilEleve(?Eleve $profilEleve): static
    {
        $this->profilEleve = $profilEleve;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getNomComplet();
    }
}
