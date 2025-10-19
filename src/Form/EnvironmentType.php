<?php

namespace App\Form;

use App\Entity\Environment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvironmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'environnement',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Production Client ABC'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'environnement',
                'choices' => [
                    'Production' => 'PRODUCTION',
                    'Staging' => 'STAGING',
                    'Développement' => 'DEVELOPMENT',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Description de l\'environnement...'
                ]
            ])
            ->add('domain', TextType::class, [
                'label' => 'Domaine personnalisé (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: client.com'
                ],
                'help' => 'Si vous laissez vide, un sous-domaine sera généré automatiquement'
            ])
            ->add('sslEnabled', CheckboxType::class, [
                'label' => 'Activer SSL/HTTPS',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('configuration', CollectionType::class, [
                'label' => 'Configuration personnalisée',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => ['class' => 'form-control']
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('environmentVariables', CollectionType::class, [
                'label' => 'Variables d\'environnement',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'attr' => ['class' => 'form-control']
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Environment::class,
        ]);
    }
}
