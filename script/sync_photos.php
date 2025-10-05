<?php
$config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
$tokenFile = $config['token_file'];
$logFile = $config['log_file'];

// 📄 Journalisation
function logMessage($msg) {
    global $logFile;
    echo $msg . PHP_EOL;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

// 🔄 Vérifie si le token est expiré
function isTokenExpired($token) {
    return time() > ($token['created_at'] ?? 0) + $token['expires_in'] - 60;
}

// 🔄 Renouvelle le token si nécessaire
function refreshToken($config, &$token) {
    $postFields = http_build_query([
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $token['refresh_token'],
        'grant_type' => 'refresh_token'
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($response['access_token'])) {
        $token['access_token'] = $response['access_token'];
        $token['expires_in'] = $response['expires_in'];
        $token['created_at'] = time();
        file_put_contents($config['token_file'], json_encode($token));
        logMessage("🔁 Token renouvelé avec succès.");
    } else {
        logMessage("❌ Échec du renouvellement du token : " . json_encode($response));
        exit;
    }
}

// 🔐 Chargement du token
if (!file_exists($tokenFile)) {
    logMessage("❌ Fichier de token introuvable.");
    exit;
}
$token = json_decode(file_get_contents($tokenFile), true);
if (!isset($token['access_token'])) {
    logMessage("❌ Token invalide.");
    exit;
}
if (isTokenExpired($token)) {
    logMessage("⚠️ Token expiré, tentative de renouvellement...");
    refreshToken($config, $token);
} else {
    logMessage("✅ Token valide.");
}

// 📸 Appel API Google Photos
function apiRequest($url, $token) {
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
    curl_close($ch);
    return json_decode($response, true);
}

// 📥 Téléchargement des photos
$mediaItems = apiRequest("https://photoslibrary.googleapis.com/v1/mediaItems?pageSize=10", $token);
if (!isset($mediaItems['mediaItems'])) {
    logMessage("❌ Erreur lors de la récupération des photos.");
    exit;
}

foreach ($mediaItems['mediaItems'] as $item) {
    $filename = $config['photo_folder'] . '/' . $item['filename'];
    if (!file_exists($filename)) {
        $url = $item['baseUrl'] . "=d";
        file_put_contents($filename, file_get_contents($url));
        logMessage("📥 Téléchargé : " . $filename);
    } else {
        logMessage("⏩ Déjà présent : " . $filename);
    }
}
