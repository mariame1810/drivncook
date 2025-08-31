<?php
// public/payment_success.php
session_start();
require_once '../config/db.php';
require_once '../php/stripe_config.php';

if (!isset($_GET['session_id'])) { header('Location: dashboard.php'); exit; }
$sessionId = $_GET['session_id'];

require_once __DIR__ . '/../vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
  $session = \Stripe\Checkout\Session::retrieve($sessionId);
  // Vérifier que le paiement est bien "paid"
  if ($session->payment_status === 'paid' && isset($_SESSION['id_franchise'])) {

    $idFr = (int)$_SESSION['id_franchise'];

    $ins = $pdo->prepare("INSERT INTO transactions (id_franchise, amount, currency, type, stripe_id, status) 
                          VALUES (?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE status=VALUES(status)");
    $ins->execute([
      $idFr,
      ENTRY_FEE_AMOUNT,
      ENTRY_FEE_CURRENCY,
      'ENTRY_FEE',
      $session->id,
      'paid'
    ]);

    // Marquer payé
    $pdo->prepare("UPDATE franchises SET paid_entry_fee=1 WHERE id_franchise=?")->execute([$idFr]);

    // Rediriger vers dashboard
    header('Location: dashboard.php?paid=1'); exit;
  }
} catch (\Throwable $e) {
  // journalise si besoin
}

header('Location: pay_entry_fee.php?err=verify');
exit;
