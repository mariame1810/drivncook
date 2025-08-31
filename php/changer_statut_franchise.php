<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../public/admin_login.php");
    exit;
}

$id = $_POST['id_franchise'] ?? null;
$nouvelStatut = $_POST['nouvel_statut'] ?? null;

if ($id && ($nouvelStatut === '0' || $nouvelStatut === '1')) {
    $stmt = $pdo->prepare("UPDATE franchises SET actif = ? WHERE id_franchise = ?");
    $stmt->execute([$nouvelStatut, $id]);
}

header("Location: ../public/admin_franchises.php?success=1");
exit;
