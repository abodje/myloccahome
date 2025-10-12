<?php

namespace App\Form;

use App\Entity\Property;
use App\Entity\Owner;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('propertyType', ChoiceType::class, [
                'label' => 'Type de bien',
                'choices' => [
                    'Appartement' => 'Appartement',
                    'Maison' => 'Maison',
                    'Studio' => 'Studio',
                    'Bureau' => 'Bureau',
                    'Local commercial' => 'Local commercial',
                    'Garage' => 'Garage',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (m²)',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('rooms', IntegerType::class, [
                'label' => 'Nombre de pièces',
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyRent', MoneyType::class, [
                'label' => 'Loyer mensuel',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control']
            ])
            ->add('charges', MoneyType::class, [
                'label' => 'Charges',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('deposit', MoneyType::class, [
                'label' => 'Dépôt de garantie',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Libre' => 'Libre',
                    'Occupé' => 'Occupé',
                    'En travaux' => 'En travaux',
                    'Hors service' => 'Hors service',
                ],
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}
