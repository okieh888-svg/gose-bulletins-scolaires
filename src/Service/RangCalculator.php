<?php

namespace App\Service;

/**
 * Calcule le rang d'un élève dans sa classe à partir des moyennes générales
 * de tous les élèves de la classe. Gère les ex-aequo : deux élèves à égalité
 * partagent le même rang, et le rang suivant "saute" (1, 2, 2, 4 — pas 1,2,2,3).
 */
class RangCalculator
{
    /**
     * @param array<int|string, float> $moyennesParEleve clé = identifiant élève, valeur = moyenne générale
     *
     * @return array<int|string, int> clé = identifiant élève, valeur = rang
     */
    public function calculerRangs(array $moyennesParEleve): array
    {
        if ([] === $moyennesParEleve) {
            return [];
        }

        arsort($moyennesParEleve, SORT_NUMERIC);

        $rangs = [];
        $rangCourant = 0;
        $rangAAttribuer = 0;
        $derniereMoyenne = null;

        foreach ($moyennesParEleve as $eleveId => $moyenne) {
            ++$rangCourant;

            if (null === $derniereMoyenne || abs($moyenne - $derniereMoyenne) > 0.0001) {
                $rangAAttribuer = $rangCourant;
            }

            $rangs[$eleveId] = $rangAAttribuer;
            $derniereMoyenne = $moyenne;
        }

        return $rangs;
    }

    public function rangDe(int|string $eleveId, array $moyennesParEleve): ?int
    {
        return $this->calculerRangs($moyennesParEleve)[$eleveId] ?? null;
    }
}
