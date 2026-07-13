<?php

namespace App\Entity;

use App\Enum\BulletinStatut;
use App\Repository\BulletinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Bulletin d'un élève pour une période donnée.
 * Les BulletinLigne sont un instantané des moyennes par matière au moment de la
 * génération/régénération : cela garantit qu'un bulletin publié reste identique
 * à l'archive même si des notes sont modifiées après coup (traçabilité).
 */
#[ORM\Entity(repositoryClass: BulletinRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_bulletin_eleve_periode', columns: ['eleve_id', 'periode_id'])]
class Bulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Eleve::class, inversedBy: 'bulletins')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Eleve $eleve = null;

    #[ORM\ManyToOne(targetEntity: Periode::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Periode $periode = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $moyenneGenerale = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rang = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $effectifClasse = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $appreciationGenerale = null;

    #[ORM\Column(length: 30, enumType: BulletinStatut::class)]
    private BulletinStatut $statut = BulletinStatut::BROUILLON;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateGeneration = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $genereParEnseignant = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateValidationEnseignant = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $valideParEnseignant = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $datePublication = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $publieParProviseur = null;

    /**
     * Code unique généré à la PUBLICATION (jamais avant), permettant à un tiers
     * (employeur, autre établissement...) de vérifier l'authenticité d'un bulletin
     * imprimé/PDF sans avoir de compte GOSE — voir App\Controller\VerificationController.
     * Un bulletin en brouillon ou régénéré perd son code (nouvelle valeur à la republication).
     */
    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $codeVerification = null;

    #[ORM\OneToMany(mappedBy: 'bulletin', targetEntity: BulletinLigne::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
        $this->dateGeneration = new \DateTimeImmutable();
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

    public function getPeriode(): ?Periode
    {
        return $this->periode;
    }

    public function setPeriode(?Periode $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    public function getMoyenneGenerale(): ?float
    {
        return $this->moyenneGenerale;
    }

    public function setMoyenneGenerale(?float $moyenneGenerale): static
    {
        $this->moyenneGenerale = $moyenneGenerale;

        return $this;
    }

    public function getRang(): ?int
    {
        return $this->rang;
    }

    public function setRang(?int $rang): static
    {
        $this->rang = $rang;

        return $this;
    }

    public function getEffectifClasse(): ?int
    {
        return $this->effectifClasse;
    }

    public function setEffectifClasse(?int $effectifClasse): static
    {
        $this->effectifClasse = $effectifClasse;

        return $this;
    }

    public function getRangAffiche(): string
    {
        if (null === $this->rang || null === $this->effectifClasse) {
            return '-';
        }

        return sprintf('%d / %d', $this->rang, $this->effectifClasse);
    }

    public function getAppreciationGenerale(): ?string
    {
        return $this->appreciationGenerale;
    }

    public function setAppreciationGenerale(?string $appreciationGenerale): static
    {
        $this->appreciationGenerale = $appreciationGenerale;

        return $this;
    }

    public function getStatut(): BulletinStatut
    {
        return $this->statut;
    }

    public function setStatut(BulletinStatut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateGeneration(): ?\DateTimeImmutable
    {
        return $this->dateGeneration;
    }

    public function setDateGeneration(\DateTimeImmutable $dateGeneration): static
    {
        $this->dateGeneration = $dateGeneration;

        return $this;
    }

    public function getGenereParEnseignant(): ?User
    {
        return $this->genereParEnseignant;
    }

    public function setGenereParEnseignant(?User $genereParEnseignant): static
    {
        $this->genereParEnseignant = $genereParEnseignant;

        return $this;
    }

    public function getDateValidationEnseignant(): ?\DateTimeImmutable
    {
        return $this->dateValidationEnseignant;
    }

    public function setDateValidationEnseignant(?\DateTimeImmutable $date): static
    {
        $this->dateValidationEnseignant = $date;

        return $this;
    }

    public function getValideParEnseignant(): ?User
    {
        return $this->valideParEnseignant;
    }

    public function setValideParEnseignant(?User $user): static
    {
        $this->valideParEnseignant = $user;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeImmutable
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeImmutable $datePublication): static
    {
        $this->datePublication = $datePublication;

        return $this;
    }

    public function getPublieParProviseur(): ?User
    {
        return $this->publieParProviseur;
    }

    public function setPublieParProviseur(?User $user): static
    {
        $this->publieParProviseur = $user;

        return $this;
    }

    public function getCodeVerification(): ?string
    {
        return $this->codeVerification;
    }

    public function setCodeVerification(?string $codeVerification): static
    {
        $this->codeVerification = $codeVerification;

        return $this;
    }

    /** @return Collection<int, BulletinLigne> */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(BulletinLigne $ligne): static
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setBulletin($this);
        }

        return $this;
    }

    public function viderLignes(): static
    {
        $this->lignes->clear();

        return $this;
    }
}
