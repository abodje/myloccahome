<?php

namespace App\Service;

use DateTimeImmutable;

/**
 * Service JWT simplifié pour l'authentification API
 */
class JwtService
{
    private string $secret;
    private int $expirationTime = 86400; // 24 heures

    public function __construct(string $appSecret)
    {
        $this->secret = $appSecret;
    }

    /**
     * Génère un token JWT
     */
    public function generateToken(array $payload): string
    {
        // Header
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        // Payload avec expiration
        $now = new DateTimeImmutable();
        $exp = $now->getTimestamp() + $this->expirationTime;

        $payload['iat'] = $now->getTimestamp(); // Issued at
        $payload['exp'] = $exp; // Expiration

        // Encoder en base64
        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));

        // Créer la signature
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secret, true);
        $base64Signature = $this->base64UrlEncode($signature);

        // Retourner le token JWT complet
        return "$base64Header.$base64Payload.$base64Signature";
    }

    /**
     * Vérifie et décode un token JWT
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                return null;
            }

            [$base64Header, $base64Payload, $base64Signature] = $parts;

            // Vérifier la signature
            $signature = hash_hmac('sha256', "$base64Header.$base64Payload", $this->secret, true);
            $expectedSignature = $this->base64UrlEncode($signature);

            if ($base64Signature !== $expectedSignature) {
                return null; // Signature invalide
            }

            // Décoder le payload
            $payload = json_decode($this->base64UrlDecode($base64Payload), true);

            // Vérifier l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return null; // Token expiré
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Extrait le token du header Authorization
     */
    public function extractTokenFromHeader(?string $authorizationHeader): ?string
    {
        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        return substr($authorizationHeader, 7);
    }

    /**
     * Encode en base64 URL-safe
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Décode depuis base64 URL-safe
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Définit le temps d'expiration en secondes
     */
    public function setExpirationTime(int $seconds): void
    {
        $this->expirationTime = $seconds;
    }
}
