<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\Lease;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lease', EntityType::class, [
                'class' => Lease::class,
                'choice_label' => function(Lease $lease) {
                    return $lease->getProperty()->getFullAddress() . ' - ' . $lease->getTenant()->getFullName();
                },
                'label' => 'Contrat de location',
                'placeholder' => 'Sélectionner un contrat',
                'attr' => ['class' => 'form-select']
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('paidDate', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de paiement',
                'choices' => [
                    'Loyer' => 'Loyer',
                    'Charges' => 'Charges',
                    'Dépôt de garantie' => 'Caution',
                    'Frais d\'agence' => 'Frais',
                    'Pénalité' => 'Pénalité',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'En attente',
                    'Payé' => 'Payé',
                    'En retard' => 'En retard',
                    'Partiel' => 'Partiel',
                    'Annulé' => 'Annulé',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Virement bancaire' => 'Virement',
                    'Chèque' => 'Chèque',
                    'Espèces' => 'Espèces',
                    'Prélèvement automatique' => 'Prélèvement',
                    'Carte bancaire' => 'Carte',
                    'Autre' => 'Autre',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner un mode de paiement',
                'attr' => ['class' => 'form-select']
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'help' => 'Numéro de chèque, référence de virement, etc.',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
