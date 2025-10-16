<?php

namespace App\Form;

use App\Entity\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code devise (ISO)',
                'help' => 'Code à 3 lettres (ex: EUR, USD, GBP)',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 3,
                    'style' => 'text-transform: uppercase;'
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Nom de la devise',
                'attr' => ['class' => 'form-control']
            ])
            ->add('symbol', TextType::class, [
                'label' => 'Symbole',
                'help' => 'Symbole affiché (ex: €, $, £)',
                'attr' => ['class' => 'form-control']
            ])
            ->add('exchangeRate', NumberType::class, [
                'label' => 'Taux de change',
                'help' => 'Taux par rapport à l\'Euro (1 EUR = ? devise)',
                'scale' => 6,
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.000001',
                    'min' => '0'
                ]
            ])
            ->add('decimalPlaces', IntegerType::class, [
                'label' => 'Nombre de décimales',
                'help' => 'Nombre de décimales pour l\'affichage (ex: 2 pour 1 234,56)',
                'data' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => '0',
                    'max' => '4'
                ]
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Devise active',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'Devise par défaut',
                'required' => false,
                'help' => 'Une seule devise peut être définie par défaut',
                'attr' => ['class' => 'form-check-input']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Currency::class,
        ]);
    }
}
