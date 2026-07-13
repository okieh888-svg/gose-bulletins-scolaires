<?php

namespace App\Controller;

use App\Entity\Bulletin;
use App\Entity\Classe;
use App\Entity\User;
use App\Enum\BulletinStatut;
use App\Form\UserType;
use App\Repository\BulletinRepository;
use App\Repository\ClasseRepository;
use App\Repository\UserRepository;
use App\Security\Voter\BulletinVoter;
use App\Security\Voter\UserVoter;
use App\Service\BulletinWorkflowService;
use App\Service\JournalisationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/proviseur')]
class ProviseurController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClasseRepository $classeRepository,
        private readonly BulletinRepository $bulletinRepository,
        private readonly UserRepository $userRepository,
        private readonly BulletinWorkflowService $workflow,
        private readonly JournalisationService $journal,
    ) {
    }

    #[Route('', name: 'app_proviseur_index')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $etablissement = $user->getEtablissement();

        $classes = $this->classeRepository->findByEtablissement($etablissement->getId());
        $enAttente = $this->bulletinRepository->findEnAttenteParEtablissement($etablissement->getId());

        return $this->render('proviseur/index.html.twig', [
            'etablissement' => $etablissement,
            'classes' => $classes,
            'nbClasses' => count($classes),
            'nbElevesTotal' => array_sum(array_map(static fn (Classe $c) => count($c->getEleves()), $classes)),
            'nbEnAttente' => count($enAttente),
        ]);
    }

    #[Route('/bulletins', name: 'app_proviseur_bulletins')]
    public function bulletins(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $bulletins = $this->bulletinRepository->findEnAttenteParEtablissement($user->getEtablissement()->getId());

        return $this->render('proviseur/bulletins.html.twig', [
            'bulletins' => $bulletins,
        ]);
    }

    #[Route('/bulletin/{id}/publier', name: 'app_proviseur_bulletin_publier', methods: ['POST'])]
    public function publier(int $id, Request $request): Response
    {
        $bulletin = $this->em->getRepository(Bulletin::class)->find($id) ?? throw $this->createNotFoundException();
        $this->denyAccessUnlessGranted(BulletinVoter::PUBLISH, $bulletin);

        if (!$this->isCsrfTokenValid('publier_bulletin_'.$id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $this->workflow->publierParProviseur($bulletin, $user);

        $this->addFlash('success', sprintf('Bulletin de %s publié : visible par l\'élève.', $bulletin->getEleve()->getNomComplet()));

        return $this->redirectToRoute('app_proviseur_bulletins');
    }

    #[Route('/statistiques', name: 'app_proviseur_statistiques')]
    public function statistiques(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $etablissement = $user->getEtablissement();
        $classes = $this->classeRepository->findByEtablissement($etablissement->getId());

        $statsParClasse = [];
        foreach ($classes as $classe) {
            $moyennes = [];
            foreach ($classe->getEleves() as $eleve) {
                foreach ($eleve->getBulletins() as $bulletin) {
                    if (BulletinStatut::PUBLIE === $bulletin->getStatut() && null !== $bulletin->getMoyenneGenerale()) {
                        $moyennes[] = $bulletin->getMoyenneGenerale();
                    }
                }
            }

            $statsParClasse[] = [
                'classe' => $classe,
                'effectif' => count($classe->getEleves()),
                'moyenneClasse' => [] !== $moyennes ? round(array_sum($moyennes) / count($moyennes), 2) : null,
                'nbBulletinsPublies' => count($moyennes),
            ];
        }

        return $this->render('proviseur/statistiques.html.twig', [
            'etablissement' => $etablissement,
            'statsParClasse' => $statsParClasse,
        ]);
    }

    #[Route('/comptes', name: 'app_proviseur_comptes')]
    public function comptes(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $comptes = $this->userRepository->findByEtablissement($user->getEtablissement()->getId());

        return $this->render('proviseur/comptes.html.twig', [
            'comptes' => $comptes,
        ]);
    }

    #[Route('/comptes/nouveau', name: 'app_proviseur_compte_nouveau')]
    public function nouveauCompte(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $proviseur */
        $proviseur = $this->getUser();

        $nouveauUser = new User();
        $form = $this->createForm(UserType::class, $nouveauUser, [
            'rolesDisponibles' => ['Enseignant' => 'ROLE_ENSEIGNANT', 'Élève' => 'ROLE_ELEVE'],
            'afficherEtablissement' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauUser->setEtablissement($proviseur->getEtablissement());
            $nouveauUser->setRoles([$form->get('rolePrincipal')->getData()]);
            $nouveauUser->setPassword($hasher->hashPassword($nouveauUser, $form->get('motDePasse')->getData()));

            $this->denyAccessUnlessGranted(UserVoter::MANAGE, $nouveauUser);

            $this->em->persist($nouveauUser);
            $this->em->flush();

            $this->journal->journaliser($proviseur, 'compte.creer', 'User#'.$nouveauUser->getId());
            $this->addFlash('success', 'Compte créé.');

            return $this->redirectToRoute('app_proviseur_comptes');
        }

        return $this->render('proviseur/compte_nouveau.html.twig', [
            'form' => $form,
        ]);
    }
}
