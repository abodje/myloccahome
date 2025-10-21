<?php

// Test simple de l'API calendrier
// À exécuter avec: php test-calendar-api.php

echo "=== Test API Calendrier ===\n";

// URL de l'API calendrier
$url = 'https://localhost:8000/calendrier/events?start=2024-01-01&end=2024-12-31';

echo "URL: $url\n\n";

// Test avec cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Code HTTP: $httpCode\n";

if ($error) {
    echo "Erreur cURL: $error\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "Erreur HTTP: $httpCode\n";
    echo "Réponse: $response\n";
    exit(1);
}

echo "Réponse reçue:\n";
echo "Longueur: " . strlen($response) . " caractères\n";

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Erreur JSON: " . json_last_error_msg() . "\n";
    echo "Réponse brute: " . substr($response, 0, 500) . "...\n";
    exit(1);
}

echo "✅ JSON valide\n";

if (isset($data['error']) && $data['error']) {
    echo "❌ Erreur API: " . ($data['message'] ?? 'Erreur inconnue') . "\n";
    exit(1);
}

$events = $data['events'] ?? $data;
$eventCount = is_array($events) ? count($events) : 0;

echo "📊 Nombre d'événements: $eventCount\n";

if ($eventCount > 0) {
    echo "\nPremiers événements:\n";
    $count = 0;
    foreach ($events as $event) {
        if ($count >= 3) break;
        echo "  " . ($count + 1) . ". " . ($event['title'] ?? 'Sans titre') .
             " (" . ($event['type'] ?? 'Sans type') . ") - " .
             ($event['start'] ?? 'Sans date') . "\n";
        $count++;
    }
} else {
    echo "\n❌ Aucun événement trouvé\n";
    echo "Causes possibles:\n";
    echo "  - Aucune donnée dans la base\n";
    echo "  - Utilisateur non connecté\n";
    echo "  - Filtres trop restrictifs\n";
    echo "  - Erreur dans les requêtes\n";
}

echo "\n=== Test terminé ===\n";
