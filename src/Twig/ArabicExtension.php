<?php

namespace App\Twig;

use ArPHP\I18N\Arabic;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * dompdf ne fait aucun rendu de texte complexe (pas de liaison des lettres
 * arabes selon leur position, pas de réordonnancement bidi) : sans ce filtre,
 * le PDF affiche les lettres arabes isolées les unes des autres. On les
 * reformate donc en amont, en glyphes déjà liés, via ar-php.
 */
class ArabicExtension extends AbstractExtension
{
    private readonly Arabic $arabic;

    public function __construct()
    {
        $this->arabic = new Arabic();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ar_glyphs', $this->reshape(...)),
        ];
    }

    public function reshape(?string $texte): string
    {
        if (null === $texte || '' === trim($texte)) {
            return (string) $texte;
        }

        // max_chars élevé : on ne veut pas du retour à la ligne automatique
        // de la lib (pensé pour un rendu texte brut), le CSS/dompdf s'en charge.
        // hindo=false : on garde les chiffres occidentaux (notes, dates, matricules).
        //
        // ar-php émet un Warning "Undefined array key" sur certains signes de
        // ponctuation (table de correspondance de glyphes incomplète) sans que
        // cela n'affecte le résultat — vérifié manuellement. Symfony convertit
        // les warnings PHP en exception en dev ; on l'ignore localement.
        set_error_handler(static fn () => true, E_WARNING);
        try {
            return $this->arabic->utf8Glyphs($texte, 10000, false, false);
        } finally {
            restore_error_handler();
        }
    }
}
