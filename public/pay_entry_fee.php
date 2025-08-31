<?php
// public/pay_entry_fee.php
session_start();
require_once '../config/db.php';
require_once '../php/stripe_config.php';

if (!isset($_SESSION['id_franchise'])) { header('Location: login.php'); exit; }

// Vérifie l’état du franchisé
$st = $pdo->prepare("SELECT docs_valides, paid_entry_fee, email, nom, prenom FROM franchises WHERE id_franchise=?");
$st->execute([$_SESSION['id_franchise']]);
$me = $st->fetch(PDO::FETCH_ASSOC);
if (!$me) { die('Franchisé introuvable'); }

// Si pas de docs validés → retour profil pour compléter
if ((int)$me['docs_valides'] !== 1) { header('Location: profil.php?step=documents'); exit; }
// Si déjà payé → dashboard
if ((int)$me['paid_entry_fee'] === 1) { header('Location: dashboard.php'); exit; }
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Paiement droit d’entrée</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="container py-5">
  <h1 class="mb-3">Droit d’entrée – 50 000 €</h1>
  <p class="text-secondary">Vos documents ont été validés ✅. Il vous reste à régler le droit d’entrée pour activer votre compte.</p>

  <div class="card p-4 shadow-sm" style="max-width:520px">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <div class="fw-semibold">Accès Driv’n Cook</div>
        <div class="small text-secondary">Paiement unique</div>
      </div>
      <div class="fs-4 fw-bold">50 000 €</div>
    </div>
    <hr>
    <button id="payBtn" class="btn btn-primary w-100">Payer maintenant</button>
    <p class="small text-muted mt-2 mb-0">Mode test Stripe : utilisez la carte <code>4242 4242 4242 4242</code>, date future, CVC au hasard.</p>
  </div>

  <script>
    const stripe = Stripe("<?= STRIPE_PUBLISHABLE_KEY ?>");
    document.getElementById('payBtn').addEventListener('click', async () => {
      const resp = await fetch('../php/create_checkout_session.php', { method: 'POST' });
      const data = await resp.json();
      if (!data.id) { alert('Erreur création session paiement'); return; }
      stripe.redirectToCheckout({ sessionId: data.id });
    });
  </script>
</body>
</html>
