<?php

namespace App\Tests\Service;

use App\Service\RangCalculator;
use PHPUnit\Framework\TestCase;

final class RangCalculatorTest extends TestCase
{
    private RangCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RangCalculator();
    }

    public function testClassementSimpleDecroissant(): void
    {
        $rangs = $this->calculator->calculerRangs([
            'eleve_a' => 12.5,
            'eleve_b' => 16.0,
            'eleve_c' => 9.0,
        ]);

        self::assertSame(2, $rangs['eleve_a']);
        self::assertSame(1, $rangs['eleve_b']);
        self::assertSame(3, $rangs['eleve_c']);
    }

    public function testExAequoPartagentLeMemeRangEtLeRangSuivantSaute(): void
    {
        // b et c sont ex-aequo à 14 : tous deux 2e, et d devient 4e (pas 3e).
        $rangs = $this->calculator->calculerRangs([
            'eleve_a' => 16.0,
            'eleve_b' => 14.0,
            'eleve_c' => 14.0,
            'eleve_d' => 12.0,
        ]);

        self::assertSame(1, $rangs['eleve_a']);
        self::assertSame(2, $rangs['eleve_b']);
        self::assertSame(2, $rangs['eleve_c']);
        self::assertSame(4, $rangs['eleve_d']);
    }

    public function testListeVideRetourneTableauVide(): void
    {
        self::assertSame([], $this->calculator->calculerRangs([]));
    }

    public function testRangDeCibleUnEleveParticulier(): void
    {
        $moyennes = ['a' => 10.0, 'b' => 15.0];

        self::assertSame(1, $this->calculator->rangDe('b', $moyennes));
        self::assertNull($this->calculator->rangDe('inexistant', $moyennes));
    }
}
