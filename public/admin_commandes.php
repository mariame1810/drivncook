<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$allowedStatuts = ['en_attente','validee','livree'];
$allowedTypes   = ['entrepot','libre'];

$statut   = isset($_GET['statut']) && in_array($_GET['statut'], $allowedStatuts, true) ? $_GET['statut'] : '';
$type     = isset($_GET['type']) && in_array($_GET['type'], $allowedTypes, true) ? $_GET['type'] : '';
$q        = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$where = [];
$params = [];

if ($statut !== '') { $where[] = 'c.statut = :statut'; $params[':statut'] = $statut; }
if ($type   !== '') { $where[] = 'c.type_commande = :type'; $params[':type'] = $type; }
if ($q      !== '') {
  $where[] = '(f.nom LIKE :q OR f.prenom LIKE :q)';
  $params[':q'] = '%'.$q.'%';
}

$sql = "SELECT c.*, f.nom AS nom_f, f.prenom AS prenom_f, e.nom AS entrepot_nom
        FROM commandes c
        JOIN franchises f ON c.id_franchise = f.id_franchise
        LEFT JOIN entrepots e ON c.id_entrepot = e.id_entrepot";
if ($where) { $sql .= "\nWHERE ".implode(' AND ', $where); }
$sql .= "\nORDER BY c.date_commande DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function frDate($dt){ $ts = strtotime($dt); return $ts ? date('d/m/Y H:i', $ts) : e($dt); }
function badgeType($type){
  $map = [ 'entrepot' => ['secondary','Depuis entrepôt','buildings'], 'libre' => ['primary','Commande libre','pencil-square'] ];
  return $map[$type] ?? ['dark', ucfirst((string)$type), 'question-circle'];
}
function badgeStatut($statut){
  $map = [ 'en_attente' => ['warning','En attente','clock'], 'validee' => ['info','Validée','check2'], 'livree' => ['success','Livrée','box-seam'] ];
  return $map[$statut] ?? ['secondary', ucfirst((string)$statut), 'dash'];
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Commandes – Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{ --brand: #6366f1; --radius: 1rem; --shadow-soft: 0 12px 28px rgba(0,0,0,.08); --shadow-hover: 0 18px 48px rgba(0,0,0,.14); }
    [data-bs-theme="dark"] { --shadow-soft: 0 12px 28px rgba(0,0,0,.35); --shadow-hover: 0 18px 48px rgba(0,0,0,.5); }
    body{ background: linear-gradient(180deg, color-mix(in oklab, var(--bs-body-bg) 95%, transparent), var(--bs-body-bg)); }
    .navbar-blur { backdrop-filter: saturate(180%) blur(10px); background: color-mix(in oklab, var(--bs-body-bg) 70%, transparent); border-bottom: 1px solid var(--bs-border-color); }
    .hero { background: linear-gradient(135deg, var(--brand) 0%, #7c3aed 50%, #0ea5e9 100%); color: #fff; border-bottom-left-radius: 1.5rem; border-bottom-right-radius: 1.5rem; }
    .hero .lead{ opacity:.92; }
    .card-elev { border: 0; border-radius: var(--radius); box-shadow: var(--shadow-soft); }
    .card-elev:hover { box-shadow: var(--shadow-hover); }
    .filters .form-select, .filters .form-control{ border-radius: 999px; }
    .table-wrap{ border-radius: var(--radius); border: 1px solid var(--bs-border-color); overflow: hidden; background: var(--bs-body-bg); }
    .table thead th{ position: sticky; top: 0; z-index: 1; background: var(--bs-body-bg) !important; }
    .table-hover tbody tr:hover{ background: color-mix(in oklab, var(--bs-body-color) 4%, transparent); }
    .badge.rounded-pill{ border-radius: 999px; }
    .chip{ display:inline-flex; align-items:center; gap:.35rem; border:1px solid var(--bs-border-color); border-radius:999px; padding:.25rem .6rem; font-size:.875rem; background: color-mix(in oklab, var(--bs-body-bg) 92%, transparent); }
    .btn-pill{ border-radius: 999px; }
  </style>
</head>
<body>

  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="admin_dashboard.php">
        <i class="bi bi-speedometer2 text-primary"></i> Tableau de bord
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Commandes</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_franchises.php">Franchisés</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_entrepots.php">Entrepôts</a></li>
        </ul>
        <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
          <i class="bi bi-moon-stars"></i>
        </button>
      </div>
    </div>
  </nav>

  <header class="hero py-5">
    <div class="container">
      <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
        <div>
          <h1 class="h2 mb-1">📦 Gestion des commandes</h1>
          <p class="lead mb-0">Consultez, filtrez, validez et exportez les commandes.</p>
        </div>
        <div class="d-flex gap-2">
          <a href="../php/export_commandes_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-light btn-pill">
            <i class="bi bi-filetype-pdf me-1"></i> Exporter (PDF)
          </a>
          <a href="admin_dashboard.php" class="btn btn-outline-light btn-pill">
            <i class="bi bi-arrow-left"></i> Retour
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="container py-5">
    <?php if (isset($_GET['success'])): ?>
      <div class="alert alert-success card-elev mb-4"><i class="bi bi-check2-circle me-2"></i>Commande validée avec succès.</div>
    <?php endif; ?>

    <div class="card-elev p-3 p-md-4 mb-4">
      <form class="row g-3 filters" method="get" action="">
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label fw-semibold" for="filtre-statut">Statut</label>
          <select class="form-select" id="filtre-statut" name="statut">
            <option value="">Tous</option>
            <?php foreach ($allowedStatuts as $s): ?>
              <option value="<?= e($s) ?>" <?= $statut===$s?'selected':'' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
          <label class="form-label fw-semibold" for="filtre-type">Type</label>
          <select class="form-select" id="filtre-type" name="type">
            <option value="">Tous</option>
            <option value="entrepot" <?= $type==='entrepot'?'selected':'' ?>>Depuis entrepôt</option>
            <option value="libre" <?= $type==='libre'?'selected':'' ?>>Commande libre</option>
          </select>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
          <label class="form-label fw-semibold" for="filtre-q">Franchisé</label>
          <input class="form-control" id="filtre-q" name="q" value="<?= e($q) ?>" placeholder="Nom ou prénom…">
        </div>
        <div class="col-12 col-sm-6 col-md-2 d-flex align-items-end gap-2">
          <button type="submit" class="btn btn-primary w-100 btn-pill">
            <i class="bi bi-funnel me-1"></i> Appliquer
          </button>
        </div>
        <div class="col-12 d-flex justify-content-between align-items-center">
          <small class="text-secondary">Astuce : vous pouvez combiner les filtres.</small>
          <?php if ($statut!=='' || $type!=='' || $q!==''): ?>
            <a class="btn btn-outline-secondary btn-sm btn-pill" href="<?= strtok($_SERVER['REQUEST_URI'],'?') ?>">
              <i class="bi bi-x-circle me-1"></i> Réinitialiser
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <?php if ($commandes): ?>
      <div class="table-wrap card-elev">
        <table class="table table-hover align-middle mb-0">
          <thead class="text-uppercase small text-secondary">
            <tr>
              <th class="ps-3">ID</th>
              <th>Franchisé</th>
              <th>Date</th>
              <th>Type</th>
              <th>Statut</th>
              <th>Entrepôt</th>
              <th>Produits</th>
              <th class="pe-3 text-end">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($commandes as $c): ?>
            <?php
              [$tClass,$tLabel,$tIcon] = badgeType($c['type_commande']);
              [$sClass,$sLabel,$sIcon] = badgeStatut($c['statut']);
              $idCommande = $c['id_commande'];
              $stmtLC = $pdo->prepare("SELECT p.nom, lc.quantite FROM ligne_commande lc JOIN produits p ON lc.id_produit = p.id_produit WHERE lc.id_commande = ?");
              $stmtLC->execute([$idCommande]);
              $prods = $stmtLC->fetchAll();
            ?>
            <tr>
              <td class="ps-3 fw-semibold">#<?= (int)$c['id_commande'] ?></td>
              <td><?= e(strtoupper($c['nom_f']).' '.ucfirst($c['prenom_f'])) ?></td>
              <td><?= frDate($c['date_commande']) ?></td>
              <td>
                <span class="badge rounded-pill bg-<?= $tClass ?>-subtle text-<?= $tClass ?> border border-<?= $tClass ?>">
                  <i class="bi bi-<?= $tIcon ?> me-1"></i><?= e($tLabel) ?>
                </span>
              </td>
              <td>
                <span class="badge rounded-pill bg-<?= $sClass ?>-subtle text-<?= $sClass ?> border border-<?= $sClass ?>">
                  <i class="bi bi-<?= $sIcon ?> me-1"></i><?= e($sLabel) ?>
                </span>
              </td>
              <td><?= $c['entrepot_nom'] ? e($c['entrepot_nom']) : '—' ?></td>
              <td style="max-width:360px;">
                <?php if ($prods): ?>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($prods as $p): ?>
                      <span class="chip">
                        <?= e($p['nom']) ?> <span class="text-secondary">×<?= (int)$p['quantite'] ?></span>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-secondary">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end pe-3">
                <?php if ($c['statut'] === 'en_attente'): ?>
                  <form action="../php/valider_commande.php" method="POST" class="d-inline">
                    <input type="hidden" name="id_commande" value="<?= (int)$c['id_commande'] ?>">
                    <button type="submit" class="btn btn-success btn-sm btn-pill">
                      <i class="bi bi-check2-circle"></i> Valider
                    </button>
                  </form>
                <?php elseif ($c['statut'] === 'validee'): ?>
                  <span class="text-info"><i class="bi bi-check2-square me-1"></i>Validée</span>
                <?php else: ?>
                  <span class="text-success"><i class="bi bi-box-seam me-1"></i>Livrée</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="card-elev p-5 text-center">
        <div class="display-6 mb-2">📭</div>
        <h5 class="mb-1">Aucune commande trouvée</h5>
        <p class="text-secondary mb-0">Ajustez vos filtres ou réinitialisez-les.</p>
      </div>
    <?php endif; ?>
  </main>

  <footer class="py-4 bg-body">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
      <span class="text-secondary small">© <?= date('Y') ?> DrivnCook. Tous droits réservés.</span>
      <div class="d-flex align-items-center gap-3 small">
        <a class="link-secondary text-decoration-none" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        <a class="link-secondary text-decoration-none" href="#">Aide</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const html = document.documentElement;
    const key = 'pref-theme-admin';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);
    else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { html.setAttribute('data-bs-theme', 'dark'); }
    function setIcon(){ btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark') ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>'; }
    setIcon();
    btn.addEventListener('click', () => { const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'; html.setAttribute('data-bs-theme', next); localStorage.setItem(key, next); setIcon(); });
  </script>
</body>
</html>
