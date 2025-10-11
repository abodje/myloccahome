<?php

namespace App\Form;

use App\Entity\Payment;
use App\Entity\RentalContract;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rentalContract', EntityType::class, [
                'class' => RentalContract::class,
                'choice_label' => function(RentalContract $contract) {
                    return $contract->getProperty()->getTitle() . ' - ' . $contract->getTenant()->getFullName();
                },
                'label' => 'Contrat de location',
                'placeholder' => 'Sélectionner un contrat...',
                'constraints' => [new Assert\NotBlank(['message' => 'Veuillez sélectionner un contrat'])],
                'attr' => ['class' => 'form-select']
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'EUR',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le montant est obligatoire']),
                    new Assert\Positive(['message' => 'Le montant doit être positif'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Date d\'échéance',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'échéance est obligatoire'])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('paymentDate', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('period', TextType::class, [
                'label' => 'Période (YYYY-MM)',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La période est obligatoire']),
                    new Assert\Regex([
                        'pattern' => '/^\d{4}-\d{2}$/',
                        'message' => 'La période doit être au format YYYY-MM'
                    ])
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => '2024-01']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'En attente' => 'pending',
                    'Payé' => 'paid',
                    'En retard' => 'overdue',
                    'Annulé' => 'cancelled',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Virement bancaire' => 'virement',
                    'Chèque' => 'cheque',
                    'Espèces' => 'especes',
                    'Carte bancaire' => 'carte',
                    'Prélèvement automatique' => 'prelevement',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('reference', TextType::class, [
                'label' => 'Référence (n° chèque, référence virement...)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('lateFee', MoneyType::class, [
                'label' => 'Pénalités de retard',
                'currency' => 'EUR',
                'required' => false,
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Les pénalités doivent être positives ou nulles'])
                ],
                'attr' => ['class' => 'form-control']
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
            'data_class' => Payment::class,
        ]);
    }
}