<?php

// Script de diagnostic pour le calendrier
// À exécuter avec: php calendar-debug.php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load('.env.local', '.env');

// Configuration de base
$baseUrl = 'http://localhost:8000'; // Ajustez selon votre configuration
$calendarApiUrl = $baseUrl . '/calendrier/events';

echo "=== Diagnostic Calendrier LOKAPRO ===\n";
echo "URL API: $calendarApiUrl\n\n";

// Test 1: Vérifier la connectivité
echo "1. Test de connectivité...\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = @file_get_contents($calendarApiUrl . '?start=2024-01-01&end=2024-12-31', false, $context);

if ($response === false) {
    echo "❌ Erreur de connectivité\n";
    echo "Vérifiez que le serveur Symfony est démarré\n";
    exit(1);
}

echo "✅ Connectivité OK\n\n";

// Test 2: Analyser la réponse
echo "2. Analyse de la réponse...\n";
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Erreur JSON: " . json_last_error_msg() . "\n";
    echo "Réponse brute: " . substr($response, 0, 200) . "...\n";
    exit(1);
}

echo "✅ Réponse JSON valide\n";

if (isset($data['error']) && $data['error']) {
    echo "❌ Erreur API: " . ($data['message'] ?? 'Erreur inconnue') . "\n";
    exit(1);
}

$events = $data['events'] ?? $data;
$eventCount = is_array($events) ? count($events) : 0;

echo "📊 Nombre d'événements: $eventCount\n\n";

// Test 3: Analyser les événements
if ($eventCount > 0) {
    echo "3. Analyse des événements...\n";

    $eventTypes = [];
    foreach ($events as $event) {
        $type = $event['type'] ?? 'unknown';
        $eventTypes[$type] = ($eventTypes[$type] ?? 0) + 1;
    }

    echo "Types d'événements trouvés:\n";
    foreach ($eventTypes as $type => $count) {
        echo "  - $type: $count événements\n";
    }

    // Afficher le premier événement comme exemple
    if (!empty($events)) {
        echo "\nPremier événement:\n";
        $firstEvent = $events[0];
        echo "  - Titre: " . ($firstEvent['title'] ?? 'N/A') . "\n";
        echo "  - Type: " . ($firstEvent['type'] ?? 'N/A') . "\n";
        echo "  - Date: " . ($firstEvent['start'] ?? 'N/A') . "\n";
        echo "  - Couleur: " . ($firstEvent['color'] ?? 'N/A') . "\n";
    }
} else {
    echo "3. Aucun événement trouvé\n";
    echo "Causes possibles:\n";
    echo "  - Aucune donnée dans la base\n";
    echo "  - Filtres trop restrictifs\n";
    echo "  - Problème d'authentification\n";
    echo "  - Erreur dans les requêtes\n";
}

echo "\n=== Diagnostic terminé ===\n";

// Test 4: Vérifier la base de données
echo "\n4. Test de la base de données...\n";

try {
    // Configuration de base pour Doctrine
    $config = [
        'driver' => 'pdo_mysql',
        'host' => $_ENV['DATABASE_HOST'] ?? 'localhost',
        'port' => $_ENV['DATABASE_PORT'] ?? '3306',
        'dbname' => $_ENV['DATABASE_NAME'] ?? 'mylocca',
        'user' => $_ENV['DATABASE_USER'] ?? 'root',
        'password' => $_ENV['DATABASE_PASSWORD'] ?? '',
        'charset' => 'utf8mb4'
    ];

    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion base de données OK\n";

    // Compter les entités
    $tables = [
        'payment' => 'Paiements',
        'lease' => 'Baux',
        'maintenance_request' => 'Demandes de maintenance',
        'property' => 'Propriétés',
        'tenant' => 'Locataires',
        'user' => 'Utilisateurs'
    ];

    echo "\nDonnées dans la base:\n";
    foreach ($tables as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "  - $label: $count\n";
        } catch (PDOException $e) {
            echo "  - $label: Table non trouvée\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Erreur base de données: " . $e->getMessage() . "\n";
}

echo "\n=== Recommandations ===\n";

if ($eventCount == 0) {
    echo "1. Vérifiez que vous avez des données dans la base\n";
    echo "2. Testez avec un utilisateur connecté\n";
    echo "3. Vérifiez les logs Symfony: var/log/dev.log\n";
    echo "4. Testez l'API directement dans le navigateur\n";
} else {
    echo "1. Le calendrier fonctionne correctement\n";
    echo "2. Vérifiez les filtres dans l'interface\n";
    echo "3. Testez avec différentes périodes\n";
}
