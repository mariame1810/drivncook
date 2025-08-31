<?php
// public/payment_success.php
session_start();

if (!isset($_SESSION['id_franchise'])) {
  header('Location: login.html'); exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../stripe_config.php';

$id_franchise = (int) $_SESSION['id_franchise'];
$type = isset($_GET['type']) ? $_GET['type'] : 'ENTRY_FEE';
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

function goOrders($okMsg = null) {
  if ($okMsg) $_SESSION['flash_success'] = $okMsg;
  header('Location: mes_commandes.php'); exit;
}

function goBackError($msg) {
  $_SESSION['flash_error'] = $msg;
  header('Location: ajout_produit_commande.php'); exit;
}

try {
  // Récupérer la session Stripe si fournie
  $stripeSession = null;
  if ($session_id) {
    $stripeSession = \Stripe\Checkout\Session::retrieve($session_id);
  }

  if ($type === 'ORDER') {
    if (!$stripeSession) goBackError("Session Stripe manquante.");
    // Vérifier statut
    // payment_status peut être 'paid'; sinon on peut récupérer le PaymentIntent
    $paidOk = ($stripeSession->payment_status === 'paid');
    if (!$paidOk && !empty($stripeSession->payment_intent)) {
      $pi = \Stripe\PaymentIntent::retrieve($stripeSession->payment_intent);
      $paidOk = ($pi && $pi->status === 'succeeded');
    }
    if (!$paidOk) goBackError("Paiement non confirmé.");

    // Idempotence: si déjà paid dans transactions, on évite les doublons
    $tx = $pdo->prepare("SELECT id, status FROM transactions WHERE stripe_id = ? AND type = 'ORDER' AND id_franchise = ? LIMIT 1");
    $tx->execute([$stripeSession->id, $id_franchise]);
    $rowTx = $tx->fetch(PDO::FETCH_ASSOC);

    if ($rowTx && $rowTx['status'] === 'paid') {
      // Panier peut être déjà vidé; on redirige juste
      goOrders("Paiement confirmé.");
    }

    // Récupérer commande cible
    // 1) depuis la session d’application (cart), plus fiable pour notre flux
    $commande_id = $_SESSION['cart']['commande_id'] ?? null;
    // 2) ou fallback depuis metadata Stripe si dispo
    if (!$commande_id && !empty($stripeSession->metadata['commande_id'])) {
      $commande_id = (int)$stripeSession->metadata['commande_id'];
    }
    if (!$commande_id) goBackError("Commande cible introuvable.");
    // Vérifier l’appartenance
    $chk = $pdo->prepare("SELECT id_commande FROM commandes WHERE id_commande = ? AND id_franchise = ?");
    $chk->execute([(int)$commande_id, $id_franchise]);
    if (!$chk->fetch()) goBackError("Commande invalide pour ce franchisé.");

    // Reconstituer le panier (depuis la session app)
    if (empty($_SESSION['cart']['items'])) goBackError("Panier vide en session lors de la validation.");

    $items = $_SESSION['cart']['items'];
    $total = 0.0;
    foreach ($items as $it) { $total += (float)$it['price'] * (int)$it['qty']; }

    // Transaction SQL
    $pdo->beginTransaction();
    try {
      // Insérer les lignes de commande
      $ins = $pdo->prepare("
        INSERT INTO ligne_commande (id_commande, id_produit, quantite, prix_unitaire)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE quantite = quantite + VALUES(quantite) /* si PK composite (id_commande,id_produit) */
      ");
      foreach ($items as $pid => $it) {
        $ins->execute([
          (int)$commande_id,
          (int)$pid,
          (int)$it['qty'],
          (float)$it['price']
        ]);

        // Décrémenter le stock (si applicable)
        // Exemple simple: table stocks(id_produit, quantite)
        // $upd = $pdo->prepare("UPDATE stocks SET quantite = GREATEST(0, quantite - ?) WHERE id_produit = ?");
        // $upd->execute([(int)$it['qty'], (int)$pid]);
        //
        // Ou bien historiser dans mouvements_stock (à adapter si tu veux l’activer)
      }

      // Mettre à jour la commande: total + statut
      // (Si tu stockes déjà un total cumulé dans commandes)
      $updCmd = $pdo->prepare("
        UPDATE commandes
        SET total = COALESCE(total,0) + :add_total,
            statut = 'payée'
        WHERE id_commande = :id
      ");
      $updCmd->execute([':add_total' => $total, ':id' => (int)$commande_id]);

      // Marquer la transaction comme payée
      if ($rowTx) {
        $pdo->prepare("UPDATE transactions SET status='paid' WHERE id = ?")->execute([$rowTx['id']]);
      } else {
        $pdo->prepare("
          INSERT INTO transactions (id_franchise, amount, currency, type, stripe_id, status)
          VALUES (?, ?, 'EUR', 'ORDER', ?, 'paid')
        ")->execute([$id_franchise, (int)round($total*100), $stripeSession->id]);
      }

      $pdo->commit();

    } catch (\Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }

    // Nettoyage panier + redirection
    unset($_SESSION['cart']);
    goOrders("Paiement validé. Les produits ont été ajoutés à la commande #{$commande_id}.");

  } else {
    /*** BRANCHE EXISTANTE : ENTRY_FEE (conserve ton comportement actuel)
     * Si tu as déjà du code ici, colle-le à la place de ce bloc simplifié.
     * Je garde un squelette minimal.
     */
    if (!$session_id) goBackError("Session Stripe manquante.");
    $stripeSession = \Stripe\Checkout\Session::retrieve($session_id);
    $paidOk = ($stripeSession->payment_status === 'paid');
    if (!$paidOk && !empty($stripeSession->payment_intent)) {
      $pi = \Stripe\PaymentIntent::retrieve($stripeSession->payment_intent);
      $paidOk = ($pi && $pi->status === 'succeeded');
    }
    if (!$paidOk) goBackError("Paiement non confirmé.");

    // ... ici, ta logique du droit d’entrée (mise à jour du franchisé, transactions, etc.)
    $_SESSION['flash_success'] = "Droit d’entrée réglé avec succès.";
    header('Location: dashboard.php'); exit;
  }

} catch (\Throwable $e) {
  error_log('payment_success error: '.$e->getMessage());
  goBackError("Une erreur est survenue : " . $e->getMessage());
}
