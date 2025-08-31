<?php
require_once '../config/db.php';

$id_franchise = $_POST['id_franchise'] ?? null;
$id_camion = $_POST['id_camion'] ?? null;

if ($id_franchise && $id_camion) {
    $stmt = $pdo->prepare("UPDATE camions SET id_franchise = ? WHERE id_camion = ?");
    $stmt->execute([$id_franchise, $id_camion]);
    header("Location: ../public/admin_attribuer_camion.php?success=1");
    exit;
} else {
    header("Location: ../public/admin_attribuer_camion.php?error=1");
    exit;
}
