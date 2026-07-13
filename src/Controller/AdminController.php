<?php

namespace App\Controller;

use App\Entity\Etablissement;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\EtablissementRepository;
use App\Repository\UserRepository;
use App\Service\JournalisationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

/** Réservé à ROLE_ADMIN (voir access_control dans config/packages/security.yaml). */
#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EtablissementRepository $etablissementRepository,
        private readonly UserRepository $userRepository,
        private readonly JournalisationService $journal,
    ) {
    }

    #[Route('', name: 'app_admin_index')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'etablissements' => $this->etablissementRepository->findAll(),
            'journal' => $this->em->getRepository(\App\Entity\JournalAcces::class)->findDernieres(30),
        ]);
    }

    #[Route('/etablissements/{id}', name: 'app_admin_etablissement')]
    public function etablissement(Etablissement $etablissement): Response
    {
        return $this->render('admin/etablissement.html.twig', [
            'etablissement' => $etablissement,
            'comptes' => $this->userRepository->findByEtablissement($etablissement->getId()),
        ]);
    }

    #[Route('/comptes/nouveau', name: 'app_admin_compte_nouveau')]
    public function nouveauCompte(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $admin */
        $admin = $this->getUser();

        $nouveauUser = new User();
        $form = $this->createForm(UserType::class, $nouveauUser, [
            'rolesDisponibles' => [
                'Administrateur' => 'ROLE_ADMIN',
                'Proviseur' => 'ROLE_PROVISEUR',
                'Enseignant' => 'ROLE_ENSEIGNANT',
                'Élève' => 'ROLE_ELEVE',
            ],
            'afficherEtablissement' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauUser->setRoles([$form->get('rolePrincipal')->getData()]);
            $nouveauUser->setPassword($hasher->hashPassword($nouveauUser, $form->get('motDePasse')->getData()));

            $this->em->persist($nouveauUser);
            $this->em->flush();

            $this->journal->journaliser($admin, 'compte.creer', 'User#'.$nouveauUser->getId());
            $this->addFlash('success', 'Compte créé.');

            return $this->redirectToRoute('app_admin_index');
        }

        return $this->render('admin/compte_nouveau.html.twig', [
            'form' => $form,
        ]);
    }
}
