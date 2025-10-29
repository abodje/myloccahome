<?php
/**
 * Script pour créer un utilisateur locataire de test pour l'API mobile
 * Usage: php create_tenant_user_test.php
 */

require __DIR__.'/vendor/autoload.php';

use App\Entity\User;
use App\Entity\Tenant;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Configuration
$email = 'locataire@test.com';
$password = 'password123';
$firstName = 'Jean';
$lastName = 'Dupont';

echo "========================================\n";
echo "Création d'un utilisateur locataire de test\n";
echo "========================================\n\n";

// Vérifier si l'utilisateur existe déjà
$existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
if ($existingUser) {
    echo "⚠️  L'utilisateur $email existe déjà.\n";
    echo "   Mise à jour du mot de passe...\n";

    // Mettre à jour le mot de passe (utiliser password_hash pour être compatible)
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $existingUser->setPassword($hashedPassword);
    $existingUser->setRoles(['ROLE_TENANT']);
    $existingUser->setActive(true);

    $em->flush();

    echo "✅ Mot de passe mis à jour avec succès !\n\n";
    $user = $existingUser;
} else {
    // Créer un nouvel utilisateur
    $user = new User();
    $user->setEmail($email);
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $user->setPassword($hashedPassword);
    $user->setFirstName($firstName);
    $user->setLastName($lastName);
    $user->setRoles(['ROLE_TENANT']);
    $user->setActive(true);

    $em->persist($user);
    $em->flush();

    echo "✅ Utilisateur créé avec succès !\n\n";
}

// Vérifier si un Tenant existe
$tenant = $em->getRepository(Tenant::class)->findOneBy(['email' => $email]);

if (!$tenant) {
    echo "Création du profil Tenant...\n";
    $tenant = new Tenant();
    $tenant->setFirstName($firstName);
    $tenant->setLastName($lastName);
    $tenant->setEmail($email);
    $tenant->setPhone('+33612345678');
    $tenant->setUser($user);

    $em->persist($tenant);
    $em->flush();

    echo "✅ Profil Tenant créé avec succès !\n\n";
} else {
    echo "✅ Profil Tenant existe déjà.\n\n";
    // S'assurer que le Tenant est lié à l'utilisateur
    if (!$tenant->getUser()) {
        $tenant->setUser($user);
        $em->flush();
        echo "✅ Liaison User-Tenant créée.\n\n";
    }
}

echo "========================================\n";
echo "✅ Configuration terminée !\n";
echo "========================================\n\n";
echo "📧 Email: $email\n";
echo "🔑 Mot de passe: $password\n";
echo "👤 Nom: $firstName $lastName\n";
echo "🎭 Rôle: ROLE_TENANT\n\n";
echo "Vous pouvez maintenant tester la connexion depuis l'app Flutter !\n";

