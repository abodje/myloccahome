<?php

namespace App\Form;

use App\Entity\Property;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la propriété',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length(['max' => 255])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [new Assert\NotBlank(['message' => 'L\'adresse est obligatoire'])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le code postal est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Le code postal doit contenir 5 chiffres'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [new Assert\NotBlank(['message' => 'La ville est obligatoire'])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de bien',
                'choices' => [
                    'Appartement' => 'appartement',
                    'Maison' => 'maison',
                    'Studio' => 'studio',
                    'Loft' => 'loft',
                    'Duplex' => 'duplex',
                    'Local commercial' => 'commercial',
                    'Bureau' => 'bureau',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (m²)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La surface est obligatoire']),
                    new Assert\Positive(['message' => 'La surface doit être positive'])
                ],
                'attr' => ['class' => 'form-control', 'step' => '0.1']
            ])
            ->add('rooms', IntegerType::class, [
                'label' => 'Nombre de pièces',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de pièces est obligatoire']),
                    new Assert\Positive(['message' => 'Le nombre de pièces doit être positif'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('bedrooms', IntegerType::class, [
                'label' => 'Chambres',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de chambres est obligatoire']),
                    new Assert\PositiveOrZero(['message' => 'Le nombre de chambres doit être positif ou nul'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'Salles de bain',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre de salles de bain est obligatoire']),
                    new Assert\Positive(['message' => 'Le nombre de salles de bain doit être positif'])
                ],
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
            ->add('furnished', CheckboxType::class, [
                'label' => 'Meublé',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('garage', CheckboxType::class, [
                'label' => 'Garage',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('balcony', CheckboxType::class, [
                'label' => 'Balcon/Terrasse',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('elevator', CheckboxType::class, [
                'label' => 'Ascenseur',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('energyRating', ChoiceType::class, [
                'label' => 'Classe énergétique',
                'choices' => [
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                    'E' => 'E',
                    'F' => 'F',
                    'G' => 'G',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}