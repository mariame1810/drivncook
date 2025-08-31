<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}

$nom = $_SESSION['nom_admin'] ?? 'Admin';

// Récupération des entrepôts
$entrepots = $pdo->query("SELECT * FROM entrepots ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total = count($entrepots);
$geocoded = 0;
foreach ($entrepots as $e) {
  if (!empty($e['latitude']) && !empty($e['longitude'])) $geocoded++;
}
$nogeo = $total - $geocoded;
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestion des entrepôts</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root{
      --brand:#4f46e5;
      --card-radius:1rem;
      --shadow-soft:0 10px 25px rgba(0,0,0,.08);
      --shadow-hover:0 16px 40px rgba(0,0,0,.12);
    }
    [data-bs-theme="dark"]{
      --shadow-soft:0 10px 25px rgba(0,0,0,.35);
      --shadow-hover:0 16px 40px rgba(0,0,0,.45);
    }
    .navbar-blur{
      backdrop-filter:saturate(180%) blur(10px);
      background: color-mix(in oklab, var(--bs-body-bg) 70%, transparent);
      border-bottom:1px solid var(--bs-border-color);
    }
    .hero{
      background: linear-gradient(135deg, var(--brand) 0%, #7c3aed 50%, #0ea5e9 100%);
      color:#fff;
      border-bottom-left-radius:2rem;
      border-bottom-right-radius:2rem;
    }
    .card-zoom{ border:0;border-radius:var(--card-radius); box-shadow:var(--shadow-soft); transition:transform .2s, box-shadow .2s; }
    .card-zoom:hover{ transform:translateY(-4px); box-shadow:var(--shadow-hover); }
    .btn-pill{ border-radius:999px; }
    .kpi{
      display:flex;align-items:center;gap:.75rem;
      padding:.75rem 1rem;border-radius:.75rem;
      background:var(--bs-body-bg); border:1px solid var(--bs-border-color);
    }
    .table thead th{ position:sticky; top:0; background:var(--bs-body-bg); z-index:1; }
    .search-input{ max-width:380px; }
    .chip{ border-radius:999px; padding:.35rem .6rem; }
    .empty{ border:1px dashed var(--bs-border-color); border-radius:1rem; padding:2rem; text-align:center; color:var(--bs-secondary-color); }
  </style>
</head>
<body class="bg-body-tertiary">

  <!-- Top nav -->
  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="admin_dashboard.php">
        <i class="bi bi-buildings text-primary"></i> Entrepôts
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Tableau de bord</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_franchises.php">Franchisés</a></li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
            <i class="bi bi-moon-stars"></i>
          </button>
          <div class="dropdown">
            <button class="btn btn-primary btn-pill dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="admin_profile.php"><i class="bi bi-gear me-2"></i>Profil</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="admin_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Se déconnecter</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <header class="hero py-5">
    <div class="container">
      <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a class="link-light text-decoration-underline" href="admin_dashboard.php">Accueil</a></li>
          <li class="breadcrumb-item active text-white">Entrepôts</li>
        </ol>
      </nav>
      <h1 class="display-6 mb-2">🏢 Gestion des entrepôts</h1>
      <p class="lead mb-0">Ajoutez de nouveaux sites et vérifiez la géolocalisation.</p>
    </div>
  </header>

  <main class="container py-5">

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-buildings fs-5 text-primary"></i>
          <div><div class="fw-semibold"><?= (int)$total ?></div><div class="text-secondary small">Total entrepôts</div></div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-geo-alt fs-5 text-success"></i>
          <div><div class="fw-semibold"><?= (int)$geocoded ?></div><div class="text-secondary small">Géolocalisés</div></div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-3">
        <div class="kpi"><i class="bi bi-question-circle fs-5 text-warning"></i>
          <div><div class="fw-semibold"><?= (int)$nogeo ?></div><div class="text-secondary small">À compléter</div></div>
        </div>
      </div>
    </div>

    <!-- Formulaire d'ajout -->
    <div class="card card-zoom mb-4">
      <div class="card-body p-4 p-lg-5">
        <h5 class="card-title d-flex align-items-center gap-2 mb-4">
          <span class="badge rounded-pill text-bg-primary"><i class="bi bi-plus-lg"></i></span>
          Ajouter un entrepôt
        </h5>
        <form action="../php/ajouter_entrepot.php" method="POST" class="row g-4 needs-validation" novalidate>
          <div class="col-12 col-md-4">
            <label class="form-label" for="nom">Nom</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-building"></i></span>
              <input id="nom" type="text" name="nom" class="form-control" required>
              <div class="invalid-feedback">Veuillez indiquer un nom.</div>
            </div>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label" for="adresse">Adresse complète</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
              <input id="adresse" type="text" name="adresse" class="form-control" placeholder="10 rue de Paris, Bordeaux…" required>
              <div class="invalid-feedback">Veuillez saisir l’adresse.</div>
            </div>
          </div>
          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-pill"><i class="bi bi-arrow-left"></i> Retour</a>
            <button type="submit" class="btn btn-success btn-pill"><i class="bi bi-check2-circle me-1"></i> Ajouter</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Liste des entrepôts -->
    <div class="card card-zoom">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
          <h5 class="card-title m-0 d-flex align-items-center gap-2">
            <span class="badge rounded-pill text-bg-secondary"><i class="bi bi-list-task"></i></span>
            Liste des entrepôts
          </h5>
          <div class="d-flex gap-2 w-100 w-md-auto">
            <div class="input-group search-input">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="search" id="tableSearch" class="form-control" placeholder="Rechercher nom ou adresse…">
            </div>
            <select id="geoFilter" class="form-select" style="max-width:200px">
              <option value="">Tous</option>
              <option value="ok">Géolocalisés</option>
              <option value="nok">À compléter</option>
            </select>
          </div>
        </div>

        <?php if ($entrepots): ?>
          <div class="table-responsive">
            <table class="table align-middle table-hover">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Adresse</th>
                  <th class="text-nowrap">Latitude</th>
                  <th class="text-nowrap">Longitude</th>
                  <th>Statut</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody id="entrepotsBody">
                <?php foreach ($entrepots as $e):
                  $nomE  = htmlspecialchars($e['nom'] ?? '', ENT_QUOTES, 'UTF-8');
                  $addr  = htmlspecialchars($e['adresse'] ?? '', ENT_QUOTES, 'UTF-8');
                  $lat   = $e['latitude'] ?? null;
                  $lon   = $e['longitude'] ?? null;
                  $isGeo = !empty($lat) && !empty($lon);
                  $searchHay = strtolower($nomE.' '.$addr);
                  $mapHref = $isGeo ? "https://www.google.com/maps?q=".rawurlencode($lat.','.$lon) : "#";
                ?>
                  <tr data-search="<?= $searchHay ?>" data-geo="<?= $isGeo ? 'ok' : 'nok' ?>">
                    <td class="fw-semibold"><?= $nomE ?></td>
                    <td><?= $addr ?: '<span class="text-secondary"><em>—</em></span>' ?></td>
                    <td><?= $lat !== null ? htmlspecialchars($lat, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td><?= $lon !== null ? htmlspecialchars($lon, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td>
                      <?php if ($isGeo): ?>
                        <span class="chip text-bg-success">Géolocalisé</span>
                      <?php else: ?>
                        <span class="chip text-bg-warning">À compléter</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($isGeo): ?>
                        <a href="<?= $mapHref ?>" target="_blank" rel="noreferrer" class="btn btn-sm btn-outline-primary btn-pill">
                          <i class="bi bi-map"></i> Carte
                        </a>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty">
            <div class="fs-5 mb-2">Aucun entrepôt enregistré</div>
            <p class="mb-0">Ajoutez un site via le formulaire ci-dessus.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <a href="admin_dashboard.php" class="btn btn-outline-secondary btn-pill"><i class="bi bi-grid"></i> Tableau de bord</a>
    </div>
  </main>

  <footer class="py-4 bg-body">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
      <span class="text-secondary small">© <?= date('Y') ?> Votre Société. Tous droits réservés.</span>
      <div class="d-flex align-items-center gap-3 small">
        <a href="#" class="link-secondary text-decoration-none">Aide</a>
        <a href="#" class="link-secondary text-decoration-none">Confidentialité</a>
        <a href="#" class="link-secondary text-decoration-none">Conditions</a>
      </div>
    </div>
  </footer>

  <!-- Toast success -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <?php if (isset($_GET['success'])): ?>
      <div class="toast align-items-center text-bg-success border-0 show" role="alert">
        <div class="d-flex">
          <div class="toast-body"><i class="bi bi-check-circle me-2"></i>Entrepôt ajouté avec succès.</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Light/Dark toggle with persistence
    const html = document.documentElement;
    const key = 'pref-theme';
    const btn = document.getElementById('themeToggle');
    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);
    const setIcon = () => btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark') ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    const toggleTheme = () => { const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark'; html.setAttribute('data-bs-theme', next); localStorage.setItem(key, next); setIcon(); };
    btn.addEventListener('click', toggleTheme); setIcon();

    // Bootstrap validation
    (() => {
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', e => {
          if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
          form.classList.add('was-validated');
        }, false);
      });
    })();

    // Client-side search + filter
    const q = document.getElementById('tableSearch');
    const filter = document.getElementById('geoFilter');
    const rows = Array.from(document.querySelectorAll('#entrepotsBody tr'));
    function applyFilters(){
      const s = (q?.value || '').toLowerCase().trim();
      const f = (filter?.value || '');
      rows.forEach(r => {
        const hay = (r.getAttribute('data-search') || '');
        const geo = (r.getAttribute('data-geo') || '');
        const okText = hay.includes(s);
        const okGeo = !f || geo === f;
        r.style.display = (okText && okGeo) ? '' : 'none';
      });
    }
    q?.addEventListener('input', applyFilters);
    filter?.addEventListener('change', applyFilters);
  </script>
</body>
</html>
