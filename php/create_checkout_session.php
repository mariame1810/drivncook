<?php
// public/create_checkout_session.php
session_start();

if (!isset($_SESSION['id_franchise'])) {
  header('Location: login.html');
  exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../stripe_config.php'; // définit \Stripe\Stripe::setApiKey(...)


$id_franchise = (int) $_SESSION['id_franchise'];

// === Vérifier en BDD si le droit d'entrée est payé ===
$stPaid = $pdo->prepare("SELECT paid_entry_fee FROM franchises WHERE id_franchise = ?");
$stPaid->execute([$id_franchise]);
$paidEntryFee = (int) ($stPaid->fetchColumn() ?? 0);
$_SESSION['paid_entry_fee'] = $paidEntryFee; // resync
$canPay = ($paidEntryFee === 1);

function back($msg = null) {
  if ($msg) $_SESSION['flash_error'] = $msg;
  $to = $_SERVER['HTTP_REFERER'] ?? 'ajout_produit_commande.php';
  header('Location: ' . $to);
  exit;
}

// === Garde-fous ===
if (!$canPay) back("Paiement bloqué tant que le droit d’entrée n’est pas réglé.");
if (empty($_SESSION['cart']['items']) || empty($_SESSION['cart']['commande_id'])) {
  back("Votre panier est vide ou aucune commande n’est sélectionnée.");
}

$commande_id = (int) $_SESSION['cart']['commande_id'];

// Vérifier que la commande appartient au franchisé
$st = $pdo->prepare("SELECT id_commande FROM commandes WHERE id_commande = ? AND id_franchise = ?");
$st->execute([$commande_id, $id_franchise]);
if (!$st->fetch()) back("Commande invalide pour ce franchisé.");

// === Construire les line_items Stripe ===
$currency = 'eur';
$line_items = [];
$total_cents = 0;

foreach ($_SESSION['cart']['items'] as $pid => $it) {
  $name  = (string)$it['name'];
  $price = (float)$it['price']; // en euros
  $qty   = (int)$it['qty'];
  if ($qty <= 0) continue;

  $unit_amount = (int) round($price * 100);
  $total_cents += $unit_amount * $qty;

  $li = [
    'price_data' => [
      'currency' => $currency,
      'unit_amount' => $unit_amount,
      'product_data' => [
        'name' => $name,
      ],
    ],
    'quantity' => $qty,
  ];
  // Image si HTTPS
  if (!empty($it['image']) && preg_match('/^https?:\/\//i', $it['image'])) {
    $li['price_data']['product_data']['images'] = [$it['image']];
  }
  $line_items[] = $li;
}

if (empty($line_items)) back("Impossible de créer la session de paiement (panier invalide).");

try {
  // URLs
  $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
           . $_SERVER['HTTP_HOST']
           . rtrim(dirname($_SERVER['REQUEST_URI']), '/');

  $successUrl = $baseUrl . '/payment_success.php?type=ORDER&session_id={CHECKOUT_SESSION_ID}';
  $cancelUrl  = $baseUrl . '/ajout_produit_commande.php';

  // Créer la session Stripe
  $session = \Stripe\Checkout\Session::create([
    'mode' => 'payment',
    'payment_method_types' => ['card'],
    'line_items' => $line_items,
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'metadata' => [
      'type'         => 'ORDER',
      'franchise_id' => (string)$id_franchise,
      'commande_id'  => (string)$commande_id,
    ],
  ]);

  // Tracer dans `transactions`
  $stmt = $pdo->prepare("
    INSERT INTO transactions (id_franchise, amount, currency, type, stripe_id, status)
    VALUES (?, ?, ?, 'ORDER', ?, 'pending')
  ");
  $stmt->execute([
    $id_franchise,
    $total_cents, // en cents
    'EUR',
    $session->id
  ]);

  // Rediriger vers Stripe
  header('Location: ' . $session->url);
  exit;

} catch (\Throwable $e) {
  error_log('Stripe create session error: ' . $e->getMessage());
  back("Erreur lors de la création du paiement : " . $e->getMessage());
}
