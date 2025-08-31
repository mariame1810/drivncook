<?php
session_start();
require_once '../config/db.php'; 

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Requête invalide (CSRF détecté).");
}

if (empty($_POST['email']) || empty($_POST['mot_de_passe'])) {
    die("Email et mot de passe requis.");
}

$email = $_POST['email'];
$pwd   = $_POST['mot_de_passe'];

$sql = "SELECT id_franchise, mot_de_passe FROM franchises WHERE email = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($pwd, $user['mot_de_passe'])) {
    $_SESSION['id_franchise'] = $user['id_franchise'];
    header("Location: ../public/dashboard.php");
    exit;
} else {
    die("Identifiants invalides.");
}
