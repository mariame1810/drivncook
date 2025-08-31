<?php
// php/upload_document.php
session_start();
require_once '../config/db.php';

// Vérif session franchisé
if (!isset($_SESSION['id_franchise'])) {
  header('Location: ../public/login.php'); exit;
}
$idFr = (int)$_SESSION['id_franchise'];

// --- Vérif CSRF ---
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
  die("Erreur CSRF : token invalide.");
}

// Vérif type (KBIS ou CNI)
$type = $_POST['type'] ?? '';
$validTypes = ['KBIS','CNI'];
if (!in_array($type, $validTypes, true)) {
  die("Type de document invalide.");
}

// Vérif upload
if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
  die("Erreur lors de l’upload.");
}

// --- Contrôles fichier ---
$maxSize = 8 * 1024 * 1024; // 8 Mo max
if ($_FILES['document']['size'] > $maxSize) {
  die("Fichier trop volumineux (max 8 Mo).");
}

// Vérif MIME réel
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['document']['tmp_name']);
$allowed = [
  'application/pdf'       => 'pdf',
  'application/x-pdf'     => 'pdf',
  'application/acrobat'   => 'pdf',
  'image/jpeg'            => 'jpg',
  'image/pjpeg'           => 'jpg',
  'image/png'             => 'png',
];
if (!isset($allowed[$mime])) {
  die("Format non autorisé ($mime). Seuls PDF, JPG et PNG sont acceptés.");
}
$ext = $allowed[$mime];

// --- Préparation dossier stockage ---
$root = dirname(__DIR__); // projet root
$storeDir = $root . '/uploads/documents';
if (!is_dir($storeDir)) {
  @mkdir($storeDir, 0775, true);
}

// Nom fichier unique
$basename = sprintf('%s_%d_%s_%s.%s',
  strtolower($type),
  $idFr,
  date('YmdHis'),
  bin2hex(random_bytes(6)),
  $ext
);
$targetPath = $storeDir . '/' . $basename;
$pathForDb = 'uploads/documents/' . $basename;

// Déplacement
if (!move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
  die("Erreur lors du déplacement du fichier.");
}

// --- Enregistrer en BDD ---
$pdo->beginTransaction();
try {
  // Vérif si doc existant
  $sel = $pdo->prepare("SELECT path FROM documents WHERE id_franchise=? AND type=? LIMIT 1");
  $sel->execute([$idFr, $type]);
  $old = $sel->fetch(PDO::FETCH_ASSOC);

  if ($old) {
    // Update
    $upd = $pdo->prepare("UPDATE documents SET path=?, statut='en_attente', uploaded_at=NOW() WHERE id_franchise=? AND type=?");
    $upd->execute([$pathForDb, $idFr, $type]);

    // Supprimer ancien fichier
    if (!empty($old['path'])) {
      $oldPath = $root . '/' . ltrim($old['path'], '/');
      if (is_file($oldPath) && str_starts_with($oldPath, $storeDir)) {
        @unlink($oldPath);
      }
    }
  } else {
    // Insert
    $ins = $pdo->prepare("INSERT INTO documents (id_franchise, type, path, statut) VALUES (?,?,?, 'en_attente')");
    $ins->execute([$idFr, $type, $pathForDb]);
  }

  $pdo->commit();
} catch (Exception $e) {
  $pdo->rollBack();
  @unlink($targetPath);
  die("Erreur BDD : " . $e->getMessage());
}

header('Location: ../public/profil.php?step=documents&success=1');
exit;
