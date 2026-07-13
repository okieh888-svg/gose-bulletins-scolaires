<?php

namespace App\Enum;

/**
 * Workflow de validation du bulletin :
 * BROUILLON (créé/regénéré par l'enseignant)
 *   -> VALIDE_ENSEIGNANT (l'enseignant valide ses saisies)
 *   -> PUBLIE (le proviseur publie ; visible par l'élève)
 *
 * Transitions autorisées uniquement dans ce sens (voir BulletinWorkflowService).
 */
enum BulletinStatut: string
{
    case BROUILLON = 'brouillon';
    case VALIDE_ENSEIGNANT = 'valide_enseignant';
    case PUBLIE = 'publie';

    public function libelle(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::VALIDE_ENSEIGNANT => 'Validé par l\'enseignant',
            self::PUBLIE => 'Publié',
        };
    }

    public function couleurBadge(): string
    {
        return match ($this) {
            self::BROUILLON => 'gris',
            self::VALIDE_ENSEIGNANT => 'orange',
            self::PUBLIE => 'vert',
        };
    }
}
