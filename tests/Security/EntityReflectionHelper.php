<?php

namespace App\Tests\Security;

/**
 * Les entités Doctrine de ce projet n'exposent pas de setId() (l'identifiant est
 * généré par la base). Pour des tests de Voter unitaires SANS base de données,
 * on force l'identifiant via réflexion afin que les comparaisons par ID dans
 * les Voters (ex: $a->getId() === $b->getId()) soient significatives — sans
 * cela, deux entités jamais persistées auraient toutes deux un id `null` et
 * les comparaisons "réussiraient" pour de mauvaises raisons.
 */
trait EntityReflectionHelper
{
    private function forcerId(object $entite, int $id): void
    {
        $propriete = new \ReflectionProperty($entite, 'id');
        $propriete->setAccessible(true);
        $propriete->setValue($entite, $id);
    }
}
