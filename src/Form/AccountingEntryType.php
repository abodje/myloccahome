<?php

namespace App\Form;

use App\Entity\AccountingEntry;
use App\Entity\Property;
use App\Entity\Owner;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountingEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entryDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control']
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Crédit (Entrée d\'argent)' => 'CREDIT',
                    'Débit (Sortie d\'argent)' => 'DEBIT',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Loyer' => 'LOYER',
                    'Charges' => 'CHARGES',
                    'Dépôt de garantie' => 'DEPOT_GARANTIE',
                    'Travaux' => 'TRAVAUX',
                    'Assurance' => 'ASSURANCE',
                    'Taxe foncière' => 'TAXE_FONCIERE',
                    'Frais de gestion' => 'FRAIS_GESTION',
                    'Honoraires' => 'HONORAIRES',
                    'Réparations' => 'REPARATIONS',
                    'Entretien' => 'ENTRETIEN',
                    'Virement' => 'VIREMENT',
                    'Prélèvement' => 'PRELEVEMENT',
                    'Autre' => 'AUTRE',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'help' => 'Numéro de transaction, chèque, virement, etc.',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'fullAddress',
                'label' => 'Propriété',
                'placeholder' => 'Sélectionner une propriété',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('owner', EntityType::class, [
                'class' => Owner::class,
                'choice_label' => 'fullName',
                'label' => 'Propriétaire',
                'placeholder' => 'Sélectionner un propriétaire',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountingEntry::class,
        ]);
    }
}
