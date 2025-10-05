<?php
$config = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
$token = json_decode(file_get_contents($config['token_file']), true);

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

$mediaItems = apiRequest("https://photoslibrary.googleapis.com/v1/mediaItems?pageSize=10", $token);

foreach ($mediaItems['mediaItems'] as $item) {
    $filename = $config['photo_folder'] . '/' . $item['filename'];
    if (!file_exists($filename)) {
        $url = $item['baseUrl'] . "=d";
        file_put_contents($filename, file_get_contents($url));
        file_put_contents($config['log_file'], "Downloaded: " . $filename . "\n", FILE_APPEND);
    }
}
