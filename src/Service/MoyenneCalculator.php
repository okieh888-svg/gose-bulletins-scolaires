<?php

namespace App\Service;

/**
 * Moteur de calcul des moyennes. Volontairement dépourvu de toute dépendance
 * à Doctrine : il ne manipule que des scalaires/tableaux, ce qui le rend
 * testable unitairement sans base de données (voir tests/Service/MoyenneCalculatorTest.php).
 */
class MoyenneCalculator
{
    public function __construct(private readonly int $precision = 2)
    {
    }

    /**
     * Moyenne simple (non pondérée) d'une liste de notes d'une matière sur une période.
     *
     * @param float[] $valeurs
     */
    public function moyenneMatiere(array $valeurs): ?float
    {
        if ([] === $valeurs) {
            return null;
        }

        return $this->arrondir(array_sum($valeurs) / count($valeurs));
    }

    /**
     * Moyenne générale pondérée par les coefficients des matières.
     *
     * @param array<int, array{moyenne: float, coefficient: int}> $moyennesParMatiere
     */
    public function moyenneGenerale(array $moyennesParMatiere): ?float
    {
        $moyennesParMatiere = array_filter(
            $moyennesParMatiere,
            static fn (array $ligne) => null !== $ligne['moyenne'] && $ligne['coefficient'] > 0
        );

        if ([] === $moyennesParMatiere) {
            return null;
        }

        $sommeCoefficients = array_sum(array_column($moyennesParMatiere, 'coefficient'));
        $sommePonderee = array_sum(array_map(
            static fn (array $ligne) => $ligne['moyenne'] * $ligne['coefficient'],
            $moyennesParMatiere
        ));

        if (0 === $sommeCoefficients) {
            return null;
        }

        return $this->arrondir($sommePonderee / $sommeCoefficients);
    }

    private function arrondir(float $valeur): float
    {
        return round($valeur, $this->precision);
    }
}
