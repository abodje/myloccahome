<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\Conversation;
use Doctrine\ORM\EntityManagerInterface;

class MessageEncryptionService
{
    private EncryptionService $encryptionService;
    private EntityManagerInterface $entityManager;

    public function __construct(EncryptionService $encryptionService, EntityManagerInterface $entityManager)
    {
        $this->encryptionService = $encryptionService;
        $this->entityManager = $entityManager;
    }

    /**
     * Chiffre un message avant de le sauvegarder
     */
    public function encryptMessage(Message $message): Message
    {
        if (!empty($message->getContent()) && !$message->isEncrypted()) {
            $encryptedContent = $this->encryptionService->encrypt($message->getContent());
            $message->setContent($encryptedContent);
            $message->setIsEncrypted(true);
        }

        return $message;
    }

    /**
     * Déchiffre un message pour l'affichage
     */
    public function decryptMessage(Message $message): Message
    {
        if ($message->isEncrypted() && !empty($message->getContent())) {
            try {
                $decryptedContent = $this->encryptionService->decrypt($message->getContent());
                $message->setContent($decryptedContent);
                $message->setIsEncrypted(false);
            } catch (\Exception $e) {
                // En cas d'erreur de déchiffrement, garder le contenu chiffré
                // et logger l'erreur
                error_log('Erreur de déchiffrement du message ID: ' . $message->getId() . ' - ' . $e->getMessage());
            }
        }

        return $message;
    }

    /**
     * Chiffre une conversation avant de la sauvegarder
     */
    public function encryptConversation(Conversation $conversation): Conversation
    {
        if (!empty($conversation->getSubject()) && !$conversation->isEncrypted()) {
            $encryptedSubject = $this->encryptionService->encrypt($conversation->getSubject());
            $conversation->setSubject($encryptedSubject);
            $conversation->setIsEncrypted(true);
        }

        return $conversation;
    }

    /**
     * Déchiffre une conversation pour l'affichage
     */
    public function decryptConversation(Conversation $conversation): Conversation
    {
        if ($conversation->isEncrypted() && !empty($conversation->getSubject())) {
            try {
                $decryptedSubject = $this->encryptionService->decrypt($conversation->getSubject());
                $conversation->setSubject($decryptedSubject);
                $conversation->setIsEncrypted(false);
            } catch (\Exception $e) {
                // En cas d'erreur de déchiffrement, garder le sujet chiffré
                error_log('Erreur de déchiffrement de la conversation ID: ' . $conversation->getId() . ' - ' . $e->getMessage());
            }
        }

        return $conversation;
    }

    /**
     * Chiffre tous les messages d'une conversation
     */
    public function encryptConversationMessages(Conversation $conversation): void
    {
        foreach ($conversation->getMessages() as $message) {
            $this->encryptMessage($message);
        }
    }

    /**
     * Déchiffre tous les messages d'une conversation
     */
    public function decryptConversationMessages(Conversation $conversation): void
    {
        foreach ($conversation->getMessages() as $message) {
            $this->decryptMessage($message);
        }
    }

    /**
     * Sauvegarde un message en le chiffrant automatiquement
     */
    public function saveEncryptedMessage(Message $message): void
    {
        $this->encryptMessage($message);
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    /**
     * Sauvegarde une conversation en la chiffrant automatiquement
     */
    public function saveEncryptedConversation(Conversation $conversation): void
    {
        $this->encryptConversation($conversation);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();
    }

    /**
     * Récupère et déchiffre un message
     */
    public function getDecryptedMessage(int $messageId): ?Message
    {
        $message = $this->entityManager->getRepository(Message::class)->find($messageId);
        
        if ($message) {
            $this->decryptMessage($message);
        }

        return $message;
    }

    /**
     * Récupère et déchiffre une conversation
     */
    public function getDecryptedConversation(int $conversationId): ?Conversation
    {
        $conversation = $this->entityManager->getRepository(Conversation::class)->find($conversationId);
        
        if ($conversation) {
            $this->decryptConversation($conversation);
            $this->decryptConversationMessages($conversation);
        }

        return $conversation;
    }

    /**
     * Chiffre en lot plusieurs messages
     */
    public function encryptMessages(array $messages): array
    {
        $encryptedMessages = [];
        
        foreach ($messages as $message) {
            $encryptedMessages[] = $this->encryptMessage($message);
        }

        return $encryptedMessages;
    }

    /**
     * Déchiffre en lot plusieurs messages
     */
    public function decryptMessages(array $messages): array
    {
        $decryptedMessages = [];
        
        foreach ($messages as $message) {
            $decryptedMessages[] = $this->decryptMessage($message);
        }

        return $decryptedMessages;
    }

    /**
     * Chiffre en lot plusieurs conversations
     */
    public function encryptConversations(array $conversations): array
    {
        $encryptedConversations = [];
        
        foreach ($conversations as $conversation) {
            $encryptedConversations[] = $this->encryptConversation($conversation);
        }

        return $encryptedConversations;
    }

    /**
     * Déchiffre en lot plusieurs conversations
     */
    public function decryptConversations(array $conversations): array
    {
        $decryptedConversations = [];
        
        foreach ($conversations as $conversation) {
            $this->decryptConversation($conversation);
            $this->decryptConversationMessages($conversation);
            $decryptedConversations[] = $conversation;
        }

        return $decryptedConversations;
    }

    /**
     * Vérifie si un message est chiffré
     */
    public function isMessageEncrypted(Message $message): bool
    {
        return $message->isEncrypted();
    }

    /**
     * Vérifie si une conversation est chiffrée
     */
    public function isConversationEncrypted(Conversation $conversation): bool
    {
        return $conversation->isEncrypted();
    }

    /**
     * Force le déchiffrement d'un message (pour l'affichage)
     */
    public function forceDecryptMessage(Message $message): Message
    {
        if ($message->isEncrypted()) {
            $this->decryptMessage($message);
        }

        return $message;
    }

    /**
     * Force le déchiffrement d'une conversation (pour l'affichage)
     */
    public function forceDecryptConversation(Conversation $conversation): Conversation
    {
        if ($conversation->isEncrypted()) {
            $this->decryptConversation($conversation);
            $this->decryptConversationMessages($conversation);
        }

        return $conversation;
    }
}
