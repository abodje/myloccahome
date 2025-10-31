<?php

namespace App\Form;

use App\Entity\MaintenanceRequest;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
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
    public function __construct(
        private Security $security,
        private PropertyRepository $propertyRepository,
        private TenantRepository $tenantRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isTenantView = $options['is_tenant_view'] ?? false;
        $tenantProperties = $options['tenant_properties'] ?? [];

        // Construire le champ property selon le contexte
        $propertyFieldConfig = [
            'class' => Property::class,
            'choice_label' => 'fullAddress',
            'label' => 'Propriété',
            'placeholder' => 'Sélectionner une propriété',
            'attr' => ['class' => 'form-select']
        ];

        if ($isTenantView) {
            // Pour les locataires, utiliser la liste prédéfinie
            $propertyFieldConfig['choices'] = $tenantProperties;
        } else {
            // Pour les admins/managers, filtrer par organisation/société
            $propertyFieldConfig['query_builder'] = function (PropertyRepository $pr) {
                $user = $this->security->getUser();
                $organization = $user && method_exists($user, 'getOrganization') ? $user->getOrganization() : null;
                $company = $user && method_exists($user, 'getCompany') ? $user->getCompany() : null;

                $qb = $pr->createQueryBuilder('p');

                if ($company) {
                    $qb->where('p.company = :company')
                       ->setParameter('company', $company);
                } elseif ($organization) {
                    $qb->where('p.organization = :organization')
                       ->setParameter('organization', $organization);
                }

                return $qb->orderBy('p.address', 'ASC');
            };
        }

        $builder->add('property', EntityType::class, $propertyFieldConfig);

        // Le champ tenant n'est affiché que pour les gestionnaires/admins
        if (!$isTenantView) {
            $builder->add('tenant', EntityType::class, [
                'class' => Tenant::class,
                'choice_label' => 'fullName',
                'label' => 'Locataire',
                'placeholder' => 'Sélectionner un locataire',
                'required' => false,
                'query_builder' => function (TenantRepository $tr) {
                    $user = $this->security->getUser();
                    $organization = $user && method_exists($user, 'getOrganization') ? $user->getOrganization() : null;
                    $company = $user && method_exists($user, 'getCompany') ? $user->getCompany() : null;

                    $qb = $tr->createQueryBuilder('t');

                    if ($company) {
                        $qb->where('t.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->where('t.organization = :organization')
                           ->setParameter('organization', $organization);
                    }

                    return $qb->orderBy('t.lastName', 'ASC')
                              ->addOrderBy('t.firstName', 'ASC');
                },
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
