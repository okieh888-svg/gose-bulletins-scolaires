<?php

namespace App\Tests\Service;

use App\Service\MoyenneCalculator;
use PHPUnit\Framework\TestCase;

final class MoyenneCalculatorTest extends TestCase
{
    private MoyenneCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new MoyenneCalculator(precision: 2);
    }

    public function testMoyenneMatiereSimple(): void
    {
        self::assertSame(14.0, $this->calculator->moyenneMatiere([12, 16]));
    }

    public function testMoyenneMatiereArrondiALaPrecisionConfiguree(): void
    {
        self::assertSame(13.33, $this->calculator->moyenneMatiere([10, 15, 15]));
    }

    public function testMoyenneMatiereSansNoteRetourneNull(): void
    {
        self::assertNull($this->calculator->moyenneMatiere([]));
    }

    public function testMoyenneGeneralePondereeParCoefficient(): void
    {
        // Maths (coef 4) à 10, Français (coef 4) à 16, EPS (coef 1) à 20
        // -> (10*4 + 16*4 + 20*1) / 9 = 124 / 9 = 13.78
        $moyenne = $this->calculator->moyenneGenerale([
            ['moyenne' => 10, 'coefficient' => 4],
            ['moyenne' => 16, 'coefficient' => 4],
            ['moyenne' => 20, 'coefficient' => 1],
        ]);

        self::assertSame(13.78, $moyenne);
    }

    public function testMoyenneGeneraleIgnoreLesMatieresSansCoefficient(): void
    {
        $moyenne = $this->calculator->moyenneGenerale([
            ['moyenne' => 10, 'coefficient' => 2],
            ['moyenne' => 20, 'coefficient' => 0], // ignorée : coefficient nul
        ]);

        self::assertSame(10.0, $moyenne);
    }

    public function testMoyenneGeneraleSansDonneesRetourneNull(): void
    {
        self::assertNull($this->calculator->moyenneGenerale([]));
    }
}
