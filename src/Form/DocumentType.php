<?php

namespace App\Form;

use App\Entity\Document;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\Lease;
use App\Entity\Owner;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du document',
                'attr' => ['class' => 'form-control']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de document',
                'choices' => [
                    'Assurance' => 'Assurance',
                    'Avis d\'échéance' => 'Avis d\'échéance',
                    'Contrat de location' => 'Contrat de location',
                    'État des lieux' => 'État des lieux',
                    'Quittance de loyer' => 'Quittance',
                    'Facture' => 'Facture',
                    'Diagnostics' => 'Diagnostics',
                    'Conseils' => 'Conseils',
                    'Attestation' => 'Attestation',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('file', FileType::class, [
                'label' => 'Fichier',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF, Word ou image valide',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('documentDate', DateType::class, [
                'label' => 'Date du document',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('expirationDate', DateType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('property', EntityType::class, [
                'class' => Property::class,
                'choice_label' => 'fullAddress',
                'label' => 'Propriété',
                'placeholder' => 'Sélectionner une propriété',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('tenant', EntityType::class, [
                'class' => Tenant::class,
                'choice_label' => 'fullName',
                'label' => 'Locataire',
                'placeholder' => 'Sélectionner un locataire',
                'required' => false,
                'attr' => ['class' => 'form-select']
            ])
            ->add('lease', EntityType::class, [
                'class' => Lease::class,
                'choice_label' => function(Lease $lease) {
                    return $lease->getProperty()->getFullAddress() . ' - ' . $lease->getTenant()->getFullName();
                },
                'label' => 'Contrat de location',
                'placeholder' => 'Sélectionner un contrat',
                'required' => false,
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
        ]);
    }
}
