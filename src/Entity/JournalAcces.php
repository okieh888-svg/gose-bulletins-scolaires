<?php

namespace App\Entity;

use App\Repository\JournalAccesRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Journalisation "qui / quoi / quand" : connexions, transitions de workflow,
 * consultations et téléchargements de bulletin, et accès refusés (403).
 * Alimenté par App\EventSubscriber\JournalisationSubscriber et les contrôleurs
 * du workflow (voir BulletinWorkflowService).
 */
#[ORM\Entity(repositoryClass: JournalAccesRepository::class)]
class JournalAcces
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $utilisateur = null;

    /** Ex: "connexion", "bulletin.publier", "bulletin.telecharger_pdf", "acces_refuse" */
    #[ORM\Column(length: 100)]
    private ?string $action = null;

    /** Ex: "Bulletin#42", "Eleve#7" */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $cible = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $adresseIp = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateAction = null;

    public function __construct()
    {
        $this->dateAction = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getCible(): ?string
    {
        return $this->cible;
    }

    public function setCible(?string $cible): static
    {
        $this->cible = $cible;

        return $this;
    }

    public function getAdresseIp(): ?string
    {
        return $this->adresseIp;
    }

    public function setAdresseIp(?string $adresseIp): static
    {
        $this->adresseIp = $adresseIp;

        return $this;
    }

    public function getDateAction(): ?\DateTimeImmutable
    {
        return $this->dateAction;
    }
}
