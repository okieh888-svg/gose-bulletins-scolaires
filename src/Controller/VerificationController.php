<?php

namespace App\Controller;

use App\Repository\BulletinRepository;
use App\Service\JournalisationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Vérification publique de l'authenticité d'un bulletin (anti-falsification).
 *
 * Volontairement SANS authentification (voir PUBLIC_ACCESS dans security.yaml) :
 * un tiers qui reçoit un bulletin papier ou PDF (employeur, autre établissement,
 * etc.) doit pouvoir en confirmer l'authenticité sans avoir de compte GOSE, en
 * scannant le QR code ou en saisissant le code affiché sur le document.
 *
 * Pour limiter l'exposition de données côté public, seules les informations de
 * synthèse sont affichées (pas le détail des notes par matière), et uniquement
 * pour un bulletin réellement PUBLIÉ (voir BulletinRepository::findOnePublieParCode).
 * Le code lui-même est un secret de 32 caractères hexadécimaux (128 bits
 * d'aléa), donc pratiquement impossible à deviner par force brute.
 */
#[Route('/verification')]
class VerificationController extends AbstractController
{
    public function __construct(
        private readonly BulletinRepository $bulletinRepository,
        private readonly JournalisationService $journal,
    ) {
    }

    #[Route('', name: 'app_verification_index')]
    public function index(Request $request): Response
    {
        $code = trim((string) $request->query->get('code', ''));
        if ('' !== $code) {
            return $this->redirectToRoute('app_verification_resultat', ['code' => $code]);
        }

        return $this->render('verification/formulaire.html.twig');
    }

    #[Route('/{code}', name: 'app_verification_resultat')]
    public function resultat(string $code): Response
    {
        $bulletin = $this->bulletinRepository->findOnePublieParCode($code);

        $this->journal->journaliser(null, 'verification.consulter', 'Code:'.substr($code, 0, 8).'…');

        return $this->render('verification/resultat.html.twig', [
            'bulletin' => $bulletin,
            'code' => $code,
        ]);
    }
}
