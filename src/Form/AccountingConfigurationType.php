<?php

namespace App\Form;

use App\Entity\AccountingConfiguration;
use App\Entity\Organization;
use App\Entity\Company;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountingConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organization', EntityType::class, [
                'label' => 'Organisation',
                'class' => Organization::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Toutes les organisations (général)',
                'attr' => ['class' => 'form-select'],
                'help' => 'Laisser vide pour une configuration globale'
            ])
            ->add('company', EntityType::class, [
                'label' => 'Société',
                'class' => Company::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Toutes les sociétés (général)',
                'attr' => ['class' => 'form-select'],
                'help' => 'Laisser vide pour une configuration globale'
            ])
            ->add('operationType', ChoiceType::class, [
                'label' => 'Type d\'opération',
                'choices' => $options['operationTypes'],
                'placeholder' => 'Sélectionner un type d\'opération',
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('accountNumber', TextType::class, [
                'label' => 'Numéro de compte',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 411000, 706000, 625000'
                ],
                'help' => 'Numéro du compte comptable (plan comptable général)'
            ])
            ->add('accountLabel', TextType::class, [
                'label' => 'Libellé du compte',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Clients - Loyers, Produits - Loyers'
                ],
                'help' => 'Libellé descriptif du compte comptable'
            ])
            ->add('entryType', ChoiceType::class, [
                'label' => 'Sens de l\'écriture',
                'choices' => $options['entryTypes'],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'CREDIT pour les revenus, DEBIT pour les charges'
            ])
            ->add('description', TextType::class, [
                'label' => 'Description de l\'opération',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Loyer généré automatiquement'
                ]
            ])
            ->add('reference', TextType::class, [
                'label' => 'Préfixe de référence',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: LOYER-GEN-, CHARGE-'
                ],
                'help' => 'Préfixe pour la référence de l\'écriture comptable'
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $options['categories'],
                'required' => true,
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes supplémentaires sur cette configuration'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Configuration active',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Décochez pour désactiver temporairement cette configuration'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountingConfiguration::class,
            'operationTypes' => [],
            'entryTypes' => [],
            'categories' => [],
        ]);
    }
}
