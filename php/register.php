<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

    // Vérifie si l’email existe déjà
    $sql = "SELECT * FROM franchises WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        die("⛔ Cette adresse email est déjà utilisée.");
    }

    // Insérer le franchisé
    $sql = "INSERT INTO franchises (nom, prenom, email, mot_de_passe, droit_entree) VALUES (?, ?, ?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $prenom, $email, $mot_de_passe]);

    // Récupérer l’ID pour la session
    $id = $pdo->lastInsertId();
    $_SESSION['id_franchise'] = $id;
    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;

    // Redirection vers dashboard
    header("Location: ../public/dashboard.php");
    exit;
}
?>
