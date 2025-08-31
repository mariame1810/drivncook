<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../public/admin_login.php");
    exit;
}

$id_camion = (int)($_POST['id_camion'] ?? 0);
$type = trim($_POST['type'] ?? 'entretien');
$echeance_date = $_POST['echeance_date'] ?: null;
$echeance_km = ($_POST['echeance_km'] ?? '') !== '' ? (int)$_POST['echeance_km'] : null;

if ($id_camion <= 0) {
    header("Location: ../public/admin_gestion_revisions.php?err=1");
    exit;
}

$stmt = $pdo->prepare("INSERT INTO revisions (id_camion, type, echeance_km, echeance_date, statut)
                       VALUES (:idc, :t, :km, :d, 'a_planifier')");
$stmt->execute([
  ':idc' => $id_camion,
  ':t'   => $type,
  ':km'  => $echeance_km,
  ':d'   => $echeance_date
]);

header("Location: ../public/admin_gestion_revisions.php?created=1");
exit;
