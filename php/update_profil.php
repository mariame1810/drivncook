<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise'])) {
    header("Location: ../public/login.html");
    exit;
}

$id_franchise = $_SESSION['id_franchise'];
$nom = $_POST['nom'] ?? '';
$prenom = $_POST['prenom'] ?? '';
$email = $_POST['email'] ?? '';
$adresse = $_POST['adresse'] ?? '';
$telephone = $_POST['telephone'] ?? '';
$siret = $_POST['siret'] ?? '';
$motDePasse = $_POST['mot_de_passe'] ?? '';

$stmt = $pdo->prepare("SELECT photo FROM franchises WHERE id_franchise = ?");
$stmt->execute([$id_franchise]);
$current = $stmt->fetch();
$photoActuelle = $current['photo'] ?? null;

$motDePasseHash = null;
if (!empty($motDePasse)) {
    $motDePasseHash = password_hash($motDePasse, PASSWORD_DEFAULT);
}

$photoName = $photoActuelle;
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['photo']['tmp_name'];
    $originalName = basename($_FILES['photo']['name']);
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($ext, $extensionsAutorisees)) {
        $photoName = 'profil_' . $id_franchise . '.' . $ext;
        move_uploaded_file($tmpName, '../uploads/' . $photoName);
    }
}

if ($motDePasseHash) {
    $sql = "UPDATE franchises SET nom = ?, prenom = ?, email = ?, mot_de_passe = ?, adresse = ?, telephone = ?, siret = ?, photo = ? WHERE id_franchise = ?";
    $params = [$nom, $prenom, $email, $motDePasseHash, $adresse, $telephone, $siret, $photoName, $id_franchise];
} else {
    $sql = "UPDATE franchises SET nom = ?, prenom = ?, email = ?, adresse = ?, telephone = ?, siret = ?, photo = ? WHERE id_franchise = ?";
    $params = [$nom, $prenom, $email, $adresse, $telephone, $siret, $photoName, $id_franchise];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header("Location: ../public/profil.php");
exit;
