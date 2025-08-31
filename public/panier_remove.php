<?php
// public/panier_remove.php
session_start();

function redirect_back(string $fallback = 'ajout_produit_commande.php'): void {
  $to = $_POST['redirect'] ?? $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? $fallback;
  header('Location: ' . $to);
  exit;
}

$id = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : (isset($_GET['id_produit']) ? (int) $_GET['id_produit'] : 0);

if ($id > 0 && isset($_SESSION['cart']['items'][$id])) {
  unset($_SESSION['cart']['items'][$id]);
  if (empty($_SESSION['cart']['items'])) unset($_SESSION['cart']);
  $_SESSION['flash_info'] = "Produit retiré du panier.";
} else {
  $_SESSION['flash_error'] = "Ligne de panier introuvable.";
}

redirect_back();
