<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Ex: "6ème A" */
    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 20)]
    private ?string $niveau = null;

    #[ORM\Column(length: 20)]
    private ?string $anneeScolaire = null;

    #[ORM\ManyToOne(targetEntity: Etablissement::class, inversedBy: 'classes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etablissement $etablissement = null;

    #[ORM\OneToMany(mappedBy: 'classe', targetEntity: Eleve::class)]
    private Collection $eleves;

    #[ORM\OneToMany(mappedBy: 'classe', targetEntity: Affectation::class)]
    private Collection $affectations;

    public function __construct()
    {
        $this->eleves = new ArrayCollection();
        $this->affectations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNiveau(): ?string
    {
        return $this->niveau;
    }

    public function setNiveau(string $niveau): static
    {
        $this->niveau = $niveau;

        return $this;
    }

    public function getAnneeScolaire(): ?string
    {
        return $this->anneeScolaire;
    }

    public function setAnneeScolaire(string $anneeScolaire): static
    {
        $this->anneeScolaire = $anneeScolaire;

        return $this;
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

    /** @return Collection<int, Eleve> */
    public function getEleves(): Collection
    {
        return $this->eleves;
    }

    /** @return Collection<int, Affectation> */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
