<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conversation>
 */
class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    public function save(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Conversation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Conversation[] Returns an array of Conversation objects for a user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find conversations with unread messages for a user
     */
    public function findWithUnreadMessages(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->join('c.messages', 'm')
            ->andWhere('p = :user')
            ->andWhere('c.isActive = :isActive')
            ->andWhere('m.isRead = :isRead')
            ->andWhere('m.sender != :user')
            ->setParameter('user', $user)
            ->setParameter('isActive', true)
            ->setParameter('isRead', false)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a conversation between specific users
     */
    public function findBetweenUsers(User $user1, User $user2): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p1')
            ->join('c.participants', 'p2')
            ->andWhere('p1 = :user1')
            ->andWhere('p2 = :user2')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get conversation statistics for a user
     */
    public function getStatisticsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->andWhere('c.isActive = :isActive')
            ->setParameter('user', $user)
            ->setParameter('isActive', true);

        $totalConversations = $qb->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $activeConversations = $qb->select('COUNT(c.id)')
            ->andWhere('c.lastMessageAt > :date')
            ->setParameter('date', new \DateTime('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_conversations' => $totalConversations,
            'active_conversations' => $activeConversations,
        ];
    }

    /**
     * Find conversations by role (for admins/managers to see all conversations)
     */
    public function findByRole(User $user): array
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles)) {
            // Filtrer les conversations selon l'organisation/société de l'admin
            $organization = $user->getOrganization();
            $company = $user->getCompany();
            
            $qb = $this->createQueryBuilder('c')
                ->join('c.participants', 'p')
                ->andWhere('c.isActive = :isActive')
                ->setParameter('isActive', true);
            
            if ($company) {
                // Admin avec société spécifique : conversations impliquant des utilisateurs de cette société
                $qb->andWhere('p.company = :company')
                   ->setParameter('company', $company);
            } elseif ($organization) {
                // Admin avec organisation : conversations impliquant des utilisateurs de cette organisation
                $qb->andWhere('p.organization = :organization')
                   ->setParameter('organization', $organization);
            }
            // Si pas d'organisation/société (Super Admin) : voir toutes les conversations
            
            return $qb->orderBy('c.lastMessageAt', 'DESC')
                      ->getQuery()
                      ->getResult();
        } elseif (in_array('ROLE_MANAGER', $roles)) {
            // Managers see conversations involving their tenants
            return $this->createQueryBuilder('c')
                ->join('c.participants', 'p')
                ->join('p.tenant', 't')
                ->join('t.leases', 'l')
                ->join('l.property', 'prop')
                ->join('prop.owner', 'o')
                ->andWhere('o.user = :user')
                ->andWhere('c.isActive = :isActive')
                ->setParameter('user', $user)
                ->setParameter('isActive', true)
                ->orderBy('c.lastMessageAt', 'DESC')
                ->getQuery()
                ->getResult();
        } elseif (in_array('ROLE_TENANT', $roles)) {
            // Tenants see only conversations with managers and admins
            // Récupérer d'abord toutes les conversations du locataire
            $allConversations = $this->findByUser($user);

            // Filtrer pour ne garder que celles avec des gestionnaires ou admins
            $filteredConversations = [];
            foreach ($allConversations as $conversation) {
                foreach ($conversation->getParticipants() as $participant) {
                    if ($participant->getId() !== $user->getId()) {
                        $participantRoles = $participant->getRoles();
                        if (in_array('ROLE_MANAGER', $participantRoles) || in_array('ROLE_ADMIN', $participantRoles)) {
                            $filteredConversations[] = $conversation;
                            break; // Une seule conversation autorisée trouvée
                        }
                    }
                }
            }

            return $filteredConversations;
        } else {
            // Regular users see only their own conversations
            return $this->findByUser($user);
        }
    }

    /**
     * Find conversations with specific filters
     */
    public function findWithFilters(User $user, ?string $search = null, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->setParameter('user', $user);

        if ($search) {
            $qb->andWhere('c.subject LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status === 'unread') {
            $qb->join('c.messages', 'm')
                ->andWhere('m.isRead = :isRead')
                ->andWhere('m.sender != :user')
                ->setParameter('isRead', false);
        } elseif ($status === 'active') {
            $qb->andWhere('c.lastMessageAt > :date')
                ->setParameter('date', new \DateTime('-7 days'));
        }

        $qb->andWhere('c.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('c.lastMessageAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
