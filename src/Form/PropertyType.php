<?php

namespace App\Form;

use App\Entity\Property;
use App\Entity\Owner;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['class' => 'form-control']
            ])
            ->add('propertyType', ChoiceType::class, [
                'label' => 'Type de bien',
                'choices' => [
                    'Appartement' => 'Appartement',
                    'Maison' => 'Maison',
                    'Studio' => 'Studio',
                    'Bureau' => 'Bureau',
                    'Local commercial' => 'Local commercial',
                    'Garage' => 'Garage',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (m²)',
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('rooms', IntegerType::class, [
                'label' => 'Nombre de pièces',
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyRent', MoneyType::class, [
                'label' => 'Loyer mensuel',
                'currency' => 'XOF',
                'attr' => ['class' => 'form-control']
            ])
            ->add('charges', MoneyType::class, [
                'label' => 'Charges',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('deposit', MoneyType::class, [
                'label' => 'Dépôt de garantie',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Libre' => 'Libre',
                    'Occupé' => 'Occupé',
                    'En travaux' => 'En travaux',
                    'Hors service' => 'Hors service',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('owner', EntityType::class, [
                'class' => Owner::class,
                'choice_label' => 'fullName',
                'label' => 'Propriétaire',
                'placeholder' => 'Sélectionner un propriétaire',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('managers', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_MANAGER"%')
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_label' => 'fullName',
                'multiple' => true,
                'expanded' => true, // Affiche des cases à cocher
                'label' => 'Gestionnaires',
                'required' => false,
                'attr' => ['class' => 'form-check-group'] // Classe pour le style
            ])

            // === INFORMATIONS GÉOGRAPHIQUES ===
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('region', TextType::class, [
                'label' => 'Région',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('district', TextType::class, [
                'label' => 'Quartier/Arrondissement',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude GPS',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.000001']
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude GPS',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.000001']
            ])

            // === CARACTÉRISTIQUES PHYSIQUES ===
            ->add('floor', IntegerType::class, [
                'label' => 'Étage',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('totalFloors', IntegerType::class, [
                'label' => 'Nombre total d\'étages',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('bedrooms', IntegerType::class, [
                'label' => 'Chambres',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('bathrooms', IntegerType::class, [
                'label' => 'Salles de bain',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('toilets', IntegerType::class, [
                'label' => 'WC séparés',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('balconies', IntegerType::class, [
                'label' => 'Nombre de balcons',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('terraceSurface', IntegerType::class, [
                'label' => 'Surface terrasse (m²)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('gardenSurface', IntegerType::class, [
                'label' => 'Surface jardin (m²)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('parkingSpaces', IntegerType::class, [
                'label' => 'Places de parking',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('garageSpaces', IntegerType::class, [
                'label' => 'Garages',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('cellarSurface', IntegerType::class, [
                'label' => 'Surface cave (m²)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('atticSurface', IntegerType::class, [
                'label' => 'Surface grenier (m²)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('landSurface', NumberType::class, [
                'label' => 'Surface terrain (m²)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])

            // === INFORMATIONS DE CONSTRUCTION ===
            ->add('constructionYear', NumberType::class, [
                'label' => 'Année de construction',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => '1800', 'max' => '2030']
            ])
            ->add('renovationYear', NumberType::class, [
                'label' => 'Année de rénovation',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => '1800', 'max' => '2030']
            ])
            ->add('heatingType', ChoiceType::class, [
                'label' => 'Type de chauffage',
                'choices' => [
                    'Individuel gaz' => 'Individuel gaz',
                    'Individuel électrique' => 'Individuel électrique',
                    'Collectif gaz' => 'Collectif gaz',
                    'Collectif électrique' => 'Collectif électrique',
                    'Pompe à chaleur' => 'Pompe à chaleur',
                    'Chauffage urbain' => 'Chauffage urbain',
                    'Autre' => 'Autre',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('hotWaterType', ChoiceType::class, [
                'label' => 'Type d\'eau chaude',
                'choices' => [
                    'Individuel gaz' => 'Individuel gaz',
                    'Individuel électrique' => 'Individuel électrique',
                    'Collectif gaz' => 'Collectif gaz',
                    'Collectif électrique' => 'Collectif électrique',
                    'Chauffe-eau solaire' => 'Chauffe-eau solaire',
                    'Autre' => 'Autre',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('energyClass', ChoiceType::class, [
                'label' => 'Classe énergétique',
                'choices' => [
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                    'E' => 'E',
                    'F' => 'F',
                    'G' => 'G',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ])
            ->add('energyConsumption', NumberType::class, [
                'label' => 'Consommation énergétique (kWh/m²/an)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'step' => '0.01']
            ])
            ->add('orientation', ChoiceType::class, [
                'label' => 'Orientation',
                'choices' => [
                    'Nord' => 'Nord',
                    'Sud' => 'Sud',
                    'Est' => 'Est',
                    'Ouest' => 'Ouest',
                    'Nord-Est' => 'Nord-Est',
                    'Nord-Ouest' => 'Nord-Ouest',
                    'Sud-Est' => 'Sud-Est',
                    'Sud-Ouest' => 'Sud-Ouest',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner...',
                'attr' => ['class' => 'form-select']
            ])

            // === DESCRIPTIONS ===
            ->add('equipment', TextareaType::class, [
                'label' => 'Équipements',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('proximity', TextareaType::class, [
                'label' => 'Proximité (transports, commerces, écoles)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('restrictions', TextareaType::class, [
                'label' => 'Restrictions (animaux, fumeurs, etc.)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes internes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])

            // === INFORMATIONS FINANCIÈRES ===
            ->add('purchasePrice', MoneyType::class, [
                'label' => 'Prix d\'achat',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('purchaseDate', DateType::class, [
                'label' => 'Date d\'achat',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('estimatedValue', MoneyType::class, [
                'label' => 'Valeur estimée actuelle',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyCharges', MoneyType::class, [
                'label' => 'Charges mensuelles',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('propertyTax', MoneyType::class, [
                'label' => 'Taxe foncière annuelle',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('insurance', MoneyType::class, [
                'label' => 'Assurance annuelle',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('maintenanceBudget', MoneyType::class, [
                'label' => 'Budget maintenance annuel',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // === INFORMATIONS D'ACCÈS ===
            ->add('keyLocation', TextType::class, [
                'label' => 'Localisation des clés',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('accessCode', TextType::class, [
                'label' => 'Code d\'accès',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('intercom', TextType::class, [
                'label' => 'Code interphone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])

            // === CARACTÉRISTIQUES BOOLÉENNES ===
            ->add('furnished', CheckboxType::class, [
                'label' => 'Meublé',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('petsAllowed', CheckboxType::class, [
                'label' => 'Animaux autorisés',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('smokingAllowed', CheckboxType::class, [
                'label' => 'Fumeurs autorisés',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('elevator', CheckboxType::class, [
                'label' => 'Ascenseur',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('hasBalcony', CheckboxType::class, [
                'label' => 'Balcon',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('hasParking', CheckboxType::class, [
                'label' => 'Parking',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('airConditioning', CheckboxType::class, [
                'label' => 'Climatisation',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('heating', CheckboxType::class, [
                'label' => 'Chauffage',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('hotWater', CheckboxType::class, [
                'label' => 'Eau chaude',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('internet', CheckboxType::class, [
                'label' => 'Internet',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('cable', CheckboxType::class, [
                'label' => 'Câble/TV',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('dishwasher', CheckboxType::class, [
                'label' => 'Lave-vaisselle',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('washingMachine', CheckboxType::class, [
                'label' => 'Machine à laver',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('dryer', CheckboxType::class, [
                'label' => 'Sèche-linge',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('refrigerator', CheckboxType::class, [
                'label' => 'Réfrigérateur',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('oven', CheckboxType::class, [
                'label' => 'Four',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('microwave', CheckboxType::class, [
                'label' => 'Micro-ondes',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('stove', CheckboxType::class, [
                'label' => 'Cuisinière',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Property::class,
        ]);
    }
}
