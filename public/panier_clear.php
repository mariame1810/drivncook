<?php
// public/panier_clear.php
session_start();

unset($_SESSION['cart']);
$_SESSION['flash_info'] = "Panier vidé.";
$to = $_POST['redirect'] ?? $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'ajout_produit_commande.php';
header('Location: ' . $to);
exit;
