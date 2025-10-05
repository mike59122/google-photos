<?php
$config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
$tokenFile = $config['token_file'];
$logFile = $config['log_file'];

function logMessage($msg) {
    global $logFile;
    echo $msg . PHP_EOL;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

function checkTokenFile($path) {
    if (!file_exists($path)) {
        logMessage("❌ Fichier de token introuvable : $path");
        return false;
    }
    $token = json_decode(file_get_contents($path), true);
    if (!isset($token['access_token'])) {
        logMessage("❌ Token invalide ou corrompu.");
        return false;
    }
    logMessage("✅ Token chargé avec succès.");
    return $token;
}

function testAPI($token) {
    $url = "https://photoslibrary.googleapis.com/v1/albums?pageSize=1";
    $headers = [
        "Authorization: Bearer " . $token['access_token'],
        "Content-Type: application/json"
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        logMessage("✅ Connexion à l’API Google Photos réussie.");
    } elseif ($httpCode === 401) {
        logMessage("⚠️ Token expiré ou non autorisé. Relancer le script de refresh.");
    } else {
        logMessage("❌ Erreur API ($httpCode). Réponse : $response");
    }
}

// Exécution
logMessage("🔍 Démarrage du diagnostic OAuth2 Google Photos...");
$token = checkTokenFile($tokenFile);
if ($token) {
    testAPI($token);
}
