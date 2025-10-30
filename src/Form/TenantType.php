<?php

namespace App\Form;

use App\Entity\Tenant;
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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;

class TenantType extends AbstractType
{
    public function __construct(private Security $security) {}
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('profession', TextType::class, [
                'label' => 'Profession',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('monthlyIncome', MoneyType::class, [
                'label' => 'Revenus mensuels',
                'currency' => 'XOF',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('emergencyContactName', TextType::class, [
                'label' => 'Contact d\'urgence (nom)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => 'Contact d\'urgence (téléphone)',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
            ->add('assignManager', EntityType::class, [
                'class' => User::class,
                'label' => 'Attribuer un gestionnaire',
                'mapped' => false,
                'required' => false,
                'placeholder' => '— Sélectionner —',
                'choice_label' => function (User $u) {
                    return trim(($u->getLastName() ?? '') . ' ' . ($u->getFirstName() ?? '')) ?: $u->getEmail();
                },
                'query_builder' => function (UserRepository $ur) {
                    $user = $this->security->getUser();
                    return $ur->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_MANAGER"%')
                        ->andWhere(':org IS NULL OR u.organization = :org')
                        ->andWhere(':comp IS NULL OR u.company = :comp')
                        ->setParameter('org', method_exists($user, 'getOrganization') ? $user->getOrganization() : null)
                        ->setParameter('comp', method_exists($user, 'getCompany') ? $user->getCompany() : null)
                        ->orderBy('u.lastName', 'ASC')
                        ->addOrderBy('u.firstName', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tenant::class,
        ]);
    }
}
