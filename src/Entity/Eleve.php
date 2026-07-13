<?php

namespace App\Entity;

use App\Repository\EleveRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EleveRepository::class)]
class Eleve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $matricule = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    /**
     * Nom en écriture arabe, optionnel — permet de démontrer le support UTF-8 / RTL
     * (préparation du Lot 8 « arabisation » du TDR) dès ce prototype.
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $nomArabe = null;

    #[ORM\Column(length: 10)]
    private ?string $sexe = null; // 'M' ou 'F'

    #[ORM\Column]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\ManyToOne(targetEntity: Classe::class, inversedBy: 'eleves')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Classe $classe = null;

    /** Compte de connexion permettant à l'élève de consulter son bulletin (peut être null si non créé). */
    #[ORM\OneToOne(inversedBy: 'profilEleve', targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'eleve', targetEntity: Note::class)]
    private Collection $notes;

    #[ORM\OneToMany(mappedBy: 'eleve', targetEntity: Bulletin::class)]
    private Collection $bulletins;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
        $this->bulletins = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }

    public function setMatricule(string $matricule): static
    {
        $this->matricule = $matricule;

        return $this;
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

    public function getNomArabe(): ?string
    {
        return $this->nomArabe;
    }

    public function setNomArabe(?string $nomArabe): static
    {
        $this->nomArabe = $nomArabe;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeImmutable $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        // Synchronise le côté inverse en mémoire (voir User::setProfilEleve()).
        $user?->setProfilEleve($this);

        return $this;
    }

    /** @return Collection<int, Note> */
    public function getNotes(): Collection
    {
        return $this->notes;
    }

    /** @return Collection<int, Bulletin> */
    public function getBulletins(): Collection
    {
        return $this->bulletins;
    }

    public function __toString(): string
    {
        return $this->getNomComplet();
    }
}
