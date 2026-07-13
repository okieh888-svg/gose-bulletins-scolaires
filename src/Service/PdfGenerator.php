<?php

namespace App\Service;

use App\Entity\Bulletin;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

/**
 * Génère le PDF du bulletin à partir d'un template Twig (HTML) rendu par dompdf.
 * Deux gabarits : français (LTR) et arabe (RTL), voir templates/bulletin/pdf.html.twig
 * et pdf_ar.html.twig — preuve du bilinguisme attendu au Lot 8 du TDR.
 */
class PdfGenerator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly AppreciationService $appreciationService,
        private readonly QrCodeGenerator $qrCodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $logoPath,
    ) {
    }

    public function genererPourBulletin(Bulletin $bulletin, bool $enArabe = false): string
    {
        $template = $enArabe ? 'bulletin/pdf_ar.html.twig' : 'bulletin/pdf.html.twig';

        $contexte = [
            'bulletin' => $bulletin,
            // Embarqué en base64 : dompdf n'a alors aucune résolution de chemin/URL
            // à faire, ce qui est plus robuste quel que soit l'environnement d'exécution.
            'logoDataUri' => $this->logoDataUri(),
            'verificationUrl' => null,
            'qrCodeDataUri' => null,
        ];

        // Seul un bulletin PUBLIÉ porte un code de vérification (voir BulletinWorkflowService).
        if (null !== $bulletin->getCodeVerification()) {
            $url = $this->urlGenerator->generate(
                'app_verification_resultat',
                ['code' => $bulletin->getCodeVerification()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $contexte['verificationUrl'] = $url;
            $contexte['qrCodeDataUri'] = $this->qrCodeGenerator->genererDataUri($url);
        }

        if ($enArabe) {
            $appreciationsLignes = [];
            foreach ($bulletin->getLignes() as $ligne) {
                $appreciationsLignes[$ligne->getId()] = $this->appreciationService->appreciationArabe($ligne->getMoyenne());
            }
            $contexte['appreciationsArabeLignes'] = $appreciationsLignes;
            $contexte['appreciationGeneraleArabe'] = null !== $bulletin->getMoyenneGenerale()
                ? $this->appreciationService->appreciationArabe($bulletin->getMoyenneGenerale())
                : null;
        }

        $html = $this->twig->render($template, $contexte);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function logoDataUri(): ?string
    {
        if (!is_file($this->logoPath)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($this->logoPath, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };

        return sprintf('data:%s;base64,%s', $mime, base64_encode(file_get_contents($this->logoPath)));
    }

    public function nomFichier(Bulletin $bulletin, bool $enArabe = false): string
    {
        $suffixe = $enArabe ? '-ar' : '';

        return sprintf(
            'bulletin-%s-%s%s.pdf',
            preg_replace('/[^a-zA-Z0-9]+/', '-', $bulletin->getEleve()->getNomComplet()),
            $bulletin->getPeriode()->getNom(),
            $suffixe
        );
    }
}
