<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une note ponctuelle (devoir, composition...) saisie par un enseignant.
 * La moyenne d'une matière sur une période est la moyenne de toutes les Note
 * associées à ce triplet (élève, matière, période) — voir MoyenneCalculator.
 */
#[ORM\Entity(repositoryClass: NoteRepository::class)]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Eleve::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Eleve $eleve = null;

    #[ORM\ManyToOne(targetEntity: Matiere::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Matiere $matiere = null;

    #[ORM\ManyToOne(targetEntity: Periode::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Periode $periode = null;

    /** Note sur 20. */
    #[ORM\Column(type: 'float')]
    private ?float $valeur = null;

    /** Ex: "Devoir 1", "Composition". Purement informatif dans ce prototype. */
    #[ORM\Column(length: 50)]
    private ?string $typeEvaluation = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $saisiePar = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateSaisie = null;

    public function __construct()
    {
        $this->dateSaisie = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEleve(): ?Eleve
    {
        return $this->eleve;
    }

    public function setEleve(?Eleve $eleve): static
    {
        $this->eleve = $eleve;

        return $this;
    }

    public function getMatiere(): ?Matiere
    {
        return $this->matiere;
    }

    public function setMatiere(?Matiere $matiere): static
    {
        $this->matiere = $matiere;

        return $this;
    }

    public function getPeriode(): ?Periode
    {
        return $this->periode;
    }

    public function setPeriode(?Periode $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getValeur(): ?float
    {
        return $this->valeur;
    }

    public function setValeur(float $valeur): static
    {
        if ($valeur < 0 || $valeur > 20) {
            throw new \InvalidArgumentException('Une note doit être comprise entre 0 et 20.');
        }

        $this->valeur = $valeur;

        return $this;
    }

    public function getTypeEvaluation(): ?string
    {
        return $this->typeEvaluation;
    }

    public function setTypeEvaluation(string $typeEvaluation): static
    {
        $this->typeEvaluation = $typeEvaluation;

        return $this;
    }

    public function getSaisiePar(): ?User
    {
        return $this->saisiePar;
    }

    public function setSaisiePar(?User $saisiePar): static
    {
        $this->saisiePar = $saisiePar;

        return $this;
    }

    public function getDateSaisie(): ?\DateTimeImmutable
    {
        return $this->dateSaisie;
    }
}
