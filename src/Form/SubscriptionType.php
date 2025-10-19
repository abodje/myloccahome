<?php

namespace App\Form;

use App\Entity\Subscription;
use App\Entity\Plan;
use App\Entity\Organization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organization', EntityType::class, [
                'label' => 'Organisation',
                'class' => Organization::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner une organisation',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('plan', EntityType::class, [
                'label' => 'Plan d\'abonnement',
                'class' => Plan::class,
                'choice_label' => function(Plan $plan) {
                    return $plan->getName() . ' - ' . number_format($plan->getMonthlyPrice(), 0, ',', ' ') . ' ' . $plan->getCurrency() . '/mois';
                },
                'placeholder' => 'Sélectionner un plan',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => 'ACTIVE',
                    'Annulé' => 'CANCELLED',
                    'Expiré' => 'EXPIRED',
                    'En attente' => 'PENDING',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('billingCycle', ChoiceType::class, [
                'label' => 'Cycle de facturation',
                'choices' => [
                    'Mensuel' => 'monthly',
                    'Annuel' => 'yearly',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('trialEndDate', DateType::class, [
                'label' => 'Fin de période d\'essai',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('cancelledAt', DateTimeType::class, [
                'label' => 'Date d\'annulation',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subscription::class,
        ]);
    }
}
