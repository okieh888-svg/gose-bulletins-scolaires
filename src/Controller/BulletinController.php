<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\User;
use App\Security\Voter\BulletinVoter;
use App\Service\JournalisationService;
use App\Service\PdfGenerator;
use App\Service\QrCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Points d'accès communs aux trois rôles (enseignant/proviseur/élève) : chaque
 * action revérifie explicitement BulletinVoter::VIEW, qui applique lui-même
 * les invariants de propriété et de cloisonnement — voir App\Security\Voter\BulletinVoter.
 */
#[Route('/bulletin')]
class BulletinController extends AbstractController
{
    public function __construct(
        private readonly PdfGenerator $pdfGenerator,
        private readonly JournalisationService $journal,
        private readonly QrCodeGenerator $qrCodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/{id}', name: 'app_bulletin_apercu')]
    public function apercu(Bulletin $bulletin): Response
    {
        $this->denyAccessUnlessGranted(BulletinVoter::VIEW, $bulletin);

        /** @var User $user */
        $user = $this->getUser();
        $this->journal->journaliser($user, 'bulletin.consulter', 'Bulletin#'.$bulletin->getId());

        $verificationUrl = null;
        $qrCodeDataUri = null;
        if (null !== $bulletin->getCodeVerification()) {
            $verificationUrl = $this->urlGenerator->generate(
                'app_verification_resultat',
                ['code' => $bulletin->getCodeVerification()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $qrCodeDataUri = $this->qrCodeGenerator->genererDataUri($verificationUrl);
        }

        return $this->render('bulletin/apercu.html.twig', [
            'bulletin' => $bulletin,
            'verificationUrl' => $verificationUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_bulletin_pdf')]
    public function pdf(Bulletin $bulletin): Response
    {
        $this->denyAccessUnlessGranted(BulletinVoter::VIEW, $bulletin);

        /** @var User $user */
        $user = $this->getUser();
        $this->journal->journaliser($user, 'bulletin.telecharger_pdf', 'Bulletin#'.$bulletin->getId());

        $pdf = $this->pdfGenerator->genererPourBulletin($bulletin, enArabe: false);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $this->pdfGenerator->nomFichier($bulletin)),
        ]);
    }

    #[Route('/{id}/pdf/arabe', name: 'app_bulletin_pdf_arabe')]
    public function pdfArabe(Bulletin $bulletin): Response
    {
        $this->denyAccessUnlessGranted(BulletinVoter::VIEW, $bulletin);

        /** @var User $user */
        $user = $this->getUser();
        $this->journal->journaliser($user, 'bulletin.telecharger_pdf_arabe', 'Bulletin#'.$bulletin->getId());

        $pdf = $this->pdfGenerator->genererPourBulletin($bulletin, enArabe: true);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="%s"', $this->pdfGenerator->nomFichier($bulletin, enArabe: true)),
        ]);
    }
}
