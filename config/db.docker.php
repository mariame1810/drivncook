<?php
// Configuration de base de données pour Docker
$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'drivncook';
$user = getenv('DB_USER') ?: 'drivncook_user';
$pass = getenv('DB_PASS') ?: 'drivncook_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
