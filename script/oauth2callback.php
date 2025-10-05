<?php
require __DIR__ . '/google-api-php-client/vendor/autoload.php';

use Google\Client;
use GuzzleHttp\Client as HttpClient;

// 🔧 Configuration
$tokenPath = __DIR__ . '/token.json';
$credentialsPath = __DIR__ . '/credentials.json';
$redirectUri = 'https://jeedom.ozaer.eu/plugins/script/data/oauth2callback.php';
$scope = 'https://www.googleapis.com/auth/photoslibrary.readonly';

// 🔌 Initialisation client Google
$client = new Client();
$client->setAuthConfig($credentialsPath);
$client->setAccessType('offline');
$client->setRedirectUri($redirectUri);
$client->setScopes([$scope]);
$client->setPrompt('consent');
$client->setIncludeGrantedScopes(false);

// 🧪 Autorisation CLI
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    parse_str($argv[1], $_GET);
}

// 🔁 Autorisation manuelle
if (isset($_GET['code'])) {
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($accessToken['error'])) {
        echo "❌ Erreur d'autorisation : " . $accessToken['error_description'] . "\n";
        exit;
    }

    file_put_contents($tokenPath, json_encode($accessToken));
    chown($tokenPath, 'pi');
    //@chgrp($tokenPath, 'www-data');
    chmod($tokenPath, 0775);

    echo "✅ Nouveau token enregistré.\n";
    print_r($accessToken);
  $info = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $accessToken), true);
echo "👤 Compte : " . ($info['email'] ?? 'inconnu') . "\n";
echo "🔍 Scope : " . ($info['scope'] ?? 'non détecté') . "\n";

    exit;
}

// 🔍 Vérification du token existant
if (!file_exists($tokenPath)) {
    echo "❌ Le fichier token.json est introuvable.\n";
    echo "🔗 Autorise l'accès via :\n" . $client->createAuthUrl() . "\n";
    exit;
}

$tokenData = json_decode(file_get_contents($tokenPath), true);
if (!is_array($tokenData) || !isset($tokenData['access_token'])) {
    echo "❌ Le fichier token.json est corrompu ou incomplet.\n";
    echo "🔁 Supprime-le et relance l'autorisation.\n";
    exit;
}

// 🔁 Renouvellement si expiré
$client->setAccessToken($tokenData);
if ($client->isAccessTokenExpired()) {
    echo "🔄 Token expiré, tentative de renouvellement...\n";
    $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

    if (isset($newToken['error'])) {
        echo "❌ Erreur de renouvellement : " . $newToken['error_description'] . "\n";
        echo "🔗 Autorise à nouveau via :\n" . $client->createAuthUrl() . "\n";
        exit;
    }
  @chown($tokenPath, 'pi');
 @chmod($tokenPath, 0775);
    $tokenData = array_merge($tokenData, $newToken);
    file_put_contents($tokenPath, json_encode($tokenData));
    
    echo "✅ Token renouvelé.\n";
}


$accessToken = $tokenData['access_token'];

$tokenInfo = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $accessToken), true);

// 🔍 Vérification du scope via tokeninfo
$info = json_decode(file_get_contents('https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=' . $accessToken), true);
if (!isset($info['scope']) || strpos($info['scope'], $scope) === false) {
    echo "⚠️ Le token n'inclut pas le bon scope.\n";
    echo "🔁 Supprime token.json et relance l'autorisation.\n";
    exit;
}
echo "✅ Scope valide : " . $info['scope'] . "\n";

// 📁 Préparation du dossier de téléchargement
$downloadDir = __DIR__ . '/photos';
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0755, true);
}

// 📡 Test d'accès à l'API Google Photos
$http = new HttpClient([
    'base_uri' => 'https://photoslibrary.googleapis.com/',
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json'
    ]
]);

try {
    $response = $http->get('v1/mediaItems?pageSize=10');
    $data = json_decode($response->getBody(), true);

    if (!isset($data['mediaItems'])) {
        echo "⚠️ Aucun élément trouvé dans la bibliothèque Google Photos.\n";
        exit;
    }

    echo "✅ Accès à l'API Google Photos confirmé.\n";

    foreach ($data['mediaItems'] as $item) {
        $baseUrl = $item['baseUrl'];
        $filename = basename($item['filename']);
        $downloadUrl = $baseUrl . '=d'; // =d pour téléchargement direct

        $filePath = $downloadDir . '/' . $filename;
        file_put_contents($filePath, file_get_contents($downloadUrl));
        echo "📥 Téléchargé : $filename\n";
    }

} catch (\GuzzleHttp\Exception\ClientException $e) {
    $errorBody = json_decode($e->getResponse()->getBody(), true);
    echo "❌ Erreur API Google Photos : " . $errorBody['error']['message'] . "\n";
    echo "🔁 Vérifie le scope, le consentement, et que le compte Google a bien autorisé l’accès.\n";
}
