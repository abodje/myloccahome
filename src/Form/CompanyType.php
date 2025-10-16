<?php

namespace App\Form;

use App\Entity\Company;
use App\Entity\Organization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la société',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: ACME Immobilier'],
            ])
            ->add('organization', EntityType::class, [
                'label' => 'Organisation',
                'class' => Organization::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Sélectionner une organisation',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'contact@societe.com'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+225 XX XX XX XX XX'],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Adresse complète de la société'],
            ])
            ->add('website', TextType::class, [
                'label' => 'Site web',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'https://www.societe.com'],
            ])
            ->add('registrationNumber', TextType::class, [
                'label' => 'Numéro d\'enregistrement',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'RCCM, SIRET, etc.'],
            ])
            ->add('taxNumber', TextType::class, [
                'label' => 'Numéro fiscal',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Numéro de TVA, etc.'],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Société active',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}

