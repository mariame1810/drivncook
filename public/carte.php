<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_franchise']) && !isset($_SESSION['id_admin'])) {
    header("Location: login.html");
    exit;
}

$entrepots = $pdo->query("SELECT nom, latitude, longitude FROM entrepots WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll();

$camions = $pdo->query("SELECT immatriculation, latitude, longitude FROM camions WHERE id_franchise IS NULL AND latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Carte - Entrepôts & Camions</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <style>
    #map { height: 600px; }
  </style>
</head>
<body>
  <div class="container p-4">
    <h3>🗺️</h3>
    <div id="map"></div>
    <br>
    <a href="dashboard.php">⬅️ Retour au Dashboard</a>
  </div>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    const map = L.map('map').setView([46.603354, 1.888334], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        minZoom: 4,
        maxZoom: 19
    }).addTo(map);

    const entrepots = <?= json_encode($entrepots) ?>;
    const camions = <?= json_encode($camions) ?>;

    const entrepotIcon = L.icon({
      iconUrl: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
      iconSize: [32, 32]
    });

    const camionIcon = L.icon({
      iconUrl: 'https://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
      iconSize: [32, 32]
    });

    entrepots.forEach(e => {
      L.marker([e.latitude, e.longitude], { icon: entrepotIcon })
        .addTo(map)
        .bindPopup(`<strong>Entrepôt</strong><br>${e.nom}`);
    });

    camions.forEach(c => {
      L.marker([c.latitude, c.longitude], { icon: camionIcon })
        .addTo(map)
        .bindPopup(`<strong>Camion libre</strong><br>${c.immatriculation}`);
    });
  </script>
</body>
</html>
