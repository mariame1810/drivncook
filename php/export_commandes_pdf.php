<?php
session_start();
require_once '../config/db.php';
require_once '../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

if (!isset($_SESSION['id_franchise'])) {
    die("Accès refusé.");
}

$id_franchise = $_SESSION['id_franchise'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];

$sql = "SELECT c.date_commande, c.type_commande, c.statut, e.nom AS entrepot_nom
        FROM commandes c
        LEFT JOIN entrepots e ON c.id_entrepot = e.id_entrepot
        WHERE c.id_franchise = ?
        ORDER BY c.date_commande DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_franchise]);
$commandes = $stmt->fetchAll();

$html = "<h1>Historique des commandes – $prenom $nom</h1>";
$html .= "<table border='1' cellpadding='5' cellspacing='0'>
<tr><th>Date</th><th>Type</th><th>Statut</th><th>Entrepôt</th></tr>";

foreach ($commandes as $c) {
    $html .= "<tr>
                <td>{$c['date_commande']}</td>
                <td>{$c['type_commande']}</td>
                <td>{$c['statut']}</td>
                <td>" . ($c['entrepot_nom'] ?? '—') . "</td>
              </tr>";
}

$html .= "</table>";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("commandes_$prenom$nom.pdf", ["Attachment" => 0]);
exit;
?>
