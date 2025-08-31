<?php
require_once '../config/db.php';

$immatriculation = $_POST['immatriculation'] ?? '';
$etat            = $_POST['etat'] ?? '';
$date            = $_POST['date_entretien'] ?? '';
$adresse         = $_POST['adresse'] ?? '';

$marque      = trim($_POST['marque'] ?? '');
$modele      = trim($_POST['modele'] ?? '');
$kilometrage = ($_POST['kilometrage'] ?? '') !== '' ? (int)$_POST['kilometrage'] : null;
$annee       = ($_POST['annee'] ?? '') !== '' ? (int)$_POST['annee'] : null;

$latitude = null;
$longitude = null;

// --- 1. Géocodage de l'adresse ---
if (!empty($adresse)) {
    $apiKey = 'fefe87074f4b487cb660c32ceb84c563'; // ta clé existante
    $encodedAddress = urlencode($adresse);
    $url = "https://api.opencagedata.com/geocode/v1/json?q=$encodedAddress&key=$apiKey&language=fr&limit=1";
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['results'][0]['geometry'])) {
            $latitude = $data['results'][0]['geometry']['lat'];
            $longitude = $data['results'][0]['geometry']['lng'];
        }
    }
}

// --- 2. Upload de l'image (dans /public/uploads/camions/) ---
$image_url = null;
if (!empty($_FILES['image']['name'])) {
    $targetDir = __DIR__ . "/../public/uploads/camions/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = uniqid() . "_" . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        // chemin relatif pour la BDD (sera accessible via http://localhost:8888/drivncook/public/uploads/camions/...)
        $image_url = "uploads/camions/" . $fileName;
    }
}

// --- 3. Insertion du camion ---
$stmt = $pdo->prepare("
  INSERT INTO camions
    (immatriculation, etat, date_prochain_entretien, latitude, longitude,
     marque, modele, kilometrage, annee, image_url)
  VALUES
    (:imm, :etat, :dpe, :lat, :lng, :marque, :modele, :km, :annee, :image)
");

$stmt->execute([
  ':imm'    => $immatriculation,
  ':etat'   => $etat,
  ':dpe'    => $date ?: null,
  ':lat'    => $latitude,
  ':lng'    => $longitude,
  ':marque' => $marque ?: null,
  ':modele' => $modele ?: null,
  ':km'     => $kilometrage,
  ':annee'  => $annee,
  ':image'  => $image_url
]);

$id_camion = $pdo->lastInsertId();

// --- 4. Crée automatiquement une révision si date d’entretien donnée ---
if ($date) {
    $stmt = $pdo->prepare("
        INSERT INTO revisions (id_camion, type, echeance_km, echeance_date, statut)
        VALUES (?, 'entretien', NULL, ?, 'a_planifier')
    ");
    $stmt->execute([$id_camion, $date]);
}

header("Location: ../public/admin_gestion_camions.php?success=1");
exit;
