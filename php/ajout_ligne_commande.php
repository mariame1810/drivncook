<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise'])) {
    header("Location: ../public/login.html");
    exit;
}

$id_franchise = $_SESSION['id_franchise'];
$id_commande = $_POST['id_commande'];
$id_produit = $_POST['id_produit'];
$quantite = $_POST['quantite'];

// Vérification que la commande appartient au franchisé
$verif = $pdo->prepare("SELECT * FROM commandes WHERE id_commande = ? AND id_franchise = ?");
$verif->execute([$id_commande, $id_franchise]);

if (!$verif->fetch()) {
    die("⛔ Accès interdit : cette commande ne vous appartient pas.");
}

// Insertion ou mise à jour de la ligne de commande
$sql = "INSERT INTO ligne_commande (id_commande, id_produit, quantite)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantite = quantite + ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_commande, $id_produit, $quantite, $quantite]);

header("Location: ../public/dashboard.php");
exit;
?>
