<?php

namespace App\Tests\Service;

use App\Service\AppreciationService;
use PHPUnit\Framework\TestCase;

final class AppreciationServiceTest extends TestCase
{
    private AppreciationService $service;

    protected function setUp(): void
    {
        // Même structure que config/packages/gose.yaml (barème injecté, pas codé en dur).
        $this->service = new AppreciationService([
            ['seuil' => 16, 'libelle' => 'Excellent', 'libelle_ar' => 'ممتاز'],
            ['seuil' => 14, 'libelle' => 'Très bien', 'libelle_ar' => 'جيد جدا'],
            ['seuil' => 12, 'libelle' => 'Bien', 'libelle_ar' => 'جيد'],
            ['seuil' => 10, 'libelle' => 'Assez bien', 'libelle_ar' => 'مقبول'],
            ['seuil' => 8, 'libelle' => 'Passable', 'libelle_ar' => 'ضعيف'],
            ['seuil' => 0, 'libelle' => 'Insuffisant', 'libelle_ar' => 'ضعيف جدا'],
        ]);
    }

    /** @dataProvider provideMoyennes */
    public function testAppreciationSelonLeBareme(float $moyenne, string $attendu): void
    {
        self::assertSame($attendu, $this->service->appreciation($moyenne));
    }

    public static function provideMoyennes(): iterable
    {
        yield 'exactement au seuil Excellent' => [16.0, 'Excellent'];
        yield 'juste en dessous du seuil Excellent' => [15.99, 'Très bien'];
        yield 'Très bien' => [14.5, 'Très bien'];
        yield 'Bien' => [12.2, 'Bien'];
        yield 'Assez bien' => [10.0, 'Assez bien'];
        yield 'Passable' => [8.5, 'Passable'];
        yield 'Insuffisant' => [3.0, 'Insuffisant'];
    }

    public function testAppreciationArabeCorrespondAuMemeSeuil(): void
    {
        self::assertSame('ممتاز', $this->service->appreciationArabe(18.0));
        self::assertSame('ضعيف جدا', $this->service->appreciationArabe(2.0));
    }
}
