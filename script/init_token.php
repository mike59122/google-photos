<?php
$config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);

// 🔐 Code d'autorisation à récupérer manuellement
$authCode = $_GET['code'] ?? null;
if (!$authCode) {
    echo "❌ Aucun code d'autorisation fourni. Ajoutez ?code=VOTRE_CODE à l'URL.";
    exit;
}

// 🔄 Échange du code contre un token
$postFields = http_build_query([
    'code' => $authCode,
    'client_id' => $config['client_id'],
    'client_secret' => $config['client_secret'],
    'redirect_uri' => $config['redirect_uri'],
    'grant_type' => 'authorization_code'
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

// 📄 Vérification et sauvegarde
if (isset($response['access_token']) && isset($response['refresh_token'])) {
    $response['created_at'] = time();
    file_put_contents($config['token_file'], json_encode($response));
    echo "✅ Token initialisé et sauvegardé dans " . $config['token_file'];
} else {
    echo "❌ Échec de l’échange : " . json_encode($response);
}
