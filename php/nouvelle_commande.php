<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise'])) {
    header('Location: ../public/login.html');
    exit;
}

$id_franchise = $_SESSION['id_franchise'];
$type = $_POST['type_commande'];
$id_entrepot = !empty($_POST['id_entrepot']) ? $_POST['id_entrepot'] : null;
$date = date('Y-m-d');


if ($type === 'libre') {
    // Compter les commandes totales
    $sqlTotal = "SELECT COUNT(*) as total FROM commandes WHERE id_franchise = ?";
    $stmt = $pdo->prepare($sqlTotal);
    $stmt->execute([$id_franchise]);
    $totalCommandes = $stmt->fetch()['total'];


    $sqlLibres = "SELECT COUNT(*) as libres FROM commandes WHERE id_franchise = ? AND type_commande = 'libre'";
    $stmt = $pdo->prepare($sqlLibres);
    $stmt->execute([$id_franchise]);
    $libres = $stmt->fetch()['libres'];


    if ($totalCommandes > 0) {
        $pourcentage = ($libres / $totalCommandes) * 100;

        if ($pourcentage >= 20) {
            die("⛔ Règle 80/20 : Vous avez atteint la limite de 20% de commandes libres.");
        }
    }
}


$sql = "INSERT INTO commandes (date_commande, type_commande, statut, id_franchise, id_entrepot)
        VALUES (?, ?, 'en_attente', ?, ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$date, $type, $id_franchise, $id_entrepot]);

header("Location: ../public/dashboard.php");
exit;
?>
