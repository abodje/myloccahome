<?php

namespace App\Form;

use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la tâche',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Envoi des quittances mensuelles'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de tâche',
                'choices' => [
                    'Envoi de quittances de loyer' => 'RENT_RECEIPT',
                    'Rappels de paiement' => 'PAYMENT_REMINDER',
                    'Alertes d\'expiration de contrats' => 'LEASE_EXPIRATION',
                    'Génération automatique des loyers' => 'GENERATE_RENTS',
                    'Autre' => 'CUSTOM',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description de la tâche...'
                ]
            ])
            ->add('frequency', ChoiceType::class, [
                'label' => 'Fréquence',
                'choices' => [
                    'Quotidienne' => 'DAILY',
                    'Hebdomadaire' => 'WEEKLY',
                    'Mensuelle' => 'MONTHLY',
                    'Annuelle' => 'YEARLY',
                    'Personnalisée (CRON)' => 'CUSTOM',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('cronExpression', TextType::class, [
                'label' => 'Expression CRON (pour fréquence personnalisée)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0 9 * * 1 (chaque lundi à 9h)'
                ],
                'help' => 'Format: minute heure jour mois jour_semaine'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Active' => 'ACTIVE',
                    'Inactive' => 'INACTIVE',
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}

