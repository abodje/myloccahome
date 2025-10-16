<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use App\Form\ConversationType;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\MessageEncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/messagerie')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(ConversationRepository $conversationRepository, MessageEncryptionService $encryptionService, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $search = $request->query->get('search');
        $status = $request->query->get('status');

        // Récupérer les conversations selon le rôle
        $conversations = $conversationRepository->findByRole($user);

        // Appliquer les filtres si nécessaire
        if ($search || $status) {
            $conversations = $conversationRepository->findWithFilters($user, $search, $status);
        }

        // Déchiffrer les conversations pour l'affichage
        $conversations = $encryptionService->decryptConversations($conversations);

        // Calculer les statistiques
        $stats = $conversationRepository->getStatisticsForUser($user);

        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
            'stats' => $stats,
            'current_search' => $search,
            'current_status' => $status,
        ]);
    }

    #[Route('/nouvelle', name: 'app_message_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, MessageEncryptionService $encryptionService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $conversation = new Conversation();
        $conversation->setInitiator($user);
        $conversation->addParticipant($user);

        $form = $this->createForm(ConversationType::class, $conversation, [
            'current_user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation : les locataires ne peuvent pas créer de conversations avec d'autres locataires
            if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
                foreach ($conversation->getParticipants() as $participant) {
                    if ($participant->getId() !== $user->getId() && in_array('ROLE_TENANT', $participant->getRoles())) {
                        $this->addFlash('error', 'Les locataires ne peuvent pas créer de conversations avec d\'autres locataires.');
                        return $this->render('message/new.html.twig', [
                            'conversation' => $conversation,
                            'form' => $form,
                        ]);
                    }
                }
            }

            // S'assurer que l'initiateur est bien ajouté comme participant
            if (!$conversation->getParticipants()->contains($user)) {
                $conversation->addParticipant($user);
            }

            // Sauvegarder la conversation avec chiffrement automatique
            $encryptionService->saveEncryptedConversation($conversation);

            $this->addFlash('success', 'La conversation a été créée avec succès.');

            return $this->redirectToRoute('app_message_show', ['id' => $conversation->getId()]);
        }

        return $this->render('message/new.html.twig', [
            'conversation' => $conversation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_message_show', methods: ['GET', 'POST'])]
    public function show(
        Conversation $conversation,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository,
        ConversationRepository $conversationRepository,
        MessageEncryptionService $encryptionService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur est participant de la conversation
        $isParticipant = false;
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getId() === $user->getId()) {
                $isParticipant = true;
                break;
            }
        }

        if (!$isParticipant) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette conversation.');
        }

        // Marquer les messages comme lus
        $messageRepository->markAsReadByConversationAndUser($conversation, $user);

        // Créer un nouveau message
        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($user);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder le message avec chiffrement automatique
            $encryptionService->saveEncryptedMessage($message);
            $conversation->addMessage($message);

            return $this->redirectToRoute('app_message_show', ['id' => $conversation->getId()]);
        }

        // Déchiffrer la conversation et ses messages pour l'affichage
        $conversation = $encryptionService->forceDecryptConversation($conversation);

        // Récupérer tous les messages de la conversation et les déchiffrer
        $messages = $messageRepository->findByConversation($conversation);
        $messages = $encryptionService->decryptMessages($messages);

        // Récupérer toutes les conversations de l'utilisateur pour la sidebar
        $conversations = $conversationRepository->findByUser($user);
        $conversations = $encryptionService->decryptConversations($conversations);

        return $this->render('message/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
            'conversations' => $conversations,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/marquer-lu', name: 'app_message_mark_read', methods: ['POST'])]
    public function markAsRead(Conversation $conversation, MessageRepository $messageRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$conversation->getParticipants()->contains($user)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette conversation.');
        }

        $messageRepository->markAsReadByConversationAndUser($conversation, $user);

        return $this->json(['success' => true]);
    }

    #[Route('/contact/{id}', name: 'app_message_contact_user', methods: ['GET', 'POST'])]
    public function contactUser(User $contactUser, Request $request, EntityManagerInterface $entityManager, ConversationRepository $conversationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier qu'on ne peut pas se contacter soi-même
        if ($user->getId() === $contactUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous envoyer un message à vous-même.');
            return $this->redirectToRoute('app_message_index');
        }

        // Vérifier que les locataires ne peuvent pas contacter d'autres locataires
        if ($user && in_array('ROLE_TENANT', $user->getRoles()) && in_array('ROLE_TENANT', $contactUser->getRoles())) {
            $this->addFlash('error', 'Les locataires ne peuvent pas contacter directement d\'autres locataires.');
            return $this->redirectToRoute('app_message_index');
        }

        // Chercher s'il existe déjà une conversation entre ces utilisateurs
        $existingConversation = $conversationRepository->findBetweenUsers($user, $contactUser);

        if ($existingConversation) {
            return $this->redirectToRoute('app_message_show', ['id' => $existingConversation->getId()]);
        }

        // Créer une nouvelle conversation
        $conversation = new Conversation();
        $conversation->setSubject('Conversation avec ' . $contactUser->getFirstName() . ' ' . $contactUser->getLastName());
        $conversation->setInitiator($user);
        $conversation->addParticipant($user);
        $conversation->addParticipant($contactUser);

        $form = $this->createForm(ConversationType::class, $conversation, [
            'current_user' => $user,
            'contact_user' => $contactUser,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($conversation);
            $entityManager->flush();

            $this->addFlash('success', 'La conversation a été créée avec succès.');

            return $this->redirectToRoute('app_message_show', ['id' => $conversation->getId()]);
        }

        return $this->render('message/contact.html.twig', [
            'conversation' => $conversation,
            'contact_user' => $contactUser,
            'form' => $form,
        ]);
    }

    #[Route('/api/unread-count', name: 'app_message_api_unread_count', methods: ['GET'])]
    public function getUnreadCount(ConversationRepository $conversationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $conversations = $conversationRepository->findWithUnreadMessages($user);
        $totalUnread = 0;

        foreach ($conversations as $conversation) {
            $totalUnread += $conversation->getUnreadCountForUser($user);
        }

        return $this->json(['unread_count' => $totalUnread]);
    }

    #[Route('/api/recent', name: 'app_message_api_recent', methods: ['GET'])]
    public function getRecentMessages(ConversationRepository $conversationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $conversations = $conversationRepository->findByUser($user);
        $recentConversations = array_slice($conversations, 0, 5);

        $data = [];
        foreach ($recentConversations as $conversation) {
            $lastMessage = $conversation->getLastMessage();
            $data[] = [
                'id' => $conversation->getId(),
                'subject' => $conversation->getSubject(),
                'last_message' => $lastMessage ? $lastMessage->getContent() : '',
                'last_message_at' => $lastMessage ? $lastMessage->getSentAt()->format('d/m/Y H:i') : '',
                'unread_count' => $conversation->getUnreadCountForUser($user),
            ];
        }

        return $this->json($data);
    }
}
