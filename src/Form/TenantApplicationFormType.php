<?php

namespace App\Form;

use App\Entity\TenantApplication;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TenantApplicationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Informations personnelles
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est requis'])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est requis'])
                ]
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de naissance est requise']),
                    new Assert\LessThan([
                        'value' => '-18 years',
                        'message' => 'Vous devez avoir au moins 18 ans'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est requis']),
                    new Assert\Email(['message' => 'Email invalide'])
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
                    new Assert\NotBlank(['message' => 'Le téléphone est requis'])
                ]
            ])
            ->add('currentAddress', TextareaType::class, [
                'label' => 'Adresse actuelle',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ])

            // Situation professionnelle
            ->add('employmentStatus', ChoiceType::class, [
                'label' => 'Situation professionnelle',
                'required' => true,
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'Salarié(e) en CDI' => 'employed',
                    'Travailleur indépendant' => 'self_employed',
                    'Étudiant(e)' => 'student',
                    'Retraité(e)' => 'retired',
                    'Sans emploi' => 'unemployed'
                ],
                'placeholder' => 'Sélectionnez votre situation',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La situation professionnelle est requise'])
                ]
            ])
            ->add('employer', TextType::class, [
                'label' => 'Employeur / Entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('jobTitle', TextType::class, [
                'label' => 'Poste / Fonction',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyIncome', MoneyType::class, [
                'label' => 'Revenus mensuels nets',
                'required' => true,
                'currency' => 'XOF',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Les revenus sont requis']),
                    new Assert\Positive(['message' => 'Les revenus doivent être positifs'])
                ]
            ])
            ->add('contractType', ChoiceType::class, [
                'label' => 'Type de contrat',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'choices' => [
                    'CDI (Contrat à Durée Indéterminée)' => 'cdi',
                    'CDD (Contrat à Durée Déterminée)' => 'cdd',
                    'Intérim / Mission' => 'interim',
                    'Freelance / Indépendant' => 'freelance'
                ],
                'placeholder' => 'Sélectionnez le type de contrat'
            ])

            // Garanties
            ->add('hasGuarantor', CheckboxType::class, [
                'label' => 'J\'ai un garant',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('guarantorName', TextType::class, [
                'label' => 'Nom complet du garant',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('guarantorRelation', TextType::class, [
                'label' => 'Lien avec le garant',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Parent, ami, employeur...'
                ]
            ])
            ->add('guarantorIncome', MoneyType::class, [
                'label' => 'Revenus mensuels du garant',
                'required' => false,
                'currency' => 'XOF',
                'attr' => ['class' => 'form-control']
            ])

            // Composition du foyer
            ->add('numberOfOccupants', IntegerType::class, [
                'label' => 'Nombre d\'occupants',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 10
                ],
                'data' => 1,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nombre d\'occupants est requis']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 10,
                        'notInRangeMessage' => 'Le nombre d\'occupants doit être entre {{ min }} et {{ max }}'
                    ])
                ]
            ])
            ->add('numberOfChildren', IntegerType::class, [
                'label' => 'Nombre d\'enfants',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 10
                ],
                'data' => 0
            ])
            ->add('hasPets', CheckboxType::class, [
                'label' => 'J\'ai un ou plusieurs animaux',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('petDetails', TextType::class, [
                'label' => 'Détails sur vos animaux',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 1 chat, 1 chien de petite taille...'
                ]
            ])

            // Informations complémentaires
            ->add('desiredMoveInDate', DateType::class, [
                'label' => 'Date d\'emménagement souhaitée',
                'required' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'emménagement est requise']),
                    new Assert\GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date d\'emménagement doit être dans le futur'
                    ])
                ]
            ])
            ->add('desiredLeaseDuration', IntegerType::class, [
                'label' => 'Durée de bail souhaitée (en mois)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 3,
                    'max' => 60
                ],
                'data' => 12,
                'help' => 'Minimum 3 mois, maximum 5 ans'
            ])
            ->add('additionalInfo', TextareaType::class, [
                'label' => 'Informations complémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Parlez-nous de vous, votre motivation, vos besoins particuliers...'
                ],
                'help' => 'Toute information supplémentaire qui pourrait renforcer votre candidature'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TenantApplication::class,
        ]);
    }
}
