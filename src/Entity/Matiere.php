<?php

namespace App\Entity;

use App\Repository\MatiereRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatiereRepository::class)]
class Matiere
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    /** Libellé arabe de la matière, pour le bulletin bilingue. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nomArabe = null;

    #[ORM\Column(length: 10)]
    private ?string $code = null;

    /** Coefficient utilisé dans le calcul de la moyenne générale pondérée. */
    #[ORM\Column(type: 'integer')]
    private int $coefficient = 1;

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

    public function getNomArabe(): ?string
    {
        return $this->nomArabe;
    }

    public function setNomArabe(?string $nomArabe): static
    {
        $this->nomArabe = $nomArabe;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCoefficient(): int
    {
        return $this->coefficient;
    }

    public function setCoefficient(int $coefficient): static
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
