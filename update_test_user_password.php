<?php
// Script pour mettre à jour le mot de passe de l'utilisateur test

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Récupérer l'utilisateur
$user = $em->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
if (!$user) {
    echo "Erreur : L'utilisateur test@example.com n'existe pas.\n";
    exit;
}

// Hash généré avec password_hash('password', PASSWORD_BCRYPT)
$hashedPassword = '$2y$10$f5ZsSQw3ZvViqk9QQG2uAO.lwYItjJM8s3oD2bk4FV/Nirqt4dnE6';
$user->setPassword($hashedPassword);

$em->flush();

echo "✅ Mot de passe mis à jour avec succès !\n";
echo "Email: test@example.com\n";
echo "Mot de passe: password\n";
echo "Hash: " . $hashedPassword . "\n";
