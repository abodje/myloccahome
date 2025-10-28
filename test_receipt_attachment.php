<?php

/**
 * Script de test pour vérifier le chemin des pièces jointes de quittances
 *
 * Ce script vérifie:
 * 1. Si le répertoire des documents existe
 * 2. Si les fichiers de quittances sont accessibles
 * 3. Si les chemins sont corrects pour les pièces jointes
 *
 * Usage: php test_receipt_attachment.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Définir les constantes
$projectDir = __DIR__;
$documentsDir = $projectDir . '/public/uploads/documents';

echo "========================================\n";
echo "Test des pièces jointes de quittances\n";
echo "========================================\n\n";

// 1. Vérifier le répertoire des documents
echo "1. Vérification du répertoire des documents\n";
echo "   Chemin: {$documentsDir}\n";

if (is_dir($documentsDir)) {
    echo "   ✅ Le répertoire existe\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($documentsDir)), -4) . "\n";
} else {
    echo "   ❌ Le répertoire n'existe pas\n";
    echo "   Tentative de création...\n";

    if (mkdir($documentsDir, 0777, true)) {
        echo "   ✅ Répertoire créé avec succès\n";
    } else {
        echo "   ❌ Impossible de créer le répertoire\n";
        exit(1);
    }
}

echo "\n";

// 2. Lister les fichiers de quittances
echo "2. Liste des fichiers de quittances existants\n";

$quittanceFiles = glob($documentsDir . '/quittance_*.pdf');

if (empty($quittanceFiles)) {
    echo "   ⚠️  Aucun fichier de quittance trouvé\n";
    echo "   Recherche dans: {$documentsDir}/quittance_*.pdf\n";
} else {
    echo "   Trouvé " . count($quittanceFiles) . " fichier(s) de quittance:\n\n";

    foreach ($quittanceFiles as $file) {
        $fileSize = filesize($file);
        $fileDate = date('Y-m-d H:i:s', filemtime($file));
        $fileName = basename($file);

        echo "   📄 {$fileName}\n";
        echo "      Taille: " . number_format($fileSize / 1024, 2) . " Ko\n";
        echo "      Modifié: {$fileDate}\n";
        echo "      Chemin absolu: {$file}\n";
        echo "      Lisible: " . (is_readable($file) ? "✅ Oui" : "❌ Non") . "\n";
        echo "\n";
    }
}

echo "\n";

// 3. Tester un exemple de chemin
echo "3. Test de construction de chemin de pièce jointe\n";

$exampleFileName = 'quittance_DUPONT_2025_01.pdf';
$examplePath = $documentsDir . '/' . $exampleFileName;

echo "   Nom de fichier exemple: {$exampleFileName}\n";
echo "   Chemin construit: {$examplePath}\n";
echo "   Fichier existe: " . (file_exists($examplePath) ? "✅ Oui" : "❌ Non") . "\n";

echo "\n";

// 4. Vérifier les documents dans la base de données
echo "4. Vérification des documents dans la base de données\n";

try {
    // Vérifier si le fichier bootstrap existe
    $bootstrapFile = __DIR__ . '/config/bootstrap.php';

    if (!file_exists($bootstrapFile)) {
        echo "   ⚠️  Fichier bootstrap.php introuvable, utilisation de autoload seul\n";
        echo "   Note: Cette partie nécessite un environnement Symfony complet\n";
    } else {
        // Bootstrap Symfony pour accéder à la base de données
        require_once $bootstrapFile;

        $kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
        $kernel->boot();
        $container = $kernel->getContainer();

        $entityManager = $container->get('doctrine')->getManager();

        $documents = $entityManager->getRepository(\App\Entity\Document::class)
            ->createQueryBuilder('d')
            ->where('d.type = :type')
            ->setParameter('type', 'Quittance de loyer')
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        if (empty($documents)) {
            echo "   ⚠️  Aucune quittance trouvée dans la base de données\n";
        } else {
            echo "   Trouvé " . count($documents) . " quittance(s) récente(s):\n\n";

            foreach ($documents as $doc) {
                $docPath = $documentsDir . '/' . $doc->getFileName();
                $fileExists = file_exists($docPath);

                echo "   📋 Document #{$doc->getId()}: {$doc->getName()}\n";
                echo "      Nom de fichier: {$doc->getFileName()}\n";
                echo "      Chemin attendu: {$docPath}\n";
                echo "      Fichier existe: " . ($fileExists ? "✅ Oui" : "❌ Non") . "\n";

                if ($fileExists) {
                    echo "      Taille: " . number_format(filesize($docPath) / 1024, 2) . " Ko\n";
                }

                echo "\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "   ⚠️  Impossible de vérifier la base de données:\n";
    echo "      {$e->getMessage()}\n";
    echo "      (Cette étape nécessite un environnement Symfony configuré)\n";
}echo "\n";

// 5. Résumé et recommandations
echo "5. Résumé et recommandations\n";
echo "========================================\n\n";

echo "✅ Actions effectuées:\n";
echo "   - Vérification du répertoire des documents\n";
echo "   - Liste des fichiers de quittances existants\n";
echo "   - Test de construction de chemin\n";
echo "   - Vérification de la correspondance base de données / fichiers\n\n";

echo "💡 Corrections apportées au code:\n";
echo "   1. NotificationService utilise maintenant \$documentsDirectory (chemin absolu)\n";
echo "   2. getDocumentFilePath() retourne le chemin absolu complet\n";
echo "   3. Ajout de logging détaillé pour les pièces jointes\n";
echo "   4. Configuration de NotificationService dans services.yaml\n\n";

echo "🔧 Pour tester l'envoi d'email avec pièce jointe:\n";
echo "   1. Assurez-vous qu'au moins une quittance existe dans la base de données\n";
echo "   2. Vérifiez que le fichier PDF correspondant existe dans {$documentsDir}\n";
echo "   3. Utilisez la commande: php bin/console app:send-rent-receipts\n";
echo "   4. Consultez les logs dans var/log/dev.log pour les messages de pièce jointe\n\n";

echo "✅ Test terminé\n";
