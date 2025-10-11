<?php

namespace App\Form;

use App\Entity\Tenant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TenantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Assert\Length(['max' => 100])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length(['max' => 100])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Email(['message' => 'L\'email n\'est pas valide'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le téléphone est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^(?:\+33|0)[1-9](?:[0-9]{8})$/',
                        'message' => 'Le numéro de téléphone n\'est pas valide'
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de naissance est obligatoire']),
                    new Assert\LessThan([
                        'value' => 'today',
                        'message' => 'La date de naissance doit être antérieure à aujourd\'hui'
                    ])
                ],
                'attr' => ['class' => 'form-control']
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
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyIncome', MoneyType::class, [
                'label' => 'Revenus mensuels',
                'currency' => 'EUR',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Les revenus doivent être positifs'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('employerName', TextType::class, [
                'label' => 'Nom de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('employerAddress', TextType::class, [
                'label' => 'Adresse de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('employerPhone', TelType::class, [
                'label' => 'Téléphone de l\'employeur',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('emergencyContactName', TextType::class, [
                'label' => 'Contact d\'urgence (nom)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => 'Contact d\'urgence (téléphone)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'active',
                    'Inactif' => 'inactive',
                    'Liste noire' => 'blacklisted',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tenant::class,
        ]);
    }
}