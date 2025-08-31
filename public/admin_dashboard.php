<?php
session_start();
if (!isset($_SESSION['id_admin'])) {
    header("Location: admin_login.php");
    exit;
}
$nom = $_SESSION['nom_admin'] ?? 'Admin';

require_once '../config/db.php';

/* ---- Compteurs révisions (à afficher en badge) ---- */
$revStmt = $pdo->query("
  SELECT r.id, r.statut, r.echeance_date, r.echeance_km, c.kilometrage
  FROM revisions r
  JOIN camions c ON c.id_camion = r.id_camion
");
$revisions = $revStmt->fetchAll(PDO::FETCH_ASSOC);

$today = new DateTimeImmutable('today');
$revAPlanifier = 0;
$revEnRetard   = 0;

foreach ($revisions as $r) {
  if ($r['statut'] === 'a_planifier') {
    $revAPlanifier++;
    continue; // on considère ce statut à part
  }

  if ($r['statut'] !== 'faite') {
    $isLate = false;

    // retard par date
    if (!empty($r['echeance_date'])) {
      $d = DateTimeImmutable::createFromFormat('Y-m-d', substr($r['echeance_date'], 0, 10));
      if ($d && $d < $today) $isLate = true;
    }

    // retard par km
    if (!$isLate && $r['echeance_km'] !== null) {
      if ((int)$r['kilometrage'] >= (int)$r['echeance_km']) $isLate = true;
    }

    if ($isLate) $revEnRetard++;
  }
}

$revATraiter = $revAPlanifier + $revEnRetard;
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin – Tableau de bord</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --brand: #6366f1;
      --brand-2: #22c55e;
      --card-radius: 1rem;
      --shadow-soft: 0 10px 25px rgba(0,0,0,.08);
      --shadow-hover: 0 16px 40px rgba(0,0,0,.12);
    }
    [data-bs-theme="dark"] {
      --shadow-soft: 0 10px 25px rgba(0,0,0,.35);
      --shadow-hover: 0 16px 40px rgba(0,0,0,.45);
    }

    .navbar-blur {
      backdrop-filter: saturate(180%) blur(10px);
      background: color-mix(in oklab, var(--bs-body-bg) 70%, transparent);
      border-bottom: 1px solid var(--bs-border-color);
    }

    .hero {
      background: linear-gradient(135deg, var(--brand) 0%, #7c3aed 50%, #0ea5e9 100%);
      color: #fff;
      border-bottom-left-radius: 2rem;
      border-bottom-right-radius: 2rem;
    }
    .hero .lead { opacity: .9; }

    .card-zoom {
      border: 0;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow-soft);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .card-zoom:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-hover);
    }
    .icon-badge {
      width: 3rem; height: 3rem;
      display: grid; place-items: center;
      border-radius: 999px;
      background: var(--bs-primary-bg-subtle);
      color: var(--bs-primary);
    }
    .icon-badge.secondary { background: var(--bs-secondary-bg-subtle); color: var(--bs-secondary); }
    .icon-badge.info      { background: var(--bs-info-bg-subtle);      color: var(--bs-info); }
    .icon-badge.success   { background: var(--bs-success-bg-subtle);   color: var(--bs-success); }
    .icon-badge.danger    { background: var(--bs-danger-bg-subtle);    color: var(--bs-danger); }
    .icon-badge.warning   { background: var(--bs-warning-bg-subtle);   color: var(--bs-warning); }

    .card-title { display: flex; align-items: center; gap: .75rem; }
    .btn-pill { border-radius: 999px; }

    .stretched-link::after { border-radius: var(--card-radius); }

    footer { border-top: 1px solid var(--bs-border-color); }
  </style>
</head>
<body class="bg-body-tertiary">

  <nav class="navbar navbar-expand-lg navbar-blur sticky-top">
    <div class="container">
      <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="#">
        <i class="bi bi-speedometer2 text-primary"></i> Tableau de bord
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Basculer la navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Accueil</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_gestion_camions.php">Camions</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_franchises.php">Franchisés</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_entrepots.php">Entrepôts</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_commandes.php">Commandes</a></li>
          <!-- Nouveau : lien Révisions -->
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center gap-2" href="admin_gestion_revisions.php">
              <span>Révisions</span>
              <?php if ($revATraiter > 0): ?>
                <span class="badge rounded-pill text-bg-danger"><?= (int)$revATraiter ?></span>
              <?php endif; ?>
            </a>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <a class="btn btn-outline-warning btn-sm btn-pill" href="admin_gestion_revisions.php">
            <i class="bi bi-wrench-adjustable-circle me-1"></i> Révisions
            <?php if ($revATraiter > 0): ?>
              <span class="badge text-bg-danger ms-1"><?= (int)$revATraiter ?></span>
            <?php endif; ?>
          </a>

          <button id="themeToggle" class="btn btn-outline-secondary btn-sm btn-pill" type="button" aria-label="Basculer le thème">
            <i class="bi bi-moon-stars"></i>
          </button>

          <div class="dropdown">
            <button class="btn btn-primary btn-pill dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
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

  <header class="hero py-5">
    <div class="container">
      <h1 class="display-6 mb-2">Bienvenue, <?= htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="lead mb-0">Gérez votre flotte, vos franchisés, vos entrepôts et vos commandes depuis un seul endroit.</p>
    </div>
  </header>

  <main class="container py-5">
    <div class="row g-4">

      <div class="col-12 col-md-6 col-xl-4">
        <div class="card card-zoom h-100 position-relative">
          <div class="card-body">
            <h5 class="card-title">
              <span class="icon-badge"><i class="bi bi-truck"></i></span>
              Attribution de camions
            </h5>
            <p class="text-secondary mb-4">Attribuez un camion disponible à un franchisé qui n'en a pas encore.</p>
            <a href="admin_attribuer_camion.php" class="btn btn-primary btn-pill">
              Attribuer un camion
              <i class="bi bi-arrow-right-short ms-1"></i>
            </a>
            <a href="admin_attribuer_camion.php" class="stretched-link" aria-label="Aller à l'attribution de camions"></a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="card card-zoom h-100 position-relative">
          <div class="card-body">
            <h5 class="card-title">
              <span class="icon-badge secondary"><i class="bi bi-wrench-adjustable-circle"></i></span>
              Gestion des camions
            </h5>
            <p class="text-secondary mb-4">Ajoutez, modifiez ou retirez des camions de la flotte.</p>
            <a href="admin_gestion_camions.php" class="btn btn-outline-secondary btn-pill">
              Gérer les camions <i class="bi bi-arrow-right-short ms-1"></i>
            </a>
            <a href="admin_gestion_camions.php" class="stretched-link" aria-label="Gérer les camions"></a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="card card-zoom h-100 position-relative">
          <div class="card-body">
            <h5 class="card-title">
              <span class="icon-badge info"><i class="bi bi-people"></i></span>
              Gestion des franchisés
            </h5>
            <p class="text-secondary mb-4">Consultez, bloquez ou examinez l'activité des franchisés.</p>
            <a href="admin_franchises.php" class="btn btn-info text-white btn-pill">
              Voir les franchisés <i class="bi bi-arrow-right-short ms-1"></i>
            </a>
            <a href="admin_franchises.php" class="stretched-link" aria-label="Gérer les franchisés"></a>
          </div>
        </div>
      </div>

      <!-- Nouvelle carte Révisions -->
      <div class="col-12 col-md-6 col-xl-4">
        <a href="admin_gestion_revisions.php" class="text-decoration-none">
          <div class="card card-zoom h-100 position-relative">
            <div class="card-body">
              <h5 class="card-title">
                <span class="icon-badge warning"><i class="bi bi-tools"></i></span>
                Révisions à traiter
              </h5>
              <p class="text-secondary mb-3">Planifiez et suivez les entretiens (dates &amp; kilométrage).</p>
              <div class="d-flex align-items-center gap-2">
                <span class="badge text-bg-danger">À traiter&nbsp;: <?= (int)$revATraiter ?></span>
                <?php if ($revEnRetard > 0): ?>
                  <span class="badge text-bg-warning">En retard&nbsp;: <?= (int)$revEnRetard ?></span>
                <?php endif; ?>
                <?php if ($revAPlanifier > 0): ?>
                  <span class="badge text-bg-info">À planifier&nbsp;: <?= (int)$revAPlanifier ?></span>
                <?php endif; ?>
              </div>
              <a href="admin_gestion_revisions.php" class="stretched-link" aria-label="Gérer les révisions"></a>
            </div>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="card card-zoom h-100 position-relative">
          <div class="card-body">
            <h5 class="card-title">
              <span class="icon-badge success"><i class="bi bi-buildings"></i></span>
              Entrepôts
            </h5>
            <p class="text-secondary mb-4">Ajoutez ou visualisez les entrepôts disponibles.</p>
            <a href="admin_entrepots.php" class="btn btn-success btn-pill">
              Gérer les entrepôts <i class="bi bi-arrow-right-short ms-1"></i>
            </a>
            <a href="admin_entrepots.php" class="stretched-link" aria-label="Gérer les entrepôts"></a>
          </div>
        </div>
      </div>

      <!-- Gestion des commandes -->
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card card-zoom h-100 position-relative">
          <div class="card-body">
            <h5 class="card-title">
              <span class="icon-badge warning"><i class="bi bi-bag-check"></i></span>
              Gestion des commandes
            </h5>
            <p class="text-secondary mb-4">Consultez, filtrez et exportez les commandes.</p>
            <a href="admin_commandes.php" class="btn btn-warning text-dark btn-pill">
              Gérer les commandes <i class="bi bi-arrow-right-short ms-1"></i>
            </a>
            <a href="admin_commandes.php" class="stretched-link" aria-label="Gérer les commandes"></a>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6 col-xl-4">
        <div class="card card-zoom h-100 position-relative">
          <div class="card-body">
            <h5 class="card-title">
              <span class="icon-badge danger"><i class="bi bi-door-open"></i></span>
              Déconnexion
            </h5>
            <p class="text-secondary mb-4">Terminez votre session administrateur.</p>
            <a href="admin_logout.php" class="btn btn-outline-danger btn-pill">
              Se déconnecter <i class="bi bi-box-arrow-right ms-1"></i>
            </a>
            <a href="admin_logout.php" class="stretched-link" aria-label="Se déconnecter"></a>
          </div>
        </div>
      </div>

    </div>
  </main>

  <footer class="py-4 bg-body">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
      <span class="text-secondary small">© <?= date('Y') ?> DrivnCook. Tous droits réservés.</span>
      <div class="d-flex align-items-center gap-3 small">
        <a href="#" class="link-secondary text-decoration-none">Aide</a>
        <a href="#" class="link-secondary text-decoration-none">Confidentialité</a>
        <a href="#" class="link-secondary text-decoration-none">Conditions</a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const html = document.documentElement;
    const key = 'pref-theme';
    const btn = document.getElementById('themeToggle');

    const saved = localStorage.getItem(key);
    if (saved) html.setAttribute('data-bs-theme', saved);

    function toggleTheme() {
      const current = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', current);
      localStorage.setItem(key, current);
      btn.innerHTML = current === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
    }

    btn.addEventListener('click', toggleTheme);
    btn.innerHTML = (html.getAttribute('data-bs-theme') === 'dark')
      ? '<i class="bi bi-sun"></i>'
      : '<i class="bi bi-moon-stars"></i>';
  </script>
</body>
</html>
