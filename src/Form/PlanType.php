<?php

namespace App\Form;

use App\Entity\Plan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du plan',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Starter, Professional, Enterprise'
                ]
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (identifiant unique)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: starter, professional, enterprise'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description du plan et de ses avantages'
                ]
            ])
            ->add('monthlyPrice', NumberType::class, [
                'label' => 'Prix mensuel (FCFA)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'min' => 0,
                    'step' => 1
                ]
            ])
            ->add('yearlyPrice', NumberType::class, [
                'label' => 'Prix annuel (FCFA)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0',
                    'min' => 0,
                    'step' => 1
                ]
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => [
                    'FCFA' => 'FCFA',
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('maxProperties', IntegerType::class, [
                'label' => 'Nombre maximum de propriétés',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => '0 = illimité'
                ]
            ])
            ->add('maxTenants', IntegerType::class, [
                'label' => 'Nombre maximum de locataires',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => '0 = illimité'
                ]
            ])
            ->add('maxUsers', IntegerType::class, [
                'label' => 'Nombre maximum d\'utilisateurs',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => '0 = illimité'
                ]
            ])
            ->add('maxDocuments', IntegerType::class, [
                'label' => 'Nombre maximum de documents',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'placeholder' => '0 = illimité'
                ]
            ])
            ->add('features', ChoiceType::class, [
                'label' => 'Fonctionnalités',
                'choices' => [
                    'Tableau de bord' => 'dashboard',
                    'Gestion des propriétés' => 'properties_management',
                    'Gestion des locataires' => 'tenants_management',
                    'Gestion des baux' => 'lease_management',
                    'Suivi des paiements' => 'payment_tracking',
                    'Documents' => 'documents',
                    'Comptabilité' => 'accounting',
                    'Demandes de maintenance' => 'maintenance_requests',
                    'Paiements en ligne' => 'online_payments',
                    'Acomptes' => 'advance_payments',
                    'Rapports' => 'reports',
                    'Notifications email' => 'email_notifications',
                    'Notifications SMS' => 'sms_notifications',
                    'Marque personnalisée' => 'custom_branding',
                    'Accès API' => 'api_access',
                    'Support prioritaire' => 'priority_support',
                    'Multi-devises' => 'multi_currency',
                    'Gestion des environnements' => 'environment_management',
                    'Multi-sociétés' => 'multi_company',
                    'Analyses avancées' => 'advanced_analytics',
                    'Marque blanche' => 'white_label',
                    'SSO' => 'sso',
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Ordre d\'affichage',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'placeholder' => '1'
                ]
            ])
            ->add('isPopular', CheckboxType::class, [
                'label' => 'Plan populaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Plan actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plan::class,
        ]);
    }
}
