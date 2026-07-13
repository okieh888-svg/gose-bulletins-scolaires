<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Génère un QR code PNG entièrement en local (aucun appel réseau/API externe),
 * embarqué en base64 — utilisable aussi bien dans une page HTML que dans un PDF
 * rendu par dompdf (voir PdfGenerator).
 */
class QrCodeGenerator
{
    public function genererDataUri(string $contenu): string
    {
        $resultat = Builder::create()
            ->writer(new PngWriter())
            ->data($contenu)
            ->size(180)
            ->margin(4)
            ->build();

        return $resultat->getDataUri();
    }
}
