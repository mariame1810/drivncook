<?php
// public/panier_update.php
session_start();

function redirect_back(string $fallback = 'ajout_produit_commande.php'): void {
  $to = $_POST['redirect'] ?? $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? $fallback;
  header('Location: ' . $to);
  exit;
}

$id  = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : 0;
$qty = isset($_POST['qty']) ? (int) $_POST['qty'] : -1;

if ($id <= 0 || !isset($_SESSION['cart']['items'][$id])) {
  $_SESSION['flash_error'] = "Ligne de panier introuvable.";
  redirect_back();
}

if ($qty <= 0) {
  // qty <= 0 => on supprime
  unset($_SESSION['cart']['items'][$id]);
  if (empty($_SESSION['cart']['items'])) unset($_SESSION['cart']);
  $_SESSION['flash_info'] = "Produit retiré du panier.";
  redirect_back();
}

$_SESSION['cart']['items'][$id]['qty'] = min(99, $qty);
$_SESSION['flash_success'] = "Quantité mise à jour.";
redirect_back();
