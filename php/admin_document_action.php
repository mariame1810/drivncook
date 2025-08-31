<?php
session_start();
require_once '../config/db.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['id_admin'])) {
    header('Location: ../public/admin_login.php');
    exit;
}

$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['valider','refuser'], true)) {
    header('Location: ../public/admin_documents.php?err=bad');
    exit;
}

// Récupérer le document
$st = $pdo->prepare("SELECT * FROM documents WHERE id=? LIMIT 1");
$st->execute([$id]);
$doc = $st->fetch(PDO::FETCH_ASSOC);
if (!$doc) {
    header('Location: ../public/admin_documents.php?err=nodoc');
    exit;
}

$nouveauStatut = $action === 'valider' ? 'valide' : 'refuse';

// Mettre à jour le statut
$upd = $pdo->prepare("UPDATE documents SET statut=? WHERE id=?");
if (!$upd->execute([$nouveauStatut, $id])) {
    header('Location: ../public/admin_documents.php?err=upd');
    exit;
}

// Vérifier si les 2 docs (KBIS + CNI) sont validés
$idFr = (int)$doc['id_franchise'];
$ok = $pdo->prepare("SELECT type, statut FROM documents WHERE id_franchise=?");
$ok->execute([$idFr]);
$kb = $cn = false;
foreach ($ok as $row) {
    if ($row['type'] === 'KBIS' && $row['statut'] === 'valide') $kb = true;
    if ($row['type'] === 'CNI'  && $row['statut'] === 'valide') $cn = true;
}
$docsValides = ($kb && $cn) ? 1 : 0;

// Mettre à jour la franchise (docs_valides)
$pdo->prepare("UPDATE franchises SET docs_valides=? WHERE id_franchise=?")
    ->execute([$docsValides, $idFr]);

// =========================
// Envoi d'email en PHP natif
// =========================
$toEmail = $fr['email'] ?? '';
$toName  = trim(($fr['prenom'] ?? '').' '.($fr['nom'] ?? ''));
$fromEmail = 'noreply@tondomaine.com';       // <-- mets une adresse RÉELLE de ton domaine
$fromName  = "Driv'n Cook";

// Sujet + contenu déjà construits dans $subject / $message
$encodedSubject = '=?UTF-8?B?'.base64_encode($subject).'?=';

// Entêtes soignés
$headers  = "From: {$fromName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$fromName} <{$fromEmail}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/".phpversion()."\r\n";

// Sur certains hébergeurs, il faut définir l'enveloppe sender (-f)
$additional_params = "-f {$fromEmail}";

// Tentative d'envoi
$sent = @mail($toEmail, $encodedSubject, $message, $headers, $additional_params);

// Log léger si échec (n'interrompt pas le flux admin)
if (!$sent) {
  error_log("[mail] Échec d'envoi à {$toEmail} sujet='{$subject}'");
}


/* ===== Redirection ===== */
$redirect = $_POST['redirect'] ?? '';
if ($redirect && preg_match('/^admin_fiche_franchise\.php\?id=\d+$/', $redirect)) {
    $sep = (str_contains($redirect, '?') ? '&' : '?');
    header('Location: ../public/' . $redirect . $sep . 'ok=1');
    exit;
}
header('Location: ../public/admin_documents.php?ok=1');
exit;


