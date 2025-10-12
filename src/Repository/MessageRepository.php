<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function save(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Message[] Returns an array of Message objects
     */
    public function findByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Message[] Returns an array of unread Message objects for a user
     */
    public function findUnreadByUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->andWhere('m.isRead = :isRead')
            ->andWhere('m.sender != :user')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->orderBy('m.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Mark all messages in a conversation as read for a specific user
     */
    public function markAsReadByConversationAndUser(Conversation $conversation, User $user): int
    {
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':isRead')
            ->set('m.readAt', ':readAt')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.sender != :user')
            ->andWhere('m.isRead = :notRead')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->setParameter('isRead', true)
            ->setParameter('readAt', new \DateTime())
            ->setParameter('notRead', false)
            ->getQuery()
            ->execute();
    }

    /**
     * Get message statistics for a user
     */
    public function getStatisticsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->setParameter('user', $user);

        $totalMessages = $qb->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $unreadMessages = $qb->select('COUNT(m.id)')
            ->andWhere('m.isRead = :isRead')
            ->andWhere('m.sender != :user')
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total_messages' => $totalMessages,
            'unread_messages' => $unreadMessages,
        ];
    }

    /**
     * Find recent messages for a user (last 10)
     */
    public function findRecentByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->join('c.participants', 'p')
            ->andWhere('p = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
