<?php
// php/create_checkout_session.php
session_start();
require_once '../config/db.php';
require_once './stripe_config.php';

if (!isset($_SESSION['id_franchise'])) {
  http_response_code(401); echo json_encode(['error'=>'unauth']); exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Infos franchisé
$st = $pdo->prepare("SELECT email FROM franchises WHERE id_franchise=?");
$st->execute([$_SESSION['id_franchise']]);
$me = $st->fetch(PDO::FETCH_ASSOC);
$email = $me['email'] ?? null;

$successUrl = sprintf('%s/public/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
  rtrim(dirname((isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), '/php')
);
$cancelUrl = sprintf('%s/public/pay_entry_fee.php',
  rtrim(dirname((isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), '/php')
);

try {
  $session = \Stripe\Checkout\Session::create([
    'mode' => 'payment',
    'payment_method_types' => ['card'],
    'customer_email' => $email ?: null,
    'line_items' => [[
      'price_data' => [
        'currency' => ENTRY_FEE_CURRENCY,
        'product_data' => ['name' => "Droit d’entrée Driv’n Cook"],
        'unit_amount' => ENTRY_FEE_AMOUNT,
      ],
      'quantity' => 1,
    ]],
    'metadata' => [
      'id_franchise' => (string)$_SESSION['id_franchise'],
      'type' => 'ENTRY_FEE'
    ],
    'success_url' => $successUrl,
    'cancel_url'  => $cancelUrl,
  ]);

  header('Content-Type: application/json');
  echo json_encode(['id' => $session->id]);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
