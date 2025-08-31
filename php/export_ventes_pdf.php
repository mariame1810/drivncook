<?php
session_start();
require_once '../config/db.php';
require_once '../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if (!isset($_SESSION['id_franchise'])) {
    die("Accès non autorisé");
}

$id_franchise = $_SESSION['id_franchise'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

$sql = "SELECT * FROM ventes WHERE id_franchise = ? ORDER BY date_vente DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$ventes = $stmt->fetchAll();

$total = 0;
$html = "<h1>Historique des ventes – $prenom $nom</h1>";
$html .= "<table border='1' cellpadding='5' cellspacing='0'><tr><th>Date</th><th>Montant (€)</th></tr>";

foreach ($ventes as $vente) {
    $html .= "<tr><td>{$vente['date_vente']}</td><td>{$vente['montant']}</td></tr>";
    $total += $vente['montant'];
}
$html .= "</table><br><strong>Total : $total €</strong>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ventes_$prenom$nom.pdf", ["Attachment" => 0]);
exit;
?>
