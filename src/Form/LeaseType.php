<?php

namespace App\Form;

use App\Entity\Lease;
use App\Entity\Property;
use App\Entity\Tenant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LeaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'fullAddress',
                'label' => 'Propriété',
                'placeholder' => 'Sélectionner une propriété',
                'attr' => ['class' => 'form-select']
            ])
            ->add('tenant', EntityType::class, [
                'class' => Tenant::class,
                'choice_label' => 'fullName',
                'label' => 'Locataire',
                'placeholder' => 'Sélectionner un locataire',
                'attr' => ['class' => 'form-select']
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'help' => 'Laisser vide pour un contrat à durée indéterminée',
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyRent', MoneyType::class, [
                'label' => 'Loyer mensuel',
                'currency' => 'XOF',
                'attr' => ['class' => 'form-control']
            ])
            ->add('charges', MoneyType::class, [
                'label' => 'Charges mensuelles',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('deposit', MoneyType::class, [
                'label' => 'Dépôt de garantie',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('rentDueDay', IntegerType::class, [
                'label' => 'Jour d\'échéance du loyer',
                'help' => 'Jour du mois où le loyer est dû (1-31)',
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 31]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'Actif',
                    'Terminé' => 'Terminé',
                    'Résilié' => 'Résilié',
                    'Suspendu' => 'Suspendu',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Conditions particulières',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lease::class,
        ]);
    }
}
