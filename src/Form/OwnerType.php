<?php

namespace App\Form;

use App\Entity\Owner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OwnerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ownerType', ChoiceType::class, [
                'label' => 'Type de propriétaire',
                'choices' => [
                    'Particulier' => 'Particulier',
                    'SCI (Société Civile Immobilière)' => 'SCI',
                    'SARL' => 'SARL',
                    'SA' => 'SA',
                    'SAS' => 'SAS',
                    'Autre société' => 'Société',
                ],
                'attr' => ['class' => 'form-select'],
                'required' => true,
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Jean'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Dupont'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'jean.dupont@example.com'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Email(['message' => 'Email invalide']),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control', 'placeholder' => '+225 01 23 45 67 89'],
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control', 'placeholder' => '123 Rue de la Paix'],
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Abidjan'],
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control', 'placeholder' => '00225'],
                'required' => false,
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET / Numéro d\'identification',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Pour les sociétés uniquement'],
                'required' => false,
                'help' => 'Uniquement pour les sociétés (SCI, SARL, etc.)',
            ])
            ->add('bankAccount', TextType::class, [
                'label' => 'Compte bancaire (IBAN)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'CI00 0000 0000 0000 0000 0000'],
                'required' => false,
                'help' => 'Pour les virements de loyers',
            ])
            ->add('commissionRate', NumberType::class, [
                'label' => 'Taux de commission (%)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '5.00',
                    'step' => '0.01',
                    'min' => '0',
                    'max' => '100'
                ],
                'required' => false,
                'help' => 'Pourcentage de commission sur les loyers',
                'html5' => true,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Informations supplémentaires...'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Owner::class,
        ]);
    }
}

