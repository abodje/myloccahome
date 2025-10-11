<?php

namespace App\Form;

use App\Entity\Maintenance;
use App\Entity\Property;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

class MaintenanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => function(Property $property) {
                    return $property->getTitle() . ' - ' . $property->getFullAddress();
                },
                'label' => 'Propriété',
                'placeholder' => 'Sélectionner une propriété...',
                'constraints' => [new Assert\NotBlank(['message' => 'Veuillez sélectionner une propriété'])],
                'attr' => ['class' => 'form-select']
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'intervention',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire']),
                    new Assert\Length(['max' => 255])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'constraints' => [new Assert\NotBlank(['message' => 'La description est obligatoire'])],
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'intervention',
                'choices' => [
                    'Réparation' => 'reparation',
                    'Maintenance préventive' => 'preventive',
                    'Urgence' => 'urgence',
                    'Amélioration' => 'amelioration',
                    'Inspection' => 'inspection',
                    'Nettoyage' => 'nettoyage',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 'low',
                    'Normale' => 'normal',
                    'Haute' => 'high',
                    'Urgente' => 'urgent',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'En cours' => 'in_progress',
                    'Terminé' => 'completed',
                    'Annulé' => 'cancelled',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('reportedDate', DateType::class, [
                'label' => 'Date de signalement',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de signalement est obligatoire'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('scheduledDate', DateType::class, [
                'label' => 'Date prévue d\'intervention',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('completedDate', DateType::class, [
                'label' => 'Date de réalisation',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('reportedBy', ChoiceType::class, [
                'label' => 'Signalé par',
                'choices' => [
                    'Locataire' => 'locataire',
                    'Propriétaire' => 'proprietaire',
                    'Inspection' => 'inspection',
                    'Voisin' => 'voisin',
                    'Autre' => 'autre',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('contractorName', TextType::class, [
                'label' => 'Nom de l\'intervenant/entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contractorPhone', TelType::class, [
                'label' => 'Téléphone de l\'intervenant',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contractorEmail', EmailType::class, [
                'label' => 'Email de l\'intervenant',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('estimatedCost', MoneyType::class, [
                'label' => 'Coût estimé',
                'currency' => 'EUR',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le coût estimé doit être positif ou nul'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('actualCost', MoneyType::class, [
                'label' => 'Coût réel',
                'currency' => 'EUR',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le coût réel doit être positif ou nul'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('workPerformed', TextareaType::class, [
                'label' => 'Travaux réalisés',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes complémentaires',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Maintenance::class,
        ]);
    }
}