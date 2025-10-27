<?php
/**
 * Script de test pour vérifier que l'URL de notification est accessible
 * Accès: https://votre-domaine.com/test-notification.php
 */

// Définir le fichier de log
$logFile = __DIR__ . '/../var/log/cinetpay_test_notification.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Logger toutes les tentatives d'accès
file_put_contents($logFile, "\n" . str_repeat('=', 80) . "\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - 🧪 TEST NOTIFICATION REÇU\n", FILE_APPEND);
file_put_contents($logFile, str_repeat('=', 80) . "\n", FILE_APPEND);

// Méthode HTTP
file_put_contents($logFile, "Méthode: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

// URL complète
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$fullUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
file_put_contents($logFile, "URL complète: $fullUrl\n", FILE_APPEND);

// Headers
file_put_contents($logFile, "\n--- HEADERS ---\n", FILE_APPEND);
foreach (getallheaders() as $name => $value) {
    file_put_contents($logFile, "$name: $value\n", FILE_APPEND);
}

// GET params
file_put_contents($logFile, "\n--- GET PARAMS ---\n", FILE_APPEND);
file_put_contents($logFile, print_r($_GET, true) . "\n", FILE_APPEND);

// POST params
file_put_contents($logFile, "\n--- POST PARAMS ---\n", FILE_APPEND);
file_put_contents($logFile, print_r($_POST, true) . "\n", FILE_APPEND);

// Raw input
$rawInput = file_get_contents('php://input');
file_put_contents($logFile, "\n--- RAW INPUT ---\n", FILE_APPEND);
file_put_contents($logFile, $rawInput . "\n", FILE_APPEND);

// IP du client
file_put_contents($logFile, "\n--- INFO CLIENT ---\n", FILE_APPEND);
file_put_contents($logFile, "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n", FILE_APPEND);
file_put_contents($logFile, "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n", FILE_APPEND);

file_put_contents($logFile, "\n✅ Test réussi - URL accessible\n", FILE_APPEND);
file_put_contents($logFile, str_repeat('=', 80) . "\n\n", FILE_APPEND);

// Réponse HTTP 200
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');

echo "✅ TEST NOTIFICATION OK\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Méthode: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "URL: $fullUrl\n";
echo "\nVérifiez le fichier de log: var/log/cinetpay_test_notification.log\n";
