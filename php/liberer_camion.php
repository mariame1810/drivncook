<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../public/admin_login.php");
    exit;
}

$id_camion = $_GET['id'] ?? null;

if ($id_camion) {
    $stmt = $pdo->prepare("UPDATE camions SET id_franchise = NULL WHERE id_camion = ?");
    $stmt->execute([$id_camion]);
}

header("Location: ../public/admin_gestion_camions.php?success=1");
exit;
