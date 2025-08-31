<?php
require_once '../config/db.php';
require_once '../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

session_start();

if (!isset($_SESSION['id_franchise']) || !isset($_GET['id_commande'])) {
    die("Accès refusé.");
}

$id_franchise = $_SESSION['id_franchise'];
$id_commande = $_GET['id_commande'];

$sql = "SELECT c.*, e.nom AS entrepot_nom
        FROM commandes c
        LEFT JOIN entrepots e ON c.id_entrepot = e.id_entrepot
        WHERE c.id_commande = ? AND c.id_franchise = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_commande, $id_franchise]);
$commande = $stmt->fetch();

if (!$commande) {
    die("Commande non trouvée.");
}

$sqlProduits = "SELECT p.nom, p.prix_unitaire, lc.quantite
                FROM ligne_commande lc
                JOIN produits p ON p.id_produit = lc.id_produit
                WHERE lc.id_commande = ?";
$stmt = $pdo->prepare($sqlProduits);
$stmt->execute([$id_commande]);
$produits = $stmt->fetchAll();

$total = 0;
$html = "<h1>Commande #{$commande['id_commande']}</h1>";
$html .= "<p><strong>Date :</strong> {$commande['date_commande']}<br>";
$html .= "<strong>Type :</strong> {$commande['type_commande']}<br>";
$html .= "<strong>Statut :</strong> {$commande['statut']}<br>";
$html .= "<strong>Entrepôt :</strong> " . ($commande['entrepot_nom'] ?? '—') . "</p>";

if ($produits) {
    $html .= "<table border='1' cellpadding='5' cellspacing='0'>
                <tr><th>Produit</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr>";
    foreach ($produits as $p) {
        $ligne_total = $p['quantite'] * $p['prix_unitaire'];
        $total += $ligne_total;
        $html .= "<tr>
                    <td>{$p['nom']}</td>
                    <td>{$p['quantite']}</td>
                    <td>{$p['prix_unitaire']} €</td>
                    <td>" . number_format($ligne_total, 2) . " €</td>
                  </tr>";
    }
    $html .= "</table><br><strong>Total commande :</strong> " . number_format($total, 2) . " €";
} else {
    $html .= "<p><em>Aucun produit dans cette commande.</em></p>";
}

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("commande_{$id_commande}.pdf", ["Attachment" => 0]);
exit;
?>
