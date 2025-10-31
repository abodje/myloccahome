<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Organization;
use App\Entity\Company;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'] ?? null;
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('mobilePhone', TextType::class, [
                'label' => 'Téléphone mobile',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Locataire' => 'ROLE_TENANT',
                    'Gestionnaire' => 'ROLE_MANAGER',
                    'Administrateur' => 'ROLE_ADMIN',
                    //'Super Administrateur' => 'ROLE_SUPER_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'data' => ['ROLE_USER'], // Rôle par défaut
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
                'data' => true, // Actif par défaut
            ]);

        // Champ mot de passe
        if (!$isEdit) {
            $builder->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'required' => true,
                'mapped' => false, // Ne pas mapper directement sur l'entité
            ]);
        } else {
            $builder->add('password', PasswordType::class, [
                'label' => 'Nouveau mot de passe (laisser vide pour ne pas changer)',
                'required' => false,
                'mapped' => false,
            ]);
        }

        // Champ organisation
        // Seulement afficher si l'utilisateur connecté n'a pas d'organisation (Super Admin)
        if (!$currentUser || !$currentUser->getOrganization()) {
            $builder->add('organization', EntityType::class, [
                'class' => Organization::class,
                'choice_label' => 'name',
                'label' => 'Organisation',
                'required' => true,
                'choices' => $options['organizations'] ?? [],
                'placeholder' => 'Sélectionner une organisation',
            ]);
        }

        // Champ société
        // Afficher seulement si l'utilisateur a une organisation mais pas de société
        if ($currentUser && $currentUser->getOrganization() && !$currentUser->getCompany()) {
            $builder->add('company', EntityType::class, [
                'class' => Company::class,
                'choice_label' => 'name',
                'label' => 'Société',
                'required' => false,
                'choices' => $options['companies'] ?? [],
                'placeholder' => 'Sélectionner une société (optionnel)',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'organizations' => [],
            'companies' => [],
            'current_user' => null,
            'is_edit' => false,
        ]);
    }
}
