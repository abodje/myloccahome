<?php
/**
 * Script de test pour vérifier que le backend répond correctement
 * Usage: php test_flutter_backend_connection.php
 */

$apiUrl = 'http://localhost:8000/api/tenant/login';

echo "========================================\n";
echo "Test de connexion Backend <-> Flutter\n";
echo "========================================\n\n";

// Test 1: Vérifier que le serveur répond
echo "1. Test de connexion au serveur...\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Erreur de connexion: $error\n";
    echo "   → Assurez-vous que le serveur Symfony est démarré:\n";
    echo "     symfony server:start\n";
    echo "     ou\n";
    echo "     php -S localhost:8000 -t public\n";
    exit(1);
}

echo "   ✅ Serveur accessible (HTTP $httpCode)\n\n";

// Test 2: Test de login avec les identifiants de test
echo "2. Test de l'endpoint /api/tenant/login...\n";
$loginData = json_encode([
    'email' => 'locataire@test.com',
    'password' => 'password123'
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Erreur lors de la requête: $error\n";
    exit(1);
}

echo "   HTTP Status: $httpCode\n";
echo "   Réponse: " . substr($response, 0, 200) . "...\n";

$data = json_decode($response, true);
if ($data && isset($data['success'])) {
    if ($data['success']) {
        echo "   ✅ Login réussi !\n";
        echo "   📧 Email: {$data['user']['email']}\n";
        echo "   👤 Nom: {$data['user']['firstName']} {$data['user']['lastName']}\n";
    } else {
        echo "   ❌ Login échoué: {$data['message']}\n";
    }
} else {
    echo "   ⚠️  Réponse inattendue\n";
}

echo "\n========================================\n";
echo "✅ Test terminé\n";
echo "========================================\n\n";

echo "📱 Pour Flutter:\n";
echo "   - Émulateur Android: http://localhost:8000 ou http://10.0.2.2:8000\n";
echo "   - Appareil physique: http://VOTRE_IP_LOCALE:8000\n";
echo "   - Pour trouver votre IP: ipconfig (Windows)\n";

