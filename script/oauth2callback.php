<?php
require __DIR__ . '/google-api-php-client/vendor/autoload.php';


use Google\Client;
use GuzzleHttp\Client as HttpClient;

$config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
$tokenFile = $config['token_file'];
$logFile = $config['log_file'];


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
function Download_Photos(){
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
}








/*
// 🔧 Configuration
$tokenPath = __DIR__ . '/token.json';
$credentialsPath = __DIR__ . '/credentials.json';
$redirectUri = 'https://mondomaine/plugins/script/data/oauth2callback.php';
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
*/