<?php
// php/middleware_onboarding.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise'])) {
  header('Location: ../public/login.php'); exit;
}

// État du franchisé
$fid = (int)$_SESSION['id_franchise'];
$st  = $pdo->prepare("SELECT docs_valides, paid_entry_fee FROM franchises WHERE id_franchise=?");
$st->execute([$fid]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) { session_destroy(); header('Location: ../public/login.php'); exit; }

// 1) Documents doivent être validés pour TOUT (sauf profil/documents)
$script = basename($_SERVER['SCRIPT_NAME']);
$alwaysAllowed = ['login.php','logout.php','profil.php']; // profil accessible pour envoyer docs

if ((int)$row['docs_valides'] !== 1 && !in_array($script, $alwaysAllowed, true)) {
  header('Location: ../public/profil.php?step=documents'); exit;
}

// 2) Paiement : on autorise le "read-only" + dashboard, on bloque les ACTIONS
$allowedWhileUnpaid = [
  'dashboard.php',         // OK, mais on affichera un bandeau d’alerte
  'profil.php',            // OK
  'mes_commandes.php',     // lecture OK (si tu veux bloquer l’export, on le fera côté bouton)
  'pay_entry_fee.php',     // nécessaire pour payer
  'payment_success.php',   // retour Stripe
  'logout.php',
];

if ((int)$row['paid_entry_fee'] !== 1) {
  if (!in_array($script, $allowedWhileUnpaid, true)) {
    // Pour les actions (création/édition) on redirige vers la page de paiement
    header('Location: ../public/pay_entry_fee.php?notice=required'); 
    exit;
  }
}
