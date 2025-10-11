<?php

namespace App\Form;

use App\Entity\Property;
use App\Entity\RentalContract;
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
use Symfony\Component\Validator\Constraints as Assert;

class RentalContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'title',
                'label' => 'Propriété',
                'placeholder' => 'Sélectionner une propriété...',
                'constraints' => [new Assert\NotBlank(['message' => 'Veuillez sélectionner une propriété'])],
                'attr' => ['class' => 'form-select']
            ])
            ->add('tenant', EntityType::class, [
                'class' => Tenant::class,
                'choice_label' => 'fullName',
                'label' => 'Locataire',
                'placeholder' => 'Sélectionner un locataire...',
                'constraints' => [new Assert\NotBlank(['message' => 'Veuillez sélectionner un locataire'])],
                'attr' => ['class' => 'form-select']
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est obligatoire'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('rentAmount', MoneyType::class, [
                'label' => 'Loyer mensuel',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le loyer est obligatoire']),
                    new Assert\Positive(['message' => 'Le loyer doit être positif'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('charges', MoneyType::class, [
                'label' => 'Charges mensuelles',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Les charges sont obligatoires']),
                    new Assert\PositiveOrZero(['message' => 'Les charges doivent être positives ou nulles'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('deposit', MoneyType::class, [
                'label' => 'Dépôt de garantie',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le dépôt de garantie est obligatoire']),
                    new Assert\PositiveOrZero(['message' => 'Le dépôt de garantie doit être positif ou nul'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('rentDueDay', IntegerType::class, [
                'label' => 'Jour d\'échéance du loyer',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le jour d\'échéance est obligatoire']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 28,
                        'notInRangeMessage' => 'Le jour d\'échéance doit être entre 1 et 28'
                    ])
                ],
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 28]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'active',
                    'Résilié' => 'terminated',
                    'Expiré' => 'expired',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('specialConditions', TextareaType::class, [
                'label' => 'Conditions particulières',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RentalContract::class,
        ]);
    }
}