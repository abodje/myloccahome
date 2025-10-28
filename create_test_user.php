<?php
// Script temporaire pour créer l'utilisateur test

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Vérifier si l'utilisateur existe déjà
$existingUser = $em->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
if ($existingUser) {
    echo "L'utilisateur test@example.com existe déjà.\n";
    exit;
}

// Récupérer le tenant
$tenant = $em->getRepository(Tenant::class)->findOneBy(['email' => 'test@example.com']);
if (!$tenant) {
    echo "Erreur : Le tenant test@example.com n'existe pas.\n";
    exit;
}

// Créer l'utilisateur
$user = new User();
$user->setEmail('test@example.com');
$user->setPassword('$2y$13$IeMmvTsxR/nTuEDUEJdH1eXvVrAzJu.SPki3QVbwJG9Jvo2RvBfKW');
$user->setRoles(['ROLE_TENANT']);
$user->setFirstName('Test');
$user->setLastName('Locataire');
$user->setPhone('+33612345678');

$em->persist($user);
$em->flush();

echo "✅ Utilisateur créé avec succès !\n";
echo "Email: test@example.com\n";
echo "Mot de passe: password\n";
