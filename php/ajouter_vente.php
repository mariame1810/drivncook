<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise'])) {
    header("Location: ../public/login.html");
    exit;
}

$id_franchise = $_SESSION['id_franchise'];
$montant = $_POST['montant'] ?? null;
$date_vente = $_POST['date_vente'] ?? date('Y-m-d'); // si vide, aujourd’hui

// Vérification : montant valide
if (!$montant || !is_numeric($montant) || $montant <= 0) {
    header("Location: ../public/dashboard.php?error=montant");
    exit;
}

// Insertion en base
$stmt = $pdo->prepare("INSERT INTO ventes (id_franchise, date_vente, montant) VALUES (?, ?, ?)");
$stmt->execute([$id_franchise, $date_vente, $montant]);

header("Location: ../public/dashboard.php?success=vente");
exit;
