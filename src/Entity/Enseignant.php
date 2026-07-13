<?php

namespace App\Entity;

use App\Repository\EnseignantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnseignantRepository::class)]
class Enseignant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'profilEnseignant', targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /** @var Collection<int, Affectation> Classes/matières attribuées à cet enseignant. */
    #[ORM\OneToMany(mappedBy: 'enseignant', targetEntity: Affectation::class)]
    private Collection $affectations;

    public function __construct()
    {
        $this->affectations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        // Synchronise le côté inverse en mémoire (voir User::setProfilEnseignant()).
        $user->setProfilEnseignant($this);

        return $this;
    }

    /** @return Collection<int, Affectation> */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    /** @return Classe[] Classes distinctes enseignées, toutes affectations confondues. */
    public function getClasses(): array
    {
        $classes = [];
        foreach ($this->affectations as $affectation) {
            $classes[$affectation->getClasse()->getId()] = $affectation->getClasse();
        }

        return array_values($classes);
    }

    public function enseigneDans(Classe $classe, ?Matiere $matiere = null): bool
    {
        foreach ($this->affectations as $affectation) {
            if ($affectation->getClasse()->getId() !== $classe->getId()) {
                continue;
            }
            if (null === $matiere || $affectation->getMatiere()->getId() === $matiere->getId()) {
                return true;
            }
        }

        return false;
    }
}
