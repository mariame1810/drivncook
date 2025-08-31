<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../public/admin_login.php");
    exit;
}

$id = (int)($_POST['id_revision'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id <= 0) {
    header("Location: ../public/admin_gestion_revisions.php?err=1");
    exit;
}

switch ($action) {
    case 'terminer':
        $stmt = $pdo->prepare("UPDATE revisions SET statut = 'terminee' WHERE id_revision = ?");
        $stmt->execute([$id]);
        break;

    case 'a_planifier':
        $stmt = $pdo->prepare("UPDATE revisions SET statut = 'a_planifier' WHERE id_revision = ?");
        $stmt->execute([$id]);
        break;

    case 'planifier':
        $date = $_POST['echeance_date'] ?: null;
        $km   = ($_POST['echeance_km'] ?? '') !== '' ? (int)$_POST['echeance_km'] : null;
        $stmt = $pdo->prepare("UPDATE revisions 
                               SET statut = 'planifiee', echeance_date = :d, echeance_km = :k
                               WHERE id_revision = :id");
        $stmt->execute([':d'=>$date, ':k'=>$km, ':id'=>$id]);
        break;
}

header("Location: ../public/admin_gestion_revisions.php?ok=1");
exit;
