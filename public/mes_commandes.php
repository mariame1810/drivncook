<?php
session_start();
require_once '../config/db.php';
require_once '../php/middleware_onboarding.php';

if (!isset($_SESSION['id_franchise'])) {
    header("Location: login.html");
    exit;
}

$id_franchise = $_SESSION['id_franchise'];
$nom = $_SESSION['nom'] ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// Filtres
$typeFilter = $_GET['type'] ?? '';
$statutFilter = $_GET['statut'] ?? '';

$sql = "SELECT c.*, e.nom AS entrepot_nom
        FROM commandes c
        LEFT JOIN entrepots e ON c.id_entrepot = e.id_entrepot
        WHERE c.id_franchise = ?";

$params = [$id_franchise];

if ($typeFilter) {
    $sql .= " AND c.type_commande = ?";
    $params[] = $typeFilter;
}
if ($statutFilter) {
    $sql .= " AND c.statut = ?";
    $params[] = $statutFilter;
}

$sql .= " ORDER BY c.date_commande DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

// Helpers d'affichage
function e($str){ return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function badgeForStatut($statut){
    $map = [
        'en_attente' => ['warning','En attente','clock'],
        'validee'    => ['info','Validée','check2'],
        'livree'     => ['success','Livrée','box-seam']
    ];
    $s = $map[$statut] ?? ['secondary', ucfirst((string)$statut), 'dash'];
    return ["class"=>"badge bg-{$s[0]}-subtle text-{$s[0]} border border-{$s[0]}", "label"=>$s[1], "icon"=>$s[2]];
}
function badgeForType($type){
    $map = [
        'entrepot' => ['secondary','Depuis entrepôt','buildings'],
        'libre'    => ['primary','Commande libre','pencil-square']
    ];
    $t = $map[$type] ?? ['dark', ucfirst((string)$type), 'question-circle'];
    return ["class"=>"badge bg-{$t[0]}-subtle text-{$t[0]} border border-{$t[0]}", "label"=>$t[1], "icon"=>$t[2]];
}
function formatEuro($n){ return number_format((float)$n, 2, ',', ' ') . ' €'; }
function formatDateFr($dateStr){ $ts = strtotime($dateStr); return $ts ? date('d/m/Y H:i', $ts) : e($dateStr); }
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Mes commandes – Driv’n Cook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --brand:#E11D48;

      /* Light theme tokens */
      --bg-grad-start:#fafafa;
      --bg-grad-end:#f5f7fb;
      --surface:#ffffff;
      --surface-2:#ffffff;
      --text:#0f172a;      /* slate-900 */
      --muted:#64748b;     /* slate-500 */
      --border:#eef0f4;
      --hover:#f3f4f6;
      --soft:#f8fafc;      /* slate-50 */
      --badge-soft:#f8fafc;
      --table-head:#ffffff;
      --link:#0f172a;
    }

    [data-theme="dark"]{
      color-scheme: dark;
      --bg-grad-start:#0b1220;
      --bg-grad-end:#0f172a;
      --surface:#0f172a;         /* slate-900 */
      --surface-2:#111827;       /* gray-900 */
      --text:#e5e7eb;            /* gray-200 */
      --muted:#94a3b8;           /* slate-400 */
      --border:#1f2937;          /* gray-800 */
      --hover:#0b132b;
      --soft:#0b1220;
      --badge-soft:#0b132b;
      --table-head:#0f172a;
      --link:#e5e7eb;
    }

    body{
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Helvetica Neue", sans-serif;
      background: linear-gradient(180deg, var(--bg-grad-start), var(--bg-grad-end));
      color: var(--text);
    }
    a{ color: var(--link); }
    .app-shell{ min-height:100vh; }

    /* Sidebar */
    .sidebar{
      width:280px; background:var(--surface);
      border-right:1px solid var(--border);
      position:sticky; top:0; height:100vh;
      color: var(--text);
    }
    .brand{ font-weight:700; letter-spacing:.2px; }
    .nav-link{
      color:#334155; border-radius:12px; padding:.6rem .85rem;
    }
    [data-theme="dark"] .nav-link{ color: var(--muted); }
    .nav-link:hover{ background:var(--hover); color:var(--text); }
    .nav-link.active{
      background: color-mix(in oklab, var(--brand) 12%, transparent);
      color: var(--brand); font-weight:600;
    }
    .logout{ color:#ef4444 !important; }

    /* Main */
    .page{ padding:32px; }
    .page h2{ font-weight:700; }

    /* Cards / containers */
    .card-elev{
      background: var(--surface);
      border:1px solid var(--border);
      box-shadow:0 1px 2px rgba(0,0,0,.03), 0 12px 24px -12px rgba(15,23,42,.12);
      border-radius:16px;
      color: var(--text);
    }
    .filter-card{ background: var(--surface); }

    /* Table */
    .table-responsive{
      border-radius:14px;
      border:1px solid var(--border);
      overflow:hidden;
      background: var(--surface);
    }
    .table thead th{
      position:sticky; top:0; z-index:1;
      background: var(--table-head);
      color: var(--muted);
      border-color: var(--border) !important;
    }
    .table-hover tbody tr:hover{ background: var(--hover); }
    .table td, .table th{ color: var(--text); border-color: var(--border) !important; }

    /* Badges + small pills */
    .badge{ font-weight:600; padding:.5em .65em; border-radius:999px; }
    .badge.bg-light{ background: var(--badge-soft) !important; color: var(--text) !important; border:1px solid var(--border) !important; }

    /* Empty state */
    .empty{
      border:2px dashed var(--border);
      border-radius:16px; padding:32px; background:var(--surface);
      color: var(--text);
    }

    /* Misc */
    .bi{ vertical-align: -0.125em; }

    /* Top actions wrapper tweaks */
    .top-actions .btn{ border-radius:10px; }
    .theme-toggle{
      border:1px solid var(--border); background: var(--surface);
      color: var(--text);
    }
    .theme-toggle:hover{ background: var(--hover); }

    /* ======= VISIBILITÉ DARK MODE : PATCH ======= */
    .table, .table td, .table th { color: var(--text) !important; }
    .table thead th { background: var(--table-head) !important; color: var(--muted) !important; }
    .table-hover tbody tr:hover { background: var(--hover) !important; }

    .text-muted, .text-secondary { color: var(--muted) !important; }
    [data-theme="dark"] .text-muted,
    [data-theme="dark"] .text-secondary { color: var(--muted) !important; }

    .badge .text-muted { color: inherit !important; opacity: .8; }
    .badge[class*="bg-"][class*="-subtle"] { border-color: var(--border) !important; }

    [data-theme="dark"] .btn-outline-secondary {
      color: var(--text) !important;
      border-color: var(--border) !important;
    }
    [data-theme="dark"] .btn-outline-secondary:hover {
      background: var(--hover) !important;
    }

    [data-theme="dark"] .nav-link { color: var(--muted) !important; }
    [data-theme="dark"] .nav-link:hover { color: var(--text) !important; background: var(--hover) !important; }

    .card-elev, .filter-card, .empty { color: var(--text) !important; background: var(--surface) !important; }

    .form-select, .form-control { color: var(--text) !important; background: var(--surface) !important; border-color: var(--border) !important; }
    .form-select:focus, .form-control:focus { box-shadow: none !important; border-color: var(--brand) !important; }

    /* Force dark mode table backgrounds */
    [data-theme="dark"] .table-responsive { background: var(--surface) !important; }
    [data-theme="dark"] .table { --bs-table-bg: var(--surface) !important; background: var(--surface) !important; color: var(--text) !important; }
    [data-theme="dark"] .table > :not(caption) > * > * { background: var(--surface) !important; color: var(--text) !important; border-color: var(--border) !important; }
    [data-theme="dark"] .table thead th { background: var(--table-head) !important; color: var(--muted) !important; }
  </style>
</head>
<body>
<div class="d-flex app-shell">
  <!-- Sidebar -->
  <aside class="sidebar p-4 d-flex flex-column">
    <div class="d-flex align-items-center gap-2 mb-4">
      <div class="rounded-4 p-2 pe-3" style="background:var(--soft); border:1px solid var(--border);"><span class="fs-4">🚚</span></div>
      <div>
        <div class="brand h4 m-0" style="color:var(--brand);">Driv’n Cook</div>
        <small class="text-muted" style="color:var(--muted) !important;">Portail franchise</small>
      </div>
    </div>
    <nav class="nav flex-column gap-2">
      <a class="nav-link" href="dashboard.php"><i class="bi bi-house-door me-2"></i>Dashboard</a>
      <a class="nav-link active" href="mes_commandes.php"><i class="bi bi-box-seam me-2"></i>Mes commandes</a>
      <a class="nav-link" href="ajout_produit_commande.php"><i class="bi bi-plus-circle me-2"></i>Ajouter un produit</a>
      <a class="nav-link" href="profil.php"><i class="bi bi-person-circle me-2"></i>Modifier mon profil</a>
      <div class="mt-auto"></div>
      <a class="nav-link logout" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="page flex-grow-1">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 top-actions">
      <div>
        <h2 class="mb-1">📦 Mes commandes</h2>
        <div class="text-muted" style="color:var(--muted) !important;"><?= e(strtoupper(trim(($prenom.' '.$nom)))) ?: '—'; ?></div>
      </div>
      <div class="d-flex gap-2">
        <!-- Theme toggle -->
        <button id="themeToggle" type="button" class="btn theme-toggle" aria-label="Basculer le thème">
          <i class="bi bi-moon-stars"></i>
        </button>

        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
        <form action="../php/export_commandes_pdf.php" method="POST">
          <button type="submit" class="btn btn-dark"><i class="bi bi-filetype-pdf"></i> Exporter tout</button>
        </form>
      </div>
    </div>

    <!-- Filtres -->
    <div class="card-elev filter-card p-3 p-md-4 mb-4">
      <form method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label fw-semibold">Type</label>
          <select name="type" class="form-select">
            <option value="">— Filtrer par type —</option>
            <option value="entrepot" <?= $typeFilter === 'entrepot' ? 'selected' : '' ?>>Depuis entrepôt</option>
            <option value="libre" <?= $typeFilter === 'libre' ? 'selected' : '' ?>>Commande libre</option>
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-semibold">Statut</label>
          <select name="statut" class="form-select">
            <option value="">— Filtrer par statut —</option>
            <option value="en_attente" <?= $statutFilter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
            <option value="validee" <?= $statutFilter === 'validee' ? 'selected' : '' ?>>Validée</option>
            <option value="livree" <?= $statutFilter === 'livree' ? 'selected' : '' ?>>Livrée</option>
          </select>
        </div>
        <div class="col-12 col-md-4 d-flex gap-2 filter-actions">
          <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filtrer</button>
          <?php if ($typeFilter || $statutFilter): ?>
            <a href="mes_commandes.php" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Réinitialiser">
              <i class="bi bi-x-lg"></i>
            </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <?php if ($commandes): ?>
      <div class="table-responsive card-elev theme-table-container">
        <table class="table align-middle table-hover m-0 theme-table">
          <thead>
            <tr class="text-uppercase small">
              <th class="ps-3">ID</th>
              <th>Date</th>
              <th>Type</th>
              <th>Statut</th>
              <th>Entrepôt</th>
              <th>Produits</th>
              <th class="text-end pe-3">Total</th>
              <th class="text-center">PDF</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($commandes as $commande): ?>
            <?php
              $sqlLigne = "SELECT p.nom, p.prix_unitaire, lc.quantite
                           FROM ligne_commande lc
                           JOIN produits p ON p.id_produit = lc.id_produit
                           WHERE lc.id_commande = ?";
              $stmtLigne = $pdo->prepare($sqlLigne);
              $stmtLigne->execute([$commande['id_commande']]);
              $produits = $stmtLigne->fetchAll();

              $total = 0;
              $listeProduits = [];
              foreach ($produits as $p) {
                  $ligneTotal = (float)$p['prix_unitaire'] * (int)$p['quantite'];
                  $total += $ligneTotal;
                  $listeProduits[] = e($p['nom']).' <span class="text-muted">×'.(int)$p['quantite'].'</span>';
              }
              $typeBadge = badgeForType($commande['type_commande']);
              $statBadge = badgeForStatut($commande['statut']);
            ?>
            <tr>
              <td class="ps-3 fw-semibold">#<?= (int)$commande['id_commande'] ?></td>
              <td><?= formatDateFr($commande['date_commande']); ?></td>
              <td>
                <span class="<?= e($typeBadge['class']) ?>">
                  <i class="bi bi-<?= e($typeBadge['icon']) ?> me-1"></i><?= e($typeBadge['label']) ?>
                </span>
              </td>
              <td>
                <span class="<?= e($statBadge['class']) ?>">
                  <i class="bi bi-<?= e($statBadge['icon']) ?> me-1"></i><?= e($statBadge['label']) ?>
                </span>
              </td>
              <td><?= $commande['entrepot_nom'] ? e($commande['entrepot_nom']) : '—'; ?></td>
              <td>
                <?php if ($listeProduits): ?>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($listeProduits as $lp): ?>
                      <span class="badge bg-light text-secondary border"><?= $lp ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <em class="text-muted">Aucun</em>
                <?php endif; ?>
              </td>
              <td class="text-end pe-3 fw-semibold"><?= formatEuro($total); ?></td>
              <td class="text-center">
                <form action="../php/export_commande_detail.php" method="GET" target="_blank" class="d-inline">
                  <input type="hidden" name="id_commande" value="<?= (int)$commande['id_commande']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-toggle="tooltip" title="Télécharger le PDF">
                    <i class="bi bi-receipt"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty text-center">
        <div class="display-6 mb-2">📭</div>
        <h5 class="mb-1">Aucune commande</h5>
        <p class="text-muted mb-3" style="color:var(--muted) !important;">Essayez d’élargir vos filtres ou ajoutez une nouvelle commande.</p>
        <a href="ajout_produit_commande.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouvelle commande</a>
      </div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // --- Theme persistence ---
  const root = document.documentElement;
  const THEME_KEY = 'pref-theme-franch';
  const saved = localStorage.getItem(THEME_KEY);

  if (saved) {
    root.setAttribute('data-theme', saved);
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    root.setAttribute('data-theme', 'dark');
  }

  function currentTheme(){ return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'; }
  function setToggleIcon(){
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.innerHTML = currentTheme() === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
  }

  document.getElementById('themeToggle')?.addEventListener('click', () => {
    const next = currentTheme() === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem(THEME_KEY, next);
    setToggleIcon();
  });

  setToggleIcon();

  // Tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el=>new bootstrap.Tooltip(el));
</script>
</body>
</html>
