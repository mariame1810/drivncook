<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['id_admin'])) { header('Location: admin_login.php'); exit; }

// Filtres simples
$filtreStatut = $_GET['statut'] ?? '';
$filtreType   = $_GET['type'] ?? '';
$params = [];
$where = [];
if (in_array($filtreStatut, ['en_attente','valide','refuse'], true)) { $where[]='d.statut=?'; $params[]=$filtreStatut; }
if (in_array($filtreType, ['KBIS','CNI'], true)) { $where[]='d.type=?'; $params[]=$filtreType; }
$sql = "SELECT d.*, f.nom AS franchise_nom, f.email AS franchise_email
        FROM documents d
        JOIN franchises f ON f.id_franchise = d.id_franchise";
if ($where) $sql .= " WHERE ".implode(' AND ',$where);
$sql .= " ORDER BY d.uploaded_at DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
function badge($s){
  return match($s){
    'valide'=>'<span class="badge text-bg-success">Validé</span>',
    'refuse'=>'<span class="badge text-bg-danger">Refusé</span>',
    default  =>'<span class="badge text-bg-secondary">En attente</span>',
  };
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Admin – Documents</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <h1 class="mb-3">Documents (KBIS & CNI)</h1>

  <form class="row g-2 mb-3">
    <div class="col-auto">
      <select name="statut" class="form-select">
        <option value="">Tous statuts</option>
        <option value="en_attente" <?php if($filtreStatut==='en_attente') echo 'selected';?>>En attente</option>
        <option value="valide" <?php if($filtreStatut==='valide') echo 'selected';?>>Validé</option>
        <option value="refuse" <?php if($filtreStatut==='refuse') echo 'selected';?>>Refusé</option>
      </select>
    </div>
    <div class="col-auto">
      <select name="type" class="form-select">
        <option value="">Tous types</option>
        <option value="KBIS" <?php if($filtreType==='KBIS') echo 'selected';?>>KBIS</option>
        <option value="CNI" <?php if($filtreType==='CNI') echo 'selected';?>>CNI</option>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-secondary">Filtrer</button>
    </div>
  </form>

  <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success py-2">Mise à jour effectuée.</div>
  <?php elseif (isset($_GET['err'])): ?>
    <div class="alert alert-danger py-2">Erreur lors de l’action.</div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr>
        <th>Franchise</th><th>Type</th><th>Statut</th><th>Fichier</th><th>Envoyé le</th><th>Action</th>
      </tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?php echo htmlspecialchars($r['franchise_nom'] ?? ''); ?></div>
            <div class="small text-muted"><?php echo htmlspecialchars($r['franchise_email'] ?? ''); ?></div>
          </td>
          <td><?php echo htmlspecialchars($r['type']); ?></td>
          <td><?php echo badge($r['statut']); ?></td>
          <td>
            <?php 
            echo htmlspecialchars(basename($r['path']));
            ?>
          </td>
          <td><?php echo htmlspecialchars($r['uploaded_at']); ?></td>
          <td class="d-flex gap-2">
            <form action="../php/admin_document_action.php" method="post">
              <input type="hidden" name="id" value="<?php echo (int)$r['id'];?>">
              <input type="hidden" name="action" value="valider">
              <button class="btn btn-success btn-sm">Valider</button>
            </form>
            <form action="../php/admin_document_action.php" method="post">
              <input type="hidden" name="id" value="<?php echo (int)$r['id'];?>">
              <input type="hidden" name="action" value="refuser">
              <button class="btn btn-danger btn-sm">Refuser</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
