<?php
session_start();
require_once '../config/db.php';

$email = $_POST['email'] ?? '';
$mot_de_passe = $_POST['mot_de_passe'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if ($admin && password_verify($mot_de_passe, $admin['mot_de_passe'])) {
    $_SESSION['id_admin'] = $admin['id_admin'];
    $_SESSION['nom_admin'] = $admin['nom'];
    header("Location: ../public/admin_dashboard.php");
    exit;
} else {
    header("Location: ../public/admin_login.php?error=Identifiants incorrects");
    exit;
}
