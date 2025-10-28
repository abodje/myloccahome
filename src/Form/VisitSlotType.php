<?php

namespace App\Form;

use App\Entity\Property;
use App\Entity\VisitSlot;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VisitSlotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'label' => 'Propriété',
                'choice_label' => function (Property $property) {
                    return $property->getFullAddress() . ' (' . $property->getPropertyType() . ')';
                },
                'placeholder' => 'Sélectionnez une propriété',
                'attr' => ['class' => 'form-select'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner une propriété'])
                ]
            ])
            ->add('startTime', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de début est requise']),
                    new Assert\GreaterThan([
                        'value' => 'now',
                        'message' => 'La date doit être dans le futur'
                    ])
                ]
            ])
            ->add('endTime', DateTimeType::class, [
                'label' => 'Date et heure de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de fin est requise'])
                ]
            ])
            ->add('maxVisitors', IntegerType::class, [
                'label' => 'Nombre maximum de visiteurs',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 10
                ],
                'data' => 1,
                'help' => 'Nombre de personnes pouvant réserver ce créneau',
                'constraints' => [
                    new Assert\Positive(['message' => 'Le nombre doit être positif']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 10,
                        'notInRangeMessage' => 'Le nombre doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Disponible' => 'available',
                    'Complet' => 'full',
                    'Annulé' => 'cancelled'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes internes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Instructions particulières, accès, etc.'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VisitSlot::class,
        ]);
    }
}
