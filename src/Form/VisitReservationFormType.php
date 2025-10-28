<?php

namespace App\Form;

use App\Entity\Visit;
use App\Entity\VisitSlot;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VisitReservationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('visitSlot', EntityType::class, [
                'class' => VisitSlot::class,
                'label' => 'Créneau de visite',
                'placeholder' => 'Sélectionnez un créneau',
                'choice_label' => function (VisitSlot $slot) {
                    return $slot->getStartTime()->format('d/m/Y à H:i') .
                           ' - ' . $slot->getEndTime()->format('H:i') .
                           ' (' . $slot->getAvailableSlots() . ' place(s) disponible(s))';
                },
                'choices' => $options['available_slots'],
                'required' => true,
                'attr' => ['class' => 'form-select']
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre prénom']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre nom']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'votre@email.com'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre email']),
                    new Assert\Email(['message' => 'L\'adresse email "{{ value }}" n\'est pas valide'])
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+225 XX XX XX XX XX'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez saisir votre téléphone']),
                    new Assert\Regex([
                        'pattern' => '/^[\d\s\+\-\(\)]+$/',
                        'message' => 'Le numéro de téléphone contient des caractères non valides'
                    ])
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Des questions ou informations complémentaires ?'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Visit::class,
            'available_slots' => []
        ]);

        $resolver->setRequired('available_slots');
    }
}
