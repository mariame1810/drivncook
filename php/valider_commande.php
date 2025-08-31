<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_commande'])) {
    $id = intval($_POST['id_commande']);

    $stmt = $pdo->prepare("UPDATE commandes SET statut = 'validée' WHERE id_commande = ?");
    $stmt->execute([$id]);

    header("Location: ../public/admin_commandes.php?success=1");
    exit;
} else {
    header("Location: ../public/admin_commandes.php");
    exit;
}
