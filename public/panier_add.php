<?php
// public/panier_add.php
session_start();
require_once __DIR__ . '/../config/db.php'; // <-- chemin corrigé (../config)
if (!isset($_SESSION['id_franchise'])) { header('Location: login.html'); exit; }

$id_franchise = (int) $_SESSION['id_franchise'];

// util
function back(string $fallback = 'ajout_produit_commande.php'){
  $to = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? $fallback;
  header('Location: '.$to); exit;
}

$id_commande = isset($_POST['id_commande']) ? (int) $_POST['id_commande'] : 0;
$id_produit  = isset($_POST['id_produit'])  ? (int) $_POST['id_produit']  : 0;
$qty         = isset($_POST['qty'])         ? (int) $_POST['qty']         : 0;

if ($id_commande <= 0 || $id_produit <= 0 || $qty <= 0) {
  $_SESSION['flash_error'] = "Veuillez sélectionner une commande, un produit et une quantité valide.";
  back();
}

// Vérifier que la commande appartient bien au franchisé
$st = $pdo->prepare("SELECT id_commande FROM commandes WHERE id_commande = ? AND id_franchise = ?");
$st->execute([$id_commande, $id_franchise]);
if (!$st->fetch()) {
  $_SESSION['flash_error'] = "Commande invalide.";
  back();
}

// Charger le produit (on ne fait pas confiance au client)
$st = $pdo->prepare("SELECT id_produit, nom, prix_unitaire, image_url FROM produits WHERE id_produit = ?");
$st->execute([$id_produit]);
$prod = $st->fetch(PDO::FETCH_ASSOC);
if (!$prod) {
  $_SESSION['flash_error'] = "Produit introuvable.";
  back();
}

// Initialiser le panier
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
  $_SESSION['cart'] = ['commande_id'=>null, 'items'=>[], 'currency'=>'EUR'];
}

// Si un panier existe déjà pour une autre commande -> on refuse (sécurité)
if (!empty($_SESSION['cart']['commande_id']) && $_SESSION['cart']['commande_id'] != $id_commande && !empty($_SESSION['cart']['items'])) {
  $_SESSION['flash_error'] = "Ce panier est rattaché à la commande #".$_SESSION['cart']['commande_id'].". Videz le panier pour changer de commande.";
  back();
}

// Associer le panier à la commande sélectionnée
$_SESSION['cart']['commande_id'] = $id_commande;

// Ajouter ou incrémenter la ligne
$pid = (int)$prod['id_produit'];
if (!isset($_SESSION['cart']['items'][$pid])) {
  $_SESSION['cart']['items'][$pid] = [
    'name'  => $prod['nom'],
    'price' => (float)$prod['prix_unitaire'],
    'image' => $prod['image_url'] ?? '',
    'qty'   => 0
  ];
}
$_SESSION['cart']['items'][$pid]['qty'] = min(99, $_SESSION['cart']['items'][$pid]['qty'] + $qty);

$_SESSION['flash_success'] = "« {$prod['nom']} » ajouté au panier de la commande #{$id_commande}.";
back();
