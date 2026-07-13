<?php

namespace App\Service;

/**
 * Attribue une appréciation littérale à une moyenne selon un barème paramétrable
 * (voir config/packages/gose.yaml: gose.bareme_appreciation). Le barème n'est
 * jamais codé en dur ici afin de pouvoir être ajusté sans toucher au code.
 */
class AppreciationService
{
    /**
     * @param array<int, array{seuil: float, libelle: string, libelle_ar: string}> $bareme
     */
    public function __construct(private readonly array $bareme)
    {
    }

    public function appreciation(float $moyenne): string
    {
        return $this->ligneBareme($moyenne)['libelle'];
    }

    public function appreciationArabe(float $moyenne): string
    {
        return $this->ligneBareme($moyenne)['libelle_ar'];
    }

    /** @return array{seuil: float, libelle: string, libelle_ar: string} */
    private function ligneBareme(float $moyenne): array
    {
        $bareme = $this->bareme;
        usort($bareme, static fn (array $a, array $b) => $b['seuil'] <=> $a['seuil']);

        foreach ($bareme as $ligne) {
            if ($moyenne >= $ligne['seuil']) {
                return $ligne;
            }
        }

        // Filet de sécurité si le barème ne descend pas jusqu'à 0.
        return end($bareme) ?: ['seuil' => 0, 'libelle' => 'Non évalué', 'libelle_ar' => 'غير مقيم'];
    }
}
