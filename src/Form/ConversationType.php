<?php

namespace App\Form;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConversationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentUser = $options['current_user'] ?? null;
        $contactUser = $options['contact_user'] ?? null;
        $isContactForm = $contactUser !== null;

        $builder
            ->add('subject', TextType::class, [
                'label' => 'Sujet de la conversation',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Demande d\'information, Problème technique...'
                ]
            ]);

        // Si c'est un formulaire de contact direct, on ne montre pas le champ participants
        if (!$isContactForm) {
            $builder->add('participants', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    $role = $this->getUserRole($user);
                    return $user->getFirstName() . ' ' . $user->getLastName() . ' (' . $role . ')';
                },
                'multiple' => true,
                'expanded' => false,
                'label' => 'Participants',
                'attr' => ['class' => 'form-select'],
                'query_builder' => function (UserRepository $userRepository) use ($currentUser) {
                    $qb = $userRepository->createQueryBuilder('u')
                        ->where('u.id != :currentUser')
                        ->setParameter('currentUser', $currentUser);
                    
                    // Si l'utilisateur actuel est un locataire, il ne peut contacter que les gestionnaires et admins
                    if ($currentUser && in_array('ROLE_TENANT', $currentUser->getRoles())) {
                        $qb->andWhere('u.roles LIKE :manager OR u.roles LIKE :admin')
                            ->setParameter('manager', '%ROLE_MANAGER%')
                            ->setParameter('admin', '%ROLE_ADMIN%');
                    }
                    
                    return $qb->orderBy('u.firstName', 'ASC');
                }
            ]);
        }

        // Ajouter un champ pour le premier message si c'est un nouveau contact
        if ($isContactForm) {
            $builder->add('first_message', TextareaType::class, [
                'label' => 'Votre message',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Tapez votre message ici...'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conversation::class,
            'current_user' => null,
            'contact_user' => null,
        ]);
    }

    private function getUserRole(User $user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            return 'Administrateur';
        } elseif (in_array('ROLE_MANAGER', $roles)) {
            return 'Gestionnaire';
        } elseif (in_array('ROLE_TENANT', $roles)) {
            return 'Locataire';
        }

        return 'Utilisateur';
    }
}
