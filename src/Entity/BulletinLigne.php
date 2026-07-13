<?php

namespace App\Entity;

use App\Repository\BulletinLigneRepository;
use Doctrine\ORM\Mapping as ORM;

/** Ligne "matière" figée dans un bulletin au moment de sa génération. */
#[ORM\Entity(repositoryClass: BulletinLigneRepository::class)]
class BulletinLigne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Bulletin::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bulletin $bulletin = null;

    #[ORM\ManyToOne(targetEntity: Matiere::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Matiere $matiere = null;

    #[ORM\Column(type: 'float')]
    private ?float $moyenne = null;

    #[ORM\Column(type: 'integer')]
    private ?int $coefficient = null;

    #[ORM\Column(type: 'float')]
    private ?float $moyenneCoefficientee = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $appreciation = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBulletin(): ?Bulletin
    {
        return $this->bulletin;
    }

    public function setBulletin(?Bulletin $bulletin): static
    {
        $this->bulletin = $bulletin;

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

    public function getMoyenne(): ?float
    {
        return $this->moyenne;
    }

    public function setMoyenne(float $moyenne): static
    {
        $this->moyenne = $moyenne;

        return $this;
    }

    public function getCoefficient(): ?int
    {
        return $this->coefficient;
    }

    public function setCoefficient(int $coefficient): static
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    public function getMoyenneCoefficientee(): ?float
    {
        return $this->moyenneCoefficientee;
    }

    public function setMoyenneCoefficientee(float $moyenneCoefficientee): static
    {
        $this->moyenneCoefficientee = $moyenneCoefficientee;

        return $this;
    }

    public function getAppreciation(): ?string
    {
        return $this->appreciation;
    }

    public function setAppreciation(?string $appreciation): static
    {
        $this->appreciation = $appreciation;

        return $this;
    }
}
