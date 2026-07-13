<?php

namespace App\Form;

use App\Entity\Etablissement;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de création de compte, utilisé par le Proviseur (limité à son
 * établissement et aux rôles ENSEIGNANT/ELEVE) et par l'Admin (tous rôles,
 * tous établissements). Le périmètre exact est imposé par le contrôleur via
 * les options 'rolesDisponibles' et 'afficherEtablissement', PAS par le Voter
 * (qui protège l'accès à l'écran, pas le contenu du formulaire).
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prenom', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('nom', TextType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [new NotBlank()],
            ])
            ->add('rolePrincipal', ChoiceType::class, [
                'mapped' => false,
                'choices' => $options['rolesDisponibles'],
                'expanded' => true,
                'label' => 'Rôle',
            ])
            ->add('motDePasse', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'constraints' => [new NotBlank(), new Length(min: 6)],
            ]);

        if ($options['afficherEtablissement']) {
            $builder->add('etablissement', EntityType::class, [
                'class' => Etablissement::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => 'Aucun (compte ADMIN uniquement)',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'rolesDisponibles' => [],
            'afficherEtablissement' => false,
        ]);
    }
}
