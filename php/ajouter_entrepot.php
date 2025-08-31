<?php
require_once '../config/db.php';

$nom = $_POST['nom'] ?? '';
$adresse = $_POST['adresse'] ?? '';

if (!$nom || !$adresse) {
    header("Location: ../public/admin_entrepots.php?error=1");
    exit;
}

$apiKey = 'fefe87074f4b487cb660c32ceb84c563'; 
$url = "https://api.opencagedata.com/geocode/v1/json?q=" . urlencode($adresse) . "&key=" . $apiKey;

$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['results'][0]['geometry'])) {
    $lat = $data['results'][0]['geometry']['lat'];
    $lng = $data['results'][0]['geometry']['lng'];
} else {
    $lat = null;
    $lng = null;
}

$stmt = $pdo->prepare("INSERT INTO entrepots (nom, adresse, latitude, longitude) VALUES (?, ?, ?, ?)");
$stmt->execute([$nom, $adresse, $lat, $lng]);

header("Location: ../public/admin_entrepots.php?success=1");
exit;
