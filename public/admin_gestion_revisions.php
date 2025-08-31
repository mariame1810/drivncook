<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$nom = $_SESSION['nom_admin'] ?? 'Admin';

// Filtres
$statutFilter = $_GET['statut'] ?? '';
$validStatuts = ['a_planifier','planifiee','faite','en_retard',''];
if (!in_array($statutFilter, $validStatuts, true)) $statutFilter = '';

// Query révisions + camions + franchisés
$params = [];
$where = [];
if ($statutFilter && $statutFilter !== 'en_retard') {
    $where[] = "r.statut = :statut";
    $params[':statut'] = $statutFilter;
}
$sql = "
SELECT 
    r.id AS id_revision, r.id_camion, r.type, r.echeance_km, r.echeance_date, r.statut,
    c.immatriculation, c.marque, c.modele, c.kilometrage AS km_camion, c.annee, c.image_url, c.id_franchise,
    f.prenom, f.nom
FROM revisions r
JOIN camions c ON c.id_camion = r.id_camion
LEFT JOIN franchises f ON f.id_franchise = c.id_franchise
";
if ($where) $sql .= " WHERE ".implode(" AND ", $where);
$sql .= " ORDER BY 
    CASE r.statut 
      WHEN 'a_planifier' THEN 1 
      WHEN 'planifiee' THEN 2 
      WHEN 'faite' THEN 3 
      ELSE 4 
    END,
    r.echeance_date IS NULL,
    r.echeance_date ASC,
    r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$revs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: badge statut + retard
$today = new DateTimeImmutable('today');
function badgeStatut(array $r, DateTimeImmutable $today): array {
    $statut = $r['statut'];
    $retard = false;

    // retard par date
    if (!empty($r['echeance_date'])) {
        $d = DateTimeImmutable::createFromFormat('Y-m-d', substr($r['echeance_date'],0,10));
        if ($d && $d < $today && $statut !== 'faite') $retard = true;
    }
    // retard par km
    if (!$retard && !empty($r['echeance_km']) && isset($r['km_camion']) && $statut !== 'faite') {
        if ((int)$r['km_camion'] >= (int)$r['echeance_km']) $retard = true;
    }

    if ($retard) {
        return ['en_retard','<span class="badge text-bg-danger"><i class="bi bi-x-octagon me-1"></i>En retard</span>'];
    }
    return match ($statut) {
        'a_planifier' => ['a_planifier','<span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i>À planifier</span>'],
        'planifiee'   => ['planifiee','<span class="badge text-bg-info"><i class="bi bi-calendar-check me-1"></i>Planifiée</span>'],
        'faite'       => ['faite','<span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>Faite</span>'],
        default       => [$statut,'<span class="badge text-bg-secondary">—</span>'],
    };
}

// Si on a demandé explicitement le filtre "en_retard", on filtre côté PHP avec notre calcul
if ($statutFilter === 'en_retard') {
    $revs = array_values(array_filter($revs, function($r) use ($today) {
        return badgeStatut($r, $today)[0] === 'en_retard';
    }));
}

?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Gestion des révisions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #0f172a; }
    .card { background: #1e293b; border-color:#334155; }
    .hero { background: linear-gradient(135deg,#f59e0b,#ef4444); border-bottom-left-radius:1rem;border-bottom-right-radius:1rem; }
    .avatar { width:56px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #334155; }
    .table > :not(caption) > * > * { background-color: transparent !important; }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark border-bottom border-secondary">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="admin_dashboard.php">Drive’n Cook — Admin</a>
    <div class="d-flex align-items-center gap-3">
      <span class="text-secondary">Bonjour, <?= htmlspecialchars($nom) ?></span>
      <a class="btn btn-sm btn-outline-light" href="admin_logout.php"><i class="bi bi-box-arrow-right me-1"></i>Déconnexion</a>
    </div>
  </div>
</nav>

<header class="hero py-5 mb-4 text-white">
  <div class="container">
    <nav aria-label="breadcrumb" class="mb-2">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a class="link-light" href="admin_dashboard.php">Accueil</a></li>
        <li class="breadcrumb-item"><a class="link-light" href="admin_gestion_camions.php">Camions</a></li>
        <li class="breadcrumb-item active text-white">Révisions</li>
      </ol>
    </nav>
    <h1 class="mb-1">🔧 Gestion des révisions</h1>
    <p class="mb-0">Planifiez et suivez les entretiens par date ou kilométrage.</p>
  </div>
</header>

<div class="container pb-5">

  <!-- barre d'actions -->
  <div class="card p-3 mb-3">
    <form class="row g-2 align-items-end" method="get">
      <div class="col-12 col-md-3">
        <label class="form-label">Filtrer par statut</label>
        <select name="statut" class="form-select" onchange="this.form.submit()">
          <option value="" <?= $statutFilter===''?'selected':'' ?>>Tous</option>
          <option value="a_planifier" <?= $statutFilter==='a_planifier'?'selected':'' ?>>À planifier</option>
          <option value="planifiee" <?= $statutFilter==='planifiee'?'selected':'' ?>>Planifiée</option>
          <option value="faite" <?= $statutFilter==='faite'?'selected':'' ?>>Faite</option>
          <option value="en_retard" <?= $statutFilter==='en_retard'?'selected':'' ?>>En retard</option>
        </select>
      </div>
    </form>
  </div>

  <!-- tableau -->
  <div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Liste des révisions</h5>
      <div class="input-group" style="max-width: 320px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="search" type="text" class="form-control" placeholder="Rechercher immatriculation / franchisé / type…">
      </div>
    </div>

    <div class="table-responsive">
      <table class="table align-middle table-hover">
        <thead>
          <tr>
            <th>Camion</th>
            <th>Immat • Modèle</th>
            <th>Franchisé</th>
            <th>Type</th>
            <th>Échéance</th>
            <th>Statut</th>
          </tr>
        </thead>
        <tbody id="revBody">
        <?php foreach ($revs as $r):
            [$statCalc, $badge] = badgeStatut($r, $today);
            $img   = $r['image_url'] ?? '';
            $immat = htmlspecialchars($r['immatriculation']);
            $modele= htmlspecialchars(trim(($r['marque'] ?? '').' '.($r['modele'] ?? '')));
            $fr    = ($r['prenom']||$r['nom']) ? htmlspecialchars(ucfirst($r['prenom']).' '.strtoupper($r['nom'])) : '—';
            $echD  = $r['echeance_date'] ? date('d/m/Y', strtotime($r['echeance_date'])) : null;
            $echK  = $r['echeance_km'] !== null ? number_format((int)$r['echeance_km'],0,',',' ').' km' : null;
        ?>
          <tr>
            <td>
              <?php if ($img): ?>
                <img src="<?= htmlspecialchars($img) ?>" class="avatar" alt="Camion">
              <?php else: ?>
                <span class="text-secondary">—</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= $immat ?></div>
              <div class="small text-secondary"><?= $modele ?></div>
            </td>
            <td><?= $fr ?></td>
            <td class="text-capitalize"><?= htmlspecialchars($r['type']) ?></td>
            <td>
              <?php if ($echD || $echK): ?>
                <div><?= $echD ?: '—' ?></div>
                <div class="small text-secondary"><?= $echK ?: '' ?></div>
              <?php else: ?>
                <span class="text-secondary">—</span>
              <?php endif; ?>
            </td>
            <td><?= $badge ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  // recherche simple
  const input = document.getElementById('search');
  const body  = document.getElementById('revBody');
  input?.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    [...body.querySelectorAll('tr')].forEach(tr => {
      tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
