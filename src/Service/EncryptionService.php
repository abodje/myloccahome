<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EncryptionService
{
    private string $encryptionKey;
    private string $cipherMethod = 'AES-256-CBC';

    public function __construct(ParameterBagInterface $params)
    {
        // Récupérer la clé de chiffrement depuis les paramètres
        $this->encryptionKey = $params->get('app.encryption_key');

        // Si aucune clé n'est définie, utiliser une clé par défaut (ATTENTION: ne pas utiliser en production)
        if (empty($this->encryptionKey)) {
            $this->encryptionKey = hash('sha256', 'mylocca_encryption_key_2025', true);
        }
    }

    /**
     * Chiffre une chaîne de caractères
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $ivLength = openssl_cipher_iv_length($this->cipherMethod);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($data, $this->cipherMethod, $this->encryptionKey, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Erreur lors du chiffrement des données');
        }

        // Combiner IV et données chiffrées, puis encoder en base64
        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffre une chaîne de caractères
     */
    public function decrypt(string $encryptedData): string
    {
        if (empty($encryptedData)) {
            return '';
        }

        $data = base64_decode($encryptedData);

        if ($data === false) {
            throw new \RuntimeException('Données de chiffrement invalides');
        }

        $ivLength = openssl_cipher_iv_length($this->cipherMethod);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        $decrypted = openssl_decrypt($encrypted, $this->cipherMethod, $this->encryptionKey, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Erreur lors du déchiffrement des données');
        }

        return $decrypted;
    }

    /**
     * Vérifie si une chaîne est chiffrée
     */
    public function isEncrypted(string $data): bool
    {
        if (empty($data)) {
            return false;
        }

        try {
            // Tenter de décoder base64
            $decoded = base64_decode($data);
            if ($decoded === false) {
                return false;
            }

            // Vérifier si la taille correspond à IV + données chiffrées
            $ivLength = openssl_cipher_iv_length($this->cipherMethod);
            return strlen($decoded) > $ivLength;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Chiffre les données d'un tableau (récursif)
     */
    public function encryptArray(array $data, array $fieldsToEncrypt = []): array
    {
        $encrypted = $data;

        foreach ($fieldsToEncrypt as $field) {
            if (isset($encrypted[$field]) && is_string($encrypted[$field]) && !empty($encrypted[$field])) {
                $encrypted[$field] = $this->encrypt($encrypted[$field]);
            }
        }

        return $encrypted;
    }

    /**
     * Déchiffre les données d'un tableau (récursif)
     */
    public function decryptArray(array $data, array $fieldsToDecrypt = []): array
    {
        $decrypted = $data;

        foreach ($fieldsToDecrypt as $field) {
            if (isset($decrypted[$field]) && is_string($decrypted[$field]) && $this->isEncrypted($decrypted[$field])) {
                try {
                    $decrypted[$field] = $this->decrypt($decrypted[$field]);
                } catch (\Exception $e) {
                    // En cas d'erreur de déchiffrement, garder la valeur originale
                    continue;
                }
            }
        }

        return $decrypted;
    }

    /**
     * Génère une nouvelle clé de chiffrement
     */
    public function generateNewKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /**
     * Hash une clé pour le stockage sécurisé
     */
    public function hashKey(string $key): string
    {
        return hash('sha256', $key, true);
    }
}
