<?php

namespace App\Form;

use App\Entity\MaintenanceRequest;
use App\Entity\Property;
use App\Entity\Tenant;
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

class MaintenanceRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isTenantView = $options['is_tenant_view'] ?? false;
        $tenantProperties = $options['tenant_properties'] ?? [];

        $builder
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'fullAddress',
                'label' => 'Propriété',
                'placeholder' => 'Sélectionner une propriété',
                'choices' => $isTenantView ? $tenantProperties : null,
                'attr' => ['class' => 'form-select']
            ]);

        // Le champ tenant n'est affiché que pour les gestionnaires/admins
        if (!$isTenantView) {
            $builder->add('tenant', EntityType::class, [
                'class' => Tenant::class,
                'choice_label' => 'fullName',
                'label' => 'Locataire',
                'placeholder' => 'Sélectionner un locataire',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ]);
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la demande',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Plomberie' => 'Plomberie',
                    'Électricité' => 'Électricité',
                    'Chauffage' => 'Chauffage',
                    'Serrurerie' => 'Serrurerie',
                    'Peinture' => 'Peinture',
                    'Menuiserie' => 'Menuiserie',
                    'Nettoyage' => 'Nettoyage',
                    'Jardinage' => 'Jardinage',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 'Basse',
                    'Normale' => 'Normale',
                    'Haute' => 'Haute',
                    'Urgente' => 'Urgente',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Nouvelle' => 'Nouvelle',
                    'En cours' => 'En cours',
                    'Terminée' => 'Terminée',
                    'Annulée' => 'Annulée',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('requestedDate', DateType::class, [
                'label' => 'Date souhaitée',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('scheduledDate', DateType::class, [
                'label' => 'Date programmée',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('assignedTo', TextType::class, [
                'label' => 'Assigné à',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('assignedPhone', TelType::class, [
                'label' => 'Téléphone du prestataire',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('assignedEmail', EmailType::class, [
                'label' => 'Email du prestataire',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('estimatedCost', MoneyType::class, [
                'label' => 'Coût estimé',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('actualCost', MoneyType::class, [
                'label' => 'Coût réel',
                'currency' => 'EUR',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('workPerformed', TextareaType::class, [
                'label' => 'Travail effectué',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
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
            'data_class' => MaintenanceRequest::class,
            'is_tenant_view' => false,
            'tenant_properties' => [],
        ]);
    }
}
